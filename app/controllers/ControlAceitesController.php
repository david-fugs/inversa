<?php
/**
 * ControlAceitesController - Control de aceites aplicados a los equipos.
 */

class ControlAceitesController extends Controller {
    use FiltrosRegistros;

    private ControlAceite $model;
    private Equipo $equipoModel;
    private Base $baseModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        // Solo Administrador y Operador (los roles del módulo de Pagos no acceden aquí).
        if (!in_array(Session::get('user_rol'), ['Administrador', 'Operador'], true)) {
            $this->redirectWith('flight-services', 'error', 'No tiene permiso para esta acción.');
            exit;
        }
        $this->model       = new ControlAceite();
        $this->equipoModel = new Equipo();
        $this->baseModel   = new Base();
    }

    public function index(): void {
        $filtros = $this->filtrosGet();
        $this->view('control_aceites/index', [
            'pageTitle'   => 'Control Aceites',
            'breadcrumbs' => ['Control Aceites' => null],
            'registros'   => $this->model->listar($filtros),
            'bases'       => $this->baseModel->getAll(),
            'filtros'     => $filtros,
        ], 'main');
    }

    /** Excel con los mismos filtros de la pantalla. */
    public function exportExcel(): void {
        $registros = $this->model->listar($this->filtrosGet());
        $headers = ['#', 'Fecha', 'Base', 'Equipo', 'RPM', 'Galones', 'Mantenimiento', 'Registrado por'];
        $rows = [];
        foreach ($registros as $r) {
            $rows[] = [(int)$r['id'], date('d/m/Y', strtotime($r['fecha'])), $r['base_nombre'], $r['equipo_nombre'],
                       (int)$r['rpm'], (float)$r['cantidad_galones'], ControlAceite::MANTENIMIENTOS[$r['mantenimiento']] ?? $r['mantenimiento'], $r['registrado_por']];
        }
        $this->enviarXlsx('Control Aceites', 'control_aceites', $headers, $rows);
    }

    public function createForm(): void {
        $this->renderForm(null, [], ['base_id' => '', 'equipo_id' => '', 'fecha' => date('Y-m-d'), 'cantidad_galones' => '', 'mantenimiento' => '']);
    }

    public function store(): void {
        $data   = $this->datos();
        $errors = $this->validar($data);
        if ($errors) {
            $this->renderForm(null, $errors, $data);
            return;
        }
        $this->model->create($data, (int)Session::get('user_id'));
        $this->redirectWith('control-aceites', 'success', 'Registro creado correctamente.');
    }

    public function editForm(string $id): void {
        $reg = $this->model->findById((int)$id);
        if (!$reg) {
            $this->redirectWith('control-aceites', 'error', 'Registro no encontrado.');
            return;
        }
        $this->renderForm($reg, [], $reg);
    }

    public function update(string $id): void {
        $reg = $this->model->findById((int)$id);
        if (!$reg) {
            $this->redirectWith('control-aceites', 'error', 'Registro no encontrado.');
            return;
        }
        $data   = $this->datos();
        $errors = $this->validar($data);
        if ($errors) {
            $this->renderForm($reg, $errors, $data);
            return;
        }
        $this->model->update((int)$reg['id'], $data);
        $this->redirectWith('control-aceites', 'success', 'Registro actualizado correctamente.');
    }

    public function delete(string $id): void {
        $reg = $this->model->findById((int)$id);
        if (!$reg) {
            $this->redirectWith('control-aceites', 'error', 'Registro no encontrado.');
            return;
        }
        $this->model->delete((int)$reg['id']);
        $this->redirectWith('control-aceites', 'success', 'Registro eliminado correctamente.');
    }

    private function datos(): array {
        return [
            'base_id'          => (int)($_POST['base_id'] ?? 0),
            'equipo_id'        => (int)($_POST['equipo_id'] ?? 0),
            'fecha'            => trim((string)($_POST['fecha'] ?? '')),
            'cantidad_galones' => str_replace(',', '.', trim((string)($_POST['cantidad_galones'] ?? ''))),
            'mantenimiento'        => trim((string)($_POST['mantenimiento'] ?? '')),
        ];
    }

    private function validar(array $d): array {
        $errors = [];
        if (!$d['base_id'] || !$this->baseModel->findById($d['base_id'])) {
            $errors['base_id'] = 'Seleccione la base.';
        }
        $equipo = $d['equipo_id'] ? $this->equipoModel->findById($d['equipo_id']) : false;
        if (!$equipo) {
            $errors['equipo_id'] = 'Seleccione el equipo.';
        } elseif ($d['base_id'] && (int)$equipo['base_id'] !== $d['base_id']) {
            $errors['equipo_id'] = 'El equipo no pertenece a la base seleccionada.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha']) || !strtotime($d['fecha'])) {
            $errors['fecha'] = 'La fecha es obligatoria.';
        }
        if (!is_numeric($d['cantidad_galones']) || (float)$d['cantidad_galones'] <= 0 || (float)$d['cantidad_galones'] > 99999999) {
            $errors['cantidad_galones'] = 'La cantidad en galones debe ser un número mayor a 0.';
        }
        if (!isset(ControlAceite::MANTENIMIENTOS[$d['mantenimiento']])) {
            $errors['mantenimiento'] = 'Seleccione el tipo de mantenimiento.';
        }
        return $errors;
    }

    private function renderForm(?array $reg, array $errors, array $old): void {
        $this->view('control_aceites/form', [
            'pageTitle'   => $reg ? 'Editar Registro' : 'Nuevo Registro',
            'breadcrumbs' => ['Control Aceites' => BASE_URL . '/control-aceites', $reg ? 'Editar' : 'Nuevo' => null],
            'registro'    => $reg,
            'bases'       => $this->baseModel->getAll(),
            'equipos'     => $this->equipoModel->getAll(),
            'errors'      => $errors,
            'old'         => $old,
        ], 'main');
    }
}
