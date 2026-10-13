<?php
/**
 * PagosController - Lotes de pago a proveedores: crear/reabrir por
 * consecutivo, agregar pagos (con comprobante PDF), descargar
 * comprobantes individuales y el PDF combinado del lote.
 */

class PagosController extends Controller {

    private const ARCHIVO_MAX_BYTES = 2 * 1024 * 1024; // 2 MB

    /** Roles del flujo de revisión: el creador solo registra/edita sus pagos,
     *  el revisor los aprueba o rechaza; el resto (Admin/Operador Pagos) arma lotes. */
    private const ROL_CREADOR = 'Creador Pagos';
    private const ROL_REVISOR = 'Revisor Pagos';

    private LotePago  $loteModel;
    private Pago      $pagoModel;
    private PagoComprobante $comprobanteModel;
    private Proveedor $proveedorModel;
    private Banco     $bancoModel;
    private TipoProducto $tipoProductoModel;
    private EgresoDocumento $egresoModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->loteModel        = new LotePago();
        $this->pagoModel        = new Pago();
        $this->comprobanteModel = new PagoComprobante();
        $this->proveedorModel   = new Proveedor();
        $this->bancoModel       = new Banco();
        $this->tipoProductoModel = new TipoProducto();
        $this->egresoModel      = new EgresoDocumento();
    }

    private function rol(): string {
        return (string)Session::get('user_rol');
    }

    private function esCreador(): bool { return $this->rol() === self::ROL_CREADOR; }
    private function esRevisor(): bool { return $this->rol() === self::ROL_REVISOR; }

    /** Pantalla de inicio de cada rol dentro del módulo. */
    private function inicioPath(): string {
        if ($this->esCreador()) return 'pagos/lotes/nuevo';
        if ($this->esRevisor()) return 'pagos/revision';
        return 'pagos';
    }

    /** Bloquea a Creador/Revisor en las acciones de lotes (gestión de pagos). */
    private function soloGestion(bool $json = false): void {
        if (!$this->esCreador() && !$this->esRevisor()) return;
        if ($json) {
            $this->json(['ok' => false, 'error' => 'No tiene permiso para esta acción.'], 403);
        }
        $this->redirectWith($this->inicioPath(), 'error', 'No tiene permiso para esta acción.');
        exit;
    }

    /** ¿El pago lo registró el usuario actual? */
    private function esPropio(array $pago): bool {
        return (int)$pago['user_id'] === (int)Session::get('user_id');
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
        if ($this->esCreador() || $this->esRevisor()) {
            $this->redirect($this->inicioPath());
            return;
        }
        $lotes = $this->loteModel->getAll();
        $this->view('pagos/lotes_index', [
            'pageTitle'   => 'Pagos a Proveedores',
            'breadcrumbs' => ['Pagos' => null],
            'lotes'       => $lotes,
        ], 'pagos');
    }

    private const POR_PAGINA = 15;

    /** Pantalla del Revisor Pagos: pagos de los creadores, por pestaña (estado o archivados), paginados. */
    public function revision(): void {
        if (!$this->esRevisor()) {
            $this->redirectWith($this->inicioPath(), 'error', 'No tiene permiso para esta acción.');
            return;
        }
        $tab = $_GET['estado'] ?? 'pendiente';
        if (!in_array($tab, ['pendiente', 'aprobado', 'rechazado', 'todos', 'archivados'], true)) {
            $tab = 'pendiente';
        }
        $archivados = $tab === 'archivados';
        $estado     = in_array($tab, ['pendiente', 'aprobado', 'rechazado'], true) ? $tab : null;

        $total = $this->pagoModel->contarParaRevision($estado, $archivados);
        [$pagina, $totalPaginas, $offset] = $this->paginar($total, self::POR_PAGINA);

        $this->view('pagos/revision', [
            'pageTitle'    => 'Revisión de Pagos',
            'breadcrumbs'  => ['Revisión de Pagos' => null],
            'pagos'        => $this->pagoModel->getParaRevision($estado, $archivados, self::POR_PAGINA, $offset),
            'tab'          => $tab,
            'resumen'      => $this->pagoModel->resumenRevision(),
            'pagina'       => $pagina,
            'totalPaginas' => $totalPaginas,
            'total'        => $total,
        ], 'pagos');
    }

    /** Archiva/desarchiva un pago en la bandeja de quien lo pide (revisor: cualquiera; creador: los suyos). */
    public function archivar(string $id): void {
        $pago = $this->pagoModel->findById((int)$id);
        $rol  = $this->esRevisor() ? 'revisor' : ($this->esCreador() ? 'creador' : null);
        if ($rol === null) {
            $this->soloGestion();
            return;
        }
        $volver = $rol === 'revisor' ? 'pagos/revision' : 'pagos/lotes/nuevo';
        if (!$pago || ($rol === 'creador' && !$this->esPropio($pago))) {
            $this->redirectWith($volver, 'error', 'Pago no encontrado.');
            return;
        }
        $archivar = ($_POST['valor'] ?? '1') === '1';
        $this->pagoModel->setArchivado((int)$pago['id'], $rol, $archivar);

        $volverUrl = $_POST['volver'] ?? '';
        $destino = (strpos($volverUrl, BASE_URL . '/pagos/') === 0) ? $volverUrl : BASE_URL . '/' . $volver;
        Session::setFlash('success', $archivar ? 'Pago archivado.' : 'Pago restaurado.');
        header('Location: ' . $destino);
        exit;
    }

    /** Aprueba o rechaza un pago (solo Revisor Pagos). */
    public function revisar(string $id): void {
        if (!$this->esRevisor()) {
            $this->redirectWith($this->inicioPath(), 'error', 'Solo el revisor puede aprobar o rechazar pagos.');
            return;
        }
        $pago = $this->pagoModel->findById((int)$id);
        if (!$pago) {
            $this->redirectWith('pagos/revision', 'error', 'Pago no encontrado.');
            return;
        }
        if ($pago['lote_pago_id'] !== null) {
            $this->redirectWith('pagos/revision', 'error', 'Este pago ya está asignado a un lote y no puede cambiarse.');
            return;
        }

        $accion = $_POST['accion'] ?? '';
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        if (!in_array($accion, ['aprobar', 'rechazar'], true)) {
            $this->redirectWith('pagos/revision', 'error', 'Acción no válida.');
            return;
        }
        if ($accion === 'rechazar' && $motivo === '') {
            $this->redirectWith('pagos/revision', 'error', 'Indique el motivo del rechazo.');
            return;
        }

        $this->pagoModel->setRevision((int)$pago['id'], $accion === 'aprobar' ? 'aprobado' : 'rechazado',
            (int)Session::get('user_id'), mb_substr($motivo, 0, 255));
        $this->redirectWith('pagos/revision', 'success', $accion === 'aprobar' ? 'Pago aprobado.' : 'Pago rechazado.');
    }

    public function nuevoLoteForm(): void {
        if ($this->esRevisor()) {
            $this->redirect($this->inicioPath());
            return;
        }
        if (isset($_GET['nuevo'])) {
            Session::remove('lotes_nuevo_ids');
        }
        $this->renderNuevo();
    }

    /** Pantalla del Creador Pagos: formulario + sus propios pagos con su estado de revisión. */
    private function renderCreador(array $errors = [], array $old = []): void {
        $userId     = (int)Session::get('user_id');
        $archivados = ($_GET['tab'] ?? '') === 'archivados';
        $resumen    = $this->pagoModel->resumenCreador($userId);
        [$pagina, $totalPaginas, $offset] = $this->paginar($archivados ? $resumen['archivados'] : $resumen['activos'], self::POR_PAGINA);
        $this->view('pagos/creador_pagos', [
            'pageTitle'     => 'Registrar Pagos',
            'breadcrumbs'   => ['Registrar Pagos' => null],
            'misPagos'      => $this->pagoModel->getByCreador($userId, $archivados, self::POR_PAGINA, $offset),
            'tab'           => $archivados ? 'archivados' : 'activos',
            'resumen'       => $resumen,
            'pagina'        => $pagina,
            'totalPaginas'  => $totalPaginas,
            'proveedores'   => $this->proveedorModel->getAll(),
            'bancos'        => $this->bancoModel->getAll(),
            'tiposProducto' => $this->tipoProductoModel->getAll(),
            'errors'        => $errors,
            'old'           => $old,
        ], 'pagos');
    }

    private function renderNuevo(array $errors = [], array $old = []): void {
        if ($this->esCreador()) {
            $this->renderCreador($errors, $old);
            return;
        }
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
        $this->soloGestion(true);
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
        $this->soloGestion(true);
        $pago = $this->pagoModel->findById((int)$id);
        if (!$pago || $pago['lote_pago_id'] !== null) {
            $this->json(['ok' => false, 'error' => 'El pago no existe o ya está asignado a un lote.'], 404);
        }

        if (($pago['estado_revision'] ?? 'aprobado') !== 'aprobado') {
            $this->json(['ok' => false, 'error' => 'Solo se pueden asignar a un lote los pagos aprobados.'], 422);
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
        if ($this->esRevisor()) {
            $this->soloGestion();
        }
        $data   = $this->datosPago();
        $errors = $this->validarPago($data);

        $archivos = $this->archivosSubidos();
        $errors  += $this->validarComprobantes($archivos, $this->esCreador());

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
        // Lo que registra un Creador queda pendiente de revisión; el resto entra aprobado.
        $data['estado_revision'] = $this->esCreador() ? 'pendiente' : 'aprobado';
        $pagoId = $this->pagoModel->create($data);
        foreach ($guardados as $orden => $g) {
            $this->comprobanteModel->create($pagoId, $g['stored'], $g['original'], $orden);
        }

        $this->redirectWith('pagos/lotes/nuevo', 'success', $this->esCreador()
            ? 'Pago registrado. Quedó pendiente de revisión.'
            : 'Pago agregado. Arrástrelo a un lote para asignarlo.');
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
        $this->soloGestion();
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
            'documentos'    => $this->egresoModel->getByLote($loteId),
            'proveedores'   => $this->proveedorModel->getAll(),
            'bancos'        => $this->bancoModel->getAll(),
            'tiposProducto' => $this->tipoProductoModel->getAll(),
            'errors'        => [],
            'old'           => [],
        ], 'pagos');
    }

    public function agregarPago(string $id): void {
        $this->soloGestion();
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
                'documentos'    => $this->egresoModel->getByLote($loteId),
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
        $data['estado_revision'] = 'aprobado';

        $pagoId = $this->pagoModel->create($data);

        foreach ($guardados as $orden => $g) {
            $this->comprobanteModel->create($pagoId, $g['stored'], $g['original'], $orden);
        }

        $this->redirectWith('pagos/lotes/' . $loteId, 'success', 'Pago agregado correctamente.');
    }

    public function eliminarPago(string $id): void {
        $this->soloGestion();
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
        if ($this->esRevisor()) {
            $this->soloGestion();
        }
        $pago = $this->pagoModel->findById((int)$id);
        if (!$pago) {
            $this->redirectWith($this->inicioPath(), 'error', 'Pago no encontrado.');
            return;
        }
        if (!$this->puedeEditarComoCreador($pago)) {
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
        if ($this->esRevisor()) {
            $this->soloGestion();
        }
        $pagoId = (int)$id;
        $pago   = $this->pagoModel->findById($pagoId);
        if (!$pago) {
            $this->redirectWith($this->inicioPath(), 'error', 'Pago no encontrado.');
            return;
        }
        if (!$this->puedeEditarComoCreador($pago)) {
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

        // Un pago editado por su creador vuelve a revisión.
        if ($this->esCreador()) {
            $this->pagoModel->resetRevision($pagoId);
        }

        $this->redirectWith($lote ? 'pagos/lotes/' . $lote['id'] : 'pagos/lotes/nuevo', 'success', 'Pago actualizado correctamente.');
    }

    /** El Creador Pagos solo edita sus propios pagos que aún no tienen lote. */
    private function puedeEditarComoCreador(array $pago): bool {
        if (!$this->esCreador()) return true;
        if (!$this->esPropio($pago) || $pago['lote_pago_id'] !== null) {
            $this->redirectWith($this->inicioPath(), 'error', 'No puede editar este pago.');
            return false;
        }
        return true;
    }

    /** Elimina un comprobante de un pago, solo si su lote no está cerrado. */
    public function eliminarComprobante(string $id): void {
        if ($this->esRevisor()) {
            $this->soloGestion();
        }
        $comprobante = $this->comprobanteModel->findById((int)$id);
        if (!$comprobante) {
            $this->redirectWith('pagos', 'error', 'El comprobante no existe.');
            return;
        }

        $pago   = $this->pagoModel->findById((int)$comprobante['pago_id']);
        if ($pago && !$this->puedeEditarComoCreador($pago)) {
            return;
        }
        $lote   = $pago && $pago['lote_pago_id'] ? $this->loteModel->findById((int)$pago['lote_pago_id']) : null;
        $volver = $lote ? 'pagos/lotes/' . $lote['id'] : 'pagos/lotes/nuevo';

        if ($lote && $lote['estado'] === 'cerrado') {
            $this->redirectWith($volver, 'error', 'No se puede eliminar un comprobante de un lote cerrado.');
            return;
        }

        $ruta = PAGOS_COMPROBANTES_PATH . '/' . $comprobante['archivo'];
        if (is_file($ruta)) @unlink($ruta);
        $this->comprobanteModel->delete((int)$comprobante['id']);
        if ($pago && $this->esCreador()) {
            $this->pagoModel->resetRevision((int)$pago['id']);
        }

        $this->redirectWith($volver, 'success', 'Comprobante eliminado correctamente.');
    }

    public function descargarComprobante(string $id): void {
        $comprobante = $this->comprobanteModel->findById((int)$id);
        if (!$comprobante) {
            $this->redirectWith('pagos', 'error', 'El comprobante no existe.');
            return;
        }

        $pago = $this->pagoModel->findById((int)$comprobante['pago_id']);
        // El creador solo puede ver los comprobantes de sus propios pagos.
        if ($this->esCreador() && (!$pago || !$this->esPropio($pago))) {
            $this->redirectWith($this->inicioPath(), 'error', 'No puede ver este comprobante.');
            return;
        }

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
        $this->soloGestion();
        $lote = $this->loteModel->findById((int)$id);
        if (!$lote) {
            $this->redirectWith('pagos', 'error', 'Lote no encontrado.');
            return;
        }
        $this->enviarCombinado([$lote], 'lote_' . $lote['consecutivo'], 'pagos/lotes/' . $lote['id']);
    }

    public function exportarExcel(string $id): void {
        $this->soloGestion();
        $lote = $this->loteModel->findById((int)$id);
        if (!$lote) {
            $this->redirectWith('pagos', 'error', 'Lote no encontrado.');
            return;
        }
        $this->enviarExcel([$lote], 'lote_' . $lote['consecutivo'], 'pagos/lotes/' . $lote['id']);
    }

    /** Lista ordenada de comprobantes de un lote; el navegador los une (pdf-lib). */
    public function comprobantesLote(string $id): void {
        $this->soloGestion(true);
        $lote = $this->loteModel->findById((int)$id);
        if (!$lote) {
            $this->json(['ok' => false, 'error' => 'Lote no encontrado.'], 404);
        }
        $this->json($this->comprobantesJson([$lote], 'lote_' . $lote['consecutivo']));
    }

    public function comprobantesNuevo(): void {
        $this->soloGestion(true);
        $this->json($this->comprobantesJson($this->lotesDeNuevo(), 'lotes_' . date('Ymd_His')));
    }

    /** Archivos de egresos/legalizaciones asociados a los lotes (sin repetir), en orden de documento. */
    private function archivosEgresosDeLotes(array $lotes): array {
        $archivos = [];
        foreach ($lotes as $lote) {
            foreach ($this->egresoModel->getByLote((int)$lote['id']) as $doc) {
                foreach ($doc['archivos'] as $a) {
                    $archivos[(int)$a['id']] = $a;
                }
            }
        }
        return array_values($archivos);
    }

    private function comprobantesJson(array $lotes, string $nombreBase): array {
        $items = [];
        // Egresos / legalizaciones primero, luego los comprobantes de cada pago.
        foreach ($this->archivosEgresosDeLotes($lotes) as $a) {
            $items[] = [
                'url'    => BASE_URL . '/pagos/egresos/archivos/' . $a['id'] . '/file',
                'nombre' => $a['archivo_original'],
            ];
        }
        foreach ($lotes as $lote) {
            foreach ($this->pagoModel->getByLote((int)$lote['id']) as $p) {
                foreach ($p['comprobantes'] as $c) {
                    $items[] = [
                        'url'    => BASE_URL . '/pagos/comprobantes/' . $c['id'] . '/file',
                        'nombre' => $c['archivo_original'],
                    ];
                }
            }
        }
        return [
            'ok'           => true,
            'archivo'      => preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombreBase) . '.pdf',
            'comprobantes' => $items,
        ];
    }

    /** PDF combinado de todos los lotes creados en /pagos/lotes/nuevo. */
    public function descargarCombinadoNuevo(): void {
        $this->soloGestion();
        $this->enviarCombinado($this->lotesDeNuevo(), 'lotes_' . date('Ymd_His'), 'pagos/lotes/nuevo');
    }

    /** Excel de todos los lotes creados en /pagos/lotes/nuevo. */
    public function exportarExcelNuevo(): void {
        $this->soloGestion();
        $this->enviarExcel($this->lotesDeNuevo(), 'lotes_' . date('Ymd_His'), 'pagos/lotes/nuevo');
    }

    /** Datos del lote para el modal "Ver" de /pagos/lotes/nuevo. */
    public function loteModal(string $id): void {
        $this->soloGestion(true);
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
            'comprobantes' => BASE_URL . '/pagos/lotes/' . $lote['id'] . '/comprobantes',
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
        $rutas   = [];
        $nombres = [];
        foreach ($this->archivosEgresosDeLotes($lotes) as $a) {
            $ruta = EGRESOS_PATH . '/' . $a['archivo'];
            $rutas[] = $ruta;
            $nombres[$ruta] = $a['archivo_original'];
        }
        foreach ($lotes as $lote) {
            foreach ($this->pagoModel->getByLote((int)$lote['id']) as $p) {
                foreach ($p['comprobantes'] as $c) {
                    $ruta = PAGOS_COMPROBANTES_PATH . '/' . $c['archivo'];
                    $rutas[] = $ruta;
                    $nombres[$ruta] = $c['archivo_original'];
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

        try {
            PdfMerger::merge($rutas, $tmpFile, $nombres);
        } catch (\RuntimeException $e) {
            @unlink($tmpFile);
            $this->redirectWith($redirectError, 'error', $e->getMessage());
            return;
        }

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

    public function eliminarLote(string $id): void {
        $this->soloGestion();
        $loteId = (int)$id;
        $lote   = $this->loteModel->findById($loteId);
        if (!$lote) {
            $this->redirectWith('pagos', 'error', 'Lote no encontrado.');
            return;
        }

        $pagos = $this->pagoModel->getByLote($loteId);
        $this->loteModel->eliminarConPagos($loteId);

        foreach ($pagos as $pago) {
            foreach ($pago['comprobantes'] as $c) {
                $ruta = PAGOS_COMPROBANTES_PATH . '/' . $c['archivo'];
                if (is_file($ruta)) @unlink($ruta);
            }
        }

        $destino = ($_GET['volver'] ?? '') === 'nuevo' ? 'pagos/lotes/nuevo' : 'pagos';
        $this->redirectWith($destino, 'success', 'Lote eliminado correctamente.');
    }

    public function cerrarLote(string $id): void {
        $this->soloGestion();
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
        $this->soloGestion();
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
