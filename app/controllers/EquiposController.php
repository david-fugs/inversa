<?php
/**
 * EquiposController - CRUD de equipos (base, nombre y RPM).
 */

class EquiposController extends Controller {

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
        $this->equipoModel = new Equipo();
        $this->baseModel   = new Base();
    }

    public function index(): void {
        $this->view('equipos/index', [
            'pageTitle'   => 'Equipos',
            'breadcrumbs' => ['Equipos' => null],
            'equipos'     => $this->equipoModel->getAll(),
        ], 'main');
    }

    public function createForm(): void {
        $this->renderForm(null, [], ['base_id' => '', 'nombre' => '', 'rpm' => '']);
    }

    public function store(): void {
        $data   = $this->datos();
        $errors = $this->validar($data);
        if ($errors) {
            $this->renderForm(null, $errors, $data);
            return;
        }
        $this->equipoModel->create($data);
        $this->redirectWith('equipos', 'success', 'Equipo creado correctamente.');
    }

    public function editForm(string $id): void {
        $equipo = $this->equipoModel->findById((int)$id);
        if (!$equipo) {
            $this->redirectWith('equipos', 'error', 'Equipo no encontrado.');
            return;
        }
        $this->renderForm($equipo, [], $equipo);
    }

    public function update(string $id): void {
        $equipo = $this->equipoModel->findById((int)$id);
        if (!$equipo) {
            $this->redirectWith('equipos', 'error', 'Equipo no encontrado.');
            return;
        }
        $data   = $this->datos();
        $errors = $this->validar($data, (int)$equipo['id']);
        if ($errors) {
            $this->renderForm($equipo, $errors, $data);
            return;
        }
        $this->equipoModel->update((int)$equipo['id'], $data);
        $this->redirectWith('equipos', 'success', 'Equipo actualizado correctamente.');
    }

    public function delete(string $id): void {
        $equipo = $this->equipoModel->findById((int)$id);
        if (!$equipo) {
            $this->redirectWith('equipos', 'error', 'Equipo no encontrado.');
            return;
        }
        if ($this->equipoModel->enUso((int)$equipo['id'])) {
            $this->redirectWith('equipos', 'error', 'No se puede eliminar: el equipo tiene registros de aceites o combustible.');
            return;
        }
        $this->equipoModel->delete((int)$equipo['id']);
        $this->redirectWith('equipos', 'success', 'Equipo eliminado correctamente.');
    }

    private function datos(): array {
        return [
            'base_id' => (int)($_POST['base_id'] ?? 0),
            'nombre'  => trim((string)($_POST['nombre'] ?? '')),
            'rpm'     => trim((string)($_POST['rpm'] ?? '')),
        ];
    }

    private function validar(array $data, int $selfId = 0): array {
        $errors = [];
        if (!$data['base_id'] || !$this->baseModel->findById($data['base_id'])) {
            $errors['base_id'] = 'Seleccione la base.';
        }
        if ($data['nombre'] === '') {
            $errors['nombre'] = 'El nombre del equipo es obligatorio.';
        } elseif (mb_strlen($data['nombre']) > 120) {
            $errors['nombre'] = 'El nombre no puede superar 120 caracteres.';
        } elseif (!isset($errors['base_id']) && $this->equipoModel->nombreExists($data['base_id'], $data['nombre'], $selfId)) {
            $errors['nombre'] = 'Ya existe un equipo con este nombre en la base.';
        }
        if (!ctype_digit($data['rpm']) || (int)$data['rpm'] <= 0) {
            $errors['rpm'] = 'Las RPM deben ser un número entero mayor a 0.';
        }
        return $errors;
    }

    private function renderForm(?array $equipo, array $errors, array $old): void {
        $this->view('equipos/form', [
            'pageTitle'   => $equipo ? 'Editar Equipo' : 'Nuevo Equipo',
            'breadcrumbs' => ['Equipos' => BASE_URL . '/equipos', $equipo ? 'Editar' : 'Nuevo' => null],
            'equipo'      => $equipo,
            'bases'       => $this->baseModel->getAll(),
            'errors'      => $errors,
            'old'         => $old,
        ], 'main');
    }
}
