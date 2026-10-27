<?php
/** Estilos y funciones de gráficos compartidos por el Panel general y los
 *  paneles por aerolínea: tooltip, lista de barras (con descripción de
 *  código de demora) y barra apilada. Incluir una sola vez por página,
 *  antes del script propio de la vista. */
?>
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
/* Con descripción de código de demora al lado del código */
.bar-row.has-desc { grid-template-columns: minmax(150px, 45%) 1fr 40px; }
.bar-row.clickable { cursor: pointer; border-radius: 4px; }
.bar-row.clickable:hover { background: var(--grid); }
.bar-row .bar-name .bar-code { color: var(--ink-primary); font-weight: 700; }
.bar-row .bar-name .bar-desc { font-weight: 400; color: var(--ink-secondary); }
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
.bar-list-extra { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
.bar-list-toggle {
    margin-top: 10px; align-self: flex-start; background: none; border: none; padding: 0;
    color: var(--series-1); font-size: 12.5px; font-weight: 600; cursor: pointer;
}
.bar-list-toggle:hover, .bar-list-toggle:focus { text-decoration: underline; outline: none; }

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
/* Descripción de cada código de demora (código => texto); lo llena cada vista. */
window.DESCRIPCIONES_DEMORA = window.DESCRIPCIONES_DEMORA || {};

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

/* ── Códigos de demora: separa cualquier formato de texto ──
   Acepta "A - B", "A,B y C", "A-B", "A/B"... y devuelve los códigos
   individuales, en mayúsculas y sin repetir. */
function separarCodigos(raw) {
    const out = new Set();
    String(raw || '').split(/\s*(?:[,;\/+\-]|\s+y\s+)\s*/i).forEach(c => {
        c = c.trim().toUpperCase();
        if (c) out.add(c);
    });
    return Array.from(out);
}

function seqStep(ratio) {
    // ratio 0..1 -> paso de la rampa secuencial azul
    const steps = ['--seq-100', '--seq-200', '--seq-300', '--seq-400', '--seq-500', '--seq-600', '--seq-700'];
    const idx = Math.min(steps.length - 1, Math.floor(ratio * steps.length));
    return steps[idx];
}

/* ── Render: bar list genérico (sequential, magnitud) ──
 * `opts` acepta un número (compat: límite de items, el resto se
 * agrupa en una barra "Otras") o un objeto { maxItems, expandable,
 * descripciones }.
 * Con expandable:true el resto NO se agrupa: queda oculto detrás de
 * un botón "Ver X más" (útil cuando agrupar perdería información
 * relevante, p.ej. códigos de demora individuales).
 * Con descripciones (código => texto) cada etiqueta muestra al lado
 * la descripción del código. */
/* Modal con vuelo, código y observación de cada registro contado en un código de demora. */
function mostrarDetalleDemora(codigo, desc, items) {
    let modal = document.getElementById('modal_detalle_demora');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modal_detalle_demora';
        modal.className = 'modal fade';
        modal.tabIndex = -1;
        modal.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">'
            + '<div class="modal-header"><h5 class="modal-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'
            + '<div class="modal-body"></div></div></div>';
        document.body.appendChild(modal);
    }
    modal.querySelector('.modal-title').textContent = 'Código ' + codigo + (desc ? ' - ' + desc : '') + ' (' + items.length + ')';
    const body = modal.querySelector('.modal-body');
    body.textContent = '';
    const table = document.createElement('table');
    table.className = 'table table-sm';
    table.innerHTML = '<thead><tr><th>#</th><th>Vuelo</th><th>Código demora</th><th>Observación</th></tr></thead>';
    const tb = document.createElement('tbody');
    items.forEach(it => {
        const tr = document.createElement('tr');
        const tdId = document.createElement('td');
        tdId.textContent = it.id;
        const td1 = document.createElement('td');
        const a = document.createElement('a');
        a.href = BASE_URL + '/flight-services/view/' + it.id;
        a.target = '_blank';
        a.textContent = it.vuelo || it.saliendo || ('#' + it.id);
        td1.appendChild(a);
        const td2 = document.createElement('td');
        td2.textContent = it.codigo;
        const td3 = document.createElement('td');
        td3.textContent = it.obs || 'Sin observación';
        if (!it.obs) td3.className = 'text-muted';
        tr.append(tdId, td1, td2, td3);
        tb.appendChild(tr);
    });
    table.appendChild(tb);
    body.appendChild(table);
    bootstrap.Modal.getOrCreateInstance(modal).show();
}

