<?php

/**
 * Modelo Facturacion
 *
 * Resume, por aerolínea (y por base cuando el catálogo de la aerolínea así
 * lo define), la cantidad de cada concepto facturable a partir de los
 * servicios de vuelo registrados. Los conceptos y su regla de conteo viven
 * en catalogo() para poder ajustarlos en un solo lugar.
 */
class Facturacion extends Model
{
    protected string $table = 'flight_services';

    /** Bases sin despacho centralizado (Arauca y Quibdó). */
    private const BASES_SIN_DESPACHO = ['AUC', 'UIB'];

    // ───────────────────────────── Datos ─────────────────────────────

    /**
     * Servicios filtrados, con los campos que usan las reglas del catálogo,
     * más los adicionales y fracciones GPU de cada uno.
     *
     * @param array{fecha_inicio?:string,fecha_fin?:string,aerolineas?:array} $filtros
     */
    public function getServicios(array $filtros, ?string $baseScope): array
    {
        $where = [];
        $params = [];

        if ($baseScope !== null) {
            $where[] = 'fs.base = ?';
            $params[] = $baseScope;
        }
        $bases = array_values(array_filter($filtros['bases'] ?? [], fn($v) => $v !== ''));
        if ($bases) {
            $where[] = 'fs.base IN (' . implode(',', array_fill(0, count($bases), '?')) . ')';
            array_push($params, ...$bases);
        }
        $tipos = array_values(array_filter($filtros['tipos_avion'] ?? [], fn($v) => $v !== ''));
        if ($tipos) {
            $where[] = 'COALESCE(at.tipo, fs.aircraft_type_custom) IN (' . implode(',', array_fill(0, count($tipos), '?')) . ')';
            array_push($params, ...$tipos);
        }
        $aerolineas = array_values(array_filter($filtros['aerolineas'] ?? [], fn($v) => $v !== ''));
        if ($aerolineas) {
            $where[] = 'COALESCE(a.nombre, fs.airline_custom_nombre) IN (' . implode(',', array_fill(0, count($aerolineas), '?')) . ')';
            array_push($params, ...$aerolineas);
        }
        if (($filtros['fecha_inicio'] ?? '') !== '') {
            $where[] = "STR_TO_DATE(CONCAT(fs.anio, '-', fs.mes, '-', fs.dia), '%Y-%m-%d') >= ?";
            $params[] = $filtros['fecha_inicio'];
        }
        if (($filtros['fecha_fin'] ?? '') !== '') {
            $where[] = "STR_TO_DATE(CONCAT(fs.anio, '-', fs.mes, '-', fs.dia), '%Y-%m-%d') <= ?";
            $params[] = $filtros['fecha_fin'];
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $rows = $this->db->fetchAll(
            "SELECT fs.id, fs.anio, fs.mes, fs.pax_saliendo, fs.base, fs.tipo_atencion, fs.despacho, fs.pax_cancelado, fs.demora_llegando,
                    fs.fracciones_adc_gpu, fs.acu, fs.fracciones_hora_acu, fs.fracciones_15min_acu,
                    fs.ventiladores_activo, fs.ventiladores, fs.sillas_ruedas, fs.rampa_escalera,
                    fs.remolque_aeronave, fs.remolque_equipajes, fs.potable, fs.drenaje,
                    fs.air_starter, fs.pay_mower, fs.aseo_aeronaves, fs.equipos_carga_descargue,
                    fs.atencion_pasajeros, fs.equipajes_transportados,
                    COALESCE(a.nombre, fs.airline_custom_nombre) AS airline_nombre,
                    COALESCE(at.tipo, fs.aircraft_type_custom)   AS aircraft_tipo
             FROM flight_services fs
             LEFT JOIN airlines       a  ON fs.airline_id = a.id AND fs.airline_id != 'otra'
             LEFT JOIN aircraft_types at ON fs.aircraft_type_id = at.id
             {$whereSql}",
            $params
        );
        if (!$rows) return [];

        $ids = array_column($rows, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));

        $adic = [];
        foreach ($this->db->fetchAll("SELECT flight_service_id, servicio, cantidad FROM flight_service_adicionales WHERE flight_service_id IN ($ph)", $ids) as $r) {
            $adic[$r['flight_service_id']][] = $r;
        }
        $gpu = [];
        foreach ($this->db->fetchAll("SELECT flight_service_id, fracciones_adc FROM flight_service_gpu_fracciones WHERE flight_service_id IN ($ph)", $ids) as $r) {
            $gpu[$r['flight_service_id']][] = $r;
        }

        foreach ($rows as &$s) {
            $s['adicionales'] = $adic[$s['id']] ?? [];
            $fr = (float)($s['fracciones_adc_gpu'] ?? 0);
            foreach ($gpu[$s['id']] ?? [] as $g) {
                $fr += (float)($g['fracciones_adc'] ?? 0);
            }
            $s['fracciones_gpu'] = $fr;
        }
        unset($s);
        return $rows;
    }

