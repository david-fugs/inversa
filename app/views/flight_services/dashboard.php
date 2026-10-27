<div class="page-actions">
    <a href="<?= BASE_URL ?>/flight-services" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
</div>

<?php $navActivo = 'general'; require __DIR__ . '/_dashboard_nav.php'; ?>

<!-- ══ FILTROS ══════════════════════════════════════ -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Rango de Fecha</label>
                <div class="d-flex gap-2">
                    <div class="flex-fill"><label for="filter_fecha_inicio" class="form-label small text-muted mb-1">Fecha inicio</label><input type="date" class="form-control" id="filter_fecha_inicio" placeholder="Fecha inicio"></div>
                    <div class="flex-fill"><label for="filter_fecha_fin" class="form-label small text-muted mb-1">Fecha fin</label><input type="date" class="form-control" id="filter_fecha_fin" placeholder="Fecha fin"></div>
                </div>
            </div>
            <div class="col-md-2">
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
                <?php require __DIR__ . '/_aerolinea_multiselect.php'; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Código Demora</label>
                <?php require __DIR__ . '/_codigo_demora_multiselect.php'; ?>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_limpiar_filtros">
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
                <p>Total Vuelos</p>
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
                <p>Vuelos con Demora</p>
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
            <div class="card-header"><h5><i class="bi bi-geo-alt-fill"></i> Vuelos por Base</h5></div>
            <div class="card-body">
                <div id="chart_base" class="bar-list"></div>
                <p id="empty_base" class="viz-empty" hidden>Sin datos para los filtros seleccionados.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-airplane-fill"></i> Vuelos por Aerolínea</h5></div>
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
    <div class="col-lg-4">
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
            <div class="card-header"><h5><i class="bi bi-airplane-engines-fill"></i> Demoras por Aerolínea</h5></div>
            <div class="card-body">
                <div id="chart_demoras_aerolinea" class="bar-list"></div>
                <p id="empty_demoras_aerolinea" class="viz-empty" hidden>Sin demoras registradas para los filtros seleccionados.</p>
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

<!-- ══ TABLA DINÁMICA (PIVOT) ══════════════════════════ -->
<div class="card">
    <div class="card-header flex-wrap gap-2">
        <h5><i class="bi bi-table"></i> Tabla Dinámica — Base × Aerolínea (Vuelos)</h5>
        <div class="d-flex align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group" id="pivot_metric_selector">
                <button type="button" class="btn btn-outline-primary active" data-metric="count">Cantidad de Vuelos</button>
                <button type="button" class="btn btn-outline-primary" data-metric="pax_saliendo">PAX Saliendo</button>
                <button type="button" class="btn btn-outline-primary" data-metric="pax_cancelado">PAX Cancelado</button>
            </div>
            <span class="badge badge-primary" id="pivot_badge">0 vuelos</span>
        </div>
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

<?php require __DIR__ . '/_dashboard_viz.php'; ?>
<style>
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
</style>

<script>
const FLIGHT_DATA = <?= $chartDataJson ?>;
window.DESCRIPCIONES_DEMORA = <?= json_encode($codigosDemoraDesc, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT) ?>;
const MESES_NOMBRE = {
    'Enero': 1, 'Febrero': 2, 'Marzo': 3, 'Abril': 4, 'Mayo': 5, 'Junio': 6,
    'Julio': 7, 'Agosto': 8, 'Septiembre': 9, 'Octubre': 10, 'Noviembre': 11, 'Diciembre': 12
};

const filterInputs = {
    fechaInicio: document.getElementById('filter_fecha_inicio'),
    fechaFin: document.getElementById('filter_fecha_fin'),
    base: document.getElementById('filter_base'),
    aerolinea: document.getElementById('filter_aerolinea'),
};


