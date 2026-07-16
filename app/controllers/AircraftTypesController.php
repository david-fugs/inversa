<?php
/**
 * AircraftTypesController - CRUD de tipos de avión
 */

class AircraftTypesController extends Controller {

    private AircraftType $model;
    private Airline      $airlineModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->model        = new AircraftType();
        $this->airlineModel = new Airline();
    }

    public function index(): void {
        $types = $this->model->getAllWithAirline();
        $this->view('aircraft_types/index', [
            'pageTitle'   => 'Tipos de Avión',
            'breadcrumbs' => ['Tipos de Avión' => null],
            'types'       => $types,
        ]);
    }

    public function createForm(): void {
        $airlines = $this->airlineModel->getAll();
        $this->view('aircraft_types/create', [
            'pageTitle'    => 'Nuevo Tipo de Avión',
            'breadcrumbs'  => ['Tipos de Avión' => BASE_URL . '/aircraft-types', 'Nuevo' => null],
            'airlines'     => $airlines,
            'errors'       => [],
            'old'          => [],
        ]);
    }

    public function store(): void {
        $data = [
            'airline_id'          => (int)$this->input('airline_id'),
            'tipo'                => $this->input('tipo', ''),
            'tiempo_cumplimiento' => (int)$this->input('tiempo_cumplimiento'),
        ];
        $errors = $this->validate($data);

        if (!empty($errors)) {
            $airlines = $this->airlineModel->getAll();
            $this->view('aircraft_types/create', [
                'pageTitle'    => 'Nuevo Tipo de Avión',
                'breadcrumbs'  => ['Tipos de Avión' => BASE_URL . '/aircraft-types', 'Nuevo' => null],
                'airlines'     => $airlines,
                'errors'       => $errors,
                'old'          => $data,
            ]);
            return;
        }

        $this->model->create($data);
        $this->redirectWith('aircraft-types', 'success', 'Tipo de avión creado correctamente.');
    }

    public function editForm(string $id): void {
        $type = $this->model->findByIdWithAirline((int)$id);
        if (!$type) {
            $this->redirectWith('aircraft-types', 'error', 'Tipo de avión no encontrado.');
            return;
        }
        $airlines = $this->airlineModel->getAll();
        $this->view('aircraft_types/edit', [
            'pageTitle'    => 'Editar Tipo de Avión',
            'breadcrumbs'  => ['Tipos de Avión' => BASE_URL . '/aircraft-types', 'Editar' => null],
            'type'         => $type,
            'airlines'     => $airlines,
            'errors'       => [],
        ]);
    }

    public function update(string $id): void {
        $typeId = (int)$id;
        $type   = $this->model->findById($typeId);
        if (!$type) {
            $this->redirectWith('aircraft-types', 'error', 'Tipo de avión no encontrado.');
            return;
        }

        $data = [
            'airline_id'          => (int)$this->input('airline_id'),
            'tipo'                => $this->input('tipo', ''),
            'tiempo_cumplimiento' => (int)$this->input('tiempo_cumplimiento'),
        ];
        $errors = $this->validate($data);

        if (!empty($errors)) {
            $airlines = $this->airlineModel->getAll();
            $this->view('aircraft_types/edit', [
                'pageTitle'    => 'Editar Tipo de Avión',
                'breadcrumbs'  => ['Tipos de Avión' => BASE_URL . '/aircraft-types', 'Editar' => null],
                'type'         => array_merge($type, $data),
                'airlines'     => $airlines,
                'errors'       => $errors,
            ]);
            return;
        }

        $this->model->update($typeId, $data);
        $this->redirectWith('aircraft-types', 'success', 'Tipo de avión actualizado correctamente.');
    }

    public function delete(string $id): void {
        $typeId = (int)$id;

        if ($this->model->hasFlightServices($typeId)) {
            $this->redirectWith('aircraft-types', 'error', 'No se puede eliminar: tiene servicios de vuelo asociados.');
            return;
        }

        if ($this->model->delete($typeId)) {
            $this->redirectWith('aircraft-types', 'success', 'Tipo de avión eliminado correctamente.');
        } else {
            $this->redirectWith('aircraft-types', 'error', 'No se pudo eliminar el tipo de avión.');
        }
    }

    /**
     * AJAX: obtener tipos de avión por aerolínea
     */
    public function byAirline(string $airline_id): void {
        $types = $this->model->getByAirline((int)$airline_id);
        $this->json($types);
    }

    private function validate(array $data): array {
        $errors = [];
        if (empty($data['airline_id'])) {
            $errors['airline_id'] = 'Seleccione una aerolínea.';
        }
        if (empty($data['tipo'])) {
            $errors['tipo'] = 'El tipo de avión es obligatorio.';
        }
        if ($data['tiempo_cumplimiento'] <= 0 || $data['tiempo_cumplimiento'] > 255) {
            $errors['tiempo_cumplimiento'] = 'Ingrese un tiempo de cumplimiento válido en minutos (1 - 255).';
        }
        return $errors;
    }
}
