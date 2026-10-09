<?php
/** Filtro multi-selección de aerolíneas (dropdown con casillas).
 *  Requiere $aerolineasUniques. Expone window.getAerolineasSeleccionadas() y
 *  window.limpiarAerolineas(); dispara el evento 'change' en #filter_aerolinea. */
?>
<div class="dropdown" id="filter_aerolinea">
    <button type="button" class="btn btn-outline-secondary form-select text-start" id="aerolinea_toggle"
            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <span id="aerolinea_label">-- Todas --</span>
    </button>
    <div class="dropdown-menu p-2 w-100" style="max-height:300px;overflow-y:auto;min-width:220px">
        <?php foreach ($aerolineasUniques as $i => $aerolinea): ?>
            <div class="form-check">
                <input class="form-check-input aerolinea-check" type="checkbox"
                       id="aerolinea_chk_<?= $i ?>" value="<?= htmlspecialchars($aerolinea) ?>">
                <label class="form-check-label w-100" for="aerolinea_chk_<?= $i ?>"><?= htmlspecialchars($aerolinea) ?></label>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
(function () {
    const root = document.getElementById('filter_aerolinea');
    const label = document.getElementById('aerolinea_label');
    const checks = () => Array.from(root.querySelectorAll('.aerolinea-check'));
    window.getAerolineasSeleccionadas = () => checks().filter(c => c.checked).map(c => c.value);
    function actualizarLabel() {
        const sel = window.getAerolineasSeleccionadas();
        label.textContent = sel.length === 0 ? '-- Todas --' : (sel.length <= 2 ? sel.join(', ') : sel.length + ' seleccionadas');
    }
    window.limpiarAerolineas = () => { checks().forEach(c => c.checked = false); actualizarLabel(); };
    root.addEventListener('change', actualizarLabel);
})();
</script>