/* ── Filtrado ────────────────────────────────────────── */
function filtrarDatos() {
    let startDate = null;
    let endDate = null;
    if (filterInputs.fechaInicio.value) {
        const [y, m, d] = filterInputs.fechaInicio.value.split('-').map(Number);
        startDate = new Date(y, m - 1, d);
    }
    if (filterInputs.fechaFin.value) {
        const [y, m, d] = filterInputs.fechaFin.value.split('-').map(Number);
        endDate = new Date(y, m - 1, d);
    }
    const filtroBase = filterInputs.base.value || null;
    const filtroAerolineas = getAerolineasSeleccionadas();
    const filtroCodigos = getCodigosDemoraSeleccionados();

    return FLIGHT_DATA.filter(s => {
        if (startDate || endDate) {
            const rowDate = new Date(s.anio, s.mes - 1, s.dia);
            if (startDate && rowDate < startDate) return false;
            if (endDate && rowDate > endDate) return false;
        }
        if (filtroBase && s.base !== filtroBase) return false;
        if (filtroAerolineas.length && !filtroAerolineas.includes(s.aerolinea)) return false;
        if (filtroCodigos.length) {
            if (!separarCodigos(s.codigo_demora).some(c => filtroCodigos.includes(c))) return false;
        }
        return true;
    });
}

/* ── Tabla dinámica: métrica seleccionada ──────────────
 * 'count' cuenta servicios (1 por fila); 'pax_saliendo' y
 * 'pax_cancelado' suman ese campo por fila. */
let pivotMetric = 'count';

function pivotValueGetter(metric) {
    if (metric === 'pax_saliendo') return (r) => r.pax_saliendo || 0;
    if (metric === 'pax_cancelado') return (r) => r.pax_cancelado || 0;
    return (r) => cuentaVuelo(r) ? 1 : 0;
}

