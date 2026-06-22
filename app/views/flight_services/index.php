<?php
$esColaborador  = Session::get('user_rol') === 'Colaborador';
$puedeEditar    = (bool)Session::get('user_puede_editar');

$meses = FlightService::$meses;
$basesUniques = [];
foreach ($services as $s) {
    if (!in_array($s['base'], $basesUniques)) {
        $basesUniques[] = $s['base'];
    }
}
sort($basesUniques);
?>
<div class="page-actions">
    <a href="<?= BASE_URL ?>/flight-services/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Servicio
    </a>
</div>

<!-- ══ FILTROS ══════════════════════════════════════ -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="filter_fecha" class="form-label">Fecha</label>
                <input type="date" class="form-control" id="filter_fecha" placeholder="Seleccionar fecha">
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
                                    <?php if (!$esColaborador || $puedeEditar): ?>
                                    <a href="<?= BASE_URL ?>/flight-services/edit/<?= $s['id'] ?>"
                                       class="btn btn-icon btn-outline-secondary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!$esColaborador): ?>
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
    // Sistema de filtros
    const filterInputs = {
        fecha: document.getElementById('filter_fecha'),
        base: document.getElementById('filter_base'),
    };

    const originalRows = Array.from(document.querySelectorAll('#tableServices tbody tr'));
    const badgeRegistros = document.getElementById('badge_registros');
    const mesesNombre = {
        'Enero': 1, 'Febrero': 2, 'Marzo': 3, 'Abril': 4, 'Mayo': 5, 'Junio': 6,
        'Julio': 7, 'Agosto': 8, 'Septiembre': 9, 'Octubre': 10, 'Noviembre': 11, 'Diciembre': 12
    };

    function aplicarFiltros() {
        // Parsear fecha seleccionada (formato: YYYY-MM-DD)
        let filtroFecha = null;
        if (filterInputs.fecha.value) {
            const [anio, mes, dia] = filterInputs.fecha.value.split('-');
            filtroFecha = {
                anio: parseInt(anio),
                mes: parseInt(mes),
                dia: parseInt(dia)
            };
        }

        const filtroBase = filterInputs.base.value || null;
        let visibles = 0;

        originalRows.forEach(row => {
            const diaMatch = row.querySelector('td:nth-child(2)');
            const baseMatch = row.querySelector('td:nth-child(3)');

            if (!diaMatch || !baseMatch) return;

            const rowFecha = diaMatch.textContent.trim();
            const rowBase = baseMatch.textContent.trim();

            let mostrar = true;

            // Filtrar por fecha si está seleccionada
            if (filtroFecha) {
                const [diaStr, mesStr, anioStr] = rowFecha.split('/');
                const mesNum = mesesNombre[mesStr] || null;
                const rowDia = parseInt(diaStr);
                const rowMes = mesNum;
                const rowAnio = parseInt(anioStr);

                mostrar = mostrar &&
                    (rowDia === filtroFecha.dia &&
                    rowMes === filtroFecha.mes &&
                    rowAnio === filtroFecha.anio);
            }

            // Filtrar por base
            if (filtroBase) {
                mostrar = mostrar && rowBase.includes(filtroBase);
            }

            row.style.display = mostrar ? '' : 'none';
            if (mostrar) visibles++;
        });

        badgeRegistros.textContent = visibles + ' registros';
    }

    // Event listeners para los filtros
    filterInputs.fecha.addEventListener('change', aplicarFiltros);
    filterInputs.base.addEventListener('change', aplicarFiltros);

    // Botón limpiar filtros
    document.getElementById('btn_limpiar_filtros').addEventListener('click', () => {
        filterInputs.fecha.value = '';
        filterInputs.base.value = '';
        aplicarFiltros();
    });
</script>
