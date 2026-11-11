<?php
/** Formulario de equipo. Requiere: $equipo (o null), $bases, $errors, $old. */
$accion = $equipo ? BASE_URL . '/equipos/' . (int)$equipo['id'] . '/edit' : BASE_URL . '/equipos/nuevo';
$baseSel = (int)($old['base_id'] ?? 0);
?>
<div class="page-actions">
    <a href="<?= BASE_URL ?>/equipos" class="btn btn-light"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card" style="max-width:520px;">
    <div class="card-header">
        <h5><i class="bi bi-gear-wide-connected"></i> <?= $equipo ? 'Editar Equipo' : 'Nuevo Equipo' ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $accion ?>" novalidate>
            <div class="mb-3">
                <label for="base_id" class="form-label">Base <span class="required-mark">*</span></label>
                <select class="form-select js-s2 <?= isset($errors['base_id']) ? 'is-invalid' : '' ?>" id="base_id" name="base_id">
                    <option value=""></option>
                    <?php foreach ($bases as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= $baseSel === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['base_id'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['base_id']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del equipo <span class="required-mark">*</span></label>
                <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>" id="nombre" name="nombre" maxlength="120"
                       value="<?= htmlspecialchars((string)($old['nombre'] ?? '')) ?>">
                <?php if (isset($errors['nombre'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['nombre']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="rpm" class="form-label">RPM <span class="required-mark">*</span></label>
                <input type="number" min="1" step="1" class="form-control <?= isset($errors['rpm']) ? 'is-invalid' : '' ?>" id="rpm" name="rpm"
                       value="<?= htmlspecialchars((string)($old['rpm'] ?? '')) ?>">
                <?php if (isset($errors['rpm'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['rpm']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad de equipos <span class="required-mark">*</span></label>
                <input type="number" min="1" max="9999" step="1" class="form-control <?= isset($errors['cantidad']) ? 'is-invalid' : '' ?>" id="cantidad" name="cantidad"
                       value="<?= htmlspecialchars((string)($old['cantidad'] ?? '1')) ?>">
                <div class="form-text">Unidades que tiene la base de este equipo (se usa en el Indicador de Disponibilidad).</div>
                <?php if (isset($errors['cantidad'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['cantidad']) ?></div><?php endif; ?>
            </div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
                <a href="<?= BASE_URL ?>/equipos" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
window.addEventListener('load', function () {   // después de app.js (que inicializa los .select2 al cargar el DOM)
    $('#base_id').select2({ placeholder: '-- Seleccione la base --', allowClear: true, width: '100%' });
});
</script>
