<?php
/**
 * TarifasGpuController - CRUD de Tarifas / Cobros GPU por aerolínea
 *
 * El listado usa un modal para crear y editar (no hay páginas
 * separadas de create/edit). Si el guardado falla la validación,
 * se vuelve a renderizar el listado con el modal abierto y los
 * errores/valores anteriores, igual que el resto de catálogos.
 */

class TarifasGpuController extends Controller {

    private TarifaGpu $model;
    private Airline    $airlineModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->model        = new TarifaGpu();
        $this->airlineModel = new Airline();
    }

    public function index(): void {
        $this->renderIndex();
    }

    public function store(): void {
        $data   = $this->collectInput();
        $errors = $this->validate($data);

        if (!empty($errors)) {
            $this->renderIndex($errors, $data, 'create');
            return;
        }

        $this->model->create($this->castData($data));
        $this->redirectWith('tarifas-cobros', 'success', 'Tarifa GPU creada correctamente.');
    }

    public function update(string $id): void {
        $tarifaId = (int)$id;
        $tarifa   = $this->model->findById($tarifaId);
        if (!$tarifa) {
            $this->redirectWith('tarifas-cobros', 'error', 'Tarifa no encontrada.');
            return;
        }

        $data   = $this->collectInput();
        $errors = $this->validate($data, $tarifaId);

        if (!empty($errors)) {
            $data['id'] = $tarifaId;
            $this->renderIndex($errors, $data, 'edit');
            return;
        }

        $this->model->update($tarifaId, $this->castData($data));
        $this->redirectWith('tarifas-cobros', 'success', 'Tarifa GPU actualizada correctamente.');
    }

    public function delete(string $id): void {
        if ($this->model->delete((int)$id)) {
            $this->redirectWith('tarifas-cobros', 'success', 'Tarifa GPU eliminada correctamente.');
        } else {
            $this->redirectWith('tarifas-cobros', 'error', 'No se pudo eliminar la tarifa.');
        }
    }

    /**
     * AJAX: obtener la tarifa GPU configurada para una aerolínea.
     * Usado desde /flight-services/create y /flight-services/edit
     * para calcular "Fracciones ADC GPU".
     */
    public function byAirline(string $airline_id): void {
        $tarifa = $this->model->findByAirline((int)$airline_id);
        if (!$tarifa) {
            $this->json(['primeros_minutos' => null, 'fraccion_minutos' => null]);
            return;
        }
        $this->json([
            'primeros_minutos' => $tarifa['primeros_minutos'] !== null ? (int)$tarifa['primeros_minutos'] : null,
            'fraccion_minutos' => (int)$tarifa['fraccion_minutos'],
        ]);
    }

    private function collectInput(): array {
        $primeros = $this->input('primeros_minutos', '');
        return [
            'airline_id'       => (int)$this->input('airline_id'),
            'primeros_minutos' => $primeros === '' ? null : $primeros,
            'fraccion_minutos' => $this->input('fraccion_minutos', ''),
        ];
    }

    private function castData(array $data): array {
        return [
            'airline_id'       => (int)$data['airline_id'],
            'primeros_minutos' => $data['primeros_minutos'] !== null ? (int)$data['primeros_minutos'] : null,
            'fraccion_minutos' => (int)$data['fraccion_minutos'],
        ];
    }

    private function validate(array $data, int $excludeId = 0): array {
        $errors = [];

        if (empty($data['airline_id'])) {
            $errors['airline_id'] = 'Seleccione una aerolínea.';
        } elseif ($this->model->airlineHasTarifa($data['airline_id'], $excludeId)) {
            $errors['airline_id'] = 'Esta aerolínea ya tiene una tarifa GPU configurada.';
        }

        if ($data['primeros_minutos'] !== null) {
            if (!ctype_digit((string)$data['primeros_minutos'])) {
                $errors['primeros_minutos'] = 'Debe ser un número entero de minutos (sin decimales).';
            } elseif ((int)$data['primeros_minutos'] <= 0) {
                $errors['primeros_minutos'] = 'Debe ser mayor a cero.';
            }
        }

        if ($data['fraccion_minutos'] === '' || !ctype_digit((string)$data['fraccion_minutos'])) {
            $errors['fraccion_minutos'] = 'La fracción es obligatoria y debe ser un número entero de minutos (sin decimales).';
        } elseif ((int)$data['fraccion_minutos'] <= 0) {
            $errors['fraccion_minutos'] = 'Debe ser mayor a cero.';
        }

        return $errors;
    }

    private function renderIndex(array $errors = [], array $old = [], ?string $openModal = null): void {
        $tarifas  = $this->model->getAllWithAirline();
        $airlines = $this->airlineModel->getAll();
        $this->view('tarifas_gpu/index', [
            'pageTitle'   => 'Tarifas / Cobros',
            'breadcrumbs' => ['Tarifas / Cobros' => null],
            'tarifas'     => $tarifas,
            'airlines'    => $airlines,
            'errors'      => $errors,
            'old'         => $old,
            'openModal'   => $openModal,
        ]);
    }
}