    /**
     * Tipos de avión (con su aerolínea) que tienen servicios registrados,
     * para poblar el filtro "Tipo de avión", que depende de las aerolíneas.
     *
     * @return array<int,array{aerolinea:string,tipo:string}>
     */
    public function getTiposAvionPorAerolinea(?string $baseScope): array
    {
        $where  = "COALESCE(at.tipo, fs.aircraft_type_custom) IS NOT NULL";
        $params = [];
        if ($baseScope !== null) {
            $where .= ' AND fs.base = ?';
            $params[] = $baseScope;
        }
        return $this->db->fetchAll(
            "SELECT DISTINCT COALESCE(a.nombre, fs.airline_custom_nombre) AS aerolinea,
                             COALESCE(at.tipo, fs.aircraft_type_custom)   AS tipo
             FROM flight_services fs
             LEFT JOIN airlines       a  ON fs.airline_id = a.id AND fs.airline_id != 'otra'
             LEFT JOIN aircraft_types at ON fs.aircraft_type_id = at.id
             WHERE {$where}
             ORDER BY tipo, aerolinea",
            $params
        );
    }

    // ───────────────────── Paneles por aerolínea ─────────────────────

    /** Métricas mensuales de los paneles Avianca / Clic / Satena: clave => [etiqueta, fn(servicio)]. */
    public function metricasPanel(): array
    {
        return [
            'vuelos'       => ['Vuelos',                          fn($s) => 1],
            'pax'          => ['Pasajeros',                       fn($s) => (int)$s['pax_saliendo']],
            'ventiladores' => ['Ventiladores',                    fn($s) => self::uno((int)$s['ventiladores_activo'] === 1 || (int)$s['ventiladores'] > 0)],
            'sillas'       => ['Sillas de ruedas',                fn($s) => (int)$s['sillas_ruedas']],
            'rampa'        => ['Rampa escalera',                  fn($s) => self::uno((int)$s['rampa_escalera'] === 1) + self::adic($s, 'Escalera', 'Rampa escalera')],
            'gpu'          => ['Planta eléctrica GPU (fracciones)', fn($s) => $s['fracciones_gpu']],
            'acu'          => ['Aire ACU (fracciones)',           fn($s) => (float)$s['fracciones_hora_acu'] + (float)$s['fracciones_15min_acu']],
            'remolque'     => ['Remolque avión',                  fn($s) => (int)$s['remolque_aeronave']],
            'drenaje'      => ['Drenaje',                         fn($s) => (int)$s['drenaje']],
            'potable'      => ['Agua potable',                    fn($s) => (int)$s['potable']],
        ];
    }

