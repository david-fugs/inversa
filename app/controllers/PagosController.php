<?php
/**
 * PagosController - Lotes de pago a proveedores: crear/reabrir por
 * consecutivo, agregar pagos (con comprobante PDF), descargar
 * comprobantes individuales y el PDF combinado del lote.
 */

class PagosController extends Controller {

    private const ARCHIVO_MAX_BYTES = 2 * 1024 * 1024; // 2 MB

    private LotePago  $loteModel;
    private Pago      $pagoModel;
    private Proveedor $proveedorModel;
    private Banco     $bancoModel;
    private TipoProducto $tipoProductoModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->loteModel        = new LotePago();
        $this->pagoModel        = new Pago();
        $this->proveedorModel   = new Proveedor();
        $this->bancoModel       = new Banco();
        $this->tipoProductoModel = new TipoProducto();
    }

    public function index(): void {
        $lotes = $this->loteModel->getAll();
        $this->view('pagos/lotes_index', [
            'pageTitle'   => 'Pagos a Proveedores',
            'breadcrumbs' => ['Pagos' => null],
            'lotes'       => $lotes,
        ], 'pagos');
    }

    public function nuevoLoteForm(): void {
        $this->view('pagos/lote_nuevo', [
            'pageTitle'   => 'Nuevo Lote de Pago',
            'breadcrumbs' => ['Pagos' => BASE_URL . '/pagos', 'Nuevo Lote' => null],
            'errors'      => [],
            'old'         => [],
        ], 'pagos');
    }

    public function verificarConsecutivo(): void {
        $consecutivo = $this->input('consecutivo', '');

        if (empty($consecutivo)) {
            $this->view('pagos/lote_nuevo', [
                'pageTitle'   => 'Nuevo Lote de Pago',
                'breadcrumbs' => ['Pagos' => BASE_URL . '/pagos', 'Nuevo Lote' => null],
                'errors'      => ['consecutivo' => 'El consecutivo es obligatorio.'],
                'old'         => ['consecutivo' => $consecutivo],
            ], 'pagos');
            return;
        }

        $lote = $this->loteModel->findByConsecutivo($consecutivo);

        if (!$lote) {
            $loteId = $this->loteModel->create([
                'consecutivo' => $consecutivo,
                'user_id'     => (int)Session::get('user_id'),
            ]);
            $this->redirectWith('pagos/lotes/' . $loteId, 'success', 'Lote creado. Ya puede agregar pagos.');
            return;
        }

        if ($lote['estado'] === 'cerrado') {
            $this->view('pagos/lote_nuevo', [
                'pageTitle'   => 'Nuevo Lote de Pago',
                'breadcrumbs' => ['Pagos' => BASE_URL . '/pagos', 'Nuevo Lote' => null],
                'errors'      => ['consecutivo' => 'Este consecutivo ya fue cerrado. Use uno diferente.'],
                'old'         => ['consecutivo' => $consecutivo],
            ], 'pagos');
            return;
        }

        $this->redirectWith('pagos/lotes/' . $lote['id'], 'warning', 'Ya existe un lote abierto con este consecutivo, se continúa agregando pagos a él.');
    }

    public function detalle(string $id): void {
        $loteId = (int)$id;
        $lote   = $this->loteModel->findById($loteId);
        if (!$lote) {
            $this->redirectWith('pagos', 'error', 'Lote no encontrado.');
            return;
        }

        $this->view('pagos/lote_detalle', [
            'pageTitle'     => 'Lote ' . $lote['consecutivo'],
            'breadcrumbs'   => ['Pagos' => BASE_URL . '/pagos', 'Lote ' . $lote['consecutivo'] => null],
            'lote'          => $lote,
            'pagos'         => $this->pagoModel->getByLote($loteId),
            'proveedores'   => $this->proveedorModel->getAll(),
            'bancos'        => $this->bancoModel->getAll(),
            'tiposProducto' => $this->tipoProductoModel->getAll(),
            'errors'        => [],
            'old'           => [],
        ], 'pagos');
    }

    public function agregarPago(string $id): void {
        $loteId = (int)$id;
        $lote   = $this->loteModel->findById($loteId);
        if (!$lote) {
            $this->redirectWith('pagos', 'error', 'Lote no encontrado.');
            return;
        }
        if ($lote['estado'] === 'cerrado') {
            $this->redirectWith('pagos/lotes/' . $loteId, 'error', 'Este lote está cerrado, no se pueden agregar más pagos.');
            return;
        }

        $data = [
            'proveedor_id'        => (int)$this->input('proveedor_id', 0),
            'tipo_identificacion' => $this->input('tipo_identificacion', ''),
            'banco_id'            => (int)$this->input('banco_id', 0),
            'tipo_producto_id'    => (int)$this->input('tipo_producto_id', 0),
            'numero_producto'     => $this->input('numero_producto', ''),
            'fecha_pago'          => $this->input('fecha_pago', ''),
            'valor'               => $this->input('valor', ''),
        ];

        $errors = $this->validarPago($data);

        $file = $_FILES['comprobante_pdf'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $errors['comprobante_pdf'] = 'Seleccione el comprobante PDF del pago.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['comprobante_pdf'] = 'Error al subir el archivo.';
        } elseif ($file['size'] > self::ARCHIVO_MAX_BYTES) {
            $errors['comprobante_pdf'] = 'El archivo supera el tamaño máximo permitido (2 MB).';
        } else {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime      = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
            if ($extension !== 'pdf' || $mime !== 'application/pdf') {
                $errors['comprobante_pdf'] = 'Solo se permiten archivos PDF.';
            }
        }

        if (!empty($errors)) {
            $this->view('pagos/lote_detalle', [
                'pageTitle'     => 'Lote ' . $lote['consecutivo'],
                'breadcrumbs'   => ['Pagos' => BASE_URL . '/pagos', 'Lote ' . $lote['consecutivo'] => null],
                'lote'          => $lote,
                'pagos'         => $this->pagoModel->getByLote($loteId),
                'proveedores'   => $this->proveedorModel->getAll(),
                'bancos'        => $this->bancoModel->getAll(),
                'tiposProducto' => $this->tipoProductoModel->getAll(),
                'errors'        => $errors,
                'old'           => $data,
            ], 'pagos');
            return;
        }

        if (!is_dir(PAGOS_COMPROBANTES_PATH)) {
            mkdir(PAGOS_COMPROBANTES_PATH, 0755, true);
        }

        $storedName = $loteId . '_' . bin2hex(random_bytes(8)) . '.pdf';
        $destino    = PAGOS_COMPROBANTES_PATH . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            $this->redirectWith('pagos/lotes/' . $loteId, 'error', 'No se pudo guardar el comprobante PDF.');
            return;
        }

        $nombreOriginal = trim(preg_replace('/[\r\n]+/', ' ', basename($file['name'])));

        $data['lote_pago_id']              = $loteId;
        $data['comprobante_pdf']           = $storedName;
        $data['comprobante_pdf_original']  = $nombreOriginal !== '' ? $nombreOriginal : 'comprobante.pdf';
        $data['user_id']                   = (int)Session::get('user_id');

        $this->pagoModel->create($data);

        $this->redirectWith('pagos/lotes/' . $loteId, 'success', 'Pago agregado correctamente.');
    }

    public function eliminarPago(string $id): void {
        $pagoId = (int)$id;
        $pago   = $this->pagoModel->findById($pagoId);
        if (!$pago) {
            $this->redirectWith('pagos', 'error', 'Pago no encontrado.');
            return;
        }

        $lote = $this->loteModel->findById((int)$pago['lote_pago_id']);
        if (!$lote || $lote['estado'] === 'cerrado') {
            $this->redirectWith('pagos/lotes/' . $pago['lote_pago_id'], 'error', 'No se puede eliminar un pago de un lote cerrado.');
            return;
        }

        if (!empty($pago['comprobante_pdf'])) {
            $ruta = PAGOS_COMPROBANTES_PATH . '/' . $pago['comprobante_pdf'];
            if (is_file($ruta)) @unlink($ruta);
        }
        $this->pagoModel->delete($pagoId);

        $this->redirectWith('pagos/lotes/' . $lote['id'], 'success', 'Pago eliminado correctamente.');
    }

    public function editarPagoForm(string $id): void {
        $pago = $this->pagoModel->findById((int)$id);
        if (!$pago) {
            $this->redirectWith('pagos', 'error', 'Pago no encontrado.');
            return;
        }

        $lote = $this->loteModel->findById((int)$pago['lote_pago_id']);
        if (!$lote || $lote['estado'] === 'cerrado') {
            $this->redirectWith('pagos/lotes/' . $pago['lote_pago_id'], 'error', 'No se puede editar un pago de un lote cerrado.');
            return;
        }

        $this->view('pagos/pago_editar', [
            'pageTitle'     => 'Editar Pago - Lote ' . $lote['consecutivo'],
            'breadcrumbs'   => ['Pagos' => BASE_URL . '/pagos', 'Lote ' . $lote['consecutivo'] => BASE_URL . '/pagos/lotes/' . $lote['id'], 'Editar Pago' => null],
            'lote'          => $lote,
            'pago'          => $pago,
            'proveedores'   => $this->proveedorModel->getAll(),
            'bancos'        => $this->bancoModel->getAll(),
            'tiposProducto' => $this->tipoProductoModel->getAll(),
            'errors'        => [],
        ], 'pagos');
    }

    public function actualizarPago(string $id): void {
        $pagoId = (int)$id;
        $pago   = $this->pagoModel->findById($pagoId);
        if (!$pago) {
            $this->redirectWith('pagos', 'error', 'Pago no encontrado.');
            return;
        }

        $lote = $this->loteModel->findById((int)$pago['lote_pago_id']);
        if (!$lote || $lote['estado'] === 'cerrado') {
            $this->redirectWith('pagos/lotes/' . $pago['lote_pago_id'], 'error', 'No se puede editar un pago de un lote cerrado.');
            return;
        }

        $data = [
            'proveedor_id'        => (int)$this->input('proveedor_id', 0),
            'tipo_identificacion' => $this->input('tipo_identificacion', ''),
            'banco_id'            => (int)$this->input('banco_id', 0),
            'tipo_producto_id'    => (int)$this->input('tipo_producto_id', 0),
            'numero_producto'     => $this->input('numero_producto', ''),
            'fecha_pago'          => $this->input('fecha_pago', ''),
            'valor'               => $this->input('valor', ''),
        ];

        $errors = $this->validarPago($data);

        // El comprobante PDF es opcional al editar: si no se sube uno
        // nuevo, se conserva el existente.
        $file = $_FILES['comprobante_pdf'] ?? null;
        $reemplazarArchivo = $file && $file['error'] !== UPLOAD_ERR_NO_FILE;
        if ($reemplazarArchivo) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors['comprobante_pdf'] = 'Error al subir el archivo.';
            } elseif ($file['size'] > self::ARCHIVO_MAX_BYTES) {
                $errors['comprobante_pdf'] = 'El archivo supera el tamaño máximo permitido (2 MB).';
            } else {
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $mime      = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
                if ($extension !== 'pdf' || $mime !== 'application/pdf') {
                    $errors['comprobante_pdf'] = 'Solo se permiten archivos PDF.';
                }
            }
        }

        if (!empty($errors)) {
            $this->view('pagos/pago_editar', [
                'pageTitle'     => 'Editar Pago - Lote ' . $lote['consecutivo'],
                'breadcrumbs'   => ['Pagos' => BASE_URL . '/pagos', 'Lote ' . $lote['consecutivo'] => BASE_URL . '/pagos/lotes/' . $lote['id'], 'Editar Pago' => null],
                'lote'          => $lote,
                'pago'          => array_merge($pago, $data),
                'proveedores'   => $this->proveedorModel->getAll(),
                'bancos'        => $this->bancoModel->getAll(),
                'tiposProducto' => $this->tipoProductoModel->getAll(),
                'errors'        => $errors,
            ], 'pagos');
            return;
        }

        $this->pagoModel->update($pagoId, $data);

        if ($reemplazarArchivo) {
            if (!is_dir(PAGOS_COMPROBANTES_PATH)) {
                mkdir(PAGOS_COMPROBANTES_PATH, 0755, true);
            }
            $storedName = $pagoId . '_' . bin2hex(random_bytes(8)) . '.pdf';
            $destino    = PAGOS_COMPROBANTES_PATH . '/' . $storedName;

            if (move_uploaded_file($file['tmp_name'], $destino)) {
                if (!empty($pago['comprobante_pdf'])) {
                    $anterior = PAGOS_COMPROBANTES_PATH . '/' . $pago['comprobante_pdf'];
                    if (is_file($anterior)) @unlink($anterior);
                }
                $nombreOriginal = trim(preg_replace('/[\r\n]+/', ' ', basename($file['name'])));
                $this->pagoModel->setArchivo($pagoId, $storedName, $nombreOriginal !== '' ? $nombreOriginal : 'comprobante.pdf');
            }
        }

        $this->redirectWith('pagos/lotes/' . $lote['id'], 'success', 'Pago actualizado correctamente.');
    }

    public function descargarComprobante(string $id): void {
        $pago = $this->pagoModel->findById((int)$id);
        if (!$pago || empty($pago['comprobante_pdf'])) {
            $this->redirectWith('pagos', 'error', 'El pago no tiene comprobante adjunto.');
            return;
        }

        $ruta = PAGOS_COMPROBANTES_PATH . '/' . $pago['comprobante_pdf'];
        if (!is_file($ruta)) {
            $this->redirectWith('pagos/lotes/' . $pago['lote_pago_id'], 'error', 'El comprobante ya no está disponible.');
            return;
        }

        $nombreDescarga = $pago['comprobante_pdf_original'] ?: 'comprobante.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $nombreDescarga) . '"');
        header('Content-Length: ' . filesize($ruta));
        header('X-Content-Type-Options: nosniff');
        readfile($ruta);
        exit;
    }

    public function descargarCombinado(string $id): void {
        $loteId = (int)$id;
        $lote   = $this->loteModel->findById($loteId);
        if (!$lote) {
            $this->redirectWith('pagos', 'error', 'Lote no encontrado.');
            return;
        }

        $pagos = $this->pagoModel->getByLote($loteId);
        $rutas = [];
        foreach ($pagos as $p) {
            if (!empty($p['comprobante_pdf'])) {
                $rutas[] = PAGOS_COMPROBANTES_PATH . '/' . $p['comprobante_pdf'];
            }
        }

        if (empty($rutas)) {
            $this->redirectWith('pagos/lotes/' . $loteId, 'error', 'El lote no tiene comprobantes para combinar.');
            return;
        }

        if (!is_dir(PAGOS_TEMP_PATH)) {
            mkdir(PAGOS_TEMP_PATH, 0755, true);
        }
        $tmpFile = PAGOS_TEMP_PATH . '/lote_' . $loteId . '_' . bin2hex(random_bytes(6)) . '.pdf';

        PdfMerger::merge($rutas, $tmpFile);

        $nombreDescarga = 'lote_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $lote['consecutivo']) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
        header('Content-Length: ' . filesize($tmpFile));
        header('X-Content-Type-Options: nosniff');
        readfile($tmpFile);
        @unlink($tmpFile);
        exit;
    }

    public function exportarExcel(string $id): void {
        $loteId = (int)$id;
        $lote   = $this->loteModel->findById($loteId);
        if (!$lote) {
            $this->redirectWith('pagos', 'error', 'Lote no encontrado.');
            return;
        }

        $pagos = $this->pagoModel->getByLote($loteId);
        if (empty($pagos)) {
            $this->redirectWith('pagos/lotes/' . $loteId, 'error', 'El lote no tiene pagos para exportar.');
            return;
        }

        $headers = [
            'Tipo de Identificación',
            'Número de Identificación',
            'Nombre',
            'Nombre',
            'Código del Banco',
            'Tipo de Producto o Servicio',
            'Número del Producto o Servicio',
            'Valor del Pago o de la Recarga',
        ];

        $rows = [];
        foreach ($pagos as $p) {
            [$nombre1, $nombre2] = $this->dividirNombre($p['proveedor_nombre']);
            $rows[] = [
                $p['tipo_identificacion'],
                $p['numero_identificacion'],
                $nombre1,
                $nombre2,
                $p['banco_codigo'],
                $p['tipo_producto_codigo'],
                $p['numero_producto'],
                (float)$p['valor'],
            ];
        }

        if (!is_dir(PAGOS_TEMP_PATH)) {
            mkdir(PAGOS_TEMP_PATH, 0755, true);
        }
        $tmpFile = PAGOS_TEMP_PATH . '/lote_' . $loteId . '_' . bin2hex(random_bytes(6)) . '.xlsx';

        XlsxWriter::write($tmpFile, 'Pagos', $headers, $rows);

        $nombreDescarga = 'lote_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $lote['consecutivo']) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
        header('Content-Length: ' . filesize($tmpFile));
        header('X-Content-Type-Options: nosniff');
        readfile($tmpFile);
        @unlink($tmpFile);
        exit;
    }

    /**
     * Divide el nombre completo del proveedor en dos mitades (por
     * palabras) para llenar las dos columnas "Nombre" de la plantilla
     * bancaria, tal como lo exige el formato de exportación.
     */
    private function dividirNombre(string $nombreCompleto): array {
        $palabras = preg_split('/\s+/', trim($nombreCompleto), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($palabras)) {
            return ['', ''];
        }
        $corte = (int)ceil(count($palabras) / 2);
        return [
            implode(' ', array_slice($palabras, 0, $corte)),
            implode(' ', array_slice($palabras, $corte)),
        ];
    }

    public function cerrarLote(string $id): void {
        $loteId = (int)$id;
        $lote   = $this->loteModel->findById($loteId);
        if (!$lote) {
            $this->redirectWith('pagos', 'error', 'Lote no encontrado.');
            return;
        }

        $pagos = $this->pagoModel->getByLote($loteId);
        if (empty($pagos)) {
            $this->redirectWith('pagos/lotes/' . $loteId, 'error', 'No se puede cerrar un lote sin pagos.');
            return;
        }

        $this->loteModel->cerrar($loteId);
        $this->redirectWith('pagos/lotes/' . $loteId, 'success', 'Lote cerrado correctamente.');
    }

    private function validarPago(array $data): array {
        $errors = [];

        if (empty($data['proveedor_id']) || !$this->proveedorModel->findById($data['proveedor_id'])) {
            $errors['proveedor_id'] = 'Seleccione un proveedor válido.';
        }
        if (empty($data['tipo_identificacion'])) {
            $errors['tipo_identificacion'] = 'El tipo de identificación es obligatorio.';
        }
        if (empty($data['banco_id']) || !$this->bancoModel->findById($data['banco_id'])) {
            $errors['banco_id'] = 'Seleccione un banco válido.';
        }
        if (empty($data['tipo_producto_id']) || !$this->tipoProductoModel->findById($data['tipo_producto_id'])) {
            $errors['tipo_producto_id'] = 'Seleccione un tipo de producto válido.';
        }
        if (empty($data['numero_producto'])) {
            $errors['numero_producto'] = 'El número de producto o servicio es obligatorio.';
        }
        if (empty($data['fecha_pago'])) {
            $errors['fecha_pago'] = 'La fecha del pago es obligatoria.';
        }
        if ($data['valor'] === '' || !is_numeric($data['valor']) || (float)$data['valor'] <= 0) {
            $errors['valor'] = 'Ingrese un valor de pago válido.';
        }

        return $errors;
    }
}
