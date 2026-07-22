<?php
/**
 * FlightServicesController - Módulo principal de servicios de vuelo
 */

class FlightServicesController extends Controller {

    private FlightService $model;
    private Airline       $airlineModel;
    private AircraftType  $aircraftModel;
    private Base          $baseModel;
    private BaseDestino   $baseDestinoModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->model         = new FlightService();
        $this->airlineModel  = new Airline();
        $this->aircraftModel = new AircraftType();
        $this->baseModel     = new Base();
        $this->baseDestinoModel = new BaseDestino();
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

    /** Exportar listado a Excel, respetando los filtros de fecha y base */
    public function export(): void {
        $rol  = Session::get('user_rol');
        $base = Session::get('user_base_asociada');

        $services = ($rol === 'Colaborador' && $base)
            ? $this->model->getAllWithJoinsByBase($base)
            : $this->model->getAllWithJoins();

        $filtroFecha     = trim($_GET['fecha'] ?? '');
        $filtroBase      = trim($_GET['base'] ?? '');
        $filtroAerolinea = trim($_GET['aerolinea'] ?? '');

        if ($filtroFecha !== '' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $filtroFecha, $m)) {
            [$fAnio, $fMes, $fDia] = [(int)$m[1], (int)$m[2], (int)$m[3]];
            $services = array_values(array_filter($services, function ($s) use ($fAnio, $fMes, $fDia) {
                return (int)$s['anio'] === $fAnio && (int)$s['mes'] === $fMes && (int)$s['dia'] === $fDia;
            }));
        }

        if ($filtroBase !== '') {
            $services = array_values(array_filter($services, fn($s) => $s['base'] === $filtroBase));
        }

        if ($filtroAerolinea !== '') {
            $services = array_values(array_filter($services, fn($s) => $s['airline_nombre'] === $filtroAerolinea));
        }

        $adicionalesPorServicio = $this->model->getAdicionalesForIds(array_column($services, 'id'));

