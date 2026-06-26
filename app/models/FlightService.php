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
                    anio, mes, quincena, dia, base, despacho,
                    airline_id, airline_custom_nombre, tipo_atencion,
                    vuelo_llegando, base_destino, matricula, aircraft_type_id, aircraft_type_custom, tiempo_cumplimiento_custom,
                    pax_saliendo, pax_cancelado, ajes_transportados, vuelo_saliendo,
                    hora_itinerada_llegada, demora_llegando,
                    hora_itinerada_salida, satena_hora_cierre_modulo, hora_real_llegada, hora_real_salida,
                    tiempo_transito, cumple_tiempo,
                    codigo_demora, observacion_demora,
                    hora_conexion_gpu, hora_desconexion_gpu, tiempo_gpu, fracciones_adc_gpu, fracciones_adicionales_gpu,
                    acu, hora_conexion_acu, hora_desconexion_acu, tiempo_acu,
                    fracciones_hora_acu, fracciones_15min_acu,
                    ventiladores_activo, hora_conexion_ventiladores, hora_desconexion_ventiladores, tiempo_ventiladores,
                    fracciones_hora_ventiladores, fracciones_15min_ventiladores,
                    sillas_ruedas, ventiladores, rampa_escalera,
                    equipajes_transportados, remolque_aeronave, remolque_equipajes,
                    potable, drenaje,
                    equipo_gse_inoperativo, afecto_operacion, rpn, observaciones, user_id
                ) VALUES (
                    ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
                )",
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
                    codigo_demora=?, observacion_demora=?,
                    hora_conexion_gpu=?, hora_desconexion_gpu=?, tiempo_gpu=?, fracciones_adc_gpu=?, fracciones_adicionales_gpu=?,
                    acu=?, hora_conexion_acu=?, hora_desconexion_acu=?, tiempo_acu=?,
                    fracciones_hora_acu=?, fracciones_15min_acu=?,
                    ventiladores_activo=?, hora_conexion_ventiladores=?, hora_desconexion_ventiladores=?, tiempo_ventiladores=?,
                    fracciones_hora_ventiladores=?, fracciones_15min_ventiladores=?,
                    sillas_ruedas=?, ventiladores=?, rampa_escalera=?,
                    equipajes_transportados=?, remolque_aeronave=?, remolque_equipajes=?,
                    potable=?, drenaje=?,
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
        foreach ($rows as $row) {
            if (empty($row['hora_conexion']) && empty($row['hora_desconexion'])) continue;
            $this->db->query(
                "INSERT INTO flight_service_gpu_fracciones
                 (flight_service_id, hora_conexion, hora_desconexion, tiempo, fracciones_adc)
                 VALUES (?,?,?,?,?)",
                [
                    $serviceId,
                    $row['hora_conexion']  ?: null,
                    $row['hora_desconexion'] ?: null,
                    $row['tiempo'] !== '' ? (int)$row['tiempo'] : null,
                    (float)($row['fracciones_adc'] ?? 0),
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
}