function pivotUnitLabel(metric, value) {
    if (metric === 'pax_saliendo') return 'PAX Saliendo';
    if (metric === 'pax_cancelado') return 'PAX Cancelado';
    return 'servicio' + (value === 1 ? '' : 's');
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

/* Las bases PPN y VVC de SATENA no cuentan como vuelos (sí cuentan en el resto de métricas) */
function cuentaVuelo(r) {
    return r.cuenta_vuelo !== false;
}

/* Un vuelo cuenta como "con demora" solo si tiene código de demora registrado */
function tieneDemora(r) {
    return !!(r.codigo_demora && r.codigo_demora.trim());
}

/* ── Render: barra apilada (categórica, parte-todo) ───── */
function renderStackedBar(rows) {
    const vuelos = rows.filter(cuentaVuelo);
    renderStackedBarMapa(contarPor(vuelos, 'tipo_atencion'), vuelos.length);
}

/* ── Render: meter de cumplimiento ────────────────────── */
function renderMeter(rows) {
    const conRespuesta = rows.filter(r => r.cumple_tiempo !== null);
    renderMeterValores(conRespuesta.filter(r => r.cumple_tiempo === true).length, conRespuesta.length);
}

/* ── Render: KPIs ──────────────────────────────────────── */
function renderKpis(rows) {
    document.getElementById('kpi_total').textContent = rows.filter(cuentaVuelo).length;

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

    const conDemora = rows.filter(tieneDemora);
    document.getElementById('kpi_demoras').textContent = conDemora.length;

    const promedioEl = document.getElementById('kpi_demora_promedio');
    if (conDemora.length === 0) {
        promedioEl.textContent = '—';
    } else {
        const promedio = conDemora.reduce((sum, r) => sum + (r.demora_llegando || 0), 0) / conDemora.length;
        promedioEl.textContent = Math.round(promedio) + ' min';
    }
}

/* ── Conteo por código de demora, separando selecciones múltiples ──
   `codigo_demora` puede traer varios códigos guardados como
   "COD1 - COD2 - COD3" (selección múltiple en el formulario). Cada
   código se cuenta por separado para que el gráfico no agrupe la
   combinación completa como si fuera una categoría propia. */
function contarPorCodigosDemora(rows) {
    const mapa = new Map();
    rows.forEach(r => {
        const codigos = separarCodigos(r.codigo_demora);
        if (codigos.length === 0) codigos.push('Sin código');
        codigos.forEach(codigo => {
            mapa.set(codigo, (mapa.get(codigo) || 0) + 1);
        });
    });
    return mapa;
}

function detallePorCodigoDemora(rows) {
    const out = {};
    rows.forEach(r => {
        const codigos = separarCodigos(r.codigo_demora);
        if (codigos.length === 0) codigos.push('Sin código');
        codigos.forEach(c => (out[c] = out[c] || []).push({ id: r.id, vuelo: r.vuelo, saliendo: r.saliendo, codigo: r.codigo_demora, obs: r.obs }));
    });
    return out;
}

/* ── Render: demoras por código, por aerolínea y por base ── */
function renderDemoras(rows) {
    const conDemora = rows.filter(tieneDemora);
    const detalle = detallePorCodigoDemora(conDemora);
    renderBarList('chart_demoras', 'empty_demoras', contarPorCodigosDemora(conDemora), { maxItems: 10, expandable: true, descripciones: window.DESCRIPCIONES_DEMORA, detalle: c => detalle[c] });
    renderBarList('chart_demoras_aerolinea', 'empty_demoras_aerolinea', contarPor(conDemora, 'aerolinea'));
    renderBarList('chart_demoras_base', 'empty_demoras_base', contarPor(conDemora, 'base'));
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

    document.getElementById('pivot_badge').textContent = rows.filter(cuentaVuelo).length + ' vuelos';

    const getValue = pivotValueGetter(pivotMetric);

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

    // Matriz de valores (cantidad de servicios, o suma de PAX según la métrica activa)
    const matriz = {};
    bases.forEach(b => { matriz[b] = {}; columnas.forEach(c => matriz[b][c] = 0); });
    rows.forEach(r => {
        const col = columnas.includes(r.aerolinea) ? r.aerolinea : 'Otras';
        if (matriz[r.base] && matriz[r.base][col] !== undefined) matriz[r.base][col] += getValue(r);
    });

    // Cancelados por base (tipo de atención "Cancelado")
    const canceladosBase = {};
    bases.forEach(b => canceladosBase[b] = 0);
    rows.forEach(r => {
        if (cuentaVuelo(r) && r.tipo_atencion === 'Cancelado' && canceladosBase[r.base] !== undefined) canceladosBase[r.base]++;
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
    const thCanc = document.createElement('th');
    thCanc.textContent = 'Total Cancelados';
    trHead.appendChild(thCanc);
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
                bindTooltip(td, `${b} · ${c}: ${value} ${pivotUnitLabel(pivotMetric, value)}`);
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

        const tdCanc = document.createElement('td');
        tdCanc.className = 'pivot-total';
        tdCanc.textContent = canceladosBase[b];
        tr.appendChild(tdCanc);

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
    const tdGranCanc = document.createElement('td');
    tdGranCanc.textContent = bases.reduce((sum, b) => sum + canceladosBase[b], 0);
    trFoot.appendChild(tdGranCanc);
    tfoot.appendChild(trFoot);
}

/* ── Orquestador ───────────────────────────────────────── */
function renderDashboard() {
    const rows = filtrarDatos();
    renderKpis(rows);
    const vuelos = rows.filter(cuentaVuelo);
    renderBarList('chart_base', 'empty_base', contarPor(vuelos, 'base'));
    renderBarList('chart_aerolinea', 'empty_aerolinea', contarPor(vuelos, 'aerolinea'), 8);
    renderStackedBar(rows);
    renderMeter(rows);
    renderDemoras(rows);
    renderPivot(rows);
}

filterInputs.fechaInicio.addEventListener('change', renderDashboard);
filterInputs.fechaFin.addEventListener('change', renderDashboard);
filterInputs.base.addEventListener('change', renderDashboard);
filterInputs.aerolinea.addEventListener('change', () => {
    filtrarCodigosPorAerolineas(getAerolineasSeleccionadas());
    renderDashboard();
});
document.getElementById('filter_codigo_demora').addEventListener('change', renderDashboard);

document.getElementById('btn_limpiar_filtros').addEventListener('click', () => {
    filterInputs.fechaInicio.value = '';
    filterInputs.fechaFin.value = '';
    filterInputs.base.value = '';
    limpiarAerolineas();
    limpiarCodigosDemora();
    filtrarCodigosPorAerolineas([]);
    renderDashboard();
});

// Selector de métrica de la tabla dinámica (Cantidad / PAX Saliendo / PAX Cancelado)
document.querySelectorAll('#pivot_metric_selector button').forEach(btn => {
    btn.addEventListener('click', () => {
        if (btn.dataset.metric === pivotMetric) return;
        pivotMetric = btn.dataset.metric;
        document.querySelectorAll('#pivot_metric_selector button').forEach(b => b.classList.toggle('active', b === btn));
        renderPivot(filtrarDatos());
    });
});

renderDashboard();
</script>
