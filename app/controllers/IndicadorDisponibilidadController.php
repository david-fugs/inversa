<?php
/**
 * IndicadorDisponibilidadController - calendario mensual de disponibilidad y
 * confiabilidad de equipos, con el registro de las novedades diarias
 * (inoperativo, correctivo, preventivo).
 */

class IndicadorDisponibilidadController extends Controller {
    use FiltrosRegistros;

    private DisponibilidadRegistro $model;
    private Equipo $equipoModel;
    private Base $baseModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        // Solo Administrador y Operador (los roles del módulo de Pagos no acceden aquí).
        if (!in_array(Session::get('user_rol'), ['Administrador', 'Operador'], true)) {
            $this->redirectWith('flight-services', 'error', 'No tiene permiso para esta acción.');
            exit;
        }
        $this->model       = new DisponibilidadRegistro();
        $this->equipoModel = new Equipo();
        $this->baseModel   = new Base();
    }

    // ───────────────────────────── Calendario ──────────────────────────

    /** Filtros de la URL: mes, año, base y si solo se cuentan los días con novedad registrada. */
    private function filtros(): array {
        $mes  = (int)($_GET['mes'] ?? date('n'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        return [
            'mes'            => ($mes >= 1 && $mes <= 12) ? $mes : (int)date('n'),
            'anio'           => ($anio >= 2000 && $anio <= 2100) ? $anio : (int)date('Y'),
            'base_id'        => (int)($_GET['base_id'] ?? 0),
            'solo_registros' => ($_GET['solo_registros'] ?? '') === '1',
        ];
    }

    /** Filas (equipo x día) del mes según los filtros. */
    private function filasFiltradas(array $f): array {
        $equipos = array_values(array_filter(
            $this->equipoModel->getAll(),
            fn($e) => !$f['base_id'] || (int)$e['base_id'] === $f['base_id']
        ));
        $registros = $this->model->delMes($f['anio'], $f['mes'], $f['base_id']);
        return [$equipos, $this->model->filasDelMes($f['anio'], $f['mes'], $equipos, $registros, !$f['solo_registros'])];
    }

    public function index(): void {
        $f = $this->filtros();
        [$equipos, $filas] = $this->filasFiltradas($f);

        // Totales por día (calendario) y por equipo (tabla).
        $filasPorDia = [];
        $filasPorEquipo = [];
        foreach ($filas as $fila) {
            $filasPorDia[$fila['dia']][] = $fila;
            $filasPorEquipo[$fila['equipo_id']][] = $fila;
        }
        $dias = [];
        foreach ($filasPorDia as $dia => $lista) {
            $t = DisponibilidadRegistro::totales($lista);
            $t['registros_dia'] = array_values(array_map(fn($x) => [
                'id' => $x['registro_id'], 'base' => $x['base_nombre'], 'equipo' => $x['equipo_nombre'],
                'cantidad' => $x['cantidad'], 'inoperativo' => $x['inoperativo'], 'correctivo' => $x['correctivo'],
                'preventivo' => $x['preventivo'], 'disponibilidad' => $x['disponibilidad'], 'confiabilidad' => $x['confiabilidad'],
            ], $lista));
            $dias[$dia] = $t;
        }
        $porEquipo = [];
        foreach ($equipos as $e) {
            $lista = $filasPorEquipo[(int)$e['id']] ?? [];
            $t = DisponibilidadRegistro::totales($lista);
            $t['dias'] = count($lista);
            $t['equipo'] = $e;
            $porEquipo[] = $t;
        }

        $this->view('indicador_disponibilidad/index', [
            'pageTitle'   => 'Indicador de Disponibilidad',
            'breadcrumbs' => ['Indicador de Disponibilidad' => null],
            'filtros'     => $f,
            'bases'       => $this->baseModel->getAll(),
            'dias'        => $dias,
            'totalMes'    => DisponibilidadRegistro::totales($filas),
            'porEquipo'   => $porEquipo,
            'hayEquipos'  => !empty($equipos),
        ], 'main');
    }

    /** Excel con una fila por equipo y día (mismas columnas del archivo Disponibilidad.xlsx), con los filtros de la pantalla. */
    public function exportExcel(): void {
        $f = $this->filtros();
        [, $filas] = $this->filasFiltradas($f);
        $headers = ['Fecha', 'Base', 'Equipo', 'Cantidad', 'Operativo', 'Inoperativo', 'Correctivo', 'Preventivo', 'Disponibilidad (%)', 'Confiabilidad (%)'];
        $rows = [];
        foreach ($filas as $x) {
            $rows[] = [
                date('d/m/Y', strtotime($x['fecha'])), $x['base_nombre'], $x['equipo_nombre'], $x['cantidad'],
                $x['cantidad'] - $x['inoperativo'], $x['inoperativo'], $x['correctivo'], $x['preventivo'],
                $x['disponibilidad'] ?? '', $x['confiabilidad'] ?? '',
            ];
        }
        $this->enviarXlsx('Disponibilidad', sprintf('disponibilidad_%04d_%02d', $f['anio'], $f['mes']), $headers, $rows);
    }

    // ───────────────────────────── Registro de novedades ───────────────

    public function createForm(): void {
        $fecha = trim((string)($_GET['fecha'] ?? ''));
        $this->renderForm(null, [], [
            'base_id'     => (int)($_GET['base_id'] ?? 0) ?: '',
            'equipo_id'   => '',
            'fecha'       => preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : date('Y-m-d'),
            'inoperativo' => '0', 'correctivo' => '0', 'preventivo' => '0',
        ]);
    }

    public function store(): void {
        $data = $this->datos();
        [$errors, $equipo] = $this->validar($data);
        if ($errors) {
            $this->renderForm(null, $errors, $data);
            return;
        }
        $data['cantidad'] = (int)$equipo['cantidad'];
        $this->model->create($data, (int)Session::get('user_id'));
        $this->volver($data['fecha'], $data['base_id'], 'Novedad registrada correctamente.');
    }

    public function editForm(string $id): void {
        $reg = $this->model->findById((int)$id);
        if (!$reg) {
            $this->redirectWith('indicador-disponibilidad', 'error', 'Registro no encontrado.');
            return;
        }
        $this->renderForm($reg, [], $reg);
    }

    public function update(string $id): void {
        $reg = $this->model->findById((int)$id);
        if (!$reg) {
            $this->redirectWith('indicador-disponibilidad', 'error', 'Registro no encontrado.');
            return;
        }
        $data = $this->datos();
        [$errors, $equipo] = $this->validar($data, (int)$reg['id'], (int)$reg['equipo_id'] === $data['equipo_id'] ? (int)$reg['cantidad'] : null);
        if ($errors) {
            $this->renderForm($reg, $errors, $data);
            return;
        }
        // Si es el mismo equipo se conserva la cantidad con la que se registró.
        $data['cantidad'] = (int)$reg['equipo_id'] === $data['equipo_id'] ? (int)$reg['cantidad'] : (int)$equipo['cantidad'];
        $this->model->update((int)$reg['id'], $data);
        $this->volver($data['fecha'], $data['base_id'], 'Novedad actualizada correctamente.');
    }

    public function delete(string $id): void {
        $reg = $this->model->findById((int)$id);
        if (!$reg) {
            $this->redirectWith('indicador-disponibilidad', 'error', 'Registro no encontrado.');
            return;
        }
        $this->model->delete((int)$reg['id']);
        $this->volver($reg['fecha'], (int)$reg['base_id'], 'Novedad eliminada correctamente.');
    }

    /** Vuelve al calendario en el mes y la base del registro. */
    private function volver(string $fecha, int $baseId, string $mensaje): void {
        $ts = strtotime($fecha);
        $this->redirectWith(sprintf('indicador-disponibilidad?mes=%d&anio=%d&base_id=%d', date('n', $ts), date('Y', $ts), $baseId), 'success', $mensaje);
    }

    private function datos(): array {
        $n = fn(string $k) => trim((string)($_POST[$k] ?? ''));
        return [
            'base_id'     => (int)($_POST['base_id'] ?? 0),
            'equipo_id'   => (int)($_POST['equipo_id'] ?? 0),
            'fecha'       => $n('fecha'),
            'inoperativo' => $n('inoperativo') === '' ? '0' : $n('inoperativo'),
            'correctivo'  => $n('correctivo') === '' ? '0' : $n('correctivo'),
            'preventivo'  => $n('preventivo') === '' ? '0' : $n('preventivo'),
        ];
    }

    /** @return array{0:array,1:array|false} errores y el equipo elegido */
    private function validar(array &$d, int $selfId = 0, ?int $cantidadRegistro = null): array {
        $errors = [];
        if (!$d['base_id'] || !$this->baseModel->findById($d['base_id'])) {
            $errors['base_id'] = 'Seleccione la base.';
        }
        $equipo = $d['equipo_id'] ? $this->equipoModel->findById($d['equipo_id']) : false;
        if (!$equipo) {
            $errors['equipo_id'] = 'Seleccione el equipo.';
        } elseif ($d['base_id'] && (int)$equipo['base_id'] !== $d['base_id']) {
            $errors['equipo_id'] = 'El equipo no pertenece a la base seleccionada.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha']) || !strtotime($d['fecha'])) {
            $errors['fecha'] = 'La fecha es obligatoria.';
        } elseif ($d['fecha'] > date('Y-m-d')) {
            $errors['fecha'] = 'La fecha no puede ser futura.';
        } elseif ($equipo && $this->model->existe($d['equipo_id'], $d['fecha'], $selfId)) {
            $errors['fecha'] = 'Ya hay una novedad de este equipo en esa fecha. Edítela desde el calendario.';
        }
        $max = $cantidadRegistro ?? ($equipo ? (int)$equipo['cantidad'] : 0);
        foreach (['inoperativo' => 'Inoperativo', 'correctivo' => 'Correctivo', 'preventivo' => 'Preventivo'] as $k => $label) {
            if (!ctype_digit($d[$k])) {
                $errors[$k] = "$label debe ser un número entero (0 o más).";
            } elseif ($equipo && (int)$d[$k] > $max) {
                $errors[$k] = "$label no puede ser mayor a la cantidad del equipo ($max).";
            } else {
                $d[$k] = (int)$d[$k];
            }
        }
        return [$errors, $equipo];
    }

    private function renderForm(?array $reg, array $errors, array $old): void {
        $this->view('indicador_disponibilidad/form', [
            'pageTitle'   => $reg ? 'Editar Novedad' : 'Nueva Novedad',
            'breadcrumbs' => ['Indicador de Disponibilidad' => BASE_URL . '/indicador-disponibilidad', $reg ? 'Editar' : 'Nueva' => null],
            'registro'    => $reg,
            'bases'       => $this->baseModel->getAll(),
            'equipos'     => $this->equipoModel->getAll(),
            'errors'      => $errors,
            'old'         => $old,
            'mostrarCantidad' => true,
        ], 'main');
    }
}