    /**
     * Totales por mes y año de las métricas pedidas.
     *
     * @return array{anios:int[],series:array<string,array{label:string,por_anio:array<int,float[]>}>}
     */
    public function seriesMensuales(array $servicios, array $claves): array
    {
        $def = $this->metricasPanel();
        $series = [];
        foreach ($claves as $k) {
            if (isset($def[$k])) $series[$k] = ['label' => $def[$k][0], 'por_anio' => []];
        }
        $anios = [];
        foreach ($servicios as $s) {
            $anio = (int)$s['anio'];
            $mes  = (int)$s['mes'];
            if ($mes < 1 || $mes > 12) continue;
            $anios[$anio] = true;
            foreach ($series as $k => &$serie) {
                $serie['por_anio'][$anio] ??= array_fill(1, 12, 0.0);
                $serie['por_anio'][$anio][$mes] += (float)$def[$k][1]($s);
            }
            unset($serie);
        }
        ksort($anios);
        foreach ($series as &$serie) {
            ksort($serie['por_anio']);
            foreach ($serie['por_anio'] as $a => $meses) {
                $serie['por_anio'][$a] = array_map(fn($v) => round($v, 2), array_values($meses));
            }
        }
        unset($serie);
        return ['anios' => array_keys($anios), 'series' => $series];
    }

    // ─────────────────────────── Agregación ──────────────────────────

    /**
     * @return array{airlines:array,total_vuelos:int}
     */
    public function resumir(array $servicios, array $agregar = [], array $quitar = []): array
    {
        $biblioteca = $this->biblioteca();
        $porAerolinea = [];
        foreach ($servicios as $s) {
            $porAerolinea[$s['airline_nombre'] ?: 'Sin aerolínea'][] = $s;
        }
        ksort($porAerolinea);

        $airlines = [];
        foreach ($porAerolinea as $nombre => $rows) {
            $secciones = $this->catalogo($nombre);
            $salida = [];
            foreach ($secciones as $sec) {
                $sec['conceptos'] = $this->ajustarConceptos($sec['conceptos'], $biblioteca, $agregar, $quitar);
                $vuelos = 0;
                $cant = array_fill(0, count($sec['conceptos']), 0);
                foreach ($rows as $s) {
                    if (!$this->aplicaSeccion($sec, $s['base'])) continue;
                    $vuelos++;
                    foreach ($sec['conceptos'] as $i => $c) {
                        $cant[$i] += (float)$c[1]($s);
                    }
                }
                if ($vuelos === 0) continue;
                $conceptos = [];
                foreach ($sec['conceptos'] as $i => $c) {
                    $conceptos[] = ['concepto' => $c[0], 'cantidad' => round($cant[$i], 2)];
                }
                $salida[] = ['titulo' => $sec['titulo'], 'vuelos' => $vuelos, 'conceptos' => $conceptos];
            }
            $airlines[] = [
                'nombre'    => $nombre,
                'vuelos'    => count($rows),
                'secciones' => $salida,
            ];
        }
        return ['airlines' => $airlines, 'total_vuelos' => count($servicios)];
    }

    /** Quita los conceptos desmarcados y agrega los marcados que la sección no traía. */
    private function ajustarConceptos(array $conceptos, array $biblioteca, array $agregar, array $quitar): array
    {
        if ($quitar) {
            $conceptos = array_values(array_filter($conceptos, fn($c) => !in_array($c[0], $quitar, true)));
        }
        $presentes = array_column($conceptos, 0);
        foreach ($agregar as $label) {
            if (isset($biblioteca[$label]) && !in_array($label, $presentes, true)) {
                $conceptos[] = [$label, $biblioteca[$label]['fn']];
            }
        }
        return $conceptos;
    }

