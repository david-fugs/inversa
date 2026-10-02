<?php
/**
 * PdfMerger - Servicio para unir varios PDF en uno solo, en orden,
 * usando FPDI (importar páginas) sobre TCPDF (motor de render).
 */

use setasign\Fpdi\Tcpdf\Fpdi;

class PdfMerger {
    /**
     * Une los PDF indicados (rutas absolutas, en el orden del array) y
     * guarda el resultado en $destino. Archivos faltantes se omiten sin
     * interrumpir el resto del merge.
     *
     * @param string[] $rutasPdf Rutas absolutas de los PDF de origen, en orden.
     * @param string[] $nombres  Opcional: ruta => nombre legible, para los mensajes de error.
     * @throws \RuntimeException Si algún PDF no puede leerse (p. ej. usa una
     *         compresión no soportada por el parser gratuito de FPDI).
     */
    public static function merge(array $rutasPdf, string $destino, array $nombres = []): string {
        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($rutasPdf as $ruta) {
            if (!is_file($ruta)) {
                continue;
            }
            try {
                $totalPaginas = $pdf->setSourceFile($ruta);
                for ($pagina = 1; $pagina <= $totalPaginas; $pagina++) {
                    $tplId  = $pdf->importPage($pagina);
                    $size   = $pdf->getTemplateSize($tplId);
                    $orient = $size['width'] > $size['height'] ? 'L' : 'P';
                    $pdf->AddPage($orient, [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplId);
                }
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    'No se pudo leer el PDF "' . ($nombres[$ruta] ?? basename($ruta)) . '" (formato o compresión no soportados).',
                    0,
                    $e
                );
            }
        }

        $pdf->Output($destino, 'F');
        return $destino;
    }
}
