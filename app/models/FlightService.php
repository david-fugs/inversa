<?php

/**
 * Modelo FlightService
 */

class FlightService extends Model
{
    protected string $table = 'flight_services';

    /** Tipos de atención */
    public static array $tiposAtencion = [
        'Tránsito',
        'Cancelado',
        'Regreso a plataforma',
        'Escala técnica',
    ];

    /** Meses */
    public static array $meses = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /** Servicios adicionales */
    public static array $serviciosAdicionales = [
        'Traslado de carga',
        'Arrancador ASU',
        'Hora hombre',
        'Pernocta',
        'Cinta transportadora / Conveyor',
        'Escalera',
        'Drenado',
        'Remolque',
    ];

    /**
     * Listar todos con joins
     */
    public function getAllWithJoins(): array
    {
        return $this->db->fetchAll(
            "SELECT fs.*,
                    COALESCE(a.nombre, fs.airline_custom_nombre)  AS airline_nombre,
                    COALESCE(at.tipo, fs.aircraft_type_custom)   AS aircraft_tipo,
                    COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom) AS tiempo_cumplimiento,
                    u.nombre_completo AS registrado_por
             FROM flight_services fs
             LEFT JOIN airlines       a  ON fs.airline_id = a.id AND fs.airline_id != 'otra'
             LEFT JOIN aircraft_types at ON fs.aircraft_type_id = at.id
             JOIN users               u  ON fs.user_id = u.id
             ORDER BY fs.anio DESC, fs.mes DESC, fs.dia DESC, fs.id DESC"
        );
    }

    public function getAllWithJoinsByBase(string $base): array
    {
        return $this->db->fetchAll(
            "SELECT fs.*,
                    COALESCE(a.nombre, fs.airline_custom_nombre)  AS airline_nombre,
                    COALESCE(at.tipo, fs.aircraft_type_custom)   AS aircraft_tipo,
                    COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom) AS tiempo_cumplimiento,
                    u.nombre_completo AS registrado_por
             FROM flight_services fs
             LEFT JOIN airlines       a  ON fs.airline_id = a.id AND fs.airline_id != 'otra'
             LEFT JOIN aircraft_types at ON fs.aircraft_type_id = at.id
             JOIN users               u  ON fs.user_id = u.id
             WHERE fs.base = ?
             ORDER BY fs.anio DESC, fs.mes DESC, fs.dia DESC, fs.id DESC",
            [$base]
        );
    }

    /** Bases y aerolíneas (nombre) con al menos un servicio registrado,
     *  para poblar los filtros del listado sin tener que cargar todos
     *  los registros. */
    public function getDistinctBasesYAerolineas(?string $baseScope): array
    {
        if ($baseScope !== null) {
            return [
                'bases'      => [$baseScope],
                'aerolineas' => array_column($this->db->fetchAll(
                    "SELECT DISTINCT COALESCE(a.nombre, fs.airline_custom_nombre) AS nombre
                     FROM flight_services fs
                     LEFT JOIN airlines a ON fs.airline_id = a.id AND fs.airline_id != 'otra'
                     WHERE fs.base = ?
                     ORDER BY nombre",
                    [$baseScope]
                ), 'nombre'),
            ];
        }

        $bases = array_column($this->db->fetchAll(
            "SELECT DISTINCT base FROM flight_services ORDER BY base"
        ), 'base');

        $aerolineas = array_column($this->db->fetchAll(
            "SELECT DISTINCT COALESCE(a.nombre, fs.airline_custom_nombre) AS nombre
             FROM flight_services fs
             LEFT JOIN airlines a ON fs.airline_id = a.id AND fs.airline_id != 'otra'
             ORDER BY nombre"
        ), 'nombre');

        return ['bases' => $bases, 'aerolineas' => $aerolineas];
    }

    /** Columnas por las que se puede ordenar el listado server-side,
     *  mapeadas al índice de columna que envía DataTables. */
    private const ORDENABLES = [
        'id'              => 'fs.id',
        'fecha'           => 'fs.anio {dir}, fs.mes {dir}, fs.dia {dir}, fs.id {dir}',
        'base'            => 'fs.base',
        'airline_nombre'  => 'airline_nombre',
        'vuelo_llegando'  => 'fs.vuelo_llegando',
        'matricula'       => 'fs.matricula',
        'aircraft_tipo'   => 'aircraft_tipo',
        'tipo_atencion'   => 'fs.tipo_atencion',
        'tiempo_transito' => 'fs.tiempo_transito',
        'cumple_tiempo'   => 'fs.cumple_tiempo',
    ];

    /**
     * Listado paginado/ordenado/filtrado en la base de datos, para el
     * origen de datos server-side de DataTables (evita cargar todos los
     * registros en el navegador para luego ordenarlos/paginarlos ahí).
     */
    public function getPaginated(array $filtros, string $orderBy, string $orderDir, int $start, int $length, ?string $baseScope): array
    {
        $where  = [];
        $params = [];

        if ($baseScope !== null) {
            $where[] = 'fs.base = ?';
            $params[] = $baseScope;
        }
        if ($filtros['base'] !== '') {
            $where[] = 'fs.base = ?';
            $params[] = $filtros['base'];
        }
        if ($filtros['aerolinea'] !== '') {
            $where[] = 'COALESCE(a.nombre, fs.airline_custom_nombre) = ?';
            $params[] = $filtros['aerolinea'];
        }
        if ($filtros['fecha_inicio'] !== '') {
            $where[] = "STR_TO_DATE(CONCAT(fs.anio, '-', fs.mes, '-', fs.dia), '%Y-%m-%d') >= ?";
            $params[] = $filtros['fecha_inicio'];
        }
        if ($filtros['fecha_fin'] !== '') {
            $where[] = "STR_TO_DATE(CONCAT(fs.anio, '-', fs.mes, '-', fs.dia), '%Y-%m-%d') <= ?";
            $params[] = $filtros['fecha_fin'];
        }
        if ($filtros['buscar'] !== '') {
            $where[] = '(fs.id = ? OR fs.matricula LIKE ? OR fs.vuelo_llegando LIKE ? OR fs.vuelo_saliendo LIKE ?
                          OR fs.base LIKE ? OR fs.tipo_atencion LIKE ?
                          OR COALESCE(a.nombre, fs.airline_custom_nombre) LIKE ?
                          OR COALESCE(at.tipo, fs.aircraft_type_custom) LIKE ?)';
            $like = '%' . $filtros['buscar'] . '%';
            $idExacto = ctype_digit($filtros['buscar']) ? (int)$filtros['buscar'] : -1;
            array_push($params, $idExacto, $like, $like, $like, $like, $like, $like, $like);
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $joins = "LEFT JOIN airlines       a  ON fs.airline_id = a.id AND fs.airline_id != 'otra'
                  LEFT JOIN aircraft_types at ON fs.aircraft_type_id = at.id";

        $totalWhere = $baseScope !== null ? 'WHERE fs.base = ?' : '';
        $totalParams = $baseScope !== null ? [$baseScope] : [];
        $total = (int)($this->db->fetchOne(
            "SELECT COUNT(*) AS total FROM flight_services fs {$totalWhere}",
            $totalParams
        )['total'] ?? 0);

        $filtered = (int)($this->db->fetchOne(
            "SELECT COUNT(*) AS total FROM flight_services fs {$joins} {$whereSql}",
            $params
        )['total'] ?? 0);

        $orderExpr = self::ORDENABLES[$orderBy] ?? self::ORDENABLES['fecha'];
        $dir = $orderDir === 'asc' ? 'ASC' : 'DESC';
        $orderExpr = str_replace('{dir}', $dir, $orderExpr);
        if (strpos($orderExpr, '{dir}') === false && strpos($orderExpr, ' ASC') === false && strpos($orderExpr, ' DESC') === false) {
            $orderExpr .= ' ' . $dir;
        }

        $rows = $this->db->fetchAll(
            "SELECT fs.id, fs.dia, fs.mes, fs.anio, fs.quincena, fs.base, fs.vuelo_llegando, fs.vuelo_saliendo,
                    fs.matricula, fs.tipo_atencion, fs.tiempo_transito, fs.cumple_tiempo, fs.archivo_pdf,
                    COALESCE(a.nombre, fs.airline_custom_nombre) AS airline_nombre,
                    COALESCE(at.tipo, fs.aircraft_type_custom)   AS aircraft_tipo
             FROM flight_services fs {$joins} {$whereSql}
             ORDER BY {$orderExpr}
             LIMIT {$length} OFFSET {$start}",
            $params
        );

        return ['total' => $total, 'filtered' => $filtered, 'rows' => $rows];
    }

    /**
     * Obtener un servicio con todos sus datos relacionados
     */
    public function findFullById(int $id): array|false
    {
        $service = $this->db->fetchOne(
            "SELECT fs.*,
                    COALESCE(a.nombre, fs.airline_custom_nombre)  AS airline_nombre,
                    COALESCE(at.tipo, fs.aircraft_type_custom)   AS aircraft_tipo,
                    COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom) AS tiempo_cumplimiento,
                    u.nombre_completo AS registrado_por
             FROM flight_services fs
             LEFT JOIN airlines       a  ON fs.airline_id = a.id AND fs.airline_id != 'otra'
             LEFT JOIN aircraft_types at ON fs.aircraft_type_id = at.id
             JOIN users               u  ON fs.user_id = u.id
             WHERE fs.id = ?",
            [$id]
        );

        if (!$service) return false;

        $service['gpu_fracciones']      = $this->getGpuFracciones($id);
        $service['acu_fracciones']      = $this->getAcuFracciones($id);
        $service['ventiladores_fracciones'] = $this->getVentiladoresFracciones($id);
        $service['adicionales']         = $this->getAdicionales($id);

        return $service;
    }

    public function getGpuFracciones(int $serviceId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM flight_service_gpu_fracciones WHERE flight_service_id = ?",
            [$serviceId]
        );
    }

    public function getAcuFracciones(int $serviceId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM flight_service_acu_fracciones WHERE flight_service_id = ?",
            [$serviceId]
        );
    }

    public function getVentiladoresFracciones(int $serviceId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM flight_service_ventiladores_fracciones WHERE flight_service_id = ?",
            [$serviceId]
        );
    }

    public function getAdicionales(int $serviceId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM flight_service_adicionales WHERE flight_service_id = ?",
            [$serviceId]
        );
    }

    /**
     * Obtener adicionales de varios servicios de una vez, agrupados por flight_service_id
     */
    public function getAdicionalesForIds(array $serviceIds): array
    {
        if (empty($serviceIds)) return [];

        $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT * FROM flight_service_adicionales WHERE flight_service_id IN ($placeholders)",
            $serviceIds
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['flight_service_id']][] = $row;
        }
        return $grouped;
    }

    /**
     * Obtener fracciones adicionales de GPU de varios servicios de una vez,
     * agrupadas por flight_service_id
     */
    public function getGpuFraccionesForIds(array $serviceIds): array
    {
        if (empty($serviceIds)) return [];

        $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT * FROM flight_service_gpu_fracciones WHERE flight_service_id IN ($placeholders)",
            $serviceIds
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['flight_service_id']][] = $row;
        }
        return $grouped;
    }

    /**
     * Crear servicio de vuelo con todos sus relacionados
     */
    public function create(array $data, array $gpuFracciones, array $acuFracciones, array $ventiladoresFracciones, array $adicionales): int
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // Si es 'otra', guardar como string; si no, guardar como int
            $airlineId = $data['airline_id'];
            $aircraftTypeId = ($data['airline_id'] === 'otra' || $data['airline_id'] == 'otra') ? null : $data['aircraft_type_id'];

            $this->db->query(
                "INSERT INTO flight_services (
                    import_id,
                    anio, mes, quincena, dia, base, despacho,
                    airline_id, airline_custom_nombre, tipo_atencion,
                    vuelo_llegando, base_destino, matricula, aircraft_type_id, aircraft_type_custom, tiempo_cumplimiento_custom,
                    pax_saliendo, pax_cancelado, ajes_transportados, vuelo_saliendo,
                    hora_itinerada_llegada, demora_llegando,
                    hora_itinerada_salida, satena_hora_cierre_modulo, hora_real_llegada, hora_real_salida,
                    tiempo_transito, cumple_tiempo,
                    codigo_demora, codigo_demora_id, observacion_demora,
                    hora_conexion_gpu, hora_desconexion_gpu, tiempo_gpu, fracciones_adc_gpu, fracciones_adicionales_gpu,
                    acu, hora_conexion_acu, hora_desconexion_acu, tiempo_acu,
                    fracciones_hora_acu, fracciones_15min_acu,
                    ventiladores_activo, hora_conexion_ventiladores, hora_desconexion_ventiladores, tiempo_ventiladores,
                    fracciones_hora_ventiladores, fracciones_15min_ventiladores,
                    sillas_ruedas, ventiladores, rampa_escalera,
                    equipajes_transportados, remolque_aeronave, remolque_equipajes,
                    potable, drenaje, air_starter, pay_mower, aseo_aeronaves, equipos_carga_descargue, atencion_pasajeros,
                    equipo_gse_inoperativo, afecto_operacion, rpn, observaciones, user_id
                ) VALUES (
                    ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
                )",
                [
                    $data['import_id'] ?? null,
                    $data['anio'],
                    $data['mes'],
                    $data['quincena'],
                    $data['dia'],
                    $data['base'],
                    $data['despacho'],
                    $airlineId,
                    $data['airline_custom_nombre'] ?: null,
                    $data['tipo_atencion'],
                    $data['vuelo_llegando'],
                    $data['base_destino'],
                    $data['matricula'],
                    $aircraftTypeId,
                    $data['aircraft_type_custom'] ?: null,
                    $data['tiempo_cumplimiento_custom'],
                    $data['pax_saliendo'],
                    $data['pax_cancelado'],
                    $data['ajes_transportados'],
                    $data['vuelo_saliendo'],
                    $data['hora_itinerada_llegada'] ?: null,
                    $data['demora_llegando'],
                    $data['hora_itinerada_salida'] ?: null,
                    $data['satena_hora_cierre_modulo'] ?: null,
                    $data['hora_real_llegada'] ?: null,
                    $data['hora_real_salida'] ?: null,
                    $data['tiempo_transito'] !== '' ? $data['tiempo_transito'] : null,
                    $data['cumple_tiempo'] !== '' ? $data['cumple_tiempo'] : null,
                    $data['codigo_demora'] ?: null,
                    $data['codigo_demora_id'] ?: null,
                    $data['observacion_demora'] ?: null,
                    $data['hora_conexion_gpu'] ?: null,
                    $data['hora_desconexion_gpu'] ?: null,
                    $data['tiempo_gpu'] !== '' ? $data['tiempo_gpu'] : null,
                    $data['fracciones_adc_gpu'],
                    $data['fracciones_adicionales_gpu'],
                    $data['acu'],
                    $data['hora_conexion_acu'] ?: null,
                    $data['hora_desconexion_acu'] ?: null,
                    $data['tiempo_acu'] !== '' ? $data['tiempo_acu'] : null,
                    $data['fracciones_hora_acu'],
                    $data['fracciones_15min_acu'],
                    $data['ventiladores_activo'],
                    $data['hora_conexion_ventiladores'] ?: null,
                    $data['hora_desconexion_ventiladores'] ?: null,
                    $data['tiempo_ventiladores'] !== '' ? $data['tiempo_ventiladores'] : null,
                    $data['fracciones_hora_ventiladores'],
                    $data['fracciones_15min_ventiladores'],
                    $data['sillas_ruedas'],
                    $data['ventiladores'],
                    $data['rampa_escalera'],
                    $data['equipajes_transportados'],
                    $data['remolque_aeronave'],
                    $data['remolque_equipajes'],
                    $data['potable'],
                    $data['drenaje'],
                    $data['air_starter'],
                    $data['pay_mower'],
                    $data['aseo_aeronaves'],
                    $data['equipos_carga_descargue'],
                    $data['atencion_pasajeros'],
                    $data['equipo_gse_inoperativo'] ?: null,
                    $data['afecto_operacion'],
                    $data['rpn'] ?: null,
                    $data['observaciones'] ?: null,
                    $data['user_id'],
                ]
            );

            $serviceId = (int)$this->db->lastInsertId();

            $this->saveGpuFracciones($serviceId, $gpuFracciones);
            $this->saveAcuFracciones($serviceId, $acuFracciones);
            $this->saveVentiladoresFracciones($serviceId, $ventiladoresFracciones);
            $this->saveAdicionales($serviceId, $adicionales);

            $pdo->commit();
            return $serviceId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar servicio de vuelo
     */
    public function update(int $id, array $data, array $gpuFracciones, array $acuFracciones, array $ventiladoresFracciones, array $adicionales): bool
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // Si es 'otra', guardar como string; si no, guardar como int
            $airlineId = $data['airline_id'];
            $aircraftTypeId = ($data['airline_id'] === 'otra' || $data['airline_id'] == 'otra') ? null : $data['aircraft_type_id'];

            $this->db->query(
                "UPDATE flight_services SET
                    anio=?, mes=?, quincena=?, dia=?, base=?, despacho=?,
                    airline_id=?, airline_custom_nombre=?, tipo_atencion=?,
                    vuelo_llegando=?, base_destino=?, matricula=?, aircraft_type_id=?, aircraft_type_custom=?, tiempo_cumplimiento_custom=?,
                    pax_saliendo=?, pax_cancelado=?, ajes_transportados=?, vuelo_saliendo=?,
                    hora_itinerada_llegada=?, demora_llegando=?,
                    hora_itinerada_salida=?, satena_hora_cierre_modulo=?, hora_real_llegada=?, hora_real_salida=?,
                    tiempo_transito=?, cumple_tiempo=?,
                    codigo_demora=?, codigo_demora_id=?, observacion_demora=?,
                    hora_conexion_gpu=?, hora_desconexion_gpu=?, tiempo_gpu=?, fracciones_adc_gpu=?, fracciones_adicionales_gpu=?,
                    acu=?, hora_conexion_acu=?, hora_desconexion_acu=?, tiempo_acu=?,
                    fracciones_hora_acu=?, fracciones_15min_acu=?,
                    ventiladores_activo=?, hora_conexion_ventiladores=?, hora_desconexion_ventiladores=?, tiempo_ventiladores=?,
                    fracciones_hora_ventiladores=?, fracciones_15min_ventiladores=?,
                    sillas_ruedas=?, ventiladores=?, rampa_escalera=?,
                    equipajes_transportados=?, remolque_aeronave=?, remolque_equipajes=?,
                    potable=?, drenaje=?, air_starter=?, pay_mower=?, aseo_aeronaves=?, equipos_carga_descargue=?, atencion_pasajeros=?,
                    equipo_gse_inoperativo=?, afecto_operacion=?, rpn=?, observaciones=?
                 WHERE id=?",
                [
                    $data['anio'],
                    $data['mes'],
                    $data['quincena'],
                    $data['dia'],
                    $data['base'],
                    $data['despacho'],
                    $airlineId,
                    $data['airline_custom_nombre'] ?: null,
                    $data['tipo_atencion'],
                    $data['vuelo_llegando'],
                    $data['base_destino'],
                    $data['matricula'],
                    $aircraftTypeId,
                    $data['aircraft_type_custom'] ?: null,
                    $data['tiempo_cumplimiento_custom'],
                    $data['pax_saliendo'],
                    $data['pax_cancelado'],
                    $data['ajes_transportados'],
                    $data['vuelo_saliendo'],
                    $data['hora_itinerada_llegada'] ?: null,
                    $data['demora_llegando'],
                    $data['hora_itinerada_salida'] ?: null,
                    $data['satena_hora_cierre_modulo'] ?: null,
                    $data['hora_real_llegada'] ?: null,
                    $data['hora_real_salida'] ?: null,
                    $data['tiempo_transito'] !== '' ? $data['tiempo_transito'] : null,
                    $data['cumple_tiempo'] !== '' ? $data['cumple_tiempo'] : null,
                    $data['codigo_demora'] ?: null,
                    $data['codigo_demora_id'] ?: null,
                    $data['observacion_demora'] ?: null,
                    $data['hora_conexion_gpu'] ?: null,
                    $data['hora_desconexion_gpu'] ?: null,
                    $data['tiempo_gpu'] !== '' ? $data['tiempo_gpu'] : null,
                    $data['fracciones_adc_gpu'],
                    $data['fracciones_adicionales_gpu'],
                    $data['acu'],
                    $data['hora_conexion_acu'] ?: null,
                    $data['hora_desconexion_acu'] ?: null,
                    $data['tiempo_acu'] !== '' ? $data['tiempo_acu'] : null,
                    $data['fracciones_hora_acu'],
                    $data['fracciones_15min_acu'],
                    $data['ventiladores_activo'],
                    $data['hora_conexion_ventiladores'] ?: null,
                    $data['hora_desconexion_ventiladores'] ?: null,
                    $data['tiempo_ventiladores'] !== '' ? $data['tiempo_ventiladores'] : null,
                    $data['fracciones_hora_ventiladores'],
                    $data['fracciones_15min_ventiladores'],
                    $data['sillas_ruedas'],
                    $data['ventiladores'],
                    $data['rampa_escalera'],
                    $data['equipajes_transportados'],
                    $data['remolque_aeronave'],
                    $data['remolque_equipajes'],
                    $data['potable'],
                    $data['drenaje'],
                    $data['air_starter'],
                    $data['pay_mower'],
                    $data['aseo_aeronaves'],
                    $data['equipos_carga_descargue'],
                    $data['atencion_pasajeros'],
                    $data['equipo_gse_inoperativo'] ?: null,
                    $data['afecto_operacion'],
                    $data['rpn'] ?: null,
                    $data['observaciones'] ?: null,
                    $id,
                ]
            );

            // Reemplazar relacionados
            $this->db->query("DELETE FROM flight_service_gpu_fracciones WHERE flight_service_id=?", [$id]);
            $this->db->query("DELETE FROM flight_service_acu_fracciones WHERE flight_service_id=?", [$id]);
            $this->db->query("DELETE FROM flight_service_ventiladores_fracciones WHERE flight_service_id=?", [$id]);
            $this->db->query("DELETE FROM flight_service_adicionales WHERE flight_service_id=?", [$id]);

            $this->saveGpuFracciones($id, $gpuFracciones);
            $this->saveAcuFracciones($id, $acuFracciones);
            $this->saveVentiladoresFracciones($id, $ventiladoresFracciones);
            $this->saveAdicionales($id, $adicionales);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function saveGpuFracciones(int $serviceId, array $rows): void
    {
        // Máximo 3 fracciones adicionales de GPU por servicio
        $rows = array_slice($rows, 0, 3);
        foreach ($rows as $row) {
            if (empty($row['hora_conexion']) && empty($row['hora_desconexion'])) continue;
            $this->db->query(
                "INSERT INTO flight_service_gpu_fracciones
                 (flight_service_id, hora_conexion, hora_desconexion, tiempo, fracciones_adc, observacion)
                 VALUES (?,?,?,?,?,?)",
                [
                    $serviceId,
                    $row['hora_conexion']  ?: null,
                    $row['hora_desconexion'] ?: null,
                    $row['tiempo'] !== '' ? (int)$row['tiempo'] : null,
                    (float)($row['fracciones_adc'] ?? 0),
                    !empty($row['observacion']) ? trim((string)$row['observacion']) : null,
                ]
            );
        }
    }

    private function saveAcuFracciones(int $serviceId, array $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['hora_conexion']) && empty($row['hora_desconexion'])) continue;
            $this->db->query(
                "INSERT INTO flight_service_acu_fracciones
                 (flight_service_id, hora_conexion, hora_desconexion, tiempo, fracciones_hora, fracciones_15min)
                 VALUES (?,?,?,?,?,?)",
                [
                    $serviceId,
                    $row['hora_conexion']    ?: null,
                    $row['hora_desconexion'] ?: null,
                    $row['tiempo'] !== '' ? (int)$row['tiempo'] : null,
                    (float)($row['fracciones_hora']  ?? 0),
                    (float)($row['fracciones_15min'] ?? 0),
                ]
            );
        }
    }

    private function saveVentiladoresFracciones(int $serviceId, array $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['hora_conexion']) && empty($row['hora_desconexion'])) continue;
            $this->db->query(
                "INSERT INTO flight_service_ventiladores_fracciones
                 (flight_service_id, hora_conexion, hora_desconexion, tiempo, fracciones_hora, fracciones_15min)
                 VALUES (?,?,?,?,?,?)",
                [
                    $serviceId,
                    $row['hora_conexion']    ?: null,
                    $row['hora_desconexion'] ?: null,
                    $row['tiempo'] !== '' ? (int)$row['tiempo'] : null,
                    (float)($row['fracciones_hora']  ?? 0),
                    (float)($row['fracciones_15min'] ?? 0),
                ]
            );
        }
    }

    private function saveAdicionales(int $serviceId, array $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['servicio'])) continue;
            $this->db->query(
                "INSERT INTO flight_service_adicionales (flight_service_id, servicio, cantidad) VALUES (?,?,?)",
                [$serviceId, $row['servicio'], max(1, (int)($row['cantidad'] ?? 1))]
            );
        }
    }

    /**
     * Calcular quincena según el día
     */
    public static function calcularQuincena(int $dia): int
    {
        return $dia <= 15 ? 1 : 2;
    }

    /** Guardar el archivo PDF adjunto (reemplaza el anterior si ya había uno) */
    public function setArchivo(int $id, string $archivoPdf, string $archivoOriginal): bool
    {
        $stmt = $this->db->query(
            "UPDATE flight_services SET archivo_pdf = ?, archivo_pdf_original = ? WHERE id = ?",
            [$archivoPdf, $archivoOriginal, $id]
        );
        return $stmt->rowCount() > 0;
    }

    /** Quitar el archivo PDF adjunto (deja las columnas en NULL) */
    public function clearArchivo(int $id): bool
    {
        $stmt = $this->db->query(
            "UPDATE flight_services SET archivo_pdf = NULL, archivo_pdf_original = NULL WHERE id = ?",
            [$id]
        );
        return $stmt->rowCount() > 0;
    }
}
