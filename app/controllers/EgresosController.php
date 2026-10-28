<?php
/**
 * EgresosController - Egresos / Legalizaciones: comprobantes de egreso y
 * legalizaciones de anticipo (uno o varios PDF) asociados, de forma
 * opcional, a lotes de pago. Al unir el PDF de un lote, estos documentos
 * van primero (ver PagosController::comprobantesJson()).
 */

class EgresosController extends Controller {

    private const ARCHIVO_MAX_BYTES = 5 * 1024 * 1024; // 5 MB por archivo
    private const POR_PAGINA = 15;

    private EgresoDocumento $docModel;
    private LotePago $loteModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        // Creador y Revisor de pagos no acceden a este módulo.
        if (in_array(Session::get('user_rol'), ['Creador Pagos', 'Revisor Pagos'], true)) {
            $this->redirectWith(Session::get('user_rol') === 'Creador Pagos' ? 'pagos/lotes/nuevo' : 'pagos/revision', 'error', 'No tiene permiso para esta acción.');
            exit;
        }
        $this->docModel  = new EgresoDocumento();
        $this->loteModel = new LotePago();
    }

    // ───────────────────────────── Listado ─────────────────────────────

    public function index(): void {
        $tipo = $_GET['tipo'] ?? '';
        $tipo = isset(EgresoDocumento::TIPOS[$tipo]) ? $tipo : null;

        [$pagina, $totalPaginas, $offset] = $this->paginar($this->docModel->count($tipo), self::POR_PAGINA);

        $this->view('egresos/index', [
            'pageTitle'    => 'Egresos / Legalizaciones',
            'breadcrumbs'  => ['Egresos / Legalizaciones' => null],
            'documentos'   => $this->docModel->paginate(self::POR_PAGINA, $offset, $tipo),
            'tipoFiltro'   => $tipo,
            'sinLote'      => $this->docModel->countSinLote(),
            'pagina'       => $pagina,
            'totalPaginas' => $totalPaginas,
        ], 'pagos');
    }

    // ───────────────────────────── Crear / editar ──────────────────────

    public function createForm(): void {
        $this->renderForm(null, [], ['tipo' => '', 'fecha' => date('Y-m-d'), 'lote_ids' => [], 'pago_ids' => []]);
    }

    public function store(): void {
        $data   = $this->datos();
        $errors = $this->validar($data);

        $archivos = $this->archivosSubidos('archivos');
        if (empty($archivos)) {
            $errors['archivos'] = 'Seleccione al menos un archivo PDF.';
        }
        $errors += $this->validarArchivos($archivos);

        if ($errors) {
            $this->renderForm(null, $errors, $data);
            return;
        }

        $docId = $this->docModel->create($data['tipo'], $data['fecha'], (int)Session::get('user_id'), $data['lote_ids'], $data['pago_ids']);
        if (!$this->guardarArchivos($docId, $archivos)) {
            $this->redirectWith('pagos/egresos', 'error', 'El documento se creó pero algún archivo no se pudo guardar.');
            return;
        }
        $this->redirectWith('pagos/egresos', 'success', 'Documento registrado correctamente.');
    }

    public function editForm(string $id): void {
        $doc = $this->docModel->findById((int)$id);
        if (!$doc) {
            $this->redirectWith('pagos/egresos', 'error', 'Documento no encontrado.');
            return;
        }
        $this->renderForm($doc, [], [
            'tipo'     => $doc['tipo'],
            'fecha'    => $doc['fecha'],
            'lote_ids' => array_column($doc['lotes'], 'id'),
            'pago_ids' => array_column($doc['pagos'], 'id'),
        ]);
    }

    public function update(string $id): void {
        $doc = $this->docModel->findById((int)$id);
        if (!$doc) {
            $this->redirectWith('pagos/egresos', 'error', 'Documento no encontrado.');
            return;
        }
        $data   = $this->datos();
        $errors = $this->validar($data);

        $archivos = $this->archivosSubidos('archivos');
        $errors += $this->validarArchivos($archivos);

        if ($errors) {
            $this->renderForm($doc, $errors, $data);
            return;
        }

        $this->docModel->updateBasico($doc['id'], $data['tipo'], $data['fecha'], $data['lote_ids'], $data['pago_ids']);
        if ($archivos && !$this->guardarArchivos((int)$doc['id'], $archivos)) {
            $this->redirectWith('pagos/egresos', 'error', 'Algún archivo no se pudo guardar.');
            return;
        }
        $this->redirectWith('pagos/egresos', 'success', 'Documento actualizado correctamente.');
    }

    public function delete(string $id): void {
        $doc = $this->docModel->findById((int)$id);
        if (!$doc) {
            $this->redirectWith('pagos/egresos', 'error', 'Documento no encontrado.');
            return;
        }
        foreach ($doc['archivos'] as $a) {
            $ruta = EGRESOS_PATH . '/' . $a['archivo'];
            if (is_file($ruta)) @unlink($ruta);
        }
        $this->docModel->delete((int)$doc['id']);
        $this->redirectWith('pagos/egresos', 'success', 'Documento eliminado correctamente.');
    }

    public function deleteArchivo(string $id): void {
        $archivo = $this->docModel->findArchivo((int)$id);
        if (!$archivo) {
            $this->redirectWith('pagos/egresos', 'error', 'El archivo no existe.');
            return;
        }
        $ruta = EGRESOS_PATH . '/' . $archivo['archivo'];
        if (is_file($ruta)) @unlink($ruta);
        $this->docModel->deleteArchivo((int)$archivo['id']);
        $this->redirectWith('pagos/egresos/' . $archivo['documento_id'] . '/edit', 'success', 'Archivo eliminado.');
    }

    public function archivo(string $id): void {
        $archivo = $this->docModel->findArchivo((int)$id);
        $ruta = $archivo ? EGRESOS_PATH . '/' . $archivo['archivo'] : '';
        if (!$archivo || !is_file($ruta)) {
            $this->redirectWith('pagos/egresos', 'error', 'El archivo ya no está disponible.');
            return;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $archivo['archivo_original'] ?: 'documento.pdf') . '"');
        header('Content-Length: ' . filesize($ruta));
        header('X-Content-Type-Options: nosniff');
        readfile($ruta);
        exit;
    }

    // ───────────────── Documentos de un lote (modal del PDF combinado) ──────

    /** Documentos asociados a los lotes creados en /pagos/lotes/nuevo. */
    public function documentosNuevo(): void {
        $this->json($this->documentosJson($this->loteModel->getPorIds((array)Session::get('lotes_nuevo_ids', []))));
    }

    public function documentosLote(string $id): void {
        $lote = $this->loteModel->findById((int)$id);
        if (!$lote) {
            $this->json(['ok' => false, 'error' => 'Lote no encontrado.'], 404);
        }
        $this->json($this->documentosJson([$lote]));
    }

    /** Sube un documento (uno o varios PDF) asociado directamente a un lote. */
    public function subirALote(string $id): void {
        $lote = $this->loteModel->findById((int)$id);
        if (!$lote) {
            $this->json(['ok' => false, 'error' => 'Lote no encontrado.'], 404);
        }
        $tipo  = (string)($_POST['tipo'] ?? '');
        $fecha = (string)($_POST['fecha'] ?? '');
        if (!isset(EgresoDocumento::TIPOS[$tipo])) {
            $this->json(['ok' => false, 'error' => 'Seleccione el tipo de documento.'], 422);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $this->json(['ok' => false, 'error' => 'La fecha es obligatoria.'], 422);
        }
        $archivos = $this->archivosSubidos('archivos');
        if (empty($archivos)) {
            $this->json(['ok' => false, 'error' => 'Seleccione al menos un archivo PDF.'], 422);
        }
        $errores = $this->validarArchivos($archivos);
        if ($errores) {
            $this->json(['ok' => false, 'error' => reset($errores)], 422);
        }

        // Solo se aceptan pagos que pertenezcan a este lote.
        $pagosLote = array_map('intval', array_column((new Pago())->getByLote((int)$lote['id']), 'id'));
        $pagoIds   = array_values(array_intersect(array_map('intval', (array)($_POST['pagos'] ?? [])), $pagosLote));

        $docId = $this->docModel->create($tipo, $fecha, (int)Session::get('user_id'), [(int)$lote['id']], $pagoIds);
        if (!$this->guardarArchivos($docId, $archivos)) {
            $this->json(['ok' => false, 'error' => 'No se pudo guardar algún archivo.'], 500);
        }
        $this->json(['ok' => true]);
    }

    private function documentosJson(array $lotes): array {
        $salida = [];
        $pagoModel = new Pago();
        foreach ($lotes as $lote) {
            $docs = [];
            foreach ($this->docModel->getByLote((int)$lote['id']) as $d) {
                $docs[] = [
                    'id'         => (int)$d['id'],
                    'tipo'       => $d['tipo'],
                    'tipo_label' => EgresoDocumento::TIPOS[$d['tipo']] ?? $d['tipo'],
                    'fecha'      => date('d/m/Y', strtotime($d['fecha'])),
                    'pagos'      => array_map('intval', array_column($d['pagos'], 'id')),
                    'archivos'   => array_map(fn($a) => [
                        'nombre' => $a['archivo_original'],
                        'url'    => BASE_URL . '/pagos/egresos/archivos/' . $a['id'] . '/file',
                    ], $d['archivos']),
                ];
            }
            $salida[] = [
                'id'          => (int)$lote['id'],
                'consecutivo' => $lote['consecutivo'],
                'documentos'  => $docs,
                'pagos'       => array_map(fn($p) => [
                    'id'    => (int)$p['id'],
                    'label' => $p['proveedor_nombre'] . ' · ' . $p['numero_producto'] . ' · $' . number_format((float)$p['valor'], 2),
                ], $pagoModel->getByLote((int)$lote['id'])),
            ];
        }
        return [
            'ok'    => true,
            'lotes' => $salida,
            'tipos' => EgresoDocumento::TIPOS,
        ];
    }

    // ───────────────────────────── Utilidades ──────────────────────────

    private function renderForm(?array $doc, array $errors, array $old): void {
        $this->view('egresos/form', [
            'pageTitle'   => $doc ? 'Editar Documento' : 'Nuevo Documento',
            'breadcrumbs' => ['Egresos / Legalizaciones' => BASE_URL . '/pagos/egresos', $doc ? 'Editar' : 'Nuevo' => null],
            'doc'         => $doc,
            'lotes'       => $this->loteModel->getAll(),
            'pagos'       => $this->docModel->pagosAsociables(),
            'errors'      => $errors,
            'old'         => $old,
        ], 'pagos');
    }

    private function datos(): array {
        $loteIds = $_POST['lotes'] ?? [];
        return [
            'tipo'     => (string)($_POST['tipo'] ?? ''),
            'fecha'    => (string)($_POST['fecha'] ?? ''),
            'lote_ids' => array_values(array_filter(array_map('intval', (array)$loteIds))),
            'pago_ids' => array_values(array_filter(array_map('intval', (array)($_POST['pagos'] ?? [])))),
        ];
    }

    private function validar(array $data): array {
        $errors = [];
        if (!isset(EgresoDocumento::TIPOS[$data['tipo']])) {
            $errors['tipo'] = 'Seleccione el tipo de documento.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha'])) {
            $errors['fecha'] = 'La fecha es obligatoria.';
        }
        return $errors;
    }

    /** Normaliza $_FILES[$campo] (name="campo[]") a una lista de archivos, sin los vacíos. */
    private function archivosSubidos(string $campo): array {
        $f = $_FILES[$campo] ?? null;
        if (!$f || !isset($f['name'])) return [];
        $out = [];
        foreach ((array)$f['name'] as $i => $name) {
            $error = $f['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE || $name === '') continue;
            $out[] = [
                'name' => $name, 'tmp_name' => $f['tmp_name'][$i] ?? '',
                'error' => $error, 'size' => $f['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    private function validarArchivos(array $archivos): array {
        foreach ($archivos as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['archivos' => 'Error al subir el archivo "' . $file['name'] . '".'];
            }
            if ($file['size'] > self::ARCHIVO_MAX_BYTES) {
                return ['archivos' => 'El archivo "' . $file['name'] . '" supera el tamaño máximo permitido (5 MB).'];
            }
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
            if ($ext !== 'pdf' || $mime !== 'application/pdf') {
                return ['archivos' => 'El archivo "' . $file['name'] . '" no es un PDF válido.'];
            }
        }
        return [];
    }

    /** Mueve los PDF a EGRESOS_PATH y los registra; false si alguno falla. */
    private function guardarArchivos(int $docId, array $archivos): bool {
        if (!is_dir(EGRESOS_PATH)) {
            mkdir(EGRESOS_PATH, 0755, true);
        }
        $orden = $this->docModel->siguienteOrdenArchivo($docId);
        $ok = true;
        foreach ($archivos as $file) {
            $stored = $docId . '_' . bin2hex(random_bytes(8)) . '.pdf';
            if (!move_uploaded_file($file['tmp_name'], EGRESOS_PATH . '/' . $stored)) {
                $ok = false;
                continue;
            }
            $original = trim(preg_replace('/[\r\n]+/', ' ', basename($file['name'])));
            $this->docModel->addArchivo($docId, $stored, $original !== '' ? $original : 'documento.pdf', $orden++);
        }
        return $ok;
    }
}
