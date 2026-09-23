<?php
/**
 * ProveedorImportController
 *
 * Importación masiva de Proveedores desde el Excel "PLANTILLA CON
 * PROVEEDORES" (hoja "Hoja1", encabezado en la fila 1, datos desde la
 * fila 2). Columnas del Excel:
 *   A = Tipo de Identificación        B = Número de Identificación
 *   C = Nombre                        D = Apellido
 *   E = Código del Banco              F = Tipo de Producto o Servicio (código)
 *   G = Número del Producto o Servicio
 *
 * "Nombre" (C) y "Apellido" (D) son en realidad las dos mitades de la
 * razón social / nombre del proveedor, y se concatenan para poblar
 * proveedores.nombre. El banco (E) y el tipo de producto (F) vienen
 * como código: si el código no existe todavía en las tablas `bancos` /
 * `tipos_producto` se crea automáticamente (con ese código como nombre
 * provisional), para garantizar que el proveedor quede correctamente
 * enlazado; un administrador puede luego renombrarlo desde /bancos o
 * /tipos-producto sin que eso afecte al proveedor ya vinculado por id.
 *
 * Si el número de identificación de la fila ya existe, el proveedor se
 * actualiza en vez de crear uno nuevo (permite volver a subir el mismo
 * archivo, o una versión más reciente, sin generar duplicados).
 */
class ProveedorImportController extends Controller {

    private const SHEET_NAME     = 'Hoja1';
    private const DATA_START_ROW = 2;

