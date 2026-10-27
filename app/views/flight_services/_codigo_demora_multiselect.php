<?php
/** Filtro multi-selección de códigos de demora (dropdown con casillas).
 *  Requiere $codigosDemoraUniques; opcional $codigosDemoraDesc (código => descripción). Expone window.getCodigosDemoraSeleccionados()
 *  y window.limpiarCodigosDemora(); dispara 'change' en #filter_codigo_demora. */
?>
<div class="dropdown" id="filter_codigo_demora">
    <button type="button" class="btn btn-outline-secondary form-select text-start"
            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <span id="codigo_demora_label">-- Todos --</span>
    </button>
    <div class="dropdown-menu p-2 w-100" style="max-height:300px;overflow-y:auto;min-width:320px">
        <input type="search" class="form-control form-control-sm mb-2" id="codigo_demora_buscar" placeholder="Buscar código o descripción..." autocomplete="off">
        <?php foreach ($codigosDemoraUniques as $i => $codigo): ?>
            <div class="form-check codigo-demora-item"<?php if (isset($codigosDemoraPorAerolinea)): ?> data-aerolineas="<?= htmlspecialchars(json_encode($codigosDemoraPorAerolinea[$codigo] ?? [], JSON_UNESCAPED_UNICODE)) ?>"<?php endif; ?>>
                <input class="form-check-input codigo-demora-check" type="checkbox"
                       id="codigo_demora_chk_<?= $i ?>" value="<?= htmlspecialchars($codigo) ?>">
                <label class="form-check-label w-100" for="codigo_demora_chk_<?= $i ?>">
                    <strong><?= htmlspecialchars($codigo) ?></strong>
                    <?php if (!empty($codigosDemoraDesc[$codigo])): ?>
                        <small class="text-muted">- <?= htmlspecialchars($codigosDemoraDesc[$codigo]) ?></small>
                    <?php endif; ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
(function () {
    const root = document.getElementById('filter_codigo_demora');
    const label = document.getElementById('codigo_demora_label');
    const checks = () => Array.from(root.querySelectorAll('.codigo-demora-check'));
    const buscar = document.getElementById('codigo_demora_buscar');
    const norm = t => t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    buscar.addEventListener('input', () => {
        const q = norm(buscar.value.trim());
        root.querySelectorAll('.codigo-demora-item').forEach(i => i.classList.toggle('d-none', q !== '' && !norm(i.textContent).includes(q)));
    });
    window.getCodigosDemoraSeleccionados = () => checks().filter(c => c.checked).map(c => c.value);
    function actualizarLabel() {
        const sel = window.getCodigosDemoraSeleccionados();
        label.textContent = sel.length === 0 ? '-- Todos --' : (sel.length <= 3 ? sel.join(', ') : sel.length + ' seleccionados');
    }
    /* Muestra solo los códigos de las aerolíneas dadas (lista vacía = todos). */
    window.filtrarCodigosPorAerolineas = (aerolineas) => {
        root.querySelectorAll('.codigo-demora-item').forEach(item => {
            if (!item.dataset.aerolineas) return;
            const propias = JSON.parse(item.dataset.aerolineas);
            const visible = !aerolineas.length || propias.some(a => aerolineas.includes(a));
            item.hidden = !visible;
            if (!visible) item.querySelector('input').checked = false;
        });
        actualizarLabel();
    };
    window.limpiarCodigosDemora = () => { checks().forEach(c => c.checked = false); root.querySelectorAll('.codigo-demora-item').forEach(i => { i.hidden = false; i.classList.remove('d-none'); }); buscar.value = ''; actualizarLabel(); };
    root.addEventListener('change', actualizarLabel);
})();
</script>
