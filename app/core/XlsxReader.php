<?php
/**
 * Lector mínimo de archivos .xlsx, sin dependencias externas
 * (ZipArchive + SimpleXML, ambas extensiones nativas de PHP).
 *
 * Solo lee lo necesario para la importación de Servicios de Vuelo:
 * shared strings, una hoja por nombre, y valores de celda ya resueltos
 * (texto, número, o fracción de día convertida a minutos cuando la
 * celda tiene formato de hora).
 */
class XlsxReader {
    private array $sharedStrings = [];
    /** numFmtId => true si es un formato de hora/fecha (built-in o personalizado) */
    private array $timeFormatIds = [];
    private ZipArchive $zip;

    /** IDs de formato de fecha/hora incorporados de Excel (built-in numFmtIds) */
    private const BUILTIN_TIME_FORMATS = [18, 19, 20, 21, 22, 45, 46, 47];
    private const BUILTIN_DATE_FORMATS = [14, 15, 16, 17];

    public function __construct(string $path) {
        $this->zip = new ZipArchive();
        if ($this->zip->open($path) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo .xlsx');
        }
        $this->loadSharedStrings();
        $this->loadStyles();
    }

    public function __destruct() {
        $this->zip->close();
    }

    private function loadSharedStrings(): void {
        $xml = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) return;
        $root = simplexml_load_string($xml);
        foreach ($root->si as $si) {
            $this->sharedStrings[] = $this->extractRichText($si);
        }
    }

    private function extractRichText(\SimpleXMLElement $si): string {
        if (isset($si->t)) {
            return (string)$si->t;
        }
        $text = '';
        foreach ($si->r as $r) {
            $text .= (string)$r->t;
        }
        return $text;
    }

    private function loadStyles(): void {
        $xml = $this->zip->getFromName('xl/styles.xml');
        if ($xml === false) return;
        $root = simplexml_load_string($xml);

        $customTimeFmtIds = [];
        if (isset($root->numFmts)) {
            foreach ($root->numFmts->numFmt as $nf) {
                $id   = (int)$nf['numFmtId'];
                $code = (string)$nf['formatCode'];
                // Formato personalizado de hora si contiene h, m o s fuera de comillas
                if (preg_match('/[hms]/i', preg_replace('/"[^"]*"/', '', $code))) {
                    $customTimeFmtIds[$id] = true;
                }
            }
        }

        if (isset($root->cellXfs)) {
            $index = 0;
            foreach ($root->cellXfs->xf as $xf) {
                $numFmtId = (int)$xf['numFmtId'];
                $isTime = in_array($numFmtId, self::BUILTIN_TIME_FORMATS, true)
                    || isset($customTimeFmtIds[$numFmtId]);
                $this->timeFormatIds[$index] = $isTime;
                $index++;
            }
        }
    }

    /** Nombre de hoja => nombre de archivo interno (sheetN.xml) */
    public function getSheetMap(): array {
        $workbookXml = $this->zip->getFromName('xl/workbook.xml');
        $relsXml     = $this->zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relsXml === false) return [];

        $wb   = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);
        $ns   = $wb->getNamespaces(true);
        $r    = $wb->children($ns['r']);

        $ridToTarget = [];
        foreach ($rels->Relationship as $rel) {
            $ridToTarget[(string)$rel['Id']] = (string)$rel['Target'];
        }

        $map = [];
        foreach ($wb->sheets->sheet as $sheet) {
            $attrs = $sheet->attributes($ns['r']);
            $rid   = (string)$attrs['id'];
            $name  = (string)$sheet['name'];
            if (isset($ridToTarget[$rid])) {
                $map[$name] = 'xl/' . $ridToTarget[$rid];
            }
        }
        return $map;
    }

    /**
     * Lee una hoja por nombre y devuelve un generador de filas.
     * Cada fila es un array [ 'A' => valor, 'B' => valor, ... ] con
     * solo las columnas que tengan celda en el XML (las vacías no
     * aparecen como clave).
     *
     * @return \Generator<int, array<string, mixed>> clave = número de fila (1-indexado)
     */
    public function rows(string $sheetName): \Generator {
        $map = $this->getSheetMap();
        if (!isset($map[$sheetName])) {
            throw new \RuntimeException("La hoja \"$sheetName\" no existe en el archivo.");
        }

        $sheetXml = $this->zip->getFromName($map[$sheetName]);
        if ($sheetXml === false) {
            throw new \RuntimeException("No se pudo leer la hoja \"$sheetName\".");
        }

        $reader = new \XMLReader();
        $reader->XML($sheetXml);

        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'row') {
                $rowNum = (int)$reader->getAttribute('r');
                $rowXmlStr = $reader->readOuterXML();
                $rowEl = simplexml_load_string($rowXmlStr);
                $values = [];
                foreach ($rowEl->c as $c) {
                    $ref = (string)$c['r'];
                    preg_match('/^([A-Z]+)/', $ref, $m);
                    $col = $m[1] ?? null;
                    if ($col === null) continue;
                    $values[$col] = $this->cellValue($c);
                }
                yield $rowNum => $values;
            }
        }
    }

    private function cellValue(\SimpleXMLElement $c): mixed {
        $type = (string)$c['t'];
        $styleIdx = $c['s'] !== null ? (int)$c['s'] : 0;

        if ($type === 's') {
            $idx = isset($c->v) ? (int)$c->v : null;
            return $idx !== null ? ($this->sharedStrings[$idx] ?? '') : '';
        }
        if ($type === 'inlineStr') {
            return isset($c->is) ? $this->extractRichText($c->is) : '';
        }
        if ($type === 'str') {
            return isset($c->v) ? (string)$c->v : '';
        }
        if ($type === 'b') {
            return isset($c->v) ? ((string)$c->v === '1') : false;
        }

        // Numérico (o vacío)
        if (!isset($c->v)) return null;
        $raw = (string)$c->v;
        if ($raw === '') return null;

        $isTimeCell = $this->timeFormatIds[$styleIdx] ?? false;
        if ($isTimeCell) {
            // Fracción de día → minutos desde medianoche (redondeado)
            $fraction = (float)$raw;
            $totalMinutes = (int)round(($fraction - floor($fraction)) * 1440);
            return ['__time_minutes__' => $totalMinutes];
        }

        return (float)$raw;
    }

    /** Convierte el valor devuelto por cellValue() para una celda de hora en "HH:MM", o null. */
    public static function minutesToTime(mixed $value): ?string {
        if (!is_array($value) || !isset($value['__time_minutes__'])) return null;
        $min = $value['__time_minutes__'];
        return sprintf('%02d:%02d', intdiv($min, 60) % 24, $min % 60);
    }

    public static function isTimeValue(mixed $value): bool {
        return is_array($value) && isset($value['__time_minutes__']);
    }
}