    /**
     * Todos los conceptos que se pueden mostrar: los de los catálogos
     * (defecto = true) y otros campos de los servicios (defecto = false).
     *
     * @return array<string,array{grupo:string,defecto:bool,fn:callable}>
     */
    public function biblioteca(): array
    {
        $lib = [];
        foreach (['CLIC', 'SATENA', 'AVIANCA'] as $aero) {
            foreach ($this->catalogo($aero) as $sec) {
                foreach ($sec['conceptos'] as $c) {
                    $lib[$c[0]] ??= ['grupo' => 'Facturación (por defecto)', 'defecto' => true, 'fn' => $c[1]];
                }
            }
        }

        $existentes = array_map('mb_strtolower', array_keys($lib));
        $extras = [
            'Tipo de atención' => [
                ['Atención Tránsito (todos los aviones)',   fn($s) => self::uno(self::tipo($s, 'Tránsito'))],
            ],
            'Equipos y servicios' => [
                ['Remolque de equipajes',          fn($s) => (int)$s['remolque_equipajes']],
                ['Agua potable',                   fn($s) => (int)$s['potable']],
                ['Drenaje',                        fn($s) => (int)$s['drenaje']],
                ['Remolque de aeronave',           fn($s) => (int)$s['remolque_aeronave']],
                ['Air Starter',                    fn($s) => (int)$s['air_starter']],
                ['Pay Mower',                      fn($s) => (int)$s['pay_mower']],
                ['Aseo de aeronaves',              fn($s) => (int)$s['aseo_aeronaves']],
                ['Equipos de carga y descargue',   fn($s) => (int)$s['equipos_carga_descargue']],
                ['Equipajes transportados',        fn($s) => (int)$s['equipajes_transportados']],
                ['Ventiladores (cantidad)',        fn($s) => (int)$s['ventiladores']],
                ['Atención pasajeros (cantidad)',  fn($s) => (int)$s['atencion_pasajeros']],
                ['ACU - Fracciones hora',          fn($s) => (float)$s['fracciones_hora_acu']],
                ['ACU - Fracciones por fracción',  fn($s) => (float)$s['fracciones_15min_acu']],
                ['Servicios con ACU (conteo)',     fn($s) => self::uno((int)$s['acu'] === 1)],
            ],
            'Demoras' => [
                ['Demora llegando (minutos)',      fn($s) => (int)$s['demora_llegando']],
                ['Vuelos con demora',              fn($s) => self::uno((int)$s['demora_llegando'] > 0)],
            ],
            'Servicios adicionales' => [],
        ];
        foreach (FlightService::$serviciosAdicionales as $nombre) {
            $extras['Servicios adicionales'][] = ['Adicional: ' . $nombre, fn($s) => self::adic($s, $nombre)];
        }

        foreach ($extras as $grupo => $items) {
            foreach ($items as [$label, $fn]) {
                if (in_array(mb_strtolower($label), $existentes, true)) continue;
                $lib[$label] = ['grupo' => $grupo, 'defecto' => false, 'fn' => $fn];
                $existentes[] = mb_strtolower($label);
            }
        }
        return $lib;
    }

    private function aplicaSeccion(array $sec, string $base): bool
    {
        if (isset($sec['bases'])) return in_array($base, $sec['bases'], true);
        if (isset($sec['excepto_bases'])) return !in_array($base, $sec['excepto_bases'], true);
        return true;
    }

    // ───────────────────────────── Catálogo ──────────────────────────

    /** Normaliza el nombre de aerolínea para elegir su catálogo. */
    private static function norm(string $s): string
    {
        return strtoupper(trim($s));
    }

    private static function esAtr(array $s): bool       { return stripos((string)$s['aircraft_tipo'], 'ATR') !== false; }
    private static function esA320(array $s): bool      { return (bool)preg_match('/A3(19|20)/i', (string)$s['aircraft_tipo']); }
    /** Satena: ATR 42/72 y EJR 145 (48/70 pax). */
    private static function es4870(array $s): bool      { return (bool)preg_match('/ATR|EJR/i', (string)$s['aircraft_tipo']); }
    /** Satena: B 1900 y DCH-6 (19 pax). */
    private static function es19(array $s): bool        { return (bool)preg_match('/1900|DCH/i', (string)$s['aircraft_tipo']); }
    private static function tipo(array $s, string $t): bool { return $s['tipo_atencion'] === $t; }
    private static function adic(array $s, string ...$nombres): float
    {
        $t = 0;
        foreach ($s['adicionales'] as $a) {
            if (in_array(self::norm($a['servicio']), array_map([self::class, 'norm'], $nombres), true)) {
                $t += (int)$a['cantidad'];
            }
        }
        return $t;
    }
    private static function uno(bool $c): int { return $c ? 1 : 0; }

    /** ACU (aire acondicionado): un solo campo con todas sus fracciones. */
    private static function conceptoAcu(): array
    {
        return ['ACU - Aire acondicionado (fracciones)', fn($s) => (float)$s['fracciones_hora_acu'] + (float)$s['fracciones_15min_acu']];
    }