        $this->downloadExcel($services, $adicionalesPorServicio, $filtroFecha, $filtroBase, $filtroAerolinea);
    }

    /** Panel analítico (tipo BI) con KPIs, gráficos y tabla dinámica, filtrable en el cliente */
    public function dashboard(): void {
        $rol  = Session::get('user_rol');
        $base = Session::get('user_base_asociada');

        $services = ($rol === 'Colaborador' && $base)
            ? $this->model->getAllWithJoinsByBase($base)
            : $this->model->getAllWithJoins();

        $basesUniques      = [];
        $aerolineasUniques = [];
        $chartData         = [];

        foreach ($services as $s) {
            if (!in_array($s['base'], $basesUniques, true)) {
                $basesUniques[] = $s['base'];
            }
            if (!in_array($s['airline_nombre'], $aerolineasUniques, true)) {
                $aerolineasUniques[] = $s['airline_nombre'];
            }
            $chartData[] = [
                'anio'            => (int)$s['anio'],
                'mes'             => (int)$s['mes'],
                'dia'             => (int)$s['dia'],
                'base'            => $s['base'],
                'aerolinea'       => $s['airline_nombre'],
                'tipo_atencion'   => $s['tipo_atencion'],
                'cumple_tiempo'   => $s['cumple_tiempo'] === null ? null : (bool)$s['cumple_tiempo'],
                'tiempo_transito' => $s['tiempo_transito'] !== null ? (int)$s['tiempo_transito'] : null,
                'pax_saliendo'    => (int)$s['pax_saliendo'],
            ];
        }
        sort($basesUniques);
        sort($aerolineasUniques);

        $this->view('flight_services/dashboard', [
            'pageTitle'         => 'Panel Analítico',
            'breadcrumbs'       => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Panel Analítico' => null],
            'chartDataJson'     => json_encode($chartData, JSON_UNESCAPED_UNICODE),
            'basesUniques'      => $basesUniques,
            'aerolineasUniques' => $aerolineasUniques,
        ]);
    }

    /** Formulario nuevo registro */
    public function createForm(): void {
        $this->view('flight_services/create', [
            'pageTitle'   => 'Nuevo Servicio de Vuelo',
            'breadcrumbs' => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Nuevo' => null],
            'airlines'    => $this->airlineModel->getAll(),
            'bases'       => $this->baseModel->getAll(),
            'baseDestinos'=> $this->baseDestinoModel->getAll(),
            'errors'      => [],
            'old'         => [],
        ]);
    }

    /** Guardar nuevo registro */
    public function store(): void {
        // Log inicial para debugging
        error_log(date('Y-m-d H:i:s') . " | STORE START | POST recibido\n", 3, dirname(__DIR__) . '/../logs/flight_services.log');
        
        $data   = $this->collectFormData();
        error_log(date('Y-m-d H:i:s') . " | DATA COLLECTED | Airline ID: " . ($data['airline_id'] ?? 'NULL') . "\n", 3, dirname(__DIR__) . '/../logs/flight_services.log');
        
        $errors = $this->validateFormData($data);
        error_log(date('Y-m-d H:i:s') . " | VALIDATION RESULT | Errors count: " . count($errors) . "\n", 3, dirname(__DIR__) . '/../logs/flight_services.log');

        if (!empty($errors)) {
            $this->view('flight_services/create', [
                'pageTitle'   => 'Nuevo Servicio de Vuelo',
                'breadcrumbs' => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Nuevo' => null],
                'airlines'    => $this->airlineModel->getAll(),
                'bases'       => $this->baseModel->getAll(),
                'baseDestinos'=> $this->baseDestinoModel->getAll(),
                'errors'      => $errors,
                'old'         => $data,
            ]);
            return;
        }

        $data['user_id'] = (int)Session::get('user_id');
        $data['quincena'] = FlightService::calcularQuincena((int)$data['dia']);

        $gpuFracciones = $_POST['gpu_fracciones'] ?? [];
        $acuFracciones = $_POST['acu_fracciones'] ?? [];
        $ventiladoresFracciones = $_POST['ventiladores_fracciones'] ?? [];
        $adicionales   = $_POST['adicionales'] ?? [];

        try {
            error_log(date('Y-m-d H:i:s') . " | BEFORE INSERT | About to call model->create\n", 3, dirname(__DIR__) . '/../logs/flight_services.log');
            
            $id = $this->model->create($data, $gpuFracciones, $acuFracciones, $ventiladoresFracciones, $adicionales);
            
            error_log(date('Y-m-d H:i:s') . " | CREATE SUCCESS | ID: $id\n", 3, dirname(__DIR__) . '/../logs/flight_services.log');
            
            $this->redirectWith('flight-services/view/' . $id, 'success', 'Servicio de vuelo registrado correctamente.');
        } catch (\Exception $e) {
            // Registrar error en log
            $logMessage = date('Y-m-d H:i:s') . " | STORE ERROR | " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . "\n";
            error_log($logMessage, 3, dirname(__DIR__) . '/../logs/flight_services.log');

            // Mostrar error detallado (cambiar a error más genérico en producción si es necesario)
            $errorMsg = 'Error al guardar el registro: ' . $e->getMessage();
            $this->redirectWith('flight-services/create', 'error', $errorMsg);
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
        // Si airline_id es 'otra' (string), no cargar aircraftTypes; si no, cargar según aerolínea
        $aircraftTypes = ($service['airline_id'] === 'otra' || $service['airline_id'] == 'otra')
            ? []
            : $this->aircraftModel->getByAirline((int)$service['airline_id']);
        $this->view('flight_services/edit', [
            'pageTitle'    => 'Editar Servicio #' . $service['id'],
            'breadcrumbs'  => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Editar' => null],
            'service'      => $service,
            'airlines'     => $this->airlineModel->getAll(),
            'aircraftTypes'=> $aircraftTypes,
            'bases'        => $this->baseModel->getAll(),
            'baseDestinos' => $this->baseDestinoModel->getAll(),
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
            // Si airline_id es 'otra' (string), no cargar aircraftTypes; si no, cargar según aerolínea
            $aircraftTypes = ($data['airline_id'] === 'otra' || $data['airline_id'] == 'otra')
                ? []
                : $this->aircraftModel->getByAirline((int)$data['airline_id']);
            $this->view('flight_services/edit', [
                'pageTitle'    => 'Editar Servicio #' . $serviceId,
                'breadcrumbs'  => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Editar' => null],
                'service'      => array_merge($service, $data),
                'airlines'     => $this->airlineModel->getAll(),
                'aircraftTypes'=> $aircraftTypes,
                'bases'        => $this->baseModel->getAll(),
                'baseDestinos' => $this->baseDestinoModel->getAll(),
                'errors'       => $errors,
            ]);
            return;
        }

        $data['quincena'] = FlightService::calcularQuincena((int)$data['dia']);

        $gpuFracciones = $_POST['gpu_fracciones'] ?? [];
        $acuFracciones = $_POST['acu_fracciones'] ?? [];
        $ventiladoresFracciones = $_POST['ventiladores_fracciones'] ?? [];
        $adicionales   = $_POST['adicionales'] ?? [];

        try {
            $this->model->update($serviceId, $data, $gpuFracciones, $acuFracciones, $ventiladoresFracciones, $adicionales);
            $this->redirectWith('flight-services/view/' . $serviceId, 'success', 'Servicio actualizado correctamente.');
        } catch (\Exception $e) {
            // Registrar error en log
            $logMessage = date('Y-m-d H:i:s') . " | UPDATE ERROR | ID: $serviceId | " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . "\n";
            error_log($logMessage, 3, dirname(__DIR__) . '/../logs/flight_services.log');

            // Mostrar error detallado
            $errorMsg = 'Error al actualizar: ' . $e->getMessage();
            $this->redirectWith('flight-services/edit/' . $serviceId, 'error', $errorMsg);
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
        $airlineInput = $this->input('airline_id');
        $airlineId = ($airlineInput === 'otra') ? 'otra' : (int)$airlineInput;

        return [
            'anio'                   => (int)$this->input('anio'),
            'mes'                    => (int)$this->input('mes'),
            'quincena'               => (int)$this->input('quincena', 1),
            'dia'                    => (int)$this->input('dia'),
            'base'                   => $this->input('base', ''),
            'despacho'               => (int)$this->input('despacho', 0),
            'airline_id'             => $airlineId,
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
            'satena_hora_cierre_modulo' => $this->inputRaw('satena_hora_cierre_modulo', ''),
            'hora_real_llegada'      => $this->inputRaw('hora_real_llegada', ''),
            'tiempo_cumplimiento_custom' => (int)$this->input('tiempo_cumplimiento_custom', 0) ?: null,
            'hora_real_salida'       => $this->inputRaw('hora_real_salida', ''),
            'tiempo_transito'        => $this->inputRaw('tiempo_transito', ''),
            'cumple_tiempo'          => $this->inputRaw('cumple_tiempo', ''),
            // Campos de demora cuando NO cumple tiempo
            'codigo_demora'          => $this->input('codigo_demora', ''),
            'observacion_demora'     => $this->input('observacion_demora', ''),
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
            // Ventiladores
            'ventiladores_activo'             => (int)$this->input('ventiladores_activo', 0),
            'hora_conexion_ventiladores'      => $this->inputRaw('hora_conexion_ventiladores', ''),
            'hora_desconexion_ventiladores'   => $this->inputRaw('hora_desconexion_ventiladores', ''),
            'tiempo_ventiladores'             => $this->inputRaw('tiempo_ventiladores', ''),
            'fracciones_hora_ventiladores'    => (float)$this->input('fracciones_hora_ventiladores', 0),
            'fracciones_15min_ventiladores'   => (float)$this->input('fracciones_15min_ventiladores', 0),
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
                ['ACU', 'TRA', 'CON', 'PAY', 'ASU', 'SVPFREE', 'PEP','GPU']
            )),
            'afecto_operacion'       => (int)$this->input('afecto_operacion', 0),
            'rpn'                    => $this->input('rpn', ''),
            'observaciones'          => $this->input('observaciones', ''),
            // Aerolínea personalizada (cuando se selecciona "Otra")
            'airline_custom_nombre'  => $this->input('airline_custom_nombre', ''),
            'aircraft_type_custom'   => $this->input('aircraft_type_custom', ''),
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
        if (!$this->baseModel->valueExists($data['base'])) {
            $errors['base'] = 'Seleccione una base válida.';
        }
        // Validar aerolínea (puede ser un ID numérico o 'otra')
        if (empty($data['airline_id']) || ($data['airline_id'] != 'otra' && $data['airline_id'] < 1)) {
            $errors['airline_id'] = 'Seleccione una aerolínea válida.';
        }
        // Si es "otra", validar que se haya ingresado el nombre
        if ($data['airline_id'] === 'otra' || $data['airline_id'] == 'otra') {
            if (empty($data['airline_custom_nombre'])) {
                $errors['airline_custom_nombre'] = 'Ingrese el nombre de la aerolínea.';
            }
            if (empty($data['aircraft_type_custom'])) {
                $errors['aircraft_type_custom'] = 'Ingrese el tipo de avión.';
            }
            if (empty($data['tiempo_cumplimiento_custom'])) {
                $errors['tiempo_cumplimiento_custom'] = 'Ingrese el tiempo de cumplimiento (en minutos).';
            }
        }
        if (!in_array($data['tipo_atencion'], FlightService::$tiposAtencion, true)) {
            $errors['tipo_atencion'] = 'Seleccione un tipo de atención.';
        }
        if (empty($data['vuelo_llegando'])) {
            $errors['vuelo_llegando'] = 'El número de vuelo llegando es obligatorio.';
        }
        if (!$this->baseDestinoModel->valueExists($data['base_destino'])) {
            $errors['base_destino'] = 'Seleccione una base destino válida.';
        }
        if (empty($data['matricula'])) {
            $errors['matricula'] = 'La matrícula es obligatoria.';
        }
        // Solo requerir aircraft_type_id si no es "otra"
        if ($data['airline_id'] !== 'otra' && $data['airline_id'] != 'otra' && empty($data['aircraft_type_id'])) {
            $errors['aircraft_type_id'] = 'Seleccione el tipo de avión.';
        }
        if (empty($data['vuelo_saliendo'])) {
            $errors['vuelo_saliendo'] = 'El número de vuelo saliendo es obligatorio.';
        }

        return $errors;
    }

    /** Definición de columnas del reporte, agrupadas y con su color de encabezado */
    private function exportColumnGroups(): array {
        return [
            [
                'title' => null,
                'color' => '#1B4F8A',
                'columns' => [
                    'id'           => '#',
                    'anio'         => 'Año',
                    'mes'          => 'Mes',
                    'dia'          => 'Día',
                    'quincena'     => 'Quincena',
                    'base'         => 'Base',
                    'base_destino' => 'Base Destino',
                ],
            ],
            [
                'title' => 'VUELO Y AERONAVE',
                'color' => '#1B4F8A',
                'columns' => [
                    'airline_nombre' => 'Aerolínea',
                    'tipo_atencion'  => 'Tipo de Atención',
                    'vuelo_llegando' => 'No. Vuelo Llegando',
                    'vuelo_saliendo' => 'No. Vuelo Saliendo',
                    'matricula'      => 'Matrícula',
                    'aircraft_tipo'  => 'Tipo Avión',
                ],
            ],
            [
                'title' => 'PASAJEROS Y CARGA',
                'color' => '#E8651A',
                'columns' => [
                    'pax_saliendo'             => 'PAX Saliendo',
                    'pax_cancelado'            => 'PAX Cancelado',
                    'ajes_transportados'       => 'AJES Transportados',
                    'equipajes_transportados'  => 'Equipajes Transportados',
                ],
            ],
            [
                'title' => 'HORARIOS',
                'color' => '#1B4F8A',
                'columns' => [
                    'hora_itinerada_llegada'    => 'Hora Itinerada Llegada',
                    'hora_real_llegada'         => 'Hora Real Llegada',
                    'demora_llegando'           => 'Demora Llegando (min)',
                    'hora_itinerada_salida'     => 'Hora Itinerada Salida',
                    'hora_real_salida'          => 'Hora Real Salida',
                    'satena_hora_cierre_modulo' => 'Hora Cierre Módulo',
                    'tiempo_transito'           => 'Tiempo Tránsito (min)',
                    'cumple_tiempo'             => 'Cumple Tiempo',
                    'codigo_demora'             => 'Código Demora',
                    'observacion_demora'        => 'Observación Demora',
                ],
            ],
            [
                'title' => 'PLANTA ELÉCTRICA GPU',
                'color' => '#E8651A',
                'columns' => [
                    'hora_conexion_gpu'          => 'Hora Conexión GPU',
                    'hora_desconexion_gpu'       => 'Hora Desconexión GPU',
                    'tiempo_gpu'                 => 'Tiempo GPU (min)',
                    'fracciones_adc_gpu'         => 'Fracciones ADC GPU',
                    'fracciones_adicionales_gpu' => 'Fracciones Adicionales GPU',
                ],
            ],
            [
                'title' => 'AIRE ACONDICIONADO ACU',
                'color' => '#1B4F8A',
                'columns' => [
                    'acu'                    => 'ACU',
                    'hora_conexion_acu'      => 'Hora Conexión ACU',
                    'hora_desconexion_acu'   => 'Hora Desconexión ACU',
                    'tiempo_acu'             => 'Tiempo ACU (min)',
                    'fracciones_hora_acu'    => 'Fracciones Hora ACU',
                    'fracciones_15min_acu'   => 'Fracciones 15min ACU',
                ],
            ],
            [
                'title' => 'VENTILADORES',
                'color' => '#27AE60',
                'columns' => [
                    'ventiladores_activo'           => 'Ventiladores Activo',
                    'hora_conexion_ventiladores'    => 'Hora Conexión Vent.',
                    'hora_desconexion_ventiladores' => 'Hora Desconexión Vent.',
                    'tiempo_ventiladores'           => 'Tiempo Vent. (min)',
                ],
            ],
            [
                'title' => 'EQUIPOS Y SERVICIOS',
                'color' => '#8E44AD',
                'columns' => [
                    'sillas_ruedas'      => 'Sillas de Ruedas',
                    'ventiladores'       => 'Ventiladores (cant.)',
                    'rampa_escalera'     => 'Rampa Escalera',
                    'remolque_aeronave'  => 'Remolque Aeronave',
                    'remolque_equipajes' => 'Remolque Equipajes',
                    'potable'            => 'Potable',
                    'drenaje'            => 'Drenaje',
                ],
            ],
            [
                'title' => 'OBSERVACIONES',
                'color' => '#7F8C8D',
                'columns' => [
                    'equipo_gse_inoperativo' => 'Equipo GSE Inoperativo',
                    'afecto_operacion'       => 'Afectó Operación',
                    'rpn'                    => 'RPN',
                    'adicionales_resumen'    => 'Servicios Adicionales',
                    'observaciones'          => 'Observaciones',
                    'registrado_por'         => 'Registrado por',
                ],
            ],
        ];
    }

    /** Formatear el valor de una celda según la columna */
    private function exportFormatCell(string $key, array $service): string {
        $boolFields         = ['rampa_escalera'];
        $boolNullableFields  = ['acu', 'ventiladores_activo', 'afecto_operacion'];
        $timeFields          = [
            'hora_itinerada_llegada', 'hora_real_llegada', 'hora_itinerada_salida', 'hora_real_salida',
            'satena_hora_cierre_modulo', 'hora_conexion_gpu', 'hora_desconexion_gpu',
            'hora_conexion_acu', 'hora_desconexion_acu', 'hora_conexion_ventiladores', 'hora_desconexion_ventiladores',
        ];

        switch ($key) {
            case 'mes':
                return FlightService::$meses[(int)$service['mes']] ?? (string)$service['mes'];
            case 'quincena':
                return $service['quincena'] == 1 ? '1ª Quincena' : '2ª Quincena';
            case 'cumple_tiempo':
                if ($service['cumple_tiempo'] === null) return '—';
                return $service['cumple_tiempo'] ? 'SI' : 'NO';
            case 'adicionales_resumen':
                if (empty($service['adicionales'])) return '—';
                return implode(', ', array_map(
                    fn($a) => $a['servicio'] . ' x' . $a['cantidad'],
                    $service['adicionales']
                ));
            case 'equipo_gse_inoperativo':
                $gse = $service['equipo_gse_inoperativo'] ?? null;
                return ($gse === null || $gse === '') ? '—' : $gse;
        }

        if (in_array($key, $boolFields, true)) {
            return $service[$key] ? 'SI' : 'NO';
        }
        if (in_array($key, $boolNullableFields, true)) {
            return $service[$key] === null ? '—' : ($service[$key] ? 'SI' : 'NO');
        }
        if (in_array($key, $timeFields, true)) {
            $val = $service[$key];
            return ($val === null || $val === '') ? '—' : substr($val, 0, 5);
        }

        $val = $service[$key] ?? null;
        return ($val === null || $val === '') ? '—' : (string)$val;
    }

    /** Convertir un índice de columna (1-based) a su letra de Excel (1=A, 27=AA, ...) */
    private function excelColLetter(int $n): string {
        $letter = '';
        while ($n > 0) {
            $rem = ($n - 1) % 26;
            $letter = chr(65 + $rem) . $letter;
            $n = intdiv($n - 1, 26);
        }
        return $letter;
    }

    /** Escapar texto para contenido XML (XLSX) */
    private function xmlText(?string $text): string {
        return htmlspecialchars((string)$text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Índices de estilo (cellXfs) definidos en el styles.xml generado por buildXlsxStylesXml().
     * Deben mantenerse sincronizados con el orden de <cellXfs> allí.
     */
    private const XLSX_STYLE_TITLE          = 1;
    private const XLSX_STYLE_SUBTITLE       = 2;
    private const XLSX_STYLE_GROUP_BLUE     = 3;
    private const XLSX_STYLE_GROUP_ORANGE   = 4;
    private const XLSX_STYLE_GROUP_GREEN    = 5;
    private const XLSX_STYLE_GROUP_PURPLE   = 6;
    private const XLSX_STYLE_GROUP_GRAY     = 7;
    private const XLSX_STYLE_GROUP_NEUTRAL  = 8;
    private const XLSX_STYLE_COL_HEADER     = 9;
    private const XLSX_STYLE_DATA_EVEN      = 10;
    private const XLSX_STYLE_DATA_ODD       = 11;
    private const XLSX_STYLE_DATA_HIGHLIGHT = 12;

    private function xlsxGroupStyle(string $color): int {
        return match ($color) {
            '#1B4F8A' => self::XLSX_STYLE_GROUP_BLUE,
            '#E8651A' => self::XLSX_STYLE_GROUP_ORANGE,
            '#27AE60' => self::XLSX_STYLE_GROUP_GREEN,
            '#8E44AD' => self::XLSX_STYLE_GROUP_PURPLE,
            '#7F8C8D' => self::XLSX_STYLE_GROUP_GRAY,
            default   => self::XLSX_STYLE_GROUP_NEUTRAL,
        };
    }

    /** styles.xml: fuentes, rellenos, bordes y los cellXfs referenciados arriba */
    private function buildXlsxStylesXml(): string {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="6">
<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
<font><b/><sz val="21"/><color rgb="FF1B4F8A"/><name val="Calibri"/></font>
<font><b/><sz val="14"/><color rgb="FF555555"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FF1B4F8A"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
</fonts>
<fills count="11">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF1B4F8A"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFE8651A"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF27AE60"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF8E44AD"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF7F8C8D"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFEAF1F8"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFF4F8FC"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFC8F0D8"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="3">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FFB9CBDF"/></left><right style="thin"><color rgb="FFB9CBDF"/></right><top style="thin"><color rgb="FFB9CBDF"/></top><bottom style="thin"><color rgb="FFB9CBDF"/></bottom></border>
<border><left style="thin"><color rgb="FFDCE4EC"/></left><right style="thin"><color rgb="FFDCE4EC"/></right><top style="thin"><color rgb="FFDCE4EC"/></top><bottom style="thin"><color rgb="FFDCE4EC"/></bottom></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="13">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="1" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="1" fillId="4" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="1" fillId="5" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="1" fillId="6" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="1" fillId="7" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="8" borderId="0" xfId="0" applyFill="1"/>
<xf numFmtId="0" fontId="4" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="9" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>
<xf numFmtId="0" fontId="5" fillId="10" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML;
    }

    /** Generar y enviar el reporte de servicios de vuelo como un archivo .xlsx real */
    private function downloadExcel(array $services, array $adicionalesPorServicio, string $filtroFecha, string $filtroBase, string $filtroAerolinea = ''): void {
        foreach ($services as &$s) {
            $s['adicionales'] = $adicionalesPorServicio[$s['id']] ?? [];
        }
        unset($s);

        $columnGroups = $this->exportColumnGroups();
        $totalCols    = array_sum(array_map(fn($g) => count($g['columns']), $columnGroups));

        $subtitulo = 'Todas las bases';
        if ($filtroBase !== '') $subtitulo = 'Base ' . $filtroBase;
        if ($filtroAerolinea !== '') $subtitulo .= ' — ' . $filtroAerolinea;
        if ($filtroFecha !== '') $subtitulo .= ' — ' . date('d/m/Y', strtotime($filtroFecha));

        $lastCol   = $this->excelColLetter($totalCols);
        $dataStart = 5; // fila donde inician los datos (tras logo/título/subtítulo/grupos/encabezados)

        // ── Columnas: anchos dinámicos según el largo de cada etiqueta ──
        $colsXml = '';
        $flatLabels = [];
        foreach ($columnGroups as $group) {
            foreach ($group['columns'] as $label) $flatLabels[] = $label;
        }
        foreach ($flatLabels as $i => $label) {
            $width = max(10, min(30, (int)round(mb_strlen($label) * 1.15) + 3));
            $colsXml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $width . '" customWidth="1"/>';
        }

        // ── Filas 1-2: logo (celda combinada) + título/subtítulo ──
        $mergeCells = ['A1:C2', 'D1:' . $lastCol . '1', 'D2:' . $lastCol . '2'];
        $sheetXml  = '<row r="1" ht="40" customHeight="1">';
        $sheetXml .= '<c r="A1" s="0"/>';
        $sheetXml .= '<c r="D1" t="inlineStr" s="' . self::XLSX_STYLE_TITLE . '"><is><t xml:space="preserve">' . $this->xmlText('INFORME OPERACIONAL Y SERVICIOS PRESTADOS') . '</t></is></c>';
        $sheetXml .= '</row>';
        $sheetXml .= '<row r="2" ht="24" customHeight="1">';
        $sheetXml .= '<c r="D2" t="inlineStr" s="' . self::XLSX_STYLE_SUBTITLE . '"><is><t xml:space="preserve">' . $this->xmlText($subtitulo) . '</t></is></c>';
        $sheetXml .= '</row>';

        // ── Fila 3: encabezados de grupo ──
        $sheetXml .= '<row r="3" ht="20" customHeight="1">';
        $col = 1;
        foreach ($columnGroups as $group) {
            $span = count($group['columns']);
            $ref  = $this->excelColLetter($col) . '3';
            $style = $this->xlsxGroupStyle($group['color'] ?? '');
            if ($group['title'] !== null) {
                $sheetXml .= '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t xml:space="preserve">' . $this->xmlText($group['title']) . '</t></is></c>';
            } else {
                $sheetXml .= '<c r="' . $ref . '" s="' . $style . '"/>';
            }
            if ($span > 1) {
                $mergeCells[] = $ref . ':' . $this->excelColLetter($col + $span - 1) . '3';
            }
            $col += $span;
        }
        $sheetXml .= '</row>';

        // ── Fila 4: encabezados de columna ──
        $sheetXml .= '<row r="4" ht="42" customHeight="1">';
        $col = 1;
        foreach ($flatLabels as $label) {
            $ref = $this->excelColLetter($col) . '4';
            $sheetXml .= '<c r="' . $ref . '" t="inlineStr" s="' . self::XLSX_STYLE_COL_HEADER . '"><is><t xml:space="preserve">' . $this->xmlText($label) . '</t></is></c>';
            $col++;
        }
        $sheetXml .= '</row>';

        // ── Filas de datos ──
        foreach ($services as $i => $s) {
            $rowNum = $dataStart + $i;
            $baseStyle = $i % 2 === 0 ? self::XLSX_STYLE_DATA_EVEN : self::XLSX_STYLE_DATA_ODD;
            $sheetXml .= '<row r="' . $rowNum . '">';
            $col = 1;
            foreach ($columnGroups as $group) {
                foreach ($group['columns'] as $key => $label) {
                    $isHighlight = $key === 'sillas_ruedas' && (int)($s['sillas_ruedas'] ?? 0) > 0;
                    $style = $isHighlight ? self::XLSX_STYLE_DATA_HIGHLIGHT : $baseStyle;
                    $value = $this->exportFormatCell($key, $s);
                    $ref   = $this->excelColLetter($col) . $rowNum;
                    $sheetXml .= '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t xml:space="preserve">' . $this->xmlText($value) . '</t></is></c>';
                    $col++;
                }
            }
            $sheetXml .= '</row>';
        }

        $mergeCellsXml = '<mergeCells count="' . count($mergeCells) . '">';
        foreach ($mergeCells as $range) $mergeCellsXml .= '<mergeCell ref="' . $range . '"/>';
        $mergeCellsXml .= '</mergeCells>';

        $lastRow = $dataStart + count($services) - 1;
        if ($lastRow < 4) $lastRow = 4;

        $worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="A1:' . $lastCol . $lastRow . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultColWidth="14" defaultRowHeight="15"/>'
            . '<cols>' . $colsXml . '</cols>'
            . '<sheetData>' . $sheetXml . '</sheetData>'
            . $mergeCellsXml
            . '<drawing r:id="rId1"/>'
            . '</worksheet>';

        // ── Logo embebido como dibujo flotante sobre A1:C2 ──
        $logoPath = ROOT_PATH . '/img/logo_completo.png';
        [$logoW, $logoH] = @getimagesize($logoPath) ?: [356, 116];
        $targetW = 190;
        $targetH = (int)round($logoH * ($targetW / $logoW));
        $emuW = $targetW * 9525;
        $emuH = $targetH * 9525;

        $drawingXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<xdr:oneCellAnchor>'
            . '<xdr:from><xdr:col>0</xdr:col><xdr:colOff>40000</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>30000</xdr:rowOff></xdr:from>'
            . '<xdr:ext cx="' . $emuW . '" cy="' . $emuH . '"/>'
            . '<xdr:pic>'
            . '<xdr:nvPicPr><xdr:cNvPr id="2" name="Logo"/><xdr:cNvPicPr/></xdr:nvPicPr>'
            . '<xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
            . '<xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $emuW . '" cy="' . $emuH . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>'
            . '</xdr:pic>'
            . '<xdr:clientData/>'
            . '</xdr:oneCellAnchor>'
            . '</xdr:wsDr>';

        $drawingRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image1.png"/>'
            . '</Relationships>';

        $sheetRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
            . '</Relationships>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Servicios de Vuelo" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Default Extension="png" ContentType="image/png"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>'
            . '</Types>';

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/styles.xml', $this->buildXlsxStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheetXml);
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $sheetRels);
        $zip->addFromString('xl/drawings/drawing1.xml', $drawingXml);
        $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $drawingRels);
        $zip->addFromString('xl/media/image1.png', file_get_contents($logoPath));
        $zip->close();

        $bytes = file_get_contents($tmpFile);
        unlink($tmpFile);

        $filename = 'servicios_vuelo_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: max-age=0');

        echo $bytes;
        exit;
    }
}
