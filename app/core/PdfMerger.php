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
     */
    public static function merge(array $rutasPdf, string $destino): string {
        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($rutasPdf as $ruta) {
            if (!is_file($ruta)) {
                continue;
            }
            $totalPaginas = $pdf->setSourceFile($ruta);
            for ($pagina = 1; $pagina <= $totalPaginas; $pagina++) {
                $tplId  = $pdf->importPage($pagina);
                $size   = $pdf->getTemplateSize($tplId);
                $orient = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orient, [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }
        }

        $pdf->Output($destino, 'F');
        return $destino;
    }
}
