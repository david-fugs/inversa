<?php
/** Panel por aerolínea (AVIANCA / CLIC / SATENA).
 *  Requiere: $slug, $cfg, $etiquetas (clave => etiqueta), $basesUniques,
 *  $aniosUniques, $codigosDemoraUniques, $codigosDemoraDesc. */
$navActivo = $slug;
require __DIR__ . '/_dashboard_nav.php';
$color = $cfg['color'];
?>
<style>
.pa-header { display:flex; align-items:center; gap:16px; margin-bottom:16px; }
.pa-header img { height:56px; width:auto; }
.pa-header h4 { margin:0; }
.pa-table { width:100%; font-size:13px; border-collapse:collapse; }
.pa-table th { background: <?= $color ?>; color:#fff; padding:6px 10px; text-align:center; font-weight:600; white-space:nowrap; }
.pa-table td { padding:5px 10px; border-bottom:1px solid #e1e0d9; text-align:center; font-variant-numeric:tabular-nums; }
.pa-table td:first-child { text-align:center; font-weight:600; }
.pa-table tfoot td { font-weight:700; border-top:2px solid <?= $color ?>; border-bottom:none; }
.pa-chart { position:relative; height:300px; }
.pa-empty { color:#898781; padding:24px; text-align:center; }
</style>

<div class="pa-header">
    <img src="<?= BASE_URL ?>/img/<?= htmlspecialchars($cfg['logo']) ?>" alt="<?= htmlspecialchars($cfg['nombre']) ?>"
         style="<?= $slug === 'avianca' ? 'object-fit:contain;background:linear-gradient(90deg,#e8001c 0%,#d4004a 55%,#b0007f 100%);padding:8px 16px;border-radius:8px;' : '' ?>">
    <div>
        <h4>Panel <?= htmlspecialchars($cfg['nombre']) ?></h4>
        <?php if ($slug === 'satena'): ?>
            <small class="text-muted">Las bases PPN y VVC no se cuentan como vuelos; el resto de los datos sí se cuenta.</small>
        <?php endif; ?>
    </div>
</div>

<div class="viz-root">

<!-- ══ FILTROS ══════════════════════════════════════ -->
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label for="pa_anio" class="form-label">Año</label>
                <select class="form-select" id="pa_anio">
                    <option value="">-- Todos --</option>
                    <?php foreach ($aniosUniques as $a): ?>
                        <option value="<?= (int)$a ?>"><?= (int)$a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="pa_mes" class="form-label">Mes</label>
                <select class="form-select" id="pa_mes">
                    <option value="">-- Todos --</option>
                    <?php foreach (['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $m): ?>
                        <option value="<?= $i + 1 ?>"><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="pa_fecha_inicio" class="form-label">Fecha inicio</label>
                <input type="date" class="form-control" id="pa_fecha_inicio">
            </div>
            <div class="col-md-2">
                <label for="pa_fecha_fin" class="form-label">Fecha fin</label>
                <input type="date" class="form-control" id="pa_fecha_fin">
            </div>
            <div class="col-md-2">
                <label class="form-label">Bases</label>
                <div class="dropdown" id="pa_bases">
                    <button type="button" class="btn btn-outline-secondary form-select text-start"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <span id="pa_bases_label">-- Todas --</span>
                    </button>
                    <div class="dropdown-menu p-2 w-100" style="max-height:300px;overflow-y:auto;min-width:180px">
                        <?php foreach ($basesUniques as $i => $b): ?>
                            <div class="form-check">
                                <input class="form-check-input pa-base-check" type="checkbox" id="pa_base_<?= $i ?>" value="<?= htmlspecialchars($b) ?>">
                                <label class="form-check-label w-100" for="pa_base_<?= $i ?>"><?= htmlspecialchars($b) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Código Demora</label>
                <?php require __DIR__ . '/_codigo_demora_multiselect.php'; ?>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="pa_limpiar">
                    <i class="bi bi-arrow-counterclockwise"></i> Limpiar Filtros
                </button>
            </div>
        </div>
    </div>
</div>

<div id="pa_loading" class="text-muted mb-3">Cargando…</div>

<!-- ══ KPIs ═════════════════════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon primary"><i class="bi bi-clipboard2-pulse-fill"></i></div>
        <div class="stat-info"><p>Total Vuelos</p><h3 id="pa_kpi_total">0</h3></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-info"><p>Cumplimiento de Tiempo</p><h3 id="pa_kpi_cumplimiento">—</h3></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon info"><i class="bi bi-stopwatch-fill"></i></div>
        <div class="stat-info"><p>Tránsito Promedio</p><h3 id="pa_kpi_transito">—</h3></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon secondary"><i class="bi bi-people-fill"></i></div>
        <div class="stat-info"><p>Total PAX Saliendo</p><h3 id="pa_kpi_pax">0</h3></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="stat-info"><p>Vuelos con Demora</p><h3 id="pa_kpi_demoras">0</h3></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon danger"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-info"><p>Demora Promedio Llegando</p><h3 id="pa_kpi_demora_promedio">—</h3></div></div></div>
</div>

<!-- ══ VUELOS / PASAJEROS (mes × año) ═══════════════ -->
<div class="row g-3 mb-3">
    <?php foreach ($cfg['tarjetas'] as $k): ?>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><?= htmlspecialchars(strtoupper($etiquetas[$k]) . ' ' . $cfg['nombre']) ?></h5></div>
            <div class="card-body">
                <div class="table-responsive" id="pa_tabla_<?= $k ?>"></div>
                <div class="pa-chart mt-3"><canvas id="pa_chart_<?= $k ?>"></canvas></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($cfg['combinada']): ?>
<div class="card mb-3">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-table"></i> <?= htmlspecialchars($cfg['combinada_titulo']) ?></h5></div>
    <div class="card-body">
        <div class="table-responsive" id="pa_combinada"></div>
        <div class="d-flex align-items-center gap-2 mt-3 mb-2">
            <label for="pa_comb_metrica" class="form-label mb-0">Gráfica de:</label>
            <select id="pa_comb_metrica" class="form-select form-select-sm w-auto">
                <?php foreach ($cfg['combinada'] as $k): ?>
                    <option value="<?= $k ?>"><?= htmlspecialchars($etiquetas[$k]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="pa-chart"><canvas id="pa_chart_combinada"></canvas></div>
    </div>
</div>
<?php endif; ?>

<!-- ══ BASE / CUMPLIMIENTO / TIPO DE ATENCIÓN ═══════ -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-geo-alt-fill"></i> Vuelos por Base</h5></div>
            <div class="card-body">
                <div id="chart_base" class="bar-list"></div>
                <p id="empty_base" class="viz-empty" hidden>Sin datos para los filtros seleccionados.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-speedometer2"></i> Cumplimiento de Tránsito</h5></div>
            <div class="card-body">
                <div class="meter-wrap">
                    <div class="meter-track"><div id="meter_fill" class="meter-fill" style="width:0%"></div></div>
                    <div class="meter-value" id="meter_value">—</div>
                </div>
                <p class="viz-caption" id="meter_caption">Sin datos suficientes.</p>
            </div>
        </div>
    </div>
</div>
<div class="card mb-3">
    <div class="card-header"><h5><i class="bi bi-pie-chart-fill"></i> Distribución por Tipo de Atención</h5></div>
    <div class="card-body">
        <div id="chart_tipo" class="stacked-bar"></div>
        <div id="legend_tipo" class="viz-legend"></div>
        <p id="empty_tipo" class="viz-empty" hidden>Sin datos para los filtros seleccionados.</p>
    </div>
</div>

<!-- ══ DEMORAS ══════════════════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-exclamation-triangle-fill"></i> Demoras por Código</h5></div>
            <div class="card-body">
                <div id="chart_demoras" class="bar-list"></div>
                <p id="empty_demoras" class="viz-empty" hidden>Sin demoras registradas para los filtros seleccionados.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-geo-alt-fill"></i> Demoras por Base</h5></div>
            <div class="card-body">
                <div id="chart_demoras_base" class="bar-list"></div>
                <p id="empty_demoras_base" class="viz-empty" hidden>Sin demoras registradas para los filtros seleccionados.</p>
            </div>
        </div>
    </div>
</div>

</div><!-- /viz-root -->

<?php require __DIR__ . '/_dashboard_viz.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
window.addEventListener('load', function () {
    const SLUG = <?= json_encode($slug) ?>;
    const TARJETAS = <?= json_encode($cfg['tarjetas']) ?>;
    const COMBINADA = <?= json_encode($cfg['combinada']) ?>;
    const ETIQUETAS = <?= json_encode($etiquetas, JSON_UNESCAPED_UNICODE) ?>;
    const MESES = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
    const PALETA = [<?= json_encode($color) ?>, '#F28C28', '#2A78D6', '#27AE60', '#8E44AD', '#16A085'];
    window.DESCRIPCIONES_DEMORA = <?= json_encode($codigosDemoraDesc, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT) ?>;

    const el = {
        anio: document.getElementById('pa_anio'),
        mes: document.getElementById('pa_mes'),
        ini: document.getElementById('pa_fecha_inicio'),
        fin: document.getElementById('pa_fecha_fin'),
        bases: document.getElementById('pa_bases'),
        loading: document.getElementById('pa_loading'),
    };
    const charts = {};
    let datos = null;

    const fmt = (n) => Number(n).toLocaleString('es-CO', { maximumFractionDigits: 2 });
    const colorAnio = (i) => PALETA[i % PALETA.length];
    const basesSel = () => Array.from(el.bases.querySelectorAll('.pa-base-check')).filter(c => c.checked).map(c => c.value);

    function dibujar(canvasId, serie, anios) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        if (charts[canvasId]) charts[canvasId].destroy();
        charts[canvasId] = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: MESES,
                datasets: anios.map((a, i) => ({
                    label: String(a),
                    data: (serie.por_anio[a] || new Array(12).fill(0)).map((v, m) => v),
                    backgroundColor: colorAnio(i),
                })),
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' }, title: { display: true, text: serie.label } },
                scales: { y: { beginAtZero: true } },
            },
        });
    }

    // Tabla mes × año de una métrica (con Total general si hay más de un año)
    function tablaSimple(serie, anios) {
        if (!anios.length) return '<div class="pa-empty">Sin datos para los filtros seleccionados.</div>';
        let h = '<table class="pa-table"><thead><tr><th>MES</th>' + anios.map(a => '<th>' + a + '</th>').join('')
            + (anios.length > 1 ? '<th>Total general</th>' : '') + '</tr></thead><tbody>';
        const tot = anios.map(() => 0);
        MESES.forEach((m, i) => {
            let fila = 0;
            h += '<tr><td>' + m + '</td>';
            anios.forEach((a, j) => {
                const v = (serie.por_anio[a] || [])[i] || 0;
                tot[j] += v; fila += v;
                h += '<td>' + (v ? fmt(v) : '') + '</td>';
            });
            if (anios.length > 1) h += '<td>' + (fila ? fmt(fila) : '') + '</td>';
            h += '</tr>';
        });
        h += '</tbody><tfoot><tr><td>Total general</td>' + tot.map(t => '<td>' + fmt(t) + '</td>').join('')
            + (anios.length > 1 ? '<td>' + fmt(tot.reduce((x, y) => x + y, 0)) + '</td>' : '') + '</tr></tfoot></table>';
        return h;
    }

    // Tabla combinada: una columna por métrica y año (como el Excel de resumen)
    function tablaCombinada(series, anios) {
        if (!anios.length) return '<div class="pa-empty">Sin datos para los filtros seleccionados.</div>';
        let h = '<table class="pa-table"><thead><tr><th rowspan="2">MES</th>'
            + COMBINADA.map(k => '<th colspan="' + anios.length + '">' + ETIQUETAS[k] + '</th>').join('')
            + '</tr><tr>' + COMBINADA.map(() => anios.map(a => '<th>' + a + '</th>').join('')).join('') + '</tr></thead><tbody>';
        const tot = {};
        MESES.forEach((m, i) => {
            h += '<tr><td>' + m + '</td>';
            COMBINADA.forEach(k => anios.forEach(a => {
                const v = ((series[k].por_anio[a]) || [])[i] || 0;
                tot[k + a] = (tot[k + a] || 0) + v;
                h += '<td>' + (v ? fmt(v) : '') + '</td>';
            }));
            h += '</tr>';
        });
        h += '</tbody><tfoot><tr><td>Total general</td>'
            + COMBINADA.map(k => anios.map(a => '<td>' + fmt(tot[k + a] || 0) + '</td>').join('')).join('') + '</tr></tfoot></table>';
        return h;
    }

    // KPIs y distribuciones (mismas que el Panel general)
    function renderIndicadores() {
        const k = datos.kpis;
        const set = (id, v) => { document.getElementById(id).textContent = v; };
        set('pa_kpi_total', fmt(k.vuelos));
        set('pa_kpi_cumplimiento', k.cumple_total ? Math.round(k.cumple_si / k.cumple_total * 100) + '%' : '—');
        set('pa_kpi_transito', k.transito_prom === null ? '—' : k.transito_prom + ' min');
        set('pa_kpi_pax', fmt(k.pax));
        set('pa_kpi_demoras', fmt(k.con_demora));
        set('pa_kpi_demora_promedio', k.demora_prom === null ? '—' : k.demora_prom + ' min');

        const mapa = (o) => new Map(Object.entries(o || {}));
        renderBarList('chart_base', 'empty_base', mapa(datos.por_base));
        renderStackedBarMapa(mapa(datos.por_tipo), k.vuelos);
        renderMeterValores(k.cumple_si, k.cumple_total);
        renderBarList('chart_demoras', 'empty_demoras', mapa(datos.demoras_codigo),
            { maxItems: 10, expandable: true, descripciones: window.DESCRIPCIONES_DEMORA });
        renderBarList('chart_demoras_base', 'empty_demoras_base', mapa(datos.demoras_base));
    }

    function render() {
        if (!datos) return;
        renderIndicadores();
        const anios = datos.anios;
        TARJETAS.forEach(k => {
            document.getElementById('pa_tabla_' + k).innerHTML = tablaSimple(datos.series[k], anios);
            dibujar('pa_chart_' + k, datos.series[k], anios);
        });
        if (COMBINADA.length) {
            document.getElementById('pa_combinada').innerHTML = tablaCombinada(datos.series, anios);
            const sel = document.getElementById('pa_comb_metrica');
            dibujar('pa_chart_combinada', datos.series[sel.value], anios);
        }
    }

    function cargar() {
        const p = new URLSearchParams();
        if (el.anio.value) p.set('anio', el.anio.value);
        if (el.mes.value) p.set('mes', el.mes.value);
        if (el.ini.value) p.set('fecha_inicio', el.ini.value);
        if (el.fin.value) p.set('fecha_fin', el.fin.value);
        basesSel().forEach(b => p.append('base[]', b));
        getCodigosDemoraSeleccionados().forEach(c => p.append('codigo_demora[]', c));
        el.loading.hidden = false;
        fetch(BASE_URL + '/flight-services/dashboard-aerolinea/' + SLUG + '/data?' + p.toString(), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => { datos = d; render(); })
            .catch(() => { el.loading.textContent = 'No se pudo cargar la información.'; el.loading.hidden = false; return; })
            .finally(() => { if (datos) el.loading.hidden = true; });
    }

    function basesLabel() {
        const s = basesSel();
        document.getElementById('pa_bases_label').textContent =
            s.length === 0 ? '-- Todas --' : (s.length <= 3 ? s.join(', ') : s.length + ' seleccionadas');
    }

    el.anio.addEventListener('change', cargar);
    el.mes.addEventListener('change', cargar);
    el.ini.addEventListener('change', cargar);
    el.fin.addEventListener('change', cargar);
    el.bases.addEventListener('change', () => { basesLabel(); cargar(); });
    document.getElementById('filter_codigo_demora').addEventListener('change', cargar);
    const comb = document.getElementById('pa_comb_metrica');
    if (comb) comb.addEventListener('change', render);
    document.getElementById('pa_limpiar').addEventListener('click', () => {
        el.anio.value = ''; el.mes.value = '';
        el.ini.value = ''; el.fin.value = '';
        el.bases.querySelectorAll('.pa-base-check').forEach(c => c.checked = false);
        basesLabel();
        limpiarCodigosDemora();
        cargar();
    });

    cargar();
});
</script>
