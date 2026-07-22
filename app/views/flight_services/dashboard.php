<div class="page-actions">
    <a href="<?= BASE_URL ?>/flight-services" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver al listado
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
                <input type="date" class="form-control" id="filter_fecha">
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

<div class="viz-root">

<!-- ══ KPIs ═════════════════════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-clipboard2-pulse-fill"></i></div>
            <div class="stat-info">
                <p>Total Servicios</p>
                <h3 id="kpi_total">0</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <p>Cumplimiento de Tiempo</p>
                <h3 id="kpi_cumplimiento">—</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon info"><i class="bi bi-stopwatch-fill"></i></div>
            <div class="stat-info">
                <p>Tránsito Promedio</p>
                <h3 id="kpi_transito">—</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon secondary"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <p>Total PAX Saliendo</p>
                <h3 id="kpi_pax">0</h3>
            </div>
        </div>
    </div>
</div>

<!-- ══ KPIs DEMORAS ═══════════════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-info">
                <p>Servicios con Demora</p>
                <h3 id="kpi_demoras">0</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon danger"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <p>Demora Promedio Llegando</p>
                <h3 id="kpi_demora_promedio">—</h3>
            </div>
        </div>
    </div>
</div>

<!-- ══ GRÁFICOS DE MAGNITUD ═══════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-geo-alt-fill"></i> Servicios por Base</h5></div>
            <div class="card-body">
                <div id="chart_base" class="bar-list"></div>
                <p id="empty_base" class="viz-empty" hidden>Sin datos para los filtros seleccionados.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-airplane-fill"></i> Servicios por Aerolínea</h5></div>
            <div class="card-body">
                <div id="chart_aerolinea" class="bar-list"></div>
                <p id="empty_aerolinea" class="viz-empty" hidden>Sin datos para los filtros seleccionados.</p>
            </div>
        </div>
    </div>
</div>

<!-- ══ TIPO DE ATENCIÓN + CUMPLIMIENTO ═════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-pie-chart-fill"></i> Distribución por Tipo de Atención</h5></div>
            <div class="card-body">
                <div id="chart_tipo" class="stacked-bar"></div>
                <div id="legend_tipo" class="viz-legend"></div>
                <p id="empty_tipo" class="viz-empty" hidden>Sin datos para los filtros seleccionados.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
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

<!-- ══ DEMORAS ══════════════════════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-exclamation-triangle-fill"></i> Demoras por Código</h5></div>
            <div class="card-body">
                <div id="chart_demoras" class="bar-list"></div>
                <p id="empty_demoras" class="viz-empty" hidden>Sin demoras registradas para los filtros seleccionados.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-airplane-engines-fill"></i> Demoras por Aerolínea</h5></div>
            <div class="card-body">
                <div id="chart_demoras_aerolinea" class="bar-list"></div>
                <p id="empty_demoras_aerolinea" class="viz-empty" hidden>Sin demoras registradas para los filtros seleccionados.</p>
            </div>
        </div>
    </div>
</div>

<!-- ══ TABLA DINÁMICA (PIVOT) ══════════════════════════ -->
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-table"></i> Tabla Dinámica — Base × Aerolínea</h5>
        <span class="badge badge-primary" id="pivot_badge">0 servicios</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table" id="pivot_table" style="width:100%;margin-bottom:0;">
                <thead></thead>
                <tbody></tbody>
                <tfoot></tfoot>
            </table>
        </div>
        <p id="empty_pivot" class="viz-empty p-3" hidden>Sin datos para los filtros seleccionados.</p>
    </div>
</div>

</div>

<style>
.viz-root {
    --series-1: #2a78d6;   /* blue   — sequential / slot 1 */
    --series-2: #eb6834;   /* orange — slot 2 */
    --series-3: #1baf7a;   /* aqua   — slot 3 */
    --series-4: #eda100;   /* yellow — slot 4 */
    --seq-100: #cde2fb; --seq-200: #9ec5f4; --seq-300: #6da7ec;
    --seq-400: #3987e5; --seq-500: #256abf; --seq-600: #184f95; --seq-700: #0d366b;
    --ink-primary: #0b0b0b; --ink-secondary: #52514e; --ink-muted: #898781;
    --grid: #e1e0d9;
}