function renderBarList(containerId, emptyId, mapa, opts) {
    if (typeof opts === 'number') opts = { maxItems: opts };
    opts = opts || {};
    const { maxItems, expandable, descripciones, detalle } = opts;

    const container = document.getElementById(containerId);
    const emptyEl = document.getElementById(emptyId);
    container.textContent = '';

    let entries = Array.from(mapa.entries()).sort((a, b) => b[1] - a[1]);
    let hidden = [];
    if (maxItems && entries.length > maxItems) {
        if (expandable) {
            hidden = entries.slice(maxItems);
            entries = entries.slice(0, maxItems);
        } else {
            const resto = entries.slice(maxItems).reduce((sum, [, v]) => sum + v, 0);
            entries = entries.slice(0, maxItems);
            if (resto > 0) entries.push(['Otras', resto]);
        }
    }

    if (entries.length === 0) {
        emptyEl.hidden = false;
        return;
    }
    emptyEl.hidden = true;

    const max = Math.max(entries[0][1], hidden.length ? hidden[0][1] : 0);

    function buildRow(label, value) {
        const desc = descripciones ? descripciones[label] : '';
        const row = document.createElement('div');
        row.className = 'bar-row' + (descripciones ? ' has-desc' : '');
        const items = detalle ? detalle(label) : null;
        if (items && items.length) {
            row.classList.add('clickable');
            row.addEventListener('click', () => mostrarDetalleDemora(label, desc, items));
        }

        const nameEl = document.createElement('span');
        nameEl.className = 'bar-name';
        if (desc) {
            const code = document.createElement('span');
            code.className = 'bar-code';
            code.textContent = label;
            const d = document.createElement('span');
            d.className = 'bar-desc';
            d.textContent = ' — ' + desc;
            nameEl.appendChild(code);
            nameEl.appendChild(d);
        } else {
            nameEl.textContent = label;
        }
        nameEl.title = desc ? `${label} — ${desc}` : label;

        const track = document.createElement('div');
        track.className = 'bar-track';
        const fill = document.createElement('div');
        fill.className = 'bar-fill';
        fill.style.width = Math.max(4, Math.round((value / max) * 100)) + '%';
        bindTooltip(fill, `${desc ? label + ' — ' + desc : label}: ${value} servicio${value === 1 ? '' : 's'}`);
        track.appendChild(fill);

        const valueEl = document.createElement('span');
        valueEl.className = 'bar-value';
        valueEl.textContent = value;

        row.appendChild(nameEl);
        row.appendChild(track);
        row.appendChild(valueEl);
        return row;
    }

    entries.forEach(([label, value]) => container.appendChild(buildRow(label, value)));

    if (hidden.length > 0) {
        const extraWrap = document.createElement('div');
        extraWrap.className = 'bar-list-extra';
        extraWrap.hidden = true;
        hidden.forEach(([label, value]) => extraWrap.appendChild(buildRow(label, value)));
        container.appendChild(extraWrap);

        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'bar-list-toggle';
        toggleBtn.textContent = `Ver ${hidden.length} más`;
        toggleBtn.addEventListener('click', () => {
            const willShow = extraWrap.hidden;
            extraWrap.hidden = !willShow;
            toggleBtn.textContent = willShow ? 'Ver menos' : `Ver ${hidden.length} más`;
        });
        container.appendChild(toggleBtn);
    }
}

/* ── Render: barra apilada (categórica, parte-todo) ───── */
const COLORES_TIPO = ['var(--series-1)', 'var(--series-2)', 'var(--series-3)', 'var(--series-4)'];

function renderStackedBarMapa(mapa, total, ids = ['chart_tipo', 'legend_tipo', 'empty_tipo']) {
    const container = document.getElementById(ids[0]);
    const legend = document.getElementById(ids[1]);
    const emptyEl = document.getElementById(ids[2]);
    container.textContent = '';
    legend.textContent = '';

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
function renderMeterValores(cumplen, total) {
    const fillEl = document.getElementById('meter_fill');
    const valueEl = document.getElementById('meter_value');
    const captionEl = document.getElementById('meter_caption');
    if (total === 0) {
        fillEl.style.width = '0%';
        valueEl.textContent = '—';
        captionEl.textContent = 'Sin datos suficientes.';
        return;
    }
    const pct = Math.round((cumplen / total) * 100);
    fillEl.style.width = pct + '%';
    valueEl.textContent = pct + '%';
    captionEl.textContent = `${cumplen} de ${total} servicios cumplieron el tiempo objetivo.`;
}
</script>
