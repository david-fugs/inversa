<?php
/**
 * FlightServiceImportController
 *
 * Importación masiva de Servicios de Vuelo desde el Excel histórico
 * ("Informe Operacional y Servicios Prestados"). Lee la hoja
 * "Informe Servicios" desde la fila 5, y se detiene en la primera fila
 * cuyas columnas AÑO, MES y QUINCENA (A, B, C) estén todas vacías (el
 * resto del sheet, más abajo, suele traer fórmulas residuales sin
 * datos reales).
 *
 * Los campos de horas/fechas y las fracciones de cobro (GPU/ACU) NO se
 * copian del Excel: se recalculan con la misma lógica de negocio vigente
 * en el formulario (tarifas configuradas, posición del registro en el
 * mes para ACU, etc.), para que el histórico importado quede consistente
 * con lo que hoy se ve/edita en la aplicación.
 */
class FlightServiceImportController extends Controller {

    private const SHEET_NAME     = 'Informe Servicios';
    private const DATA_START_ROW = 5;

    /** Meses (abreviatura o nombre completo, sin tildes, mayúsculas) => número */
    private const MESES_MAP = [
        'ENE' => 1, 'ENERO' => 1,
        'FEB' => 2, 'FEBRERO' => 2,
        'MAR' => 3, 'MARZO' => 3,
        'ABR' => 4, 'ABRIL' => 4,
        'MAY' => 5, 'MAYO' => 5,
        'JUN' => 6, 'JUNIO' => 6,
        'JUL' => 7, 'JULIO' => 7,
        'AGO' => 8, 'AGOSTO' => 8,
        'SEP' => 9, 'SET' => 9, 'SEPTIEMBRE' => 9,
        'OCT' => 10, 'OCTUBRE' => 10,
        'NOV' => 11, 'NOVIEMBRE' => 11,
        'DIC' => 12, 'DICIEMBRE' => 12,
    ];

    /** Tipo de atención del Excel (sin tildes, mayúsculas) => valor válido de FlightService::$tiposAtencion */
    private const TIPO_ATENCION_MAP = [
        'TRANSITO'          => 'Tránsito',
        'CANCELADO'         => 'Cancelado',
        'CANCELACION'       => 'Cancelado',
        'REGRESO PLATAFORMA'   => 'Regreso a plataforma',
        'REGRESO A PLATAFORMA' => 'Regreso a plataforma',
        'ESCALA TECNICA'    => 'Escala técnica',
    ];

    /** Nombre del adicional en el Excel (sin tildes, mayúsculas) => valor válido en el select actual */
    private const ADICIONAL_MAP = [
        'TRASLADO DE CARGA' => 'Traslado de carga',
        'ARRANCADOR (ASU) ADICIONAL AL INCLUIDO POR TRANSITO' => 'Arrancador ASU',
        'ARRANCADOR ASU'    => 'Arrancador ASU',
        'HORA HOMBRE'       => 'Hora hombre',
        'PERNOCTA'          => 'Pernocta',
        'CINTA TRANSPORTADORA/CONVEYOR' => 'Cinta transportadora / Conveyor',
        'CINTA TRANSPORTADORA / CONVEYOR' => 'Cinta transportadora / Conveyor',
        'CONVEYOR'          => 'Cinta transportadora / Conveyor',
        'ESCALERA'          => 'Escalera',
        'DRENADO'           => 'Drenado',
        'REMOLQUE'          => 'Remolque',
    ];

