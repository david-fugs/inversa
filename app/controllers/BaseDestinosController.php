<?php
/**
 * BaseDestinosController - CRUD de bases destino
 */

class BaseDestinosController extends Controller {

    private BaseDestino $baseDestinoModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->requireAdmin();
        $this->baseDestinoModel = new BaseDestino();
    }

    private function requireAdmin(): void {
        if (Session::get('user_rol') !== 'Administrador') {
            $this->redirectWith('flight-services', 'error', 'Acceso denegado.');
            exit;
        }
    }

    public function index(): void {
        $baseDestinos = $this->baseDestinoModel->getAll();
        $this->view('base_destinos/index', [
            'pageTitle'    => 'Bases Destino',
            'breadcrumbs'  => ['Bases Destino' => null],
            'baseDestinos' => $baseDestinos,
        ]);
    }

    public function createForm(): void {
        $this->view('base_destinos/create', [
            'pageTitle'   => 'Nueva Base Destino',
            'breadcrumbs' => ['Bases Destino' => BASE_URL . '/base-destinos', 'Nueva' => null],
            'errors'      => [],
            'old'         => [],
        ]);
    }

    public function store(): void {
        $nombre = strtoupper($this->input('nombre', ''));
        $errors = $this->validate($nombre);

        if (!empty($errors)) {
            $this->view('base_destinos/create', [
                'pageTitle'   => 'Nueva Base Destino',
                'breadcrumbs' => ['Bases Destino' => BASE_URL . '/base-destinos', 'Nueva' => null],
                'errors'      => $errors,
                'old'         => ['nombre' => $nombre],
            ]);
            return;
        }

        $this->baseDestinoModel->create(['nombre' => $nombre]);
        $this->redirectWith('base-destinos', 'success', 'Base destino creada correctamente.');
    }

    public function editForm(string $id): void {
        $baseDestino = $this->baseDestinoModel->findById((int)$id);
        if (!$baseDestino) {
            $this->redirectWith('base-destinos', 'error', 'Base destino no encontrada.');
            return;
        }

        $this->view('base_destinos/edit', [
            'pageTitle'   => 'Editar Base Destino',
            'breadcrumbs' => ['Bases Destino' => BASE_URL . '/base-destinos', 'Editar' => null],
            'baseDestino' => $baseDestino,
            'errors'      => [],
        ]);
    }

    public function update(string $id): void {
        $baseDestinoId = (int)$id;
        $baseDestino = $this->baseDestinoModel->findById($baseDestinoId);
        if (!$baseDestino) {
            $this->redirectWith('base-destinos', 'error', 'Base destino no encontrada.');
            return;
        }

        $nombre = strtoupper($this->input('nombre', ''));
        $errors = $this->validate($nombre, $baseDestinoId);

        if (!empty($errors)) {
            $this->view('base_destinos/edit', [
                'pageTitle'   => 'Editar Base Destino',
                'breadcrumbs' => ['Bases Destino' => BASE_URL . '/base-destinos', 'Editar' => null],
                'baseDestino' => array_merge($baseDestino, ['nombre' => $nombre]),
                'errors'      => $errors,
            ]);
            return;
        }

        $this->baseDestinoModel->update($baseDestinoId, ['nombre' => $nombre]);
        $this->redirectWith('base-destinos', 'success', 'Base destino actualizada correctamente.');
    }

    public function delete(string $id): void {
        $baseDestino = $this->baseDestinoModel->findById((int)$id);
        if (!$baseDestino) {
            $this->redirectWith('base-destinos', 'error', 'Base destino no encontrada.');
            return;
        }

        if ($this->baseDestinoModel->hasFlightServices($baseDestino['nombre'])) {
            $this->redirectWith('base-destinos', 'error', 'No se puede eliminar: la base destino tiene servicios asociados.');
            return;
        }

        if ($this->baseDestinoModel->delete((int)$id)) {
            $this->redirectWith('base-destinos', 'success', 'Base destino eliminada correctamente.');
        } else {
            $this->redirectWith('base-destinos', 'error', 'No se pudo eliminar la base destino.');
        }
    }

    private function validate(string $nombre, int $excludeId = 0): array {
        $errors = [];
        if (empty($nombre)) {
            $errors['nombre'] = 'El nombre de la base destino es obligatorio.';
        } elseif ($this->baseDestinoModel->nombreExists($nombre, $excludeId)) {
            $errors['nombre'] = 'Ya existe una base destino con este nombre.';
        }
        return $errors;
    }
}
