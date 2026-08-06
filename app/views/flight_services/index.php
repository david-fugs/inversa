<?php
$esColaborador  = Session::get('user_rol') === 'Colaborador';
$esVisualizador = Session::get('user_rol') === 'Visualizador';
$puedeEditar    = (bool)Session::get('user_puede_editar');

$meses = FlightService::$meses;
$basesUniques = [];
$aerolineasUniques = [];
foreach ($services as $s) {
    if (!in_array($s['base'], $basesUniques)) {
        $basesUniques[] = $s['base'];
    }
    if (!in_array($s['airline_nombre'], $aerolineasUniques)) {
        $aerolineasUniques[] = $s['airline_nombre'];
    }
}
sort($basesUniques);
sort($aerolineasUniques);
?>
<div class="page-actions">
    <a href="<?= BASE_URL ?>/flight-services/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Servicio
    </a>
    <a href="<?= BASE_URL ?>/flight-services/dashboard" class="btn btn-outline-primary">
        <i class="bi bi-bar-chart-line-fill"></i> Panel Analítico
    </a>
    <a href="#" id="btn_exportar_excel" class="btn btn-success">
        <i class="bi bi-file-earmark-excel-fill"></i> Exportar a Excel
    </a>
</div>

<!-- ══ FILTROS ══════════════════════════════════════ -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6>
    </div>
    <div class="card-body">
        <?php $fechaInicio = trim($_GET['fecha_inicio'] ?? ''); $fechaFin = trim($_GET['fecha_fin'] ?? ''); ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Rango de Fecha</label>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control" id="filter_fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>" placeholder="Fecha inicio">
                        <input type="date" class="form-control" id="filter_fecha_fin" value="<?= htmlspecialchars($fechaFin) ?>" placeholder="Fecha fin">
                    </div>
                </div>
            <div class="col-md-3">
                <label for="filter_base" class="form-label">Base</label>
                <select class="form-select" id="filter_base">
                    <option value="">-- Todas --</option>
                    <?php foreach ($basesUniques as $base): ?>
                        <option value="<?= htmlspecialchars($base) ?>"><?= htmlspecialchars($base) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_aerolinea" class="form-label">Aerolínea</label>
                <select class="form-select" id="filter_aerolinea">
                    <option value="">-- Todas --</option>
                    <?php foreach ($aerolineasUniques as $aerolinea): ?>
                        <option value="<?= htmlspecialchars($aerolinea) ?>"><?= htmlspecialchars($aerolinea) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btn_limpiar_filtros">
                    <i class="bi bi-arrow-counterclockwise"></i> Limpiar Filtros
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-clipboard2-pulse-fill"></i> Servicios de Vuelo</h5>
        <span class="badge badge-primary" id="badge_registros"><?= count($services) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" id="tableServices" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Base</th>
                        <th>Aerolínea</th>
                        <th>Vuelo</th>
                        <th>Matrícula</th>
                        <th>Tipo Avión</th>
                        <th>Tipo Atención</th>
                        <th>Tránsito</th>
                        <th>Cumple</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                        <tr>
                            <td><strong>#<?= $s['id'] ?></strong></td>
                            <td>
                                <?php
                                    $meses = FlightService::$meses;
                                    echo sprintf('%02d/%s/%s', $s['dia'], $meses[$s['mes']] ?? $s['mes'], $s['anio']);
                                ?>
                                <small class="d-block text-muted"><?= $s['quincena'] == 1 ? '1ª Quincena' : '2ª Quincena' ?></small>
                            </td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($s['base']) ?></span></td>
                            <td><?= htmlspecialchars($s['airline_nombre']) ?></td>
                            <td>
                                <div>
                                    <small class="text-muted">↓</small> <strong><?= htmlspecialchars($s['vuelo_llegando']) ?></strong>
                                </div>
                                <div>
                                    <small class="text-muted">↑</small> <?= htmlspecialchars($s['vuelo_saliendo']) ?>
                                </div>
                            </td>
                            <td><code><?= htmlspecialchars($s['matricula']) ?></code></td>
                            <td><?= htmlspecialchars($s['aircraft_tipo']) ?></td>
                            <td>
                                <span class="badge badge-info"><?= htmlspecialchars($s['tipo_atencion']) ?></span>
                            </td>
                            <td>
                                <?php if ($s['tiempo_transito'] !== null): ?>
                                    <span class="time-display"><?= $s['tiempo_transito'] ?> min</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['cumple_tiempo'] === null): ?>
                                    <span class="text-muted">—</span>
                                <?php elseif ($s['cumple_tiempo']): ?>
                                    <span class="cumple-si"><i class="bi bi-check-circle-fill"></i> SI</span>
                                <?php else: ?>
                                    <span class="cumple-no"><i class="bi bi-x-circle-fill"></i> NO</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/flight-services/view/<?= $s['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Ver detalle">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <?php if (!$esVisualizador && (!$esColaborador || $puedeEditar)): ?>
                                    <a href="<?= BASE_URL ?>/flight-services/edit/<?= $s['id'] ?>"
                                       class="btn btn-icon btn-outline-secondary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!$esVisualizador && !$esColaborador): ?>
                                    <a href="<?= BASE_URL ?>/flight-services/delete/<?= $s['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar el servicio #<?= $s['id'] ?>?">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Este bloque se renderiza en el <body>, antes de que se carguen jQuery,
// DataTables y app.js (que van al final del layout). Se espera al evento
// "load" para que app.js ya haya inicializado la tabla como DataTable.
window.addEventListener('load', function () {
    // Sistema de filtros — integrado con la API de DataTables para que
    // funcione correctamente junto con la paginación (no basta con ocultar
    // filas por CSS: DataTables solo mantiene en el DOM las filas de la
    // página actual, por lo que ocultar filas "a mano" ignora las que están
    // en otras páginas).
    const table = $('#tableServices').DataTable();

    const filterInputs = {
        fechaInicio: document.getElementById('filter_fecha_inicio'),
        fechaFin: document.getElementById('filter_fecha_fin'),
        base: document.getElementById('filter_base'),
        aerolinea: document.getElementById('filter_aerolinea'),
    };

    const badgeRegistros = document.getElementById('badge_registros');
    const mesesNombre = {
        'Enero': 1, 'Febrero': 2, 'Marzo': 3, 'Abril': 4, 'Mayo': 5, 'Junio': 6,
        'Julio': 7, 'Agosto': 8, 'Septiembre': 9, 'Octubre': 10, 'Noviembre': 11, 'Diciembre': 12
    };

    $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
        if (settings.nTable.id !== 'tableServices') return true;

        // Filtrado por rango de fecha (inclusive)
        if (filterInputs.fechaInicio.value || filterInputs.fechaFin.value) {
            const [diaStr, mesStr, anioStr] = $(table.cell(dataIndex, 1).node()).clone().find('small').remove().end().text().trim().split('/');
            const rowDia = parseInt(diaStr, 10);
            const rowMes = mesesNombre[mesStr] || null;
            const rowAnio = parseInt(anioStr, 10);
            if (!rowMes) return true; // fall back if parsing falla
            const rowDate = new Date(rowAnio, rowMes - 1, rowDia);

            if (filterInputs.fechaInicio.value) {
                const [sY, sM, sD] = filterInputs.fechaInicio.value.split('-').map(Number);
                const startDate = new Date(sY, sM - 1, sD);
                if (rowDate < startDate) return false;
            }
            if (filterInputs.fechaFin.value) {
                const [eY, eM, eD] = filterInputs.fechaFin.value.split('-').map(Number);
                const endDate = new Date(eY, eM - 1, eD);
                if (rowDate > endDate) return false;
            }
        }

        if (filterInputs.base.value) {
            const rowBase = $(table.cell(dataIndex, 2).node()).text().trim();
            if (!rowBase.includes(filterInputs.base.value)) return false;
        }

        if (filterInputs.aerolinea.value) {
            const rowAerolinea = $(table.cell(dataIndex, 3).node()).text().trim();
            if (rowAerolinea !== filterInputs.aerolinea.value) return false;
        }

        return true;
    });

    function aplicarFiltros() {
        table.draw();
    }

    table.on('draw', function () {
        badgeRegistros.textContent = table.rows({ search: 'applied' }).count() + ' registros';
    });

    // Event listeners para los filtros
    filterInputs.fechaInicio.addEventListener('change', aplicarFiltros);
    filterInputs.fechaFin.addEventListener('change', aplicarFiltros);
    filterInputs.base.addEventListener('change', aplicarFiltros);
    filterInputs.aerolinea.addEventListener('change', aplicarFiltros);

    // Botón limpiar filtros
    document.getElementById('btn_limpiar_filtros').addEventListener('click', () => {
        filterInputs.fechaInicio.value = '';
        filterInputs.fechaFin.value = '';
        filterInputs.base.value = '';
        filterInputs.aerolinea.value = '';
        aplicarFiltros();
    });

    // Exportar a Excel respetando los filtros activos
    document.getElementById('btn_exportar_excel').addEventListener('click', (e) => {
        e.preventDefault();
        const params = new URLSearchParams();
        if (filterInputs.fechaInicio.value) params.set('fecha_inicio', filterInputs.fechaInicio.value);
        if (filterInputs.fechaFin.value) params.set('fecha_fin', filterInputs.fechaFin.value);
        if (filterInputs.base.value) params.set('base', filterInputs.base.value);
        if (filterInputs.aerolinea.value) params.set('aerolinea', filterInputs.aerolinea.value);
        window.location.href = BASE_URL + '/flight-services/export?' + params.toString();
    });
});
</script>
