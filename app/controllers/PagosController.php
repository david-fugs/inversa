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
    private PagoComprobante $comprobanteModel;
    private Proveedor $proveedorModel;
    private Banco     $bancoModel;
    private TipoProducto $tipoProductoModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->loteModel        = new LotePago();
        $this->pagoModel        = new Pago();
        $this->comprobanteModel = new PagoComprobante();
        $this->proveedorModel   = new Proveedor();
        $this->bancoModel       = new Banco();
        $this->tipoProductoModel = new TipoProducto();
    }

    /**
     * Normaliza $_FILES['comprobante_pdf'] (que PHP entrega en "forma de
     * columnas" cuando el input es name="comprobante_pdf[]") a una lista
     * de archivos individuales, ignorando los slots vacíos.
     */
    private function archivosSubidos(): array {
        $campo = $_FILES['comprobante_pdf'] ?? null;
        if (!$campo || !isset($campo['name'])) {
            return [];
        }

        $archivos = [];
        foreach ((array)$campo['name'] as $i => $name) {
            $error = $campo['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE || $name === '') {
                continue;
            }
            $archivos[] = [
                'name'     => $name,
                'type'     => $campo['type'][$i] ?? '',
                'tmp_name' => $campo['tmp_name'][$i] ?? '',
                'error'    => $error,
                'size'     => $campo['size'][$i] ?? 0,
            ];
        }
        return $archivos;
    }

    /** Valida un solo archivo subido; retorna un mensaje de error o null si es válido. */
    private function validarArchivoPdf(array $file): ?string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Error al subir el archivo "' . $file['name'] . '".';
        }
        if ($file['size'] > self::ARCHIVO_MAX_BYTES) {
            return 'El archivo "' . $file['name'] . '" supera el tamaño máximo permitido (2 MB).';
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime      = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
        if ($extension !== 'pdf' || $mime !== 'application/pdf') {
            return 'El archivo "' . $file['name'] . '" no es un PDF válido.';
        }
        return null;
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
        if (isset($_GET['nuevo'])) {
            Session::remove('lotes_nuevo_ids');
        }
        $this->renderNuevo();
    }

    private function renderNuevo(array $errors = [], array $old = []): void {
        $this->view('pagos/lote_nuevo', [
            'pageTitle'     => 'Nuevo Lote de Pago',
            'breadcrumbs'   => ['Pagos' => BASE_URL . '/pagos', 'Nuevo Lote' => null],
            'pagosSinLote'  => $this->pagoModel->getSinLote((int)Session::get('user_id')),
            'pagosAsignados' => $this->pagosAsignadosNuevo(),
            'lotesAbiertos' => $this->loteModel->getPorIds((array)Session::get('lotes_nuevo_ids', [])),
            'proveedores'   => $this->proveedorModel->getAll(),
            'bancos'        => $this->bancoModel->getAll(),
            'tiposProducto' => $this->tipoProductoModel->getAll(),
            'errors'        => $errors,
            'old'           => $old,
        ], 'pagos');
    }

    /** Pagos ya asignados a los lotes creados en esta pantalla (se muestran en verde). */
    private function pagosAsignadosNuevo(): array {
        $pagos = [];
        foreach ($this->lotesDeNuevo() as $lote) {
            foreach ($this->pagoModel->getByLote((int)$lote['id']) as $p) {
                $p['lote_consecutivo'] = $lote['consecutivo'];
                $pagos[] = $p;
            }
        }
        return $pagos;
    }

    /** Crea un lote (modal "Crear lote"); si ya existe uno abierto con ese consecutivo lo reutiliza. */
    public function crearLote(): void {
        $consecutivo = $this->input('consecutivo', '');
        if ($consecutivo === '') {
            $this->json(['ok' => false, 'error' => 'El consecutivo es obligatorio.'], 422);
        }

        $lote = $this->loteModel->findByConsecutivo($consecutivo);
        if ($lote && $lote['estado'] === 'cerrado') {
            $this->json(['ok' => false, 'error' => 'Este consecutivo ya fue cerrado. Use uno diferente.'], 422);
        }

        $existente = (bool)$lote;
        if (!$lote) {
            $loteId = $this->loteModel->create([
                'consecutivo' => $consecutivo,
                'user_id'     => (int)Session::get('user_id'),
            ]);
            $lote = $this->loteModel->findById($loteId);
        }

        $ids = (array)Session::get('lotes_nuevo_ids', []);
        if (!in_array((int)$lote['id'], $ids, true)) {
            $ids[] = (int)$lote['id'];
            Session::set('lotes_nuevo_ids', $ids);
        }

        $this->json([
            'ok'        => true,
            'existente' => $existente,
            'lote'      => $this->loteJson($this->loteModel->findConTotales((int)$lote['id'])),
        ]);
    }

    /** Asigna un pago sin lote a un lote abierto (al soltarlo sobre el lote). */
    public function asignarPago(string $id): void {
        $pago = $this->pagoModel->findById((int)$id);
        if (!$pago || $pago['lote_pago_id'] !== null) {
            $this->json(['ok' => false, 'error' => 'El pago no existe o ya está asignado a un lote.'], 404);
        }

        $lote = $this->loteModel->findById((int)$this->input('lote_id', 0));
        if (!$lote) {
            $this->json(['ok' => false, 'error' => 'Lote no encontrado.'], 404);
        }
        if ($lote['estado'] === 'cerrado') {
            $this->json(['ok' => false, 'error' => 'Este lote está cerrado, no se pueden agregar más pagos.'], 422);
        }

        $this->pagoModel->asignarALote((int)$pago['id'], (int)$lote['id']);

        $this->json(['ok' => true, 'lote' => $this->loteJson($this->loteModel->findConTotales((int)$lote['id']))]);
    }

    private function loteJson(array $lote): array {
        return [
            'id'          => (int)$lote['id'],
            'consecutivo' => $lote['consecutivo'],
            'estado'      => $lote['estado'],
            'total_pagos' => (int)$lote['total_pagos'],
            'total_valor' => '$' . number_format((float)$lote['total_valor'], 2),
            'url'         => BASE_URL . '/pagos/lotes/' . $lote['id'],
        ];
    }

    /** Registra un pago sin lote; luego se arrastra a un lote desde /pagos/lotes/nuevo. */
    public function agregarPagoSinLote(): void {
        $data   = $this->datosPago();
        $errors = $this->validarPago($data);

        $archivos = $this->archivosSubidos();
        $errors  += $this->validarComprobantes($archivos, true);

        if (!empty($errors)) {
            $this->renderNuevo($errors, $data);
            return;
        }

        $guardados = $this->guardarArchivos($archivos, 'sl');
        if ($guardados === null) {
            $this->redirectWith('pagos/lotes/nuevo', 'error', 'No se pudo guardar el comprobante.');
            return;
        }

        $data['lote_pago_id'] = null;
        $data['user_id']      = (int)Session::get('user_id');
        $pagoId = $this->pagoModel->create($data);
        foreach ($guardados as $orden => $g) {
            $this->comprobanteModel->create($pagoId, $g['stored'], $g['original'], $orden);
        }

        $this->redirectWith('pagos/lotes/nuevo', 'success', 'Pago agregado. Arrástrelo a un lote para asignarlo.');
    }

    private function datosPago(): array {
        return [
            'proveedor_id'        => (int)$this->input('proveedor_id', 0),
            'tipo_identificacion' => $this->input('tipo_identificacion', ''),
            'banco_id'            => (int)$this->input('banco_id', 0),
            'tipo_producto_id'    => (int)$this->input('tipo_producto_id', 0),
            'numero_producto'     => $this->input('numero_producto', ''),
            'fecha_pago'          => $this->input('fecha_pago', ''),
            'valor'               => $this->input('valor', ''),
        ];
    }

    /** Errores de validación de los comprobantes subidos (vacío si todo está bien). */
    private function validarComprobantes(array $archivos, bool $obligatorio): array {
        if (empty($archivos)) {
            return $obligatorio ? ['comprobante_pdf' => 'Seleccione al menos un comprobante PDF del pago.'] : [];
        }
        foreach ($archivos as $file) {
            $mensaje = $this->validarArchivoPdf($file);
            if ($mensaje !== null) {
                return ['comprobante_pdf' => $mensaje];
            }
        }
        return [];
    }

    /** Mueve los PDFs a su carpeta; retorna null si alguno falla. */
    private function guardarArchivos(array $archivos, string $prefijo): ?array {
        if (!is_dir(PAGOS_COMPROBANTES_PATH)) {
            mkdir(PAGOS_COMPROBANTES_PATH, 0755, true);
        }
        $guardados = [];
        foreach ($archivos as $file) {
            $storedName = $prefijo . '_' . bin2hex(random_bytes(8)) . '.pdf';
            if (!move_uploaded_file($file['tmp_name'], PAGOS_COMPROBANTES_PATH . '/' . $storedName)) {
                return null;
            }
            $nombreOriginal = trim(preg_replace('/[\r\n]+/', ' ', basename($file['name'])));
            $guardados[] = [
                'stored'   => $storedName,
                'original' => $nombreOriginal !== '' ? $nombreOriginal : 'comprobante.pdf',
            ];
        }
        return $guardados;
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

        $archivos = $this->archivosSubidos();
        if (empty($archivos)) {
            $errors['comprobante_pdf'] = 'Seleccione al menos un comprobante PDF del pago.';
        } else {
            foreach ($archivos as $file) {
                $mensaje = $this->validarArchivoPdf($file);
                if ($mensaje !== null) {
                    $errors['comprobante_pdf'] = $mensaje;
                    break;
                }
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

        $guardados = [];
        foreach ($archivos as $file) {
            $storedName = $loteId . '_' . bin2hex(random_bytes(8)) . '.pdf';
            $destino    = PAGOS_COMPROBANTES_PATH . '/' . $storedName;
            if (!move_uploaded_file($file['tmp_name'], $destino)) {
                $this->redirectWith('pagos/lotes/' . $loteId, 'error', 'No se pudo guardar el comprobante "' . $file['name'] . '".');
                return;
            }
            $nombreOriginal = trim(preg_replace('/[\r\n]+/', ' ', basename($file['name'])));
            $guardados[] = [
                'stored'   => $storedName,
                'original' => $nombreOriginal !== '' ? $nombreOriginal : 'comprobante.pdf',
            ];
        }

        $data['lote_pago_id'] = $loteId;
        $data['user_id']      = (int)Session::get('user_id');

        $pagoId = $this->pagoModel->create($data);

        foreach ($guardados as $orden => $g) {
            $this->comprobanteModel->create($pagoId, $g['stored'], $g['original'], $orden);
        }

        $this->redirectWith('pagos/lotes/' . $loteId, 'success', 'Pago agregado correctamente.');
    }

    public function eliminarPago(string $id): void {
        $pagoId = (int)$id;
        $pago   = $this->pagoModel->findById($pagoId);
        if (!$pago) {
            $this->redirectWith('pagos', 'error', 'Pago no encontrado.');
            return;
        }

        $lote = $pago['lote_pago_id'] ? $this->loteModel->findById((int)$pago['lote_pago_id']) : null;
        if ($pago['lote_pago_id'] && (!$lote || $lote['estado'] === 'cerrado')) {
            $this->redirectWith('pagos/lotes/' . $pago['lote_pago_id'], 'error', 'No se puede eliminar un pago de un lote cerrado.');
            return;
        }

        foreach ($pago['comprobantes'] as $c) {
            $ruta = PAGOS_COMPROBANTES_PATH . '/' . $c['archivo'];
            if (is_file($ruta)) @unlink($ruta);
        }
        $this->pagoModel->delete($pagoId);

        $this->redirectWith($lote ? 'pagos/lotes/' . $lote['id'] : 'pagos/lotes/nuevo', 'success', 'Pago eliminado correctamente.');
    }

    public function editarPagoForm(string $id): void {
        $pago = $this->pagoModel->findById((int)$id);
        if (!$pago) {
            $this->redirectWith('pagos', 'error', 'Pago no encontrado.');
            return;
        }

        $lote = $pago['lote_pago_id'] ? $this->loteModel->findById((int)$pago['lote_pago_id']) : null;
        if ($pago['lote_pago_id'] && (!$lote || $lote['estado'] === 'cerrado')) {
            $this->redirectWith('pagos/lotes/' . $pago['lote_pago_id'], 'error', 'No se puede editar un pago de un lote cerrado.');
            return;
        }
        $volverUrl = $lote ? BASE_URL . '/pagos/lotes/' . $lote['id'] : BASE_URL . '/pagos/lotes/nuevo';

        $this->view('pagos/pago_editar', [
            'pageTitle'     => $lote ? 'Editar Pago - Lote ' . $lote['consecutivo'] : 'Editar Pago',
            'breadcrumbs'   => ['Pagos' => BASE_URL . '/pagos', ($lote ? 'Lote ' . $lote['consecutivo'] : 'Nuevo Lote') => $volverUrl, 'Editar Pago' => null],
            'lote'          => $lote,
            'volverUrl'     => $volverUrl,
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

        $lote = $pago['lote_pago_id'] ? $this->loteModel->findById((int)$pago['lote_pago_id']) : null;
        if ($pago['lote_pago_id'] && (!$lote || $lote['estado'] === 'cerrado')) {
            $this->redirectWith('pagos/lotes/' . $pago['lote_pago_id'], 'error', 'No se puede editar un pago de un lote cerrado.');
            return;
        }
        $volverUrl = $lote ? BASE_URL . '/pagos/lotes/' . $lote['id'] : BASE_URL . '/pagos/lotes/nuevo';

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

        // Los comprobantes PDF son opcionales al editar: si no se sube
        // ninguno, se conservan los existentes. Si se sube al menos uno,
        // se reemplaza el conjunto completo de comprobantes del pago.
        $archivos = $this->archivosSubidos();
        $reemplazarArchivos = !empty($archivos);
        if ($reemplazarArchivos) {
            foreach ($archivos as $file) {
                $mensaje = $this->validarArchivoPdf($file);
                if ($mensaje !== null) {
                    $errors['comprobante_pdf'] = $mensaje;
                    break;
                }
            }
        }

        if (!empty($errors)) {
            $this->view('pagos/pago_editar', [
                'pageTitle'     => $lote ? 'Editar Pago - Lote ' . $lote['consecutivo'] : 'Editar Pago',
                'breadcrumbs'   => ['Pagos' => BASE_URL . '/pagos', ($lote ? 'Lote ' . $lote['consecutivo'] : 'Nuevo Lote') => $volverUrl, 'Editar Pago' => null],
                'lote'          => $lote,
                'volverUrl'     => $volverUrl,
                'pago'          => array_merge($pago, $data),
                'proveedores'   => $this->proveedorModel->getAll(),
                'bancos'        => $this->bancoModel->getAll(),
                'tiposProducto' => $this->tipoProductoModel->getAll(),
                'errors'        => $errors,
            ], 'pagos');
            return;
        }

        $this->pagoModel->update($pagoId, $data);

        if ($reemplazarArchivos) {
            if (!is_dir(PAGOS_COMPROBANTES_PATH)) {
                mkdir(PAGOS_COMPROBANTES_PATH, 0755, true);
            }

            $guardados = [];
            foreach ($archivos as $file) {
                $storedName = $pagoId . '_' . bin2hex(random_bytes(8)) . '.pdf';
                $destino    = PAGOS_COMPROBANTES_PATH . '/' . $storedName;
                if (move_uploaded_file($file['tmp_name'], $destino)) {
                    $nombreOriginal = trim(preg_replace('/[\r\n]+/', ' ', basename($file['name'])));
                    $guardados[] = [
                        'stored'   => $storedName,
                        'original' => $nombreOriginal !== '' ? $nombreOriginal : 'comprobante.pdf',
                    ];
                }
            }

            if (!empty($guardados)) {
                foreach ($pago['comprobantes'] as $c) {
                    $anterior = PAGOS_COMPROBANTES_PATH . '/' . $c['archivo'];
                    if (is_file($anterior)) @unlink($anterior);
                }
                $this->comprobanteModel->deleteByPago($pagoId);

                foreach ($guardados as $orden => $g) {
                    $this->comprobanteModel->create($pagoId, $g['stored'], $g['original'], $orden);
                }
            }
        }

        $this->redirectWith($lote ? 'pagos/lotes/' . $lote['id'] : 'pagos/lotes/nuevo', 'success', 'Pago actualizado correctamente.');
    }

    public function descargarComprobante(string $id): void {
        $comprobante = $this->comprobanteModel->findById((int)$id);
        if (!$comprobante) {
            $this->redirectWith('pagos', 'error', 'El comprobante no existe.');
            return;
        }

        $pago = $this->pagoModel->findById((int)$comprobante['pago_id']);

        $ruta = PAGOS_COMPROBANTES_PATH . '/' . $comprobante['archivo'];
        if (!is_file($ruta)) {
            $this->redirectWith('pagos/lotes/' . ($pago['lote_pago_id'] ?? ''), 'error', 'El comprobante ya no está disponible.');
            return;
        }

        $nombreDescarga = $comprobante['archivo_original'] ?: 'comprobante.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $nombreDescarga) . '"');
        header('Content-Length: ' . filesize($ruta));
        header('X-Content-Type-Options: nosniff');
        readfile($ruta);
        exit;
    }

    public function descargarCombinado(string $id): void {
        $lote = $this->loteModel->findById((int)$id);
        if (!$lote) {
            $this->redirectWith('pagos', 'error', 'Lote no encontrado.');
            return;
        }
        $this->enviarCombinado([$lote], 'lote_' . $lote['consecutivo'], 'pagos/lotes/' . $lote['id']);
    }

    public function exportarExcel(string $id): void {
        $lote = $this->loteModel->findById((int)$id);
        if (!$lote) {
            $this->redirectWith('pagos', 'error', 'Lote no encontrado.');
            return;
        }
        $this->enviarExcel([$lote], 'lote_' . $lote['consecutivo'], 'pagos/lotes/' . $lote['id']);
    }

    /** PDF combinado de todos los lotes creados en /pagos/lotes/nuevo. */
    public function descargarCombinadoNuevo(): void {
        $this->enviarCombinado($this->lotesDeNuevo(), 'lotes_' . date('Ymd_His'), 'pagos/lotes/nuevo');
    }

    /** Excel de todos los lotes creados en /pagos/lotes/nuevo. */
    public function exportarExcelNuevo(): void {
        $this->enviarExcel($this->lotesDeNuevo(), 'lotes_' . date('Ymd_His'), 'pagos/lotes/nuevo');
    }

    /** Datos del lote para el modal "Ver" de /pagos/lotes/nuevo. */
    public function loteModal(string $id): void {
        $lote = $this->loteModel->findConTotales((int)$id);
        if (!$lote) {
            $this->json(['ok' => false, 'error' => 'Lote no encontrado.'], 404);
        }

        $pagos = [];
        foreach ($this->pagoModel->getByLote((int)$lote['id']) as $p) {
            $pagos[] = [
                'orden'           => (int)$p['orden'],
                'proveedor'       => $p['proveedor_nombre'],
                'banco'           => $p['banco_nombre'],
                'tipo_producto'   => $p['tipo_producto_nombre'],
                'numero_producto' => $p['numero_producto'],
                'fecha'           => date('d/m/Y', strtotime($p['fecha_pago'])),
                'valor'           => '$' . number_format((float)$p['valor'], 2),
                'comprobantes'    => array_map(fn($c) => [
                    'url'    => BASE_URL . '/pagos/comprobantes/' . $c['id'] . '/file',
                    'nombre' => $c['archivo_original'],
                ], $p['comprobantes']),
            ];
        }

        $this->json([
            'ok'       => true,
            'lote'     => $this->loteJson($lote),
            'pagos'    => $pagos,
            'combinado' => BASE_URL . '/pagos/lotes/' . $lote['id'] . '/combinado',
            'exportar'  => BASE_URL . '/pagos/lotes/' . $lote['id'] . '/exportar',
        ]);
    }

    /** Lotes guardados en sesión para /pagos/lotes/nuevo que aún existen. */
    private function lotesDeNuevo(): array {
        $lotes = [];
        foreach ((array)Session::get('lotes_nuevo_ids', []) as $loteId) {
            $lote = $this->loteModel->findById((int)$loteId);
            if ($lote) {
                $lotes[] = $lote;
            }
        }
        return $lotes;
    }

    private function enviarCombinado(array $lotes, string $nombreBase, string $redirectError): void {
        $rutas = [];
        foreach ($lotes as $lote) {
            foreach ($this->pagoModel->getByLote((int)$lote['id']) as $p) {
                foreach ($p['comprobantes'] as $c) {
                    $rutas[] = PAGOS_COMPROBANTES_PATH . '/' . $c['archivo'];
                }
            }
        }

        if (empty($rutas)) {
            $this->redirectWith($redirectError, 'error', 'No hay comprobantes para combinar.');
            return;
        }

        if (!is_dir(PAGOS_TEMP_PATH)) {
            mkdir(PAGOS_TEMP_PATH, 0755, true);
        }
        $tmpFile = PAGOS_TEMP_PATH . '/lote_' . bin2hex(random_bytes(6)) . '.pdf';

        PdfMerger::merge($rutas, $tmpFile);

        $nombreDescarga = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombreBase) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
        header('Content-Length: ' . filesize($tmpFile));
        header('X-Content-Type-Options: nosniff');
        readfile($tmpFile);
        @unlink($tmpFile);
        exit;
    }

    private function enviarExcel(array $lotes, string $nombreBase, string $redirectError): void {
        $pagos = [];
        foreach ($lotes as $lote) {
            $pagos = array_merge($pagos, $this->pagoModel->getByLote((int)$lote['id']));
        }
        if (empty($pagos)) {
            $this->redirectWith($redirectError, 'error', 'No hay pagos para exportar.');
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
        $tmpFile = PAGOS_TEMP_PATH . '/lote_' . bin2hex(random_bytes(6)) . '.xlsx';

        XlsxWriter::write($tmpFile, 'Pagos', $headers, $rows);

        $nombreDescarga = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombreBase) . '.xlsx';
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
        $destino = ($_GET['volver'] ?? '') === 'nuevo' ? 'pagos/lotes/nuevo' : 'pagos/lotes/' . $loteId;
        $this->redirectWith($destino, 'success', 'Lote cerrado correctamente.');
    }

    /** Cierra todos los lotes abiertos de /pagos/lotes/nuevo que tengan pagos. */
    public function cerrarTodosNuevo(): void {
        $cerrados = 0;
        $vacios   = 0;
        foreach ($this->lotesDeNuevo() as $lote) {
            if ($lote['estado'] !== 'abierto') {
                continue;
            }
            if (empty($this->pagoModel->getByLote((int)$lote['id']))) {
                $vacios++;
                continue;
            }
            $this->loteModel->cerrar((int)$lote['id']);
            $cerrados++;
        }

        if ($cerrados === 0) {
            $this->redirectWith('pagos/lotes/nuevo', 'error', 'No hay lotes abiertos con pagos para cerrar.');
            return;
        }
        $msg = $cerrados . ($cerrados === 1 ? ' lote cerrado' : ' lotes cerrados') . ' correctamente.';
        if ($vacios > 0) {
            $msg .= ' Se omitieron ' . $vacios . ' sin pagos.';
        }
        $this->redirectWith('pagos/lotes/nuevo', 'success', $msg);
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
