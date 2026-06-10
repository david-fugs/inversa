<?php
/**
 * BasesController - CRUD de bases
 */

class BasesController extends Controller {

    private Base $baseModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->requireAdmin();
        $this->baseModel = new Base();
    }

    private function requireAdmin(): void {
        if (Session::get('user_rol') !== 'Administrador') {
            $this->redirectWith('flight-services', 'error', 'Acceso denegado.');
            exit;
        }
    }

    public function index(): void {
        $bases = $this->baseModel->getAll();
        $this->view('bases/index', [
            'pageTitle'   => 'Bases',
            'breadcrumbs' => ['Bases' => null],
            'bases'       => $bases,
        ]);
    }

    public function createForm(): void {
        $this->view('bases/create', [
            'pageTitle'   => 'Nueva Base',
            'breadcrumbs' => ['Bases' => BASE_URL . '/bases', 'Nueva' => null],
            'errors'      => [],
            'old'         => [],
        ]);
    }

    public function store(): void {
        $nombre = strtoupper($this->input('nombre', ''));
        $errors = $this->validate($nombre);

        if (!empty($errors)) {
            $this->view('bases/create', [
                'pageTitle'   => 'Nueva Base',
                'breadcrumbs' => ['Bases' => BASE_URL . '/bases', 'Nueva' => null],
                'errors'      => $errors,
                'old'         => ['nombre' => $nombre],
            ]);
            return;
        }

        $this->baseModel->create(['nombre' => $nombre]);
        $this->redirectWith('bases', 'success', 'Base creada correctamente.');
    }

    public function editForm(string $id): void {
        $base = $this->baseModel->findById((int)$id);
        if (!$base) {
            $this->redirectWith('bases', 'error', 'Base no encontrada.');
            return;
        }

        $this->view('bases/edit', [
            'pageTitle'   => 'Editar Base',
            'breadcrumbs' => ['Bases' => BASE_URL . '/bases', 'Editar' => null],
            'base'        => $base,
            'errors'      => [],
        ]);
    }

    public function update(string $id): void {
        $baseId = (int)$id;
        $base = $this->baseModel->findById($baseId);
        if (!$base) {
            $this->redirectWith('bases', 'error', 'Base no encontrada.');
            return;
        }

        $nombre = strtoupper($this->input('nombre', ''));
        $errors = $this->validate($nombre, $baseId);

        if (!empty($errors)) {
            $this->view('bases/edit', [
                'pageTitle'   => 'Editar Base',
                'breadcrumbs' => ['Bases' => BASE_URL . '/bases', 'Editar' => null],
                'base'        => array_merge($base, ['nombre' => $nombre]),
                'errors'      => $errors,
            ]);
            return;
        }

        $this->baseModel->update($baseId, ['nombre' => $nombre]);
        $this->redirectWith('bases', 'success', 'Base actualizada correctamente.');
    }

    public function delete(string $id): void {
        $base = $this->baseModel->findById((int)$id);
        if (!$base) {
            $this->redirectWith('bases', 'error', 'Base no encontrada.');
            return;
        }

        if ($this->baseModel->hasFlightServices($base['nombre']) || $this->baseModel->hasUsers($base['nombre'])) {
            $this->redirectWith('bases', 'error', 'No se puede eliminar: la base tiene servicios o usuarios asociados.');
            return;
        }

        if ($this->baseModel->delete((int)$id)) {
            $this->redirectWith('bases', 'success', 'Base eliminada correctamente.');
        } else {
            $this->redirectWith('bases', 'error', 'No se pudo eliminar la base.');
        }
    }

    private function validate(string $nombre, int $excludeId = 0): array {
        $errors = [];
        if (empty($nombre)) {
            $errors['nombre'] = 'El nombre de la base es obligatorio.';
        } elseif ($this->baseModel->nombreExists($nombre, $excludeId)) {
            $errors['nombre'] = 'Ya existe una base con este nombre.';
        }
        return $errors;
    }
}