    /** GPU (planta eléctrica): un solo campo con todas sus fracciones. */
    private static function conceptoGpu(): array
    {
        return ['GPU - Planta eléctrica (fracciones)', fn($s) => $s['fracciones_gpu']];
    }

    /**
     * Secciones y conceptos facturables de una aerolínea. Cada concepto es
     * [etiqueta, fn(servicio) => cantidad]: devuelve 1/0 para conteos de
     * "SI/NO" o de tipo de atención, o la cantidad/fracciones a sumar.
     *
     * @return array<int,array{titulo:string,bases?:array,excepto_bases?:array,conceptos:array}>
     */
    public function catalogo(string $aerolinea): array
    {
        $n = self::norm($aerolinea);

        if ($n === 'CLIC') {
            return [['titulo' => 'Clic', 'conceptos' => [
                ['Atención Rampa',                    fn($s) => self::uno(self::tipo($s, 'Tránsito'))],
                ['Vuelos Cancelados',                 fn($s) => self::uno(self::tipo($s, 'Cancelado'))],
                ['Hora Hombre',                       fn($s) => self::adic($s, 'Hora hombre')],
                self::conceptoGpu(),
                self::conceptoAcu(),
                ['Demora del min 91 al 210 (15%)',    fn($s) => self::uno((int)$s['demora_llegando'] >= 91 && (int)$s['demora_llegando'] <= 210)],
                ['Demora a partir del min 211 (25%)', fn($s) => self::uno((int)$s['demora_llegando'] >= 211)],
            ]]];
        }

        if ($n === 'SATENA') {
            return $this->catalogoSatena();
        }

        // Avianca, Regional Express y demás aerolíneas: catálogo general.
        return [['titulo' => 'Servicios generales', 'conceptos' => $this->conceptosGenerales()]];
    }

    private function conceptosGenerales(): array
    {
        return [
            ['Atención Rampa ATR',                         fn($s) => self::uno(self::tipo($s, 'Tránsito') && self::esAtr($s))],
            ['Atención Rampa A320',                        fn($s) => self::uno(self::tipo($s, 'Tránsito') && self::esA320($s))],
            ['Atención Pax',                               fn($s) => (int)$s['atencion_pasajeros']],
            self::conceptoAcu(),
            self::conceptoGpu(),
            ['Despacho Centralizado (no aplica Arauca y Quibdó)', fn($s) => self::uno((int)$s['despacho'] === 1 && !in_array($s['base'], self::BASES_SIN_DESPACHO, true))],
            ['Silla de Ruedas',                            fn($s) => (int)$s['sillas_ruedas']],
            ['Ventiladores/Extractores',                   fn($s) => self::uno((int)$s['ventiladores_activo'] === 1 || (int)$s['ventiladores'] > 0)],
            ['Rampa Escalera',                             fn($s) => self::uno((int)$s['rampa_escalera'] === 1) + self::adic($s, 'Escalera', 'Rampa escalera')],
            ['Cancelación Tránsito - Vuelos cancelados',   fn($s) => self::uno(self::tipo($s, 'Cancelado'))],
            ['Cancelación Pax - Pasajeros cancelados',     fn($s) => (int)$s['pax_cancelado']],
            ['Escala Técnica',                             fn($s) => self::uno(self::tipo($s, 'Escala técnica'))],
            ['Regreso Plataforma',                         fn($s) => self::uno(self::tipo($s, 'Regreso a plataforma'))],
            ['Pernocta',                                   fn($s) => self::adic($s, 'Pernocta')],
            ['Hora Hombre',                                fn($s) => self::adic($s, 'Hora hombre')],
            ['Arrancador Ad',                              fn($s) => self::adic($s, 'Arrancador ASU') + (int)$s['air_starter']],
            ['Cinta transportadora Conveyor',              fn($s) => self::adic($s, 'Cinta transportadora / Conveyor')],
            ['Push Back (Remolque de aeronave)',           fn($s) => (int)$s['remolque_aeronave'] + self::adic($s, 'Remolque')],
        ];
    }

