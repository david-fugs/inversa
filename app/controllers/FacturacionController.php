<?php
/**
 * FacturacionController - Dashboard de facturación por aerolínea
 * (cantidades de cada concepto facturable) con exportación a Excel.
 */

class FacturacionController extends Controller {
    /** Roles cuya vista queda restringida a su "Base Asociada". */
    private const ROLES_ESCOPADOS_A_BASE = ['Colaborador', 'Líder SVC'];

    private Facturacion   $model;
    private FlightService $servicios;

    public function __construct() {
        parent::__construct();
        // Por ahora solo el Administrador accede a Facturación.
        if (Session::get('user_rol') !== 'Administrador') {
            $this->redirectWith('flight-services', 'error', 'No tiene permiso para acceder a Facturación.');
            exit;
        }
        $this->model     = new Facturacion();
        $this->servicios = new FlightService();
    }

    private function baseScope(): ?string {
        $rol  = Session::get('user_rol');
        $base = Session::get('user_base_asociada');
        return (in_array($rol, self::ROLES_ESCOPADOS_A_BASE, true) && $base) ? $base : null;
    }

    /** Lista de textos no vacíos recibida por GET (acepta valor suelto o arreglo). */
    private function lista(string $key): array {
        $raw = $_GET[$key] ?? [];
        $raw = is_array($raw) ? $raw : [$raw];
        $out = [];
        foreach ($raw as $v) {
            $v = trim((string)$v);
            if ($v !== '') $out[] = $v;
        }
        return array_values(array_unique($out));
    }

    /** Filtros de la petición: fechas, aerolinea[], base[], tipo_avion[] y ajustes de campos. */
    private function filtros(): array {
        $aerolineas = $this->lista('aerolinea');
        $fecha = fn(string $k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $f = trim((string)($_GET[$k] ?? ''))) ? $f : '';
        return [
            'fecha_inicio' => $fecha('fecha_inicio'),
            'fecha_fin'    => $fecha('fecha_fin'),
            'aerolineas'   => $aerolineas,
            'bases'        => $this->lista('base'),
            'tipos_avion'  => $this->lista('tipo_avion'),
            'agregar'      => $this->lista('campo_agregar'),
            'quitar'       => $this->lista('campo_quitar'),
        ];
    }

    private function resumen(): array {
        $filtros = $this->filtros();
        $resumen = $this->model->resumir(
            $this->model->getServicios($filtros, $this->baseScope()),
            $filtros['agregar'],
            $filtros['quitar']
        );
        $resumen['filtros'] = $filtros;
        return $resumen;
    }

    public function index(): void {
        $opciones = $this->servicios->getDistinctBasesYAerolineas($this->baseScope());
        $campos = [];
        foreach ($this->model->biblioteca() as $label => $info) {
            $campos[] = ['label' => $label, 'grupo' => $info['grupo'], 'defecto' => $info['defecto']];
        }
        // Campos por defecto de cada aerolínea (unión de sus secciones), para que
        // el menú "Campos a mostrar" siga a las aerolíneas seleccionadas.
        $catalogoPorAerolinea = [];
        foreach ($opciones['aerolineas'] as $nombre) {
            $labels = [];
            foreach ($this->model->catalogo((string)$nombre) as $sec) {
                foreach ($sec['conceptos'] as $c) { $labels[$c[0]] = true; }
            }
            $catalogoPorAerolinea[$nombre] = array_keys($labels);
        }
        $this->view('facturacion/index', [
            'pageTitle'         => 'Facturación',
            'breadcrumbs'       => ['Facturación' => null],
            'aerolineasUniques' => $opciones['aerolineas'],
            'basesUniques'      => $opciones['bases'],
            'tiposAvion'        => $this->model->getTiposAvionPorAerolinea($this->baseScope()),
            'campos'            => $campos,
            'catalogoPorAerolinea' => $catalogoPorAerolinea,
        ]);
    }

    public function data(): void {
        $this->json($this->resumen());
    }

    public function exportExcel(): void {
        $r = $this->resumen();
        $f = $r['filtros'];

        // Hoja 1: los mismos servicios (y columnas) que el Excel de
        // /flight-services, con los filtros de esta pantalla.
        $base = $this->baseScope();
        $services = $base !== null ? $this->servicios->getAllWithJoinsByBase($base) : $this->servicios->getAllWithJoins();
        $ini = $f['fecha_inicio'] !== '' ? strtotime($f['fecha_inicio']) : null;
        $fin = $f['fecha_fin'] !== '' ? strtotime($f['fecha_fin']) : null;
        $services = array_values(array_filter($services, function ($s) use ($f, $ini, $fin) {
            $ts = strtotime(sprintf('%04d-%02d-%02d', (int)$s['anio'], (int)$s['mes'], (int)$s['dia']));
            if ($ini !== null && $ts < $ini) return false;
            if ($fin !== null && $ts > $fin) return false;
            if ($f['bases'] && !in_array($s['base'], $f['bases'], true)) return false;
            if ($f['aerolineas'] && !in_array($s['airline_nombre'], $f['aerolineas'], true)) return false;
            if ($f['tipos_avion'] && !in_array($s['aircraft_tipo'], $f['tipos_avion'], true)) return false;
            return true;
        }));

        // Hoja 2: el resumen de facturación actual.
        (new FlightServicesController())->downloadFacturacionExcel($services, $f, $r);
    }
}
