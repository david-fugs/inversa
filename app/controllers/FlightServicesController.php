<?php
/**
 * FlightServicesController - Módulo principal de servicios de vuelo
 */

class FlightServicesController extends Controller {

    private FlightService $model;
    private Airline       $airlineModel;
    private AircraftType  $aircraftModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->model         = new FlightService();
        $this->airlineModel  = new Airline();
        $this->aircraftModel = new AircraftType();
    }

    /** Listado principal */
    public function index(): void {
        $rol  = Session::get('user_rol');
        $base = Session::get('user_base_asociada');

        if ($rol === 'Colaborador' && $base) {
            $services = $this->model->getAllWithJoinsByBase($base);
        } else {
            $services = $this->model->getAllWithJoins();
        }

        $this->view('flight_services/index', [
            'pageTitle'   => 'Servicios de Vuelo',
            'breadcrumbs' => ['Servicios de Vuelo' => null],
            'services'    => $services,
        ]);
    }

    /** Formulario nuevo registro */
    public function createForm(): void {
        $this->view('flight_services/create', [
            'pageTitle'   => 'Nuevo Servicio de Vuelo',
            'breadcrumbs' => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Nuevo' => null],
            'airlines'    => $this->airlineModel->getAll(),
            'errors'      => [],
            'old'         => [],
        ]);
    }

    /** Guardar nuevo registro */
    public function store(): void {
        $data   = $this->collectFormData();
        $errors = $this->validateFormData($data);

        if (!empty($errors)) {
            $this->view('flight_services/create', [
                'pageTitle'   => 'Nuevo Servicio de Vuelo',
                'breadcrumbs' => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Nuevo' => null],
                'airlines'    => $this->airlineModel->getAll(),
                'errors'      => $errors,
                'old'         => $data,
            ]);
            return;
        }

        $data['user_id'] = (int)Session::get('user_id');
        $data['quincena'] = FlightService::calcularQuincena((int)$data['dia']);

        $gpuFracciones = $_POST['gpu_fracciones'] ?? [];
        $acuFracciones = $_POST['acu_fracciones'] ?? [];
        $adicionales   = $_POST['adicionales'] ?? [];

        try {
            $id = $this->model->create($data, $gpuFracciones, $acuFracciones, $adicionales);
            $this->redirectWith('flight-services/view/' . $id, 'success', 'Servicio de vuelo registrado correctamente.');
        } catch (\Exception $e) {
            $this->redirectWith('flight-services/create', 'error', 'Error al guardar el registro. Intente de nuevo.');
        }
    }

    /** Ver detalle */
    public function detail(string $id): void {
        $service = $this->model->findFullById((int)$id);
        if (!$service) {
            $this->redirectWith('flight-services', 'error', 'Servicio no encontrado.');
            return;
        }
        $this->view('flight_services/view', [
            'pageTitle'   => 'Detalle Servicio #' . $service['id'],
            'breadcrumbs' => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Detalle' => null],
            'service'     => $service,
        ]);
    }

    /** Formulario editar */
    public function editForm(string $id): void {
        if (Session::get('user_rol') === 'Colaborador' && !Session::get('user_puede_editar')) {
            $this->redirectWith('flight-services', 'error', 'No tiene permiso para editar registros.');
            return;
        }
        $service = $this->model->findFullById((int)$id);
        if (!$service) {
            $this->redirectWith('flight-services', 'error', 'Servicio no encontrado.');
            return;
        }
        $aircraftTypes = $this->aircraftModel->getByAirline((int)$service['airline_id']);
        $this->view('flight_services/edit', [
            'pageTitle'    => 'Editar Servicio #' . $service['id'],
            'breadcrumbs'  => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Editar' => null],
            'service'      => $service,
            'airlines'     => $this->airlineModel->getAll(),
            'aircraftTypes'=> $aircraftTypes,
            'errors'       => [],
        ]);
    }

    /** Actualizar registro */
    public function update(string $id): void {
        if (Session::get('user_rol') === 'Colaborador' && !Session::get('user_puede_editar')) {
            $this->redirectWith('flight-services', 'error', 'No tiene permiso para editar registros.');
            return;
        }
        $serviceId = (int)$id;
        $service   = $this->model->findById($serviceId);
        if (!$service) {
            $this->redirectWith('flight-services', 'error', 'Servicio no encontrado.');
            return;
        }

        $data   = $this->collectFormData();
        $errors = $this->validateFormData($data);

        if (!empty($errors)) {
            $service = $this->model->findFullById($serviceId);
            $aircraftTypes = $this->aircraftModel->getByAirline((int)$data['airline_id']);
            $this->view('flight_services/edit', [
                'pageTitle'    => 'Editar Servicio #' . $serviceId,
                'breadcrumbs'  => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Editar' => null],
                'service'      => array_merge($service, $data),
                'airlines'     => $this->airlineModel->getAll(),
                'aircraftTypes'=> $aircraftTypes,
                'errors'       => $errors,
            ]);
            return;
        }

        $data['quincena'] = FlightService::calcularQuincena((int)$data['dia']);

        $gpuFracciones = $_POST['gpu_fracciones'] ?? [];
        $acuFracciones = $_POST['acu_fracciones'] ?? [];
        $adicionales   = $_POST['adicionales'] ?? [];

        try {
            $this->model->update($serviceId, $data, $gpuFracciones, $acuFracciones, $adicionales);
            $this->redirectWith('flight-services/view/' . $serviceId, 'success', 'Servicio actualizado correctamente.');
        } catch (\Exception $e) {
            $this->redirectWith('flight-services/edit/' . $serviceId, 'error', 'Error al actualizar. Intente de nuevo.');
        }
    }

    /** Eliminar */
    public function delete(string $id): void {
        if (Session::get('user_rol') === 'Colaborador') {
            $this->redirectWith('flight-services', 'error', 'No tiene permiso para eliminar registros.');
            return;
        }
        if ($this->model->delete((int)$id)) {
            $this->redirectWith('flight-services', 'success', 'Servicio eliminado correctamente.');
        } else {
            $this->redirectWith('flight-services', 'error', 'No se pudo eliminar el servicio.');
        }
    }

    /** Recolectar y sanitizar datos del formulario */
    private function collectFormData(): array {
        return [
            'anio'                   => (int)$this->input('anio'),
            'mes'                    => (int)$this->input('mes'),
            'quincena'               => (int)$this->input('quincena', 1),
            'dia'                    => (int)$this->input('dia'),
            'base'                   => $this->input('base', ''),
            'despacho'               => (int)$this->input('despacho', 0),
            'airline_id'             => (int)$this->input('airline_id'),
            'tipo_atencion'          => $this->input('tipo_atencion', ''),
            'vuelo_llegando'         => $this->input('vuelo_llegando', ''),
            'base_destino'           => $this->input('base_destino', ''),
            'matricula'              => $this->input('matricula', ''),
            'aircraft_type_id'       => (int)$this->input('aircraft_type_id'),
            'pax_saliendo'           => (int)$this->input('pax_saliendo', 0),
            'pax_cancelado'          => (int)$this->input('pax_cancelado', 0),
            'ajes_transportados'     => (int)$this->input('ajes_transportados', 0),
            'vuelo_saliendo'         => $this->input('vuelo_saliendo', ''),
            'hora_itinerada_llegada' => $this->inputRaw('hora_itinerada_llegada', ''),
            'demora_llegando'        => (int)$this->input('demora_llegando', 0),
            'hora_itinerada_salida'  => $this->inputRaw('hora_itinerada_salida', ''),
            'hora_real_llegada'      => $this->inputRaw('hora_real_llegada', ''),
            'hora_real_salida'       => $this->inputRaw('hora_real_salida', ''),
            'tiempo_transito'        => $this->inputRaw('tiempo_transito', ''),
            'cumple_tiempo'          => $this->inputRaw('cumple_tiempo', ''),
            // GPU
            'hora_conexion_gpu'          => $this->inputRaw('hora_conexion_gpu', ''),
            'hora_desconexion_gpu'       => $this->inputRaw('hora_desconexion_gpu', ''),
            'tiempo_gpu'                 => $this->inputRaw('tiempo_gpu', ''),
            'fracciones_adc_gpu'         => (float)$this->input('fracciones_adc_gpu', 0),
            'fracciones_adicionales_gpu' => (float)$this->input('fracciones_adicionales_gpu', 0),
            // ACU
            'acu'                    => (int)$this->input('acu', 0),
            'hora_conexion_acu'      => $this->inputRaw('hora_conexion_acu', ''),
            'hora_desconexion_acu'   => $this->inputRaw('hora_desconexion_acu', ''),
            'tiempo_acu'             => $this->inputRaw('tiempo_acu', ''),
            'fracciones_hora_acu'    => (float)$this->input('fracciones_hora_acu', 0),
            'fracciones_15min_acu'   => (float)$this->input('fracciones_15min_acu', 0),
            // Equipos
            'sillas_ruedas'          => (int)$this->input('sillas_ruedas', 0),
            'ventiladores'           => (int)$this->input('ventiladores', 0),
            'rampa_escalera'         => (int)$this->input('rampa_escalera', 0),
            'equipajes_transportados'=> (int)$this->input('equipajes_transportados', 0),
            'remolque_aeronave'      => (int)$this->input('remolque_aeronave', 0),
            'remolque_equipajes'     => (int)$this->input('remolque_equipajes', 0),
            'potable'                => (int)$this->input('potable', 0),
            'drenaje'                => (int)$this->input('drenaje', 0),
            // Observaciones
            'equipo_gse_inoperativo' => implode(',', array_intersect(
                array_map('trim', (array)($_POST['equipo_gse_inoperativo'] ?? [])),
                ['ACU','TRA','CON','PAY','ASU','E318/A320']
            )),
            'afecto_operacion'       => (int)$this->input('afecto_operacion', 0),
        ];
    }

    /** Validar datos del formulario */
    private function validateFormData(array $data): array {
        $errors = [];

        if ($data['anio'] < 2000 || $data['anio'] > 2100) {
            $errors['anio'] = 'Ingrese un año válido.';
        }
        if ($data['mes'] < 1 || $data['mes'] > 12) {
            $errors['mes'] = 'Seleccione un mes.';
        }
        if ($data['dia'] < 1 || $data['dia'] > 31) {
            $errors['dia'] = 'Ingrese un día válido (1-31).';
        }
        if (!in_array($data['base'], FlightService::$bases, true)) {
            $errors['base'] = 'Seleccione una base válida.';
        }
        if (empty($data['airline_id'])) {
            $errors['airline_id'] = 'Seleccione una aerolínea.';
        }
        if (!in_array($data['tipo_atencion'], FlightService::$tiposAtencion, true)) {
            $errors['tipo_atencion'] = 'Seleccione un tipo de atención.';
        }
        if (empty($data['vuelo_llegando'])) {
            $errors['vuelo_llegando'] = 'El número de vuelo llegando es obligatorio.';
        }
        if (empty($data['base_destino'])) {
            $errors['base_destino'] = 'La base destino es obligatoria.';
        }
        if (empty($data['matricula'])) {
            $errors['matricula'] = 'La matrícula es obligatoria.';
        }
        if (empty($data['aircraft_type_id'])) {
            $errors['aircraft_type_id'] = 'Seleccione el tipo de avión.';
        }
        if (empty($data['vuelo_saliendo'])) {
            $errors['vuelo_saliendo'] = 'El número de vuelo saliendo es obligatorio.';
        }

        return $errors;
    }
}
