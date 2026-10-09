<div class="page-actions">
    <a href="<?= BASE_URL ?>/flight-services" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver a Ground Handling
    </a>
    <a href="#" id="btn_exportar_excel" class="btn btn-success">
        <i class="bi bi-file-earmark-excel-fill"></i> Exportar a Excel
    </a>
</div>

<!-- ══ FILTROS ══════════════════════════════════════ -->
<?php
$gruposCampos = [];
foreach ($campos as $c) { $gruposCampos[$c['grupo']][] = $c; }
$tiposAvionMap = [];
foreach ($tiposAvion as $ta) { $tiposAvionMap[$ta['tipo']][] = $ta['aerolinea']; }
?>
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label for="filter_fecha_inicio" class="form-label">Fecha inicio</label>
                <input type="date" class="form-control" id="filter_fecha_inicio">
            </div>
            <div class="col-md-2">
                <label for="filter_fecha_fin" class="form-label">Fecha fin</label>
                <input type="date" class="form-control" id="filter_fecha_fin">
            </div>
            <div class="col-md-2">
                <label class="form-label">Base</label>
                <div class="dropdown fact-ms" id="filter_base">
                    <button type="button" class="btn btn-outline-secondary form-select text-start"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <span class="fact-ms-label">-- Todas --</span>
                    </button>
                    <div class="dropdown-menu p-2 w-100" style="max-height:300px;overflow-y:auto;min-width:160px">
                        <?php foreach ($basesUniques as $i => $b): ?>
                            <div class="form-check">
                                <input class="form-check-input fact-ms-check" type="checkbox" id="base_chk_<?= $i ?>" value="<?= htmlspecialchars($b) ?>">
                                <label class="form-check-label w-100" for="base_chk_<?= $i ?>"><?= htmlspecialchars($b) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Aerolíneas</label>
                <?php require APP_PATH . '/views/flight_services/_aerolinea_multiselect.php'; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo de avión</label>
                <div class="dropdown fact-ms" id="filter_tipo_avion">
                    <button type="button" class="btn btn-outline-secondary form-select text-start"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <span class="fact-ms-label">-- Todos --</span>
                    </button>
                    <div class="dropdown-menu p-2 w-100" style="max-height:300px;overflow-y:auto;min-width:200px">
                        <?php $i = 0; foreach ($tiposAvionMap as $tipo => $aeros): $i++; ?>
                            <div class="form-check" data-aerolineas="<?= htmlspecialchars(json_encode(array_values(array_unique($aeros)))) ?>">
                                <input class="form-check-input fact-ms-check" type="checkbox" id="tipo_chk_<?= $i ?>" value="<?= htmlspecialchars($tipo) ?>">
                                <label class="form-check-label w-100" for="tipo_chk_<?= $i ?>">
                                    <?= htmlspecialchars($tipo) ?>
                                    <small class="text-muted">· <?= htmlspecialchars(implode(', ', array_unique($aeros))) ?></small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$tiposAvionMap): ?><span class="text-muted small">Sin tipos de avión</span><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3 align-items-end mt-0">
            <div class="col-md-3">
                <label class="form-label">Campos a mostrar</label>
                <div class="dropdown" id="filter_campos">
                    <button type="button" class="btn btn-outline-secondary form-select text-start"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <span id="campos_label">Campos por defecto</span>
                    </button>
                    <div class="dropdown-menu p-2" style="width:380px;max-width:92vw;">
                        <div class="d-flex gap-2 mb-2">
                            <input type="search" class="form-control form-control-sm" id="campos_buscar" placeholder="Buscar campo…">
                            <button type="button" class="btn btn-outline-secondary btn-sm text-nowrap" id="campos_reset">Restablecer</button>
                        </div>
                        <div style="max-height:340px;overflow-y:auto;">
                            <?php $k = 0; foreach ($gruposCampos as $grupo => $items): ?>
                                <div class="campos-grupo">
                                    <div class="small fw-bold text-muted mt-2 mb-1"><?= htmlspecialchars($grupo) ?></div>
                                    <?php foreach ($items as $c): $k++; ?>
                                        <div class="form-check campos-item">
                                            <input class="form-check-input campo-check" type="checkbox" id="campo_chk_<?= $k ?>"
                                                   value="<?= htmlspecialchars($c['label']) ?>"
                                                   data-defecto="<?= $c['defecto'] ? '1' : '0' ?>" <?= $c['defecto'] ? 'checked' : '' ?>>
                                            <label class="form-check-label w-100" for="campo_chk_<?= $k ?>"><?= htmlspecialchars($c['label']) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 ms-auto">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btn_limpiar_filtros">
                    <i class="bi bi-arrow-counterclockwise"></i> Limpiar Filtros
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ KPIs ═════════════════════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-clipboard2-pulse-fill"></i></div>
            <div class="stat-info"><p>Total Vuelos</p><h3 id="kpi_vuelos">0</h3></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon info"><i class="bi bi-airplane-fill"></i></div>
            <div class="stat-info"><p>Aerolíneas</p><h3 id="kpi_aerolineas">0</h3></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-receipt"></i></div>
            <div class="stat-info"><p>Conceptos con movimiento</p><h3 id="kpi_conceptos">0</h3></div>
        </div>
    </div>
    <div class="col-6 col-lg-3 d-flex align-items-center">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="ocultar_ceros">
            <label class="form-check-label" for="ocultar_ceros">Ocultar conceptos en 0</label>
        </div>
    </div>