    private Proveedor      $proveedorModel;
    private Banco          $bancoModel;
    private TipoProducto   $tipoProductoModel;
    private ProveedorImport $importModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->proveedorModel    = new Proveedor();
        $this->bancoModel        = new Banco();
        $this->tipoProductoModel = new TipoProducto();
        $this->importModel       = new ProveedorImport();
    }

    /** Formulario de carga + histórico de importaciones */
    public function form(): void {
        $this->view('proveedores/import', [
            'pageTitle'   => 'Importar Proveedores desde Excel',
            'breadcrumbs' => ['Proveedores' => BASE_URL . '/proveedores', 'Importar Excel' => null],
            'imports'     => $this->importModel->getAllWithUser(),
        ], 'pagos');
    }

    /** Recibir el .xlsx, procesarlo y guardar el resultado */
    public function upload(): void {
        $file = $_FILES['archivo_excel'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $this->redirectWith('proveedores/import', 'error', 'Seleccione un archivo Excel (.xlsx).');
            return;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->redirectWith('proveedores/import', 'error', 'Error al subir el archivo.');
            return;
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'xlsx') {
            $this->redirectWith('proveedores/import', 'error', 'Solo se permiten archivos .xlsx.');
            return;
        }

        $nombreOriginal = trim(preg_replace('/[\r\n]+/', ' ', basename($file['name'])));

        $importId = $this->importModel->create([
            'nombre_archivo'      => $nombreOriginal,
            'total_filas'         => 0,
            'filas_creadas'       => 0,
            'filas_actualizadas'  => 0,
            'filas_error'         => 0,
            'user_id'             => (int)Session::get('user_id'),
        ]);

        try {
            [$total, $creadas, $actualizadas, $errores] = $this->runImport($file['tmp_name'], $importId);
        } catch (\Throwable $e) {
            $this->importModel->delete($importId);
            $this->redirectWith('proveedores/import', 'error', 'No se pudo procesar el archivo: ' . $e->getMessage());
            return;
        }

        $this->importModel->updateStats($importId, $total, $creadas, $actualizadas, count($errores));

        foreach ($errores as $err) {
            $this->importModel->addError($importId, $err['fila'], $err['mensaje'], $err['datos']);
        }

        $mensaje = "Importación completada: $creadas proveedor(es) creado(s), $actualizadas actualizado(s), de $total fila(s).";
        if (count($errores) > 0) {
            $mensaje .= ' ' . count($errores) . ' fila(s) con error, ver detalle.';
        }
        $this->redirectWith('proveedores/import/' . $importId . '/errors', count($errores) > 0 ? 'error' : 'success', $mensaje);
    }

    /** Detalle de errores de una importación puntual */
    public function errors(string $id): void {
        $import = $this->importModel->findByIdWithUser((int)$id);
        if (!$import) {
            $this->redirectWith('proveedores/import', 'error', 'Importación no encontrada.');
            return;
        }
        $this->view('proveedores/import_errors', [
            'pageTitle'   => 'Errores de importación #' . $import['id'],
            'breadcrumbs' => [
                'Proveedores'     => BASE_URL . '/proveedores',
                'Importar Excel'  => BASE_URL . '/proveedores/import',
                'Errores'         => null,
            ],
            'import' => $import,
            'errors' => $this->importModel->getErrors((int)$id),
        ], 'pagos');
    }

    /** Elimina únicamente el registro histórico de la importación (no borra los proveedores ya creados/actualizados) */
    public function deleteImport(string $id): void {
        $import = $this->importModel->findByIdWithUser((int)$id);
        if (!$import) {
            $this->redirectWith('proveedores/import', 'error', 'Importación no encontrada.');
            return;
        }
        $this->importModel->delete((int)$id);
        $this->redirectWith('proveedores/import', 'success', 'Registro de importación "' . $import['nombre_archivo'] . '" eliminado.');
    }

    // ─────────────────────────────────────────────────────────────
    //  Lógica de importación
    // ─────────────────────────────────────────────────────────────

    /** @return array{0:int,1:int,2:int,3:array} [total filas, creadas, actualizadas, errores] */
    private function runImport(string $filePath, int $importId): array {
        $reader = new XlsxReader($filePath);

        $total        = 0;
        $creadas      = 0;
        $actualizadas = 0;
        $errores      = [];

        foreach ($reader->rows(self::SHEET_NAME) as $rowNum => $row) {
            if ($rowNum < self::DATA_START_ROW) continue;

            $tipoIdent = $this->cellText($row['A'] ?? null);
            $numIdent  = $this->cellText($row['B'] ?? null);
            $nombreC   = $this->cellText($row['C'] ?? null);
            $apellidoD = $this->cellText($row['D'] ?? null);
            if ($tipoIdent === '' && $numIdent === '' && $nombreC === '' && $apellidoD === '') {
                continue; // fila completamente vacía
            }

            $total++;
            try {
                $creado = $this->importRow($row);
                $creado ? $creadas++ : $actualizadas++;
            } catch (\Throwable $e) {
                $errores[] = [
                    'fila'    => $rowNum,
                    'mensaje' => $e->getMessage(),
                    'datos'   => $this->rowToPlainArray($row),
                ];
            }
        }

        return [$total, $creadas, $actualizadas, $errores];
    }

    /** @return bool true si se creó un proveedor nuevo, false si se actualizó uno existente */
    private function importRow(array $row): bool {
        $tipoIdent = $this->cellText($row['A'] ?? null);
        $numIdent  = $this->cellText($row['B'] ?? null);
        $nombre    = trim($this->cellText($row['C'] ?? null) . ' ' . $this->cellText($row['D'] ?? null));
        $codBanco  = $this->cellText($row['E'] ?? null);
        $codTipoProd = $this->cellText($row['F'] ?? null);
        $numProducto = $this->cellText($row['G'] ?? null);

        if ($numIdent === '') throw new \RuntimeException('El número de identificación es obligatorio.');
        if ($nombre === '') throw new \RuntimeException('El nombre del proveedor es obligatorio.');
        if ($codBanco === '') throw new \RuntimeException('El código del banco es obligatorio.');
        if ($codTipoProd === '') throw new \RuntimeException('El código del tipo de producto o servicio es obligatorio.');
        if ($numProducto === '') throw new \RuntimeException('El número de producto o servicio es obligatorio.');

        $bancoId       = $this->resolveBancoId($codBanco);
        $tipoProductoId = $this->resolveTipoProductoId($codTipoProd);

        $data = [
            'tipo_identificacion'   => $tipoIdent !== '' ? $tipoIdent : '01',
            'numero_identificacion' => $numIdent,
            'nombre'                => $nombre,
            'banco_id'              => $bancoId,
            'tipo_producto_id'      => $tipoProductoId,
            'numero_producto'       => $numProducto,
        ];

        $existente = $this->proveedorModel->findByNumeroIdentificacion($numIdent);
        if ($existente) {
            $this->proveedorModel->update((int)$existente['id'], $data);
            return false;
        }

        $this->proveedorModel->create($data);
        return true;
    }

    /** Busca el banco por código; si no existe, lo crea automáticamente */
    private function resolveBancoId(string $codigo): int {
        $banco = $this->bancoModel->findByCodigo($codigo);
        if ($banco) return (int)$banco['id'];
        $id = $this->bancoModel->create(['codigo' => $codigo, 'nombre' => $codigo]);
        return $id;
    }

    /** Busca el tipo de producto por código; si no existe, lo crea automáticamente */
    private function resolveTipoProductoId(string $codigo): int {
        $tipoProducto = $this->tipoProductoModel->findByCodigo($codigo);
        if ($tipoProducto) return (int)$tipoProducto['id'];
        $id = $this->tipoProductoModel->create(['codigo' => $codigo, 'nombre' => $codigo]);
        return $id;
    }

    // ─── Lectura de celdas ──────────────────────────────────────

    private function cellText(mixed $value): string {
        if ($value === null) return '';
        if (XlsxReader::isTimeValue($value)) return '';
        if (is_float($value)) {
            return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
        }
        return trim((string)$value);
    }

    private function rowToPlainArray(array $row): array {
        $plain = [];
        foreach ($row as $col => $value) {
            if (XlsxReader::isTimeValue($value)) {
                $plain[$col] = XlsxReader::minutesToTime($value);
            } else {
                $plain[$col] = $value;
            }
        }
        return $plain;
    }
}
