<?php
$rolActual         = Session::get('user_rol');
$esColaborador     = $rolActual === 'Colaborador';
$esVisualizador    = $rolActual === 'Visualizador';
$esSupervisorRampa = $rolActual === 'Líder SVC';
$puedeEditar       = (bool)Session::get('user_puede_editar');

$meses = FlightService::$meses;
?>
<div class="page-actions">
    <?php if (!$esSupervisorRampa): ?>
    <a href="<?= BASE_URL ?>/flight-services/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Servicio
    </a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/flight-services/dashboard" class="btn btn-outline-primary">
        <i class="bi bi-bar-chart-line-fill"></i> Panel Analítico
    </a>
    <?php if ($rolActual === 'Administrador'): ?>
    <a href="<?= BASE_URL ?>/flight-services/import" class="btn btn-outline-secondary">
        <i class="bi bi-file-earmark-arrow-up-fill"></i> Importar Excel
    </a>
    <?php endif; ?>
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
        <span class="badge badge-primary" id="badge_registros">&hellip;</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table" id="tableServices" style="width:100%">
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
                        <th class="text-center">Archivo</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Esta tabla NO usa el auto-init genérico de `.data-table` (ver app.js):
// carga los registros por AJAX con paginación/orden/filtro resueltos en el
// servidor, en vez de traer todos los registros al navegador y ordenarlos
// ahí (con muchos registros eso hacía la carga muy lenta).
// Este bloque se renderiza en el <body>, antes de que se carguen jQuery y
// DataTables (que van al final del layout); por eso se espera "load".
window.addEventListener('load', function () {
    const esVisualizador    = <?= json_encode($esVisualizador) ?>;
    const esColaborador     = <?= json_encode($esColaborador) ?>;
    const esSupervisorRampa = <?= json_encode($esSupervisorRampa) ?>;
    const puedeEditar       = <?= json_encode($puedeEditar) ?>;
    const puedeEditarFila   = !esVisualizador && !esSupervisorRampa && (!esColaborador || puedeEditar);
    const puedeEliminarFila = !esVisualizador && !esColaborador && !esSupervisorRampa;
    const meses = <?= json_encode(FlightService::$meses, JSON_UNESCAPED_UNICODE) ?>;

    function esc(v) {
        return $('<div>').text(v === null || v === undefined ? '' : String(v)).html();
    }

    const filterInputs = {
        fechaInicio: document.getElementById('filter_fecha_inicio'),
        fechaFin: document.getElementById('filter_fecha_fin'),
        base: document.getElementById('filter_base'),
        aerolinea: document.getElementById('filter_aerolinea'),
    };

    const badgeRegistros = document.getElementById('badge_registros');

    const table = $('#tableServices').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
            emptyTable: 'No hay registros disponibles',
            processing: 'Cargando...'
        },
        responsive: true,
        dom: '<"row align-items-center mb-3"<"col-sm-6"l><"col-sm-6 text-end"f>>rtip',
        pageLength: 15,
        processing: true,
        serverSide: true,
        order: [[1, 'desc']],
        ajax: {
            url: BASE_URL + '/flight-services/data',
            data: function (d) {
                d.fecha_inicio = filterInputs.fechaInicio.value;
                d.fecha_fin    = filterInputs.fechaFin.value;
                d.base         = filterInputs.base.value;
                d.aerolinea    = filterInputs.aerolinea.value;
            }
        },
        columns: [
            { data: 'id', orderable: true, render: (d) => '<strong>#' + esc(d) + '</strong>' },
            {
                data: null, orderable: true,
                render: (d, type, row) => sprintf02(row.dia) + '/' + (meses[row.mes] || row.mes) + '/' + row.anio
                    + '<small class="d-block text-muted">' + (row.quincena == 1 ? '1ª Quincena' : '2ª Quincena') + '</small>'
            },
            { data: 'base', orderable: true, render: (d) => '<span class="badge badge-primary">' + esc(d) + '</span>' },
            { data: 'airline_nombre', orderable: true, render: (d) => esc(d) },
            {
                data: null, orderable: true,
                render: (d, type, row) => '<div><small class="text-muted">↓</small> <strong>' + esc(row.vuelo_llegando) + '</strong></div>'
                    + '<div><small class="text-muted">↑</small> ' + esc(row.vuelo_saliendo) + '</div>'
            },
            { data: 'matricula', orderable: true, render: (d) => '<code>' + esc(d) + '</code>' },
            { data: 'aircraft_tipo', orderable: true, render: (d) => esc(d) },
            { data: 'tipo_atencion', orderable: true, render: (d) => '<span class="badge badge-info">' + esc(d) + '</span>' },
            {
                data: 'tiempo_transito', orderable: true,
                render: (d) => d !== null ? '<span class="time-display">' + esc(d) + ' min</span>' : '<span class="text-muted">—</span>'
            },
            {
                data: 'cumple_tiempo', orderable: true,
                render: (d) => d === null
                    ? '<span class="text-muted">—</span>'
                    : (Number(d) ? '<span class="cumple-si"><i class="bi bi-check-circle-fill"></i> SI</span>' : '<span class="cumple-no"><i class="bi bi-x-circle-fill"></i> NO</span>')
            },
            {
                data: 'archivo_pdf', orderable: false, className: 'text-center',
                render: (d) => d
                    ? '<span class="cumple-si" title="Tiene archivo adjunto"><i class="bi bi-check-circle-fill"></i></span>'
                    : '<span class="cumple-no" title="Sin archivo adjunto"><i class="bi bi-x-circle-fill"></i></span>'
            },
            {
                data: 'id', orderable: false, className: 'text-center',
                render: (id) => {
                    let html = '<div class="d-flex gap-1 justify-content-center">';
                    html += '<a href="' + BASE_URL + '/flight-services/view/' + id + '" class="btn btn-icon btn-outline-primary btn-sm" title="Ver detalle"><i class="bi bi-eye-fill"></i></a>';
                    if (puedeEditarFila) {
                        html += '<a href="' + BASE_URL + '/flight-services/edit/' + id + '" class="btn btn-icon btn-outline-secondary btn-sm" title="Editar"><i class="bi bi-pencil-fill"></i></a>';
                    }
                    if (puedeEliminarFila) {
                        html += '<a href="' + BASE_URL + '/flight-services/delete/' + id + '" class="btn btn-icon btn-danger btn-sm" title="Eliminar" data-confirm="¿Está seguro de eliminar el servicio #' + id + '?"><i class="bi bi-trash-fill"></i></a>';
                    }
                    html += '</div>';
                    return html;
                }
            }
        ]
    });

    function sprintf02(n) {
        n = parseInt(n, 10) || 0;
        return n < 10 ? '0' + n : String(n);
    }

    table.on('xhr', function () {
        const json = table.ajax.json();
        if (json) badgeRegistros.textContent = json.recordsFiltered + ' registros';
    });

    function aplicarFiltros() {
        table.ajax.reload();
    }

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
