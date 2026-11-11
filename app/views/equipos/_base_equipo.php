<?php
/** Selects Base + Equipo (con buscador). El equipo se filtra por la base elegida y muestra sus RPM al lado.
 *  Requiere: $bases, $equipos, $errors, $old. */
$baseSel   = (int)($old['base_id'] ?? 0);
$equipoSel = (int)($old['equipo_id'] ?? 0);
$equiposJs = array_map(fn($e) => [
    'id'    => (int)$e['id'],
    'base'  => (int)$e['base_id'],
    'texto' => !empty($mostrarCantidad)
        ? $e['nombre'] . '  ·  Cantidad: ' . (int)$e['cantidad']
        : $e['nombre'] . '  ·  ' . number_format((int)$e['rpm'], 0, ',', '.') . ' RPM',
    'cantidad' => (int)$e['cantidad'],
], $equipos);
?>
<div class="mb-3">
    <label for="base_id" class="form-label">Base <span class="required-mark">*</span></label>
    <select class="form-select js-s2 <?= isset($errors['base_id']) ? 'is-invalid' : '' ?>" id="base_id" name="base_id" data-placeholder="-- Seleccione la base --">
        <option value=""></option>
        <?php foreach ($bases as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= $baseSel === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($errors['base_id'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['base_id']) ?></div><?php endif; ?>
</div>

<div class="mb-3">
    <label for="equipo_id" class="form-label">Equipo <span class="required-mark">*</span></label>
    <select class="form-select js-s2 <?= isset($errors['equipo_id']) ? 'is-invalid' : '' ?>" id="equipo_id" name="equipo_id" data-placeholder="-- Seleccione el equipo --">
        <option value=""></option>
    </select>
    <?php if (isset($errors['equipo_id'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['equipo_id']) ?></div><?php endif; ?>
    <div class="form-text text-danger" id="equipoSinDatos" style="display:none;">
        Esta base no tiene equipos registrados. Créelos en el menú <a href="<?= BASE_URL ?>/equipos/nuevo">Equipos</a>.
    </div>
</div>

<script>
window.addEventListener('load', function () {   // después de app.js (que inicializa los .select2 al cargar el DOM)
    var equipos = <?= json_encode($equiposJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    window.equiposLista = equipos;
    var seleccionado = <?= $equipoSel ?>;
    var $base = $('#base_id'), $equipo = $('#equipo_id');
    var idioma = { noResults: function () { return 'No se encontraron equipos'; }, searching: function () { return 'Buscando...'; } };

    [$base, $equipo].forEach(function ($s) {
        $s.select2({ placeholder: $s.data('placeholder'), allowClear: true, width: '100%', language: idioma });
    });

    function cargarEquipos() {
        var baseId = parseInt($base.val() || '0', 10);
        $equipo.empty().append(new Option('', '', false, false));
        equipos.filter(function (e) { return e.base === baseId; }).forEach(function (e) {
            $equipo.append(new Option(e.texto, e.id, false, e.id === seleccionado));
        });
        var hay = equipos.some(function (e) { return e.base === baseId; });
        $equipo.prop('disabled', !baseId || !hay);
        document.getElementById('equipoSinDatos').style.display = (baseId && !hay) ? '' : 'none';
        $equipo.trigger('change.select2');
    }

    $base.on('change', function () { seleccionado = 0; cargarEquipos(); });
    cargarEquipos();
});
</script>
