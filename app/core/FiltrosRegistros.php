<?php
/**
 * Filtros (base, fecha desde / hasta) y exportación a Excel compartidos por
 * Control de Aceites y Combustibles de Equipos. El controlador que lo use debe
 * tener $this->model (con getAll(array $filtros)) y $this->baseModel.
 */
trait FiltrosRegistros {

    /** Filtros de la URL (?base_id=&desde=&hasta=), ya validados. */
    private function filtrosGet(): array {
        $fecha = function (string $k): string {
            $v = trim((string)($_GET[$k] ?? ''));
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
        };
        return [
            'base_id' => (int)($_GET['base_id'] ?? 0),
            'desde'   => $fecha('desde'),
            'hasta'   => $fecha('hasta'),
        ];
    }

    /** Envía un .xlsx con $headers y $rows, y termina la petición. */
    private function enviarXlsx(string $hoja, string $prefijo, array $headers, array $rows): void {
        $dir = sys_get_temp_dir();
        $tmp = $dir . DIRECTORY_SEPARATOR . 'xlsx_' . bin2hex(random_bytes(6)) . '.xlsx';
        XlsxWriter::write($tmp, $hoja, $headers, $rows);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $prefijo . '_' . date('Ymd_His') . '.xlsx"');
        header('Content-Length: ' . filesize($tmp));
        header('X-Content-Type-Options: nosniff');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }
}