    private function catalogoSatena(): array
    {
        $rampa4870 = ['Atención Rampa Avión 48/70 Pax', fn($s) => self::uno(self::tipo($s, 'Tránsito') && self::es4870($s))];
        $pax4870   = ['Atención Pax Avión 48/70',        fn($s) => self::es4870($s) ? (int)$s['atencion_pasajeros'] : 0];
        $rampa19   = ['Atención Rampa Avión 19 Pax',     fn($s) => self::uno(self::tipo($s, 'Tránsito') && self::es19($s))];
        $pax19     = ['Atención Pax Avión 19',           fn($s) => self::es19($s) ? (int)$s['atencion_pasajeros'] : 0];
        $acu30     = self::conceptoAcu();
        $silla     = ['Silla de Ruedas',                 fn($s) => (int)$s['sillas_ruedas']];
        $remAdic   = ['Remolque adicional por servicio', fn($s) => self::adic($s, 'Remolque')];
        $tractor   = ['Servicio de tractor 30 Min o frac', fn($s) => (int)$s['remolque_aeronave']];
        $gpu       = self::conceptoGpu();

        $aguaPotable = ['Svc de Agua Potable', fn($s) => (int)$s['potable']];
        $tractorSvc  = ['Svc de Tractor 30 min o fracción', fn($s) => (int)$s['remolque_aeronave']];
        $asistencia  = ['Asistencia en tierra, Remolque, Planta 28Vx40 Min', fn($s) => self::uno(self::tipo($s, 'Tránsito'))];
        $sillaDos    = ['Silla de Ruedas (tienen derecho a dos)', fn($s) => (int)$s['sillas_ruedas']];

        $secciones = [
            ['titulo' => 'Satena Villavicencio', 'bases' => ['VVC'], 'conceptos' => [
                $gpu,
                ['Svc Remolque aeronaves',    fn($s) => (int)$s['remolque_aeronave']],
                ['Svc Remolque equipaje',     fn($s) => (int)$s['remolque_equipajes']],
                ['Svc de agua potable',       fn($s) => (int)$s['potable']],
                ['Svc de drenaje',            fn($s) => (int)$s['drenaje']],
            ]],
            ['titulo' => 'Satena Yopal', 'bases' => ['EYP'], 'conceptos' => [
                $rampa4870, $pax4870, $rampa19, $pax19,
                ['Vuelo Cancelado 48/70 (40% sobre la neta)',       fn($s) => self::uno(self::tipo($s, 'Cancelado') && self::es4870($s))],
                ['Pasajeros Cancelado 48/70 (30% sobre la neta)',   fn($s) => (self::tipo($s, 'Cancelado') && self::es4870($s)) ? (int)$s['pax_cancelado'] : 0],
                $acu30, $silla, $remAdic, $tractor, $gpu,
            ]],
            ['titulo' => 'Satena Popayán', 'bases' => ['PPN'], 'conceptos' => [
                ['Svc Remolque aeronaves Ppn', fn($s) => (int)$s['remolque_aeronave']],
            ]],
            ['titulo' => 'Satena Valledupar', 'bases' => ['VUP'], 'conceptos' => [
                $rampa4870, $pax4870, $rampa19, $pax19, $acu30, $silla, $remAdic, $tractor, $gpu,
            ]],
            ['titulo' => 'Satena Riohacha', 'bases' => ['RCH'], 'conceptos' => [
                $asistencia, $aguaPotable, $sillaDos, $tractorSvc, $gpu,
            ]],
            ['titulo' => 'Montería Satena', 'bases' => ['MTR'], 'conceptos' => [
                $asistencia,
                ['Escala técnica',      fn($s) => self::uno(self::tipo($s, 'Escala técnica'))],
                ['Retorno a posición',  fn($s) => self::uno(self::tipo($s, 'Regreso a plataforma'))],
                $aguaPotable, $sillaDos, $tractorSvc, $gpu,
            ]],
        ];

        $conCatalogo = [];
        foreach ($secciones as $sec) {
            $conCatalogo = array_merge($conCatalogo, $sec['bases']);
        }
        $secciones[] = ['titulo' => 'Satena Otras bases', 'excepto_bases' => $conCatalogo, 'conceptos' => $this->conceptosGenerales()];
        return $secciones;
    }
}
