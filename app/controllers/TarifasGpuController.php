<?php
/**
 * TarifasGpuController - CRUD de Tarifas / Cobros (GPU y ACU) por
 * aerolínea y base.
 *
 * El listado usa un modal para crear y editar (no hay páginas
 * separadas de create/edit). Si el guardado falla la validación,
 * se vuelve a renderizar el listado con el modal abierto y los
 * errores/valores anteriores, igual que el resto de catálogos.
 *
 * Cada tarifa se configura para una aerolínea, un tipo de cobro
 * (gpu = planta eléctrica, acu = aire acondicionado) y,
 * opcionalmente, una base específica. Si no se selecciona base
 * (base_id = NULL), la tarifa aplica a "todas las bases" para esa
 * aerolínea y tipo de cobro.
 */

class TarifasGpuController extends Controller {

    private TarifaGpu $model;
    private Airline    $airlineModel;
    private Base       $baseModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->model        = new TarifaGpu();
        $this->airlineModel = new Airline();
        $this->baseModel    = new Base();
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
        $this->redirectWith('tarifas-cobros', 'success', 'Tarifa creada correctamente.');
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
        $this->redirectWith('tarifas-cobros', 'success', 'Tarifa actualizada correctamente.');
    }

    public function delete(string $id): void {
        if ($this->model->delete((int)$id)) {
            $this->redirectWith('tarifas-cobros', 'success', 'Tarifa eliminada correctamente.');
        } else {
            $this->redirectWith('tarifas-cobros', 'error', 'No se pudo eliminar la tarifa.');
        }
    }

    /**
     * AJAX: obtener la tarifa configurada para una aerolínea, un tipo
     * de cobro (gpu/acu) y, opcionalmente, una base. Usado desde
     * /flight-services/create y /flight-services/edit para calcular
     * "Fracciones ADC GPU" y las fracciones de ACU.
     *
     * Si se pasa ?base_id=N y existe una tarifa específica para esa
     * base, se retorna esa. Si no, se retorna la tarifa de "todas las
     * bases" configurada para la aerolínea + tipo de cobro, si existe.
     * ?tipo_cobro=gpu|acu (por defecto 'gpu', para no romper llamadas
     * existentes que no lo envíen).
     */
    public function byAirline(string $airline_id): void {
        $baseIdParam = $_GET['base_id'] ?? '';
        $baseId = ($baseIdParam !== '' && ctype_digit((string)$baseIdParam)) ? (int)$baseIdParam : null;

        $tipoCobro = $_GET['tipo_cobro'] ?? TarifaGpu::TIPO_GPU;
        if (!in_array($tipoCobro, TarifaGpu::TIPOS_COBRO, true)) {
            $tipoCobro = TarifaGpu::TIPO_GPU;
        }

        $tarifa = $this->model->findByAirlineBaseTipo((int)$airline_id, $baseId, $tipoCobro);
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
        $primeros  = $this->input('primeros_minutos', '');
        $baseId    = $this->input('base_id', '');
        $tipoCobro = $this->input('tipo_cobro', TarifaGpu::TIPO_GPU);
        return [
            'airline_id'       => (int)$this->input('airline_id'),
            'base_id'          => $baseId === '' ? null : $baseId,
            'tipo_cobro'       => $tipoCobro,
            'primeros_minutos' => $primeros === '' ? null : $primeros,
            'fraccion_minutos' => $this->input('fraccion_minutos', ''),
        ];
    }

    private function castData(array $data): array {
        return [
            'airline_id'       => (int)$data['airline_id'],
            'base_id'          => $data['base_id'] !== null ? (int)$data['base_id'] : null,
            'tipo_cobro'       => $data['tipo_cobro'],
            'primeros_minutos' => $data['primeros_minutos'] !== null ? (int)$data['primeros_minutos'] : null,
            'fraccion_minutos' => (int)$data['fraccion_minutos'],
        ];
    }

    private function validate(array $data, int $excludeId = 0): array {
        $errors = [];

        if (empty($data['airline_id'])) {
            $errors['airline_id'] = 'Seleccione una aerolínea.';
        }

        if (!in_array($data['tipo_cobro'], TarifaGpu::TIPOS_COBRO, true)) {
            $errors['tipo_cobro'] = 'Seleccione un tipo de cobro válido.';
        }

        if ($data['base_id'] !== null) {
            if (!ctype_digit((string)$data['base_id']) || !$this->baseModel->findById((int)$data['base_id'])) {
                $errors['base_id'] = 'Seleccione una base válida.';
            }
        }

        if (empty($errors['airline_id']) && empty($errors['base_id']) && empty($errors['tipo_cobro'])) {
            $baseId = $data['base_id'] !== null ? (int)$data['base_id'] : null;
            if ($this->model->airlineBaseTipoHasTarifa((int)$data['airline_id'], $baseId, $data['tipo_cobro'], $excludeId)) {
                $tipoTexto = $data['tipo_cobro'] === TarifaGpu::TIPO_ACU ? 'Aire Acondicionado (ACU)' : 'Planta Eléctrica (GPU)';
                $errors['base_id'] = $baseId === null
                    ? "Esta aerolínea ya tiene una tarifa de {$tipoTexto} configurada para \"Todas las bases\"."
                    : "Esta aerolínea ya tiene una tarifa de {$tipoTexto} configurada para esa base.";
            }
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
        $bases    = $this->baseModel->getAll();
        $this->view('tarifas_gpu/index', [
            'pageTitle'   => 'Tarifas / Cobros',
            'breadcrumbs' => ['Tarifas / Cobros' => null],
            'tarifas'     => $tarifas,
            'airlines'    => $airlines,
            'bases'       => $bases,
            'errors'      => $errors,
            'old'         => $old,
            'openModal'   => $openModal,
        ]);
    }
}
