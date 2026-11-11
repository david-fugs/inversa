<?php
/** Formulario de novedad diaria. Requiere: $registro (o null), $bases, $equipos, $errors, $old, $mostrarCantidad. */
$accion = $registro ? BASE_URL . '/indicador-disponibilidad/' . (int)$registro['id'] . '/edit' : BASE_URL . '/indicador-disponibilidad/nuevo';
$campos = ['inoperativo' => 'Inoperativo', 'correctivo' => 'Correctivo', 'preventivo' => 'Preventivo'];
?>
<div class="page-actions">
    <a href="<?= BASE_URL ?>/indicador-disponibilidad" class="btn btn-light"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card" style="max-width:560px;">
    <div class="card-header">
        <h5><i class="bi bi-calendar-check"></i> <?= $registro ? 'Editar Novedad' : 'Nueva Novedad' ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $accion ?>" novalidate>
            <?php require APP_PATH . '/views/equipos/_base_equipo.php'; ?>

            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha <span class="required-mark">*</span></label>
                <input type="date" class="form-control <?= isset($errors['fecha']) ? 'is-invalid' : '' ?>" id="fecha" name="fecha" max="<?= date('Y-m-d') ?>"
                       value="<?= htmlspecialchars((string)($old['fecha'] ?? '')) ?>">
                <?php if (isset($errors['fecha'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['fecha']) ?></div><?php endif; ?>
            </div>

            <div class="row g-2 mb-1">
                <?php foreach ($campos as $k => $label): ?>
                    <div class="col-md-4">
                        <label for="<?= $k ?>" class="form-label"><?= $label ?></label>
                        <input type="number" min="0" step="1" class="form-control js-novedad <?= isset($errors[$k]) ? 'is-invalid' : '' ?>" id="<?= $k ?>" name="<?= $k ?>"
                               value="<?= htmlspecialchars((string)($old[$k] ?? '0')) ?>">
                        <?php if (isset($errors[$k])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors[$k]) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-text mb-3">
                Escriba cuántas unidades del equipo están inoperativas, en correctivo y en preventivo ese día (0 si ninguna).
            </div>

            <div class="alert alert-light border py-2 small" id="vistaPrevia" style="display:none;"></div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
                <a href="<?= BASE_URL ?>/indicador-disponibilidad" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    var previa = document.getElementById('vistaPrevia');
    var cantidadRegistro = <?= $registro ? (int)$registro['cantidad'] : 'null' ?>;
    var equipoRegistro = <?= $registro ? (int)$registro['equipo_id'] : 'null' ?>;

    function valor(id) { return parseInt(document.getElementById(id).value || '0', 10) || 0; }

    function calcular() {
        var id = parseInt($('#equipo_id').val() || '0', 10);
        var eq = (window.equiposLista || []).filter(function (e) { return e.id === id; })[0];
        if (!eq) { previa.style.display = 'none'; return; }
        var cant = (id === equipoRegistro && cantidadRegistro !== null) ? cantidadRegistro : eq.cantidad;
        var disp = ((cant - valor('inoperativo')) / cant * 100);
        var conf = ((cant - valor('correctivo')) / cant * 100);
        previa.style.display = '';
        previa.textContent = 'Cantidad: ' + cant + ' · Disponibilidad: ' + disp.toFixed(1) + '% · Confiabilidad: ' + conf.toFixed(1) + '%';
    }

    $('#equipo_id').on('change', calcular);
    document.querySelectorAll('.js-novedad').forEach(function (i) { i.addEventListener('input', calcular); });
    calcular();
});
</script>