    private FlightService       $model;
    private FlightServiceImport $importModel;
    private Airline              $airlineModel;
    private AircraftType         $aircraftModel;
    private Base                 $baseModel;
    private BaseDestino          $baseDestinoModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        if (Session::get('user_rol') !== 'Administrador') {
            $this->redirectWith('flight-services', 'error', 'No tiene permiso para importar datos.');
        }
        $this->model            = new FlightService();
        $this->importModel      = new FlightServiceImport();
        $this->airlineModel     = new Airline();
        $this->aircraftModel    = new AircraftType();
        $this->baseModel        = new Base();
        $this->baseDestinoModel = new BaseDestino();
    }

    /** Formulario de carga + histórico de importaciones */
    public function form(): void {
        $this->view('flight_services/import', [
            'pageTitle'   => 'Importar desde Excel',
            'breadcrumbs' => ['Servicios de Vuelo' => BASE_URL . '/flight-services', 'Importar Excel' => null],
            'imports'     => $this->importModel->getAllWithUser(),
        ]);
    }

    /** Recibir el .xlsx, procesarlo y guardar el resultado */
    public function upload(): void {
        $file = $_FILES['archivo_excel'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $this->redirectWith('flight-services/import', 'error', 'Seleccione un archivo Excel (.xlsx).');
            return;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->redirectWith('flight-services/import', 'error', 'Error al subir el archivo.');
            return;
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'xlsx') {
            $this->redirectWith('flight-services/import', 'error', 'Solo se permiten archivos .xlsx.');
            return;
        }

        $nombreOriginal = trim(preg_replace('/[\r\n]+/', ' ', basename($file['name'])));

        $importId = $this->importModel->create([
            'nombre_archivo' => $nombreOriginal,
            'total_filas'    => 0,
            'filas_exitosas' => 0,
            'filas_error'    => 0,
            'user_id'        => (int)Session::get('user_id'),
        ]);

        try {
            [$totalFilas, $exitosas, $erroresPorFila] = $this->runImport($file['tmp_name'], $importId);
        } catch (\Throwable $e) {
            $this->importModel->delete($importId);
            $this->redirectWith('flight-services/import', 'error', 'No se pudo procesar el archivo: ' . $e->getMessage());
            return;
        }

        $this->importModel->updateStats($importId, $totalFilas, $exitosas, count($erroresPorFila));

        foreach ($erroresPorFila as $err) {
            $this->importModel->addError($importId, $err['fila'], $err['mensaje'], $err['datos']);
        }

        $mensaje = "Importación completada: $exitosas de $totalFilas filas guardadas correctamente.";
        if (count($erroresPorFila) > 0) {
            $mensaje .= ' ' . count($erroresPorFila) . ' fila(s) con error, ver detalle.';
        }
        $this->redirectWith('flight-services/import/' . $importId . '/errors', count($erroresPorFila) > 0 ? 'error' : 'success', $mensaje);
    }

    /** Detalle de errores de una importación puntual */
    public function errors(string $id): void {
        $import = $this->importModel->findByIdWithUser((int)$id);
        if (!$import) {
            $this->redirectWith('flight-services/import', 'error', 'Importación no encontrada.');
            return;
        }
        $this->view('flight_services/import_errors', [
            'pageTitle'   => 'Errores de importación #' . $import['id'],
            'breadcrumbs' => [
                'Servicios de Vuelo' => BASE_URL . '/flight-services',
                'Importar Excel'     => BASE_URL . '/flight-services/import',
                'Errores'            => null,
            ],
            'import' => $import,
            'errors' => $this->importModel->getErrors((int)$id),
        ]);
    }

    /** Elimina una importación y todos los servicios de vuelo que creó */
    public function deleteImport(string $id): void {
        $import = $this->importModel->findByIdWithUser((int)$id);
        if (!$import) {
            $this->redirectWith('flight-services/import', 'error', 'Importación no encontrada.');
            return;
        }
        $this->importModel->delete((int)$id);
        $this->redirectWith('flight-services/import', 'success', 'Importación "' . $import['nombre_archivo'] . '" y sus ' . (int)$import['filas_exitosas'] . ' registro(s) fueron eliminados.');
    }

    // ─────────────────────────────────────────────────────────────
    //  Lógica de importación
    // ─────────────────────────────────────────────────────────────

    /** @return array{0:int,1:int,2:array} [total filas leídas, filas exitosas, errores] */
    private function runImport(string $filePath, int $importId): array {
        $reader = new XlsxReader($filePath);

        $total    = 0;
        $exitosas = 0;
        $errores  = [];

        foreach ($reader->rows(self::SHEET_NAME) as $rowNum => $row) {
            if ($rowNum < self::DATA_START_ROW) continue;

            $a = $this->cellText($row['A'] ?? null);
            $b = $this->cellText($row['B'] ?? null);
            $c = $this->cellText($row['C'] ?? null);
            if ($a === '' && $b === '' && $c === '') {
                break; // fin de los datos reales
            }

            $total++;
            try {
                $data = $this->buildServiceData($row, $importId);
                $this->model->create($data['data'], [], [], [], $data['adicionales']);
                $exitosas++;
            } catch (\Throwable $e) {
                $errores[] = [
                    'fila'    => $rowNum,
                    'mensaje' => $e->getMessage(),
                    'datos'   => $this->rowToPlainArray($row),
                ];
            }
        }

        return [$total, $exitosas, $errores];
    }

    /** Construye el $data que espera FlightService::create(), o lanza \RuntimeException con el motivo */
    private function buildServiceData(array $row, int $importId): array {
        $anio = (int)$this->cellNumber($row['A'] ?? null);
        $mes  = $this->mapMes($this->cellText($row['B'] ?? null));
        $dia  = (int)$this->cellNumber($row['D'] ?? null);

        if ($anio < 2000 || $anio > 2100) throw new \RuntimeException("Año inválido: \"{$row['A']}\".");
        if ($mes === null) throw new \RuntimeException('Mes no reconocido: "' . $this->cellText($row['B'] ?? null) . '".');
        if ($dia < 1 || $dia > 31) throw new \RuntimeException("Día inválido: \"{$row['D']}\".");

        $baseNombre = $this->cellText($row['E'] ?? null);
        $base = $this->baseModel->findByNombre($baseNombre);
        if (!$base) throw new \RuntimeException("La base \"$baseNombre\" no existe en el sistema.");

        $aerolineaNombre = $this->cellText($row['F'] ?? null);
        $airline = $this->airlineModel->findByNombre($aerolineaNombre);
        if (!$airline) throw new \RuntimeException("La aerolínea \"$aerolineaNombre\" no existe en el sistema.");

        $tipoAtencion = $this->normalizeFromMap($this->cellText($row['G'] ?? null), self::TIPO_ATENCION_MAP);
        if ($tipoAtencion === null) throw new \RuntimeException('Tipo de atención no reconocido: "' . $this->cellText($row['G'] ?? null) . '".');

        $baseDestinoNombre = $this->cellText($row['I'] ?? null);
        $baseDestino = $this->baseDestinoModel->findByNombre($baseDestinoNombre);
        if (!$baseDestino) throw new \RuntimeException("La base destino \"$baseDestinoNombre\" no existe en el sistema.");

        $tipoAvionNombre = $this->cellText($row['K'] ?? null);
        $aircraftType = $this->aircraftModel->findByAirlineAndTipo((int)$airline['id'], $tipoAvionNombre);
        if (!$aircraftType) throw new \RuntimeException("El tipo de avión \"$tipoAvionNombre\" no existe para la aerolínea \"{$airline['nombre']}\".");

        $horaItLlegada  = $this->cellTime($row['O'] ?? null);
        $horaRealLlegada = $this->cellTime($row['P'] ?? null);
        $horaItSalida   = $this->cellTime($row['R'] ?? null);
        $horaRealSalida = $this->cellTime($row['S'] ?? null);

        $demoraLlegando = $this->calcularDemora($horaItLlegada, $horaRealLlegada);
        $tiempoTransito = $this->calcularDiffMinutos($horaRealLlegada, $horaRealSalida);

        $horaConexionGpu    = $this->cellTime($row['U'] ?? null);
        $horaDesconexionGpu = $this->cellTime($row['V'] ?? null);
        $tiempoGpu = $this->calcularDiffMinutos($horaConexionGpu, $horaDesconexionGpu);

        // Fracciones GPU: se importan tal cual vienen en el Excel, no se
        // recalculan con la tarifa configurada del sistema. El campo que
        // se ve en el detalle como "Fracciones ADC" (fracciones_adc_gpu)
        // corresponde a la columna Y ("Fracciones adicionales GPU") del
        // Excel, no a la X, según confirmó el cliente.
        $fraccionesAdcGpu        = $this->cellNumber($row['Y'] ?? null);
        $fraccionesAdicionalesGpu = $this->cellNumber($row['X'] ?? null);

        // Z = Despacho, AA = ACU: se importan tal cual (dato operativo registrado), no se recalculan.
        $despacho = $this->cellFlag($row['Z'] ?? null) ? 1 : 0;
        $acu = $this->cellFlag($row['AA'] ?? null) ? 1 : 0;

        $horaConexionAcu    = $this->cellTime($row['AB'] ?? null);
        $horaDesconexionAcu = $this->cellTime($row['AC'] ?? null);
        $tiempoAcu = $this->calcularDiffMinutos($horaConexionAcu, $horaDesconexionAcu);

        // Fracciones ACU (AE, AG): igual que GPU, se importan tal cual
        // vienen en el Excel en vez de recalcularlas con la tarifa/posición.
        $fraccionesHoraAcu  = $this->cellNumber($row['AE'] ?? null);
        $fracciones15minAcu = $this->cellNumber($row['AG'] ?? null);

        $tiempoCumplimiento = (int)$aircraftType['tiempo_cumplimiento'];
        $cumpleTiempo = $this->calcularCumpleTiempo($horaItSalida, $horaRealSalida, $tiempoTransito, $tiempoCumplimiento);

        $adicionales = [];
        foreach ([['AK', 'AL'], ['AM', 'AN'], ['AO', 'AP']] as [$colServicio, $colCantidad]) {
            $servicioTexto = $this->cellText($row[$colServicio] ?? null);
            if ($servicioTexto === '') continue;
            $servicioNormalizado = $this->normalizeFromMap($servicioTexto, self::ADICIONAL_MAP) ?? $servicioTexto;
            $cantidad = (int)$this->cellNumber($row[$colCantidad] ?? null);
            $adicionales[] = [
                'servicio' => $servicioNormalizado,
                'cantidad' => $cantidad > 0 ? $cantidad : 1,
            ];
        }

        $data = [
            'import_id' => $importId,
            'anio' => $anio,
            'mes' => $mes,
            'quincena' => FlightService::calcularQuincena($dia),
            'dia' => $dia,
            'base' => $base['nombre'],
            'despacho' => $despacho,
            'airline_id' => (int)$airline['id'],
            'airline_custom_nombre' => '',
            'tipo_atencion' => $tipoAtencion,
            'vuelo_llegando' => $this->cellFlightNumber($row['H'] ?? null),
            'base_destino' => $baseDestino['nombre'],
            'matricula' => strtoupper($this->cellText($row['J'] ?? null)),
            'aircraft_type_id' => (int)$aircraftType['id'],
            'aircraft_type_custom' => '',
            'tiempo_cumplimiento_custom' => null,
            'pax_saliendo' => (int)$this->cellNumber($row['L'] ?? null),
            'pax_cancelado' => (int)$this->cellNumber($row['M'] ?? null),
            'ajes_transportados' => 0,
            'vuelo_saliendo' => $this->cellFlightNumber($row['N'] ?? null),
            'hora_itinerada_llegada' => $horaItLlegada,
            'demora_llegando' => $demoraLlegando,
            'hora_itinerada_salida' => $horaItSalida,
            'satena_hora_cierre_modulo' => '',
            'hora_real_llegada' => $horaRealLlegada,
            'hora_real_salida' => $horaRealSalida,
            'tiempo_transito' => $tiempoTransito ?? '',
            'cumple_tiempo' => $cumpleTiempo === null ? '' : $cumpleTiempo,
            'codigo_demora' => null,
            'codigo_demora_id' => null,
            'observacion_demora' => '',
            'hora_conexion_gpu' => $horaConexionGpu,
            'hora_desconexion_gpu' => $horaDesconexionGpu,
            'tiempo_gpu' => $tiempoGpu ?? '',
            'fracciones_adc_gpu' => $fraccionesAdcGpu,
            'fracciones_adicionales_gpu' => $fraccionesAdicionalesGpu,
            'acu' => $acu,
            'hora_conexion_acu' => $horaConexionAcu,
            'hora_desconexion_acu' => $horaDesconexionAcu,
            'tiempo_acu' => $tiempoAcu ?? '',
            'fracciones_hora_acu' => $fraccionesHoraAcu,
            'fracciones_15min_acu' => $fracciones15minAcu,
            // AI = Ventiladores: la vista de detalle muestra "ventiladores_activo"
            // (la sección nueva), así que el flag del Excel se refleja ahí; el
            // Excel no trae horas de conexión/desconexión de este equipo.
            'ventiladores_activo' => $this->cellFlag($row['AI'] ?? null) ? 1 : 0,
            'hora_conexion_ventiladores' => '',
            'hora_desconexion_ventiladores' => '',
            'tiempo_ventiladores' => '',
            'fracciones_hora_ventiladores' => 0,
            'fracciones_15min_ventiladores' => 0,
            'sillas_ruedas' => (int)$this->cellNumber($row['AH'] ?? null),
            'ventiladores' => $this->cellFlag($row['AI'] ?? null) ? 1 : 0,
            'rampa_escalera' => $this->cellFlag($row['AJ'] ?? null) ? 1 : 0,
            'equipajes_transportados' => 0,
            'remolque_aeronave' => 0,
            'remolque_equipajes' => 0,
            'potable' => 0,
            'drenaje' => 0,
            'air_starter' => 0,
            'pay_mower' => 0,
            'aseo_aeronaves' => 0,
            'equipos_carga_descargue' => 0,
            'atencion_pasajeros' => 0,
            'equipo_gse_inoperativo' => '',
            'afecto_operacion' => 0,
            'rpn' => '',
            'observaciones' => '',
            'user_id' => (int)Session::get('user_id'),
        ];

        return ['data' => $data, 'adicionales' => $adicionales];
    }

    // ─── Cálculos (réplica de la lógica de public/js/app.js) ──────

    private function calcularDiffMinutos(?string $horaInicio, ?string $horaFin): ?int {
        if ($horaInicio === null || $horaFin === null) return null;
        $ini = $this->timeToMinutes($horaInicio);
        $fin = $this->timeToMinutes($horaFin);
        $diff = $fin - $ini;
        if ($diff < 0) $diff += 1440;
        return $diff;
    }

    private function calcularDemora(?string $horaItinerada, ?string $horaReal): int {
        if ($horaItinerada === null || $horaReal === null) return 0;
        $demora = $this->timeToMinutes($horaReal) - $this->timeToMinutes($horaItinerada);
        return max(0, $demora);
    }

    private function calcularCumpleTiempo(?string $horaItSalida, ?string $horaRealSalida, ?int $tiempoTransito, ?int $tiempoCumplimiento): ?int {
        if ($horaItSalida === null || $horaRealSalida === null || $tiempoTransito === null || !$tiempoCumplimiento) {
            return null;
        }
        $salioTarde = $this->timeToMinutes($horaRealSalida) > $this->timeToMinutes($horaItSalida);
        $excedeTransito = $tiempoTransito > $tiempoCumplimiento;
        return ($salioTarde && $excedeTransito) ? 0 : 1;
    }

    private function timeToMinutes(string $hhmm): int {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    }

    private function mapMes(string $texto): ?int {
        $key = $this->normalizeKey($texto);
        return self::MESES_MAP[$key] ?? (ctype_digit($texto) && (int)$texto >= 1 && (int)$texto <= 12 ? (int)$texto : null);
    }

    private function normalizeFromMap(string $texto, array $map): ?string {
        return $map[$this->normalizeKey($texto)] ?? null;
    }

    /** Mayúsculas, sin tildes, espacios colapsados: para comparar contra las claves de los mapas fijos */
    private function normalizeKey(string $texto): string {
        $texto = trim($texto);
        $sinTildes = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);
        return preg_replace('/\s+/', ' ', mb_strtoupper($sinTildes));
    }

    // ─── Lectura de celdas ──────────────────────────────────────

    private function cellText(mixed $value): string {
        if ($value === null) return '';
        if (XlsxReader::isTimeValue($value)) return '';
        if (is_float($value)) {
            return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
        }
        return trim((string)$value);
    }

    private function cellNumber(mixed $value): float {
        if ($value === null) return 0.0;
        if (XlsxReader::isTimeValue($value)) return 0.0;
        if (is_float($value)) return $value;
        return is_numeric($value) ? (float)$value : 0.0;
    }

    /** Números guardados como float en el Excel (ej. números de vuelo) => texto sin decimales */
    private function cellFlightNumber(mixed $value): string {
        if ($value === null) return '';
        if (is_float($value)) return (string)(int)round($value);
        return strtoupper(trim((string)$value));
    }

    private function cellTime(mixed $value): ?string {
        return XlsxReader::minutesToTime($value);
    }

    /** "SI" (cualquier variante de mayúsculas) => true; vacío o cualquier otra cosa => false */
    private function cellFlag(mixed $value): bool {
        return $this->normalizeKey($this->cellText($value)) === 'SI';
    }

    private function rowToPlainArray(array $row): array {
        $plain = [];
        foreach ($row as $col => $value) {
            if (XlsxReader::isTimeValue($value)) {
                $plain[$col] = XlsxReader::minutesToTime($value);
            } else {
                $plain[$col] = $value;
            }
        }
        return $plain;
    }
}
