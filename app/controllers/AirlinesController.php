<?php
/**
 * AirlinesController - CRUD de aerolíneas
 */

class AirlinesController extends Controller {

    private Airline $airlineModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->airlineModel = new Airline();
    }

    public function index(): void {
        $airlines = $this->airlineModel->getAll();
        $this->view('airlines/index', [
            'pageTitle'   => 'Aerolíneas',
            'breadcrumbs' => ['Aerolíneas' => null],
            'airlines'    => $airlines,
        ]);
    }

    public function createForm(): void {
        $this->view('airlines/create', [
            'pageTitle'   => 'Nueva Aerolínea',
            'breadcrumbs' => ['Aerolíneas' => BASE_URL . '/airlines', 'Nueva' => null],
            'errors'      => [],
            'old'         => [],
        ]);
    }

    public function store(): void {
        $nombre = $this->input('nombre', '');
        $errors = [];

        if (empty($nombre)) {
            $errors['nombre'] = 'El nombre de la aerolínea es obligatorio.';
        } elseif ($this->airlineModel->nombreExists($nombre)) {
            $errors['nombre'] = 'Ya existe una aerolínea con este nombre.';
        }

        if (!empty($errors)) {
            $this->view('airlines/create', [
                'pageTitle'   => 'Nueva Aerolínea',
                'breadcrumbs' => ['Aerolíneas' => BASE_URL . '/airlines', 'Nueva' => null],
                'errors'      => $errors,
                'old'         => ['nombre' => $nombre],
            ]);
            return;
        }

        $this->airlineModel->create(['nombre' => $nombre]);
        $this->redirectWith('airlines', 'success', 'Aerolínea creada correctamente.');
    }

    public function editForm(string $id): void {
        $airline = $this->airlineModel->findById((int)$id);
        if (!$airline) {
            $this->redirectWith('airlines', 'error', 'Aerolínea no encontrada.');
            return;
        }
        $this->view('airlines/edit', [
            'pageTitle'   => 'Editar Aerolínea',
            'breadcrumbs' => ['Aerolíneas' => BASE_URL . '/airlines', 'Editar' => null],
            'airline'     => $airline,
            'errors'      => [],
        ]);
    }

    public function update(string $id): void {
        $airlineId = (int)$id;
        $airline   = $this->airlineModel->findById($airlineId);
        if (!$airline) {
            $this->redirectWith('airlines', 'error', 'Aerolínea no encontrada.');
            return;
        }

        $nombre = $this->input('nombre', '');
        $errors = [];

        if (empty($nombre)) {
            $errors['nombre'] = 'El nombre de la aerolínea es obligatorio.';
        } elseif ($this->airlineModel->nombreExists($nombre, $airlineId)) {
            $errors['nombre'] = 'Ya existe una aerolínea con este nombre.';
        }

        if (!empty($errors)) {
            $this->view('airlines/edit', [
                'pageTitle'   => 'Editar Aerolínea',
                'breadcrumbs' => ['Aerolíneas' => BASE_URL . '/airlines', 'Editar' => null],
                'airline'     => array_merge($airline, ['nombre' => $nombre]),
                'errors'      => $errors,
            ]);
            return;
        }

        $this->airlineModel->update($airlineId, ['nombre' => $nombre]);
        $this->redirectWith('airlines', 'success', 'Aerolínea actualizada correctamente.');
    }

    public function delete(string $id): void {
        $airlineId = (int)$id;

        if ($this->airlineModel->hasAircraftTypes($airlineId)) {
            $this->redirectWith('airlines', 'error', 'No se puede eliminar: la aerolínea tiene tipos de avión asociados.');
            return;
        }

        if ($this->airlineModel->delete($airlineId)) {
            $this->redirectWith('airlines', 'success', 'Aerolínea eliminada correctamente.');
        } else {
            $this->redirectWith('airlines', 'error', 'No se pudo eliminar la aerolínea.');
        }
    }
}