.stat-icon.warning { background: linear-gradient(135deg, #E8B92E, #F1C94A); }
.stat-icon.danger { background: linear-gradient(135deg, #C0392B, #E74C3C); }

.viz-empty { color: var(--ink-muted); font-size: 13px; margin: 8px 0 0; }
.viz-caption { color: var(--ink-secondary); font-size: 12px; margin: 10px 0 0; text-align: center; }

/* Bar list (Servicios por Base / Aerolínea) */
.bar-list { display: flex; flex-direction: column; gap: 10px; }
.bar-row { display: grid; grid-template-columns: 110px 1fr 40px; align-items: center; gap: 10px; }
.bar-row .bar-name {
    font-size: 12.5px; color: var(--ink-secondary); font-weight: 600;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.bar-track { background: var(--grid); border-radius: 4px; height: 20px; position: relative; }
.bar-fill {
    height: 20px; border-radius: 4px 4px 4px 0; background: var(--series-1);
    transition: filter .15s, width .3s ease; cursor: pointer;
}
.bar-fill:hover, .bar-fill:focus { filter: brightness(1.12); outline: none; }
.bar-row .bar-value {
    font-size: 12.5px; font-weight: 700; color: var(--ink-primary); text-align: right;
    font-variant-numeric: tabular-nums;
}

/* Stacked bar (Tipo de Atención) */
.stacked-bar {
    display: flex; height: 32px; border-radius: 6px; overflow: hidden;
    background: var(--grid);
}
.stacked-seg {
    height: 100%; display: flex; align-items: center; justify-content: center;
    font-size: 11.5px; font-weight: 700; color: #fff; cursor: pointer;
    transition: filter .15s; border-right: 2px solid #fff;
}
.stacked-seg:last-child { border-right: none; }
.stacked-seg:hover, .stacked-seg:focus { filter: brightness(1.1); outline: none; }
.viz-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 14px; }
.viz-legend-item { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--ink-secondary); }
.viz-legend-swatch { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; }

/* Meter */
.meter-wrap { display: flex; align-items: center; gap: 14px; }
.meter-track { flex: 1; height: 18px; border-radius: 9px; background: var(--seq-100); overflow: hidden; }
.meter-fill { height: 100%; background: var(--series-1); transition: width .3s ease; border-radius: 9px; }
.meter-value { font-size: 22px; font-weight: 700; color: var(--ink-primary); min-width: 64px; text-align: right; font-variant-numeric: tabular-nums; }

/* Pivot heatmap table */
#pivot_table { font-size: 12.5px; }
#pivot_table th, #pivot_table td {
    text-align: center; padding: 8px 10px; border: 1px solid var(--grid);
    font-variant-numeric: tabular-nums; white-space: nowrap;
}
#pivot_table thead th { background: #EAF1F8; color: #1B4F8A; font-weight: 700; }
#pivot_table tbody th {
    text-align: left; background: #EAF1F8; color: #1B4F8A; font-weight: 700;
    position: sticky; left: 0;
}
#pivot_table td.pivot-cell { cursor: default; transition: filter .15s; }
#pivot_table td.pivot-cell:hover { filter: brightness(0.95); }
#pivot_table .pivot-total { background: #F4F8FC; font-weight: 700; color: var(--ink-primary); }
#pivot_table tfoot td, #pivot_table tfoot th { background: #DCE9F7; font-weight: 700; color: #1B4F8A; }

/* Tooltip */
.viz-tooltip {
    position: fixed; z-index: 2000; pointer-events: none; background: #1a1a19; color: #fff;
    font-size: 12px; padding: 6px 10px; border-radius: 6px; box-shadow: 0 4px 14px rgba(0,0,0,.25);
    opacity: 0; transform: translateY(4px); transition: opacity .1s, transform .1s; max-width: 240px;
}
.viz-tooltip.is-visible { opacity: 1; transform: translateY(0); }
.viz-tooltip strong { font-variant-numeric: tabular-nums; }
</style>

<script>
const FLIGHT_DATA = <?= $chartDataJson ?>;
const MESES_NOMBRE = {
    'Enero': 1, 'Febrero': 2, 'Marzo': 3, 'Abril': 4, 'Mayo': 5, 'Junio': 6,
    'Julio': 7, 'Agosto': 8, 'Septiembre': 9, 'Octubre': 10, 'Noviembre': 11, 'Diciembre': 12
};

const filterInputs = {
    fecha: document.getElementById('filter_fecha'),
    base: document.getElementById('filter_base'),
    aerolinea: document.getElementById('filter_aerolinea'),
};

/* ── Tooltip compartido ─────────────────────────────── */
const tooltipEl = document.createElement('div');
tooltipEl.className = 'viz-tooltip';
document.body.appendChild(tooltipEl);

function showTooltip(evt, text) {
    tooltipEl.textContent = '';
    tooltipEl.appendChild(document.createTextNode(text));
    tooltipEl.classList.add('is-visible');
    moveTooltip(evt);
}
function moveTooltip(evt) {
    const pad = 14;
    let x = evt.clientX + pad;
    let y = evt.clientY + pad;
    if (x + 240 > window.innerWidth) x = evt.clientX - 240 - pad;
    if (y + 40 > window.innerHeight) y = evt.clientY - 40 - pad;
    tooltipEl.style.left = x + 'px';
    tooltipEl.style.top = y + 'px';
}
function hideTooltip() { tooltipEl.classList.remove('is-visible'); }

function bindTooltip(el, text) {
    el.addEventListener('mousemove', (e) => showTooltip(e, text));
    el.addEventListener('mouseleave', hideTooltip);
    el.setAttribute('tabindex', '0');
    el.addEventListener('focus', (e) => showTooltip(e, text));
    el.addEventListener('blur', hideTooltip);
}

/* ── Filtrado ────────────────────────────────────────── */
function filtrarDatos() {
    let filtroFecha = null;
    if (filterInputs.fecha.value) {
        const [anio, mes, dia] = filterInputs.fecha.value.split('-').map(Number);
        filtroFecha = { anio, mes, dia };
    }
    const filtroBase = filterInputs.base.value || null;
    const filtroAerolinea = filterInputs.aerolinea.value || null;

    return FLIGHT_DATA.filter(s => {
        if (filtroFecha && (s.anio !== filtroFecha.anio || s.mes !== filtroFecha.mes || s.dia !== filtroFecha.dia)) return false;
        if (filtroBase && s.base !== filtroBase) return false;
        if (filtroAerolinea && s.aerolinea !== filtroAerolinea) return false;
        return true;
    });
}

/* ── Utilidades ──────────────────────────────────────── */
function contarPor(rows, campo) {
    const mapa = new Map();
    rows.forEach(r => {
        const key = r[campo] || '—';
        mapa.set(key, (mapa.get(key) || 0) + 1);
    });
    return mapa;
}

function seqStep(ratio) {
    // ratio 0..1 -> paso de la rampa secuencial azul
    const steps = ['--seq-100', '--seq-200', '--seq-300', '--seq-400', '--seq-500', '--seq-600', '--seq-700'];
    const idx = Math.min(steps.length - 1, Math.floor(ratio * steps.length));
    return steps[idx];
}

/* ── Render: bar list genérico (sequential, magnitud) ── */
function renderBarList(containerId, emptyId, mapa, maxItems) {
    const container = document.getElementById(containerId);
    const emptyEl = document.getElementById(emptyId);
    container.textContent = '';

    let entries = Array.from(mapa.entries()).sort((a, b) => b[1] - a[1]);
    if (maxItems && entries.length > maxItems) {
        const resto = entries.slice(maxItems).reduce((sum, [, v]) => sum + v, 0);
        entries = entries.slice(0, maxItems);
        if (resto > 0) entries.push(['Otras', resto]);
    }

    if (entries.length === 0) {
        emptyEl.hidden = false;
        return;
    }
    emptyEl.hidden = true;

    const max = entries[0][1];
    entries.forEach(([label, value]) => {
        const row = document.createElement('div');
        row.className = 'bar-row';

        const nameEl = document.createElement('span');
        nameEl.className = 'bar-name';
        nameEl.textContent = label;

        const track = document.createElement('div');
        track.className = 'bar-track';
        const fill = document.createElement('div');
        fill.className = 'bar-fill';
        fill.style.width = Math.max(4, Math.round((value / max) * 100)) + '%';
        bindTooltip(fill, `${label}: ${value} servicio${value === 1 ? '' : 's'}`);
        track.appendChild(fill);

        const valueEl = document.createElement('span');
        valueEl.className = 'bar-value';
        valueEl.textContent = value;

        row.appendChild(nameEl);
        row.appendChild(track);
        row.appendChild(valueEl);
        container.appendChild(row);
    });
}

/* ── Render: barra apilada (categórica, parte-todo) ───── */
const COLORES_TIPO = ['var(--series-1)', 'var(--series-2)', 'var(--series-3)', 'var(--series-4)'];

function renderStackedBar(rows) {
    const container = document.getElementById('chart_tipo');
    const legend = document.getElementById('legend_tipo');
    const emptyEl = document.getElementById('empty_tipo');
    container.textContent = '';
    legend.textContent = '';

    const mapa = contarPor(rows, 'tipo_atencion');
    const total = rows.length;

    if (total === 0) {
        emptyEl.hidden = false;
        return;
    }
    emptyEl.hidden = true;

    const entries = Array.from(mapa.entries()).sort((a, b) => b[1] - a[1]);
    entries.forEach(([label, value], i) => {
        const pct = (value / total) * 100;
        const color = COLORES_TIPO[i % COLORES_TIPO.length];

        const seg = document.createElement('div');
        seg.className = 'stacked-seg';
        seg.style.width = pct + '%';
        seg.style.background = color;
        if (pct >= 12) {
            seg.textContent = Math.round(pct) + '%';
        }
        bindTooltip(seg, `${label}: ${value} servicio${value === 1 ? '' : 's'} (${pct.toFixed(1)}%)`);
        container.appendChild(seg);

        const legendItem = document.createElement('div');
        legendItem.className = 'viz-legend-item';
        const swatch = document.createElement('span');
        swatch.className = 'viz-legend-swatch';
        swatch.style.background = color;
        const text = document.createElement('span');
        text.textContent = `${label} — ${value}`;
        legendItem.appendChild(swatch);
        legendItem.appendChild(text);
        legend.appendChild(legendItem);
    });
}

/* ── Render: meter de cumplimiento ────────────────────── */
function renderMeter(rows) {
    const conRespuesta = rows.filter(r => r.cumple_tiempo !== null);
    const fillEl = document.getElementById('meter_fill');
    const valueEl = document.getElementById('meter_value');
    const captionEl = document.getElementById('meter_caption');

    if (conRespuesta.length === 0) {
        fillEl.style.width = '0%';
        valueEl.textContent = '—';
        captionEl.textContent = 'Sin datos suficientes.';
        return;
    }
    const cumplen = conRespuesta.filter(r => r.cumple_tiempo === true).length;
    const pct = Math.round((cumplen / conRespuesta.length) * 100);
    fillEl.style.width = pct + '%';
    valueEl.textContent = pct + '%';
    captionEl.textContent = `${cumplen} de ${conRespuesta.length} servicios cumplieron el tiempo objetivo.`;
}

/* ── Render: KPIs ──────────────────────────────────────── */
function renderKpis(rows) {
    document.getElementById('kpi_total').textContent = rows.length;

    const conRespuesta = rows.filter(r => r.cumple_tiempo !== null);
    const cumpEl = document.getElementById('kpi_cumplimiento');
    if (conRespuesta.length === 0) {
        cumpEl.textContent = '—';
    } else {
        const cumplen = conRespuesta.filter(r => r.cumple_tiempo === true).length;
        cumpEl.textContent = Math.round((cumplen / conRespuesta.length) * 100) + '%';
    }

    const conTransito = rows.filter(r => r.tiempo_transito !== null);
    const transitoEl = document.getElementById('kpi_transito');
    if (conTransito.length === 0) {
        transitoEl.textContent = '—';
    } else {
        const promedio = conTransito.reduce((sum, r) => sum + r.tiempo_transito, 0) / conTransito.length;
        transitoEl.textContent = Math.round(promedio) + ' min';
    }

    const totalPax = rows.reduce((sum, r) => sum + (r.pax_saliendo || 0), 0);
    document.getElementById('kpi_pax').textContent = totalPax.toLocaleString('es-CO');

    const conDemora = rows.filter(r => r.demora_llegando > 0);
    document.getElementById('kpi_demoras').textContent = conDemora.length;

    const promedioEl = document.getElementById('kpi_demora_promedio');
    if (conDemora.length === 0) {
        promedioEl.textContent = '—';
    } else {
        const promedio = conDemora.reduce((sum, r) => sum + r.demora_llegando, 0) / conDemora.length;
        promedioEl.textContent = Math.round(promedio) + ' min';
    }
}

/* ── Render: demoras por código y por aerolínea ───────── */
function renderDemoras(rows) {
    const conDemora = rows.filter(r => r.demora_llegando > 0);
    renderBarList('chart_demoras', 'empty_demoras', contarPor(conDemora, 'codigo_demora'));
    renderBarList('chart_demoras_aerolinea', 'empty_demoras_aerolinea', contarPor(conDemora, 'aerolinea'));
}

/* ── Render: tabla dinámica Base × Aerolínea ──────────── */
function renderPivot(rows) {
    const thead = document.querySelector('#pivot_table thead');
    const tbody = document.querySelector('#pivot_table tbody');
    const tfoot = document.querySelector('#pivot_table tfoot');
    const emptyEl = document.getElementById('empty_pivot');
    thead.textContent = '';
    tbody.textContent = '';
    tfoot.textContent = '';

    document.getElementById('pivot_badge').textContent = rows.length + ' servicios';

    if (rows.length === 0) {
        document.getElementById('pivot_table').style.display = 'none';
        emptyEl.hidden = false;
        return;
    }
    document.getElementById('pivot_table').style.display = '';
    emptyEl.hidden = true;

    // Top 6 aerolíneas por volumen, resto agrupado en "Otras"
    const totalesAerolinea = contarPor(rows, 'aerolinea');
    let aerolineas = Array.from(totalesAerolinea.entries()).sort((a, b) => b[1] - a[1]).map(e => e[0]);
    let hayOtras = false;
    if (aerolineas.length > 6) {
        aerolineas = aerolineas.slice(0, 6);
        hayOtras = true;
    }
    const columnas = hayOtras ? [...aerolineas, 'Otras'] : aerolineas;

    const bases = Array.from(new Set(rows.map(r => r.base))).sort();

    // Matriz de conteos
    const matriz = {};
    bases.forEach(b => { matriz[b] = {}; columnas.forEach(c => matriz[b][c] = 0); });
    rows.forEach(r => {
        const col = columnas.includes(r.aerolinea) ? r.aerolinea : 'Otras';
        if (matriz[r.base] && matriz[r.base][col] !== undefined) matriz[r.base][col]++;
    });

    let maxCell = 0;
    bases.forEach(b => columnas.forEach(c => { maxCell = Math.max(maxCell, matriz[b][c]); }));

    // Encabezado
    const trHead = document.createElement('tr');
    const thBase = document.createElement('th');
    thBase.textContent = 'Base';
    trHead.appendChild(thBase);
    columnas.forEach(c => {
        const th = document.createElement('th');
        th.textContent = c;
        trHead.appendChild(th);
    });
    const thTotal = document.createElement('th');
    thTotal.textContent = 'Total';
    trHead.appendChild(thTotal);
    thead.appendChild(trHead);

    // Filas
    bases.forEach(b => {
        const tr = document.createElement('tr');
        const th = document.createElement('th');
        th.textContent = b;
        tr.appendChild(th);

        let totalFila = 0;
        columnas.forEach(c => {
            const value = matriz[b][c];
            totalFila += value;
            const td = document.createElement('td');
            td.className = 'pivot-cell';
            if (value > 0) {
                const ratio = maxCell > 0 ? value / maxCell : 0;
                td.style.background = `var(${seqStep(ratio)})`;
                td.style.color = ratio >= 0.55 ? '#fff' : 'var(--ink-primary)';
                td.textContent = value;
                bindTooltip(td, `${b} · ${c}: ${value} servicio${value === 1 ? '' : 's'}`);
            } else {
                td.textContent = '—';
                td.style.color = 'var(--ink-muted)';
            }
            tr.appendChild(td);
        });

        const tdTotal = document.createElement('td');
        tdTotal.className = 'pivot-total';
        tdTotal.textContent = totalFila;
        tr.appendChild(tdTotal);

        tbody.appendChild(tr);
    });

    // Totales por columna
    const trFoot = document.createElement('tr');
    const thFootLabel = document.createElement('th');
    thFootLabel.textContent = 'Total';
    trFoot.appendChild(thFootLabel);
    let granTotal = 0;
    columnas.forEach(c => {
        const totalCol = bases.reduce((sum, b) => sum + matriz[b][c], 0);
        granTotal += totalCol;
        const td = document.createElement('td');
        td.textContent = totalCol;
        trFoot.appendChild(td);
    });
    const tdGranTotal = document.createElement('td');
    tdGranTotal.textContent = granTotal;
    trFoot.appendChild(tdGranTotal);
    tfoot.appendChild(trFoot);
}

/* ── Orquestador ───────────────────────────────────────── */
function renderDashboard() {
    const rows = filtrarDatos();
    renderKpis(rows);
    renderBarList('chart_base', 'empty_base', contarPor(rows, 'base'));
    renderBarList('chart_aerolinea', 'empty_aerolinea', contarPor(rows, 'aerolinea'), 8);
    renderStackedBar(rows);
    renderMeter(rows);
    renderDemoras(rows);
    renderPivot(rows);
}

filterInputs.fecha.addEventListener('change', renderDashboard);
filterInputs.base.addEventListener('change', renderDashboard);
filterInputs.aerolinea.addEventListener('change', renderDashboard);

document.getElementById('btn_limpiar_filtros').addEventListener('click', () => {
    filterInputs.fecha.value = '';
    filterInputs.base.value = '';
    filterInputs.aerolinea.value = '';
    renderDashboard();
});

renderDashboard();
</script>