</div>

<div id="fact_loading" class="text-muted mb-3">Cargando…</div>
<p id="fact_empty" class="text-muted" hidden>Sin datos para los filtros seleccionados.</p>
<div id="fact_airlines"></div>

<style>
.fact-section-title {
    background: #8FA9DC; color: #0b0b0b; font-weight: 700; font-size: 13px;
    padding: 6px 12px; display: flex; justify-content: space-between;
}
.fact-table { width: 100%; font-size: 13px; margin-bottom: 0; }
.fact-table td { padding: 6px 12px; border-bottom: 1px solid #e1e0d9; vertical-align: middle; }
.fact-table td.fact-qty { width: 90px; text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
.fact-table td.fact-bar { width: 32%; }
.fact-table .fact-track { background: #e1e0d9; border-radius: 4px; height: 12px; }
.fact-table .fact-fill { background: #2a78d6; height: 12px; border-radius: 4px; }
.fact-table tr.is-zero td:first-child { color: #898781; }
</style>

<script>
window.addEventListener('load', function () {
    const el = {
        ini: document.getElementById('filter_fecha_inicio'),
        fin: document.getElementById('filter_fecha_fin'),
        filtroAero: document.getElementById('filter_aerolinea'),
        base: document.getElementById('filter_base'),
        tipo: document.getElementById('filter_tipo_avion'),
        campos: document.getElementById('filter_campos'),
        ceros: document.getElementById('ocultar_ceros'),
        loading: document.getElementById('fact_loading'),
        empty: document.getElementById('fact_empty'),
        cont: document.getElementById('fact_airlines'),
    };
    let ultimo = null;

    const esc = (s) => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmt = (n) => Number(n).toLocaleString('es-CO', { maximumFractionDigits: 2 });

    // ── Multiselect genérico (Base, Tipo de avión) ──
    const msChecks  = (root) => Array.from(root.querySelectorAll('.fact-ms-check'));
    const msValores = (root) => msChecks(root).filter(c => c.checked).map(c => c.value);
    function msLabel(root, vacio) {
        const sel = msValores(root);
        root.querySelector('.fact-ms-label').textContent =
            sel.length === 0 ? vacio : (sel.length <= 2 ? sel.join(', ') : sel.length + ' seleccionados');
    }

    // El tipo de avión depende de la aerolínea: con aerolíneas elegidas
    // solo se ofrecen los tipos de esas aerolíneas.
    function filtrarTiposPorAerolinea() {
        const aeros = getAerolineasSeleccionadas();
        el.tipo.querySelectorAll('.form-check').forEach(div => {
            const de = JSON.parse(div.dataset.aerolineas || '[]');
            const visible = aeros.length === 0 || de.some(a => aeros.includes(a));
            div.hidden = !visible;
            if (!visible) div.querySelector('input').checked = false;
        });
        msLabel(el.tipo, '-- Todos --');
    }

    // ── Campos a mostrar: se envía solo la diferencia contra el catálogo por defecto ──
    const campoChecks = () => Array.from(el.campos.querySelectorAll('.campo-check'));
    const catalogoPorAerolinea = <?= json_encode($catalogoPorAerolinea, JSON_UNESCAPED_UNICODE) ?>;
    const camposTocados = new Set();   // campos que el usuario marcó/desmarcó contra el defecto

    // Campos por defecto = unión de los catálogos de las aerolíneas seleccionadas
    // (o de todas si no hay ninguna seleccionada). Los cambios manuales se conservan.
    function actualizarCamposPorAerolineas() {
        const sel = getAerolineasSeleccionadas();
        const fuente = sel.length ? sel : Object.keys(catalogoPorAerolinea);
        const defecto = new Set();
        fuente.forEach(a => (catalogoPorAerolinea[a] || []).forEach(l => defecto.add(l)));
        campoChecks().forEach(c => {
            const d = defecto.has(c.value);
            c.dataset.defecto = d ? '1' : '0';
            c.checked = camposTocados.has(c.value) ? !d : d;
        });
        camposLabel();
    }
    function ajustesCampos() {
        const agregar = [], quitar = [];
        campoChecks().forEach(c => {
            const defecto = c.dataset.defecto === '1';
            if (c.checked && !defecto) agregar.push(c.value);
            if (!c.checked && defecto) quitar.push(c.value);
        });
        return { agregar, quitar };
    }
    function camposLabel() {
        const aj = ajustesCampos();
        const t = [];
        if (aj.agregar.length) t.push('+' + aj.agregar.length);
        if (aj.quitar.length) t.push('−' + aj.quitar.length);
        document.getElementById('campos_label').textContent =
            t.length ? 'Campos por defecto (' + t.join(' / ') + ')' : 'Campos por defecto';
    }

    function params() {
        const p = new URLSearchParams();
        if (el.ini.value) p.set('fecha_inicio', el.ini.value);
        if (el.fin.value) p.set('fecha_fin', el.fin.value);
        getAerolineasSeleccionadas().forEach(a => p.append('aerolinea[]', a));
        msValores(el.base).forEach(v => p.append('base[]', v));
        msValores(el.tipo).forEach(v => p.append('tipo_avion[]', v));
        const aj = ajustesCampos();
        aj.agregar.forEach(v => p.append('campo_agregar[]', v));
        aj.quitar.forEach(v => p.append('campo_quitar[]', v));
        return p.toString();
    }

    function render() {
        if (!ultimo) return;
        const ocultar = el.ceros.checked;
        let conceptosConMov = 0;
        let html = '';
        ultimo.airlines.forEach(a => {
            html += '<div class="card mb-3"><div class="card-header"><h5 class="mb-0"><i class="bi bi-airplane-fill"></i> '
                + esc(a.nombre) + '</h5><span class="badge badge-primary">' + fmt(a.vuelos) + ' vuelos</span></div>'
                + '<div class="card-body p-0">';
            a.secciones.forEach(sec => {
                const max = Math.max(...sec.conceptos.map(c => c.cantidad), 0);
                html += '<div class="fact-section-title"><span>' + esc(sec.titulo) + '</span><span>' + fmt(sec.vuelos) + ' vuelos</span></div>'
                    + '<table class="fact-table"><tbody>';
                sec.conceptos.forEach(c => {
                    if (c.cantidad > 0) conceptosConMov++;
                    if (ocultar && c.cantidad <= 0) return;
                    const w = max > 0 ? Math.round(c.cantidad / max * 100) : 0;
                    html += '<tr class="' + (c.cantidad > 0 ? '' : 'is-zero') + '"><td>' + esc(c.concepto) + '</td>'
                        + '<td class="fact-bar"><div class="fact-track"><div class="fact-fill" style="width:' + w + '%"></div></div></td>'
                        + '<td class="fact-qty">' + fmt(c.cantidad) + '</td></tr>';
                });
                html += '</tbody></table>';
            });
            html += '</div></div>';
        });
        el.cont.innerHTML = html;
        el.empty.hidden = ultimo.airlines.length > 0;
        document.getElementById('kpi_vuelos').textContent = fmt(ultimo.total_vuelos);
        document.getElementById('kpi_aerolineas').textContent = fmt(ultimo.airlines.length);
        document.getElementById('kpi_conceptos').textContent = fmt(conceptosConMov);
    }

    function cargar() {
        el.loading.hidden = false;
        fetch(BASE_URL + '/facturacion/data?' + params(), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => { ultimo = d; render(); })
            .catch(() => { el.cont.innerHTML = '<div class="alert alert-danger">No se pudo cargar la información.</div>'; })
            .finally(() => { el.loading.hidden = true; });
    }

    el.ini.addEventListener('change', cargar);
    el.fin.addEventListener('change', cargar);
    el.filtroAero.addEventListener('change', () => { filtrarTiposPorAerolinea(); actualizarCamposPorAerolineas(); cargar(); });
    el.base.addEventListener('change', () => { msLabel(el.base, '-- Todas --'); cargar(); });
    el.tipo.addEventListener('change', () => { msLabel(el.tipo, '-- Todos --'); cargar(); });
    el.campos.addEventListener('change', (e) => {
        const c = e.target;
        if (c.classList && c.classList.contains('campo-check')) {
            if (c.checked !== (c.dataset.defecto === '1')) camposTocados.add(c.value);
            else camposTocados.delete(c.value);
        }
        camposLabel();
        cargar();
    });
    document.getElementById('campos_buscar').addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        el.campos.querySelectorAll('.campos-item').forEach(d => {
            d.hidden = q !== '' && !d.textContent.toLowerCase().includes(q);
        });
        el.campos.querySelectorAll('.campos-grupo').forEach(g => {
            g.hidden = !Array.from(g.querySelectorAll('.campos-item')).some(d => !d.hidden);
        });
    });
    document.getElementById('campos_reset').addEventListener('click', () => {
        camposTocados.clear();
        actualizarCamposPorAerolineas();
        cargar();
    });
    el.ceros.addEventListener('change', render);
    document.getElementById('btn_limpiar_filtros').addEventListener('click', () => {
        el.ini.value = ''; el.fin.value = '';
        limpiarAerolineas();
        msChecks(el.base).forEach(c => c.checked = false);
        msChecks(el.tipo).forEach(c => c.checked = false);
        msLabel(el.base, '-- Todas --');
        filtrarTiposPorAerolinea();
        camposTocados.clear();
        actualizarCamposPorAerolineas();
        cargar();
    });
    document.getElementById('btn_exportar_excel').addEventListener('click', (e) => {
        e.preventDefault();
        window.location.href = BASE_URL + '/facturacion/export-excel?' + params();
    });

    actualizarCamposPorAerolineas();
    cargar();
});
</script>
