<?php
/**
 * Escritor mínimo de archivos .xlsx, sin dependencias externas
 * (ZipArchive, extensión nativa de PHP). Genera una hoja con
 * encabezados destacados (fondo azul, negrita, bordes), columnas
 * anchas según el contenido y filas de datos con bordes, usando
 * cadenas inline para no requerir sharedStrings.xml.
 */
class XlsxWriter {

    private const MIN_COL_WIDTH = 14;
    private const MAX_COL_WIDTH = 42;

    /**
     * @param string $path       Ruta de destino del archivo .xlsx
     * @param string $sheetName  Nombre de la hoja
     * @param array<int,string> $headers
     * @param array<int,array<int,string|int|float>> $rows Cada valor numérico
     *        se escribe como número; el resto como texto (inlineStr).
     */
    public static function write(string $path, string $sheetName, array $headers, array $rows): void {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo .xlsx');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::relsXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml($sheetName));
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($headers, $rows));

        $zip->close();
    }

    private static function contentTypesXml(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function relsXml(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbookRelsXml(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(string $sheetName): string {
        $name = htmlspecialchars($sheetName, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $name . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    /**
     * Hojas de estilo: fuentes, relleno y bordes para encabezado (azul,
     * negrita, texto centrado) y celdas de datos (con borde y alto
     * cómodo). cellXfs: 0=normal, 1=encabezado, 2=texto con borde,
     * 3=número con borde y separador de miles.
     */
    private static function stylesXml(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF1F3864"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFD9E1F2"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFB7C6E5"/></left><right style="thin"><color rgb="FFB7C6E5"/></right>'
            . '<top style="thin"><color rgb="FFB7C6E5"/></top><bottom style="thin"><color rgb="FFB7C6E5"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            . '<alignment vertical="center"/></xf>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1">'
            . '<alignment vertical="center" horizontal="right"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    private static function sheetXml(array $headers, array $rows): string {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . self::colsXml($headers, $rows)
            . '<sheetData>';

        $xml .= self::rowXml(1, $headers, true, 22);

        $rowNum = 2;
        foreach ($rows as $row) {
            $xml .= self::rowXml($rowNum, $row, false, 18);
            $rowNum++;
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    /** Ancho de columna según el contenido más largo (encabezado o datos), con topes cómodos. */
    private static function colsXml(array $headers, array $rows): string {
        $widths = [];
        foreach ($headers as $i => $h) {
            $widths[$i] = mb_strlen((string)$h);
        }
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $value) {
                $len = mb_strlen((string)$value);
                if (!isset($widths[$i]) || $len > $widths[$i]) {
                    $widths[$i] = $len;
                }
            }
        }

        $xml = '<cols>';
        foreach ($widths as $i => $len) {
            $width = max(self::MIN_COL_WIDTH, min(self::MAX_COL_WIDTH, $len + 6));
            $col   = $i + 1;
            $xml  .= '<col min="' . $col . '" max="' . $col . '" width="' . $width . '" customWidth="1"/>';
        }
        $xml .= '</cols>';
        return $xml;
    }

    private static function rowXml(int $rowNum, array $values, bool $bold, int $height): string {
        $cells = '';
        $col   = 0;
        foreach ($values as $value) {
            $ref = self::columnLetter($col) . $rowNum;
            if ($bold) {
                $style = ' s="1"';
            } elseif (is_int($value) || is_float($value)) {
                $style = ' s="3"';
            } else {
                $style = ' s="2"';
            }

            if (!$bold && (is_int($value) || is_float($value))) {
                $cells .= '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>';
            } else {
                $text = htmlspecialchars((string)$value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $cells .= '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . $text . '</t></is></c>';
            }
            $col++;
        }
        return '<row r="' . $rowNum . '" ht="' . $height . '" customHeight="1">' . $cells . '</row>';
    }

    private static function columnLetter(int $index): string {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod    = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index  = intdiv($index - $mod, 26);
        }
        return $letter;
    }
}
