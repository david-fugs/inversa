<?php
/** Formulario de control de aceites. Requiere: $registro (o null), $bases, $equipos, $errors, $old. */
$accion = $registro ? BASE_URL . '/control-aceites/' . (int)$registro['id'] . '/edit' : BASE_URL . '/control-aceites/nuevo';
?>
<div class="page-actions">
    <a href="<?= BASE_URL ?>/control-aceites" class="btn btn-light"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card" style="max-width:560px;">
    <div class="card-header">
        <h5><i class="bi bi-droplet-half"></i> <?= $registro ? 'Editar Control de Aceite' : 'Nuevo Control de Aceite' ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $accion ?>" novalidate>
            <?php require APP_PATH . '/views/equipos/_base_equipo.php'; ?>

            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha <span class="required-mark">*</span></label>
                <input type="date" class="form-control <?= isset($errors['fecha']) ? 'is-invalid' : '' ?>" id="fecha" name="fecha"
                       value="<?= htmlspecialchars((string)($old['fecha'] ?? '')) ?>">
                <?php if (isset($errors['fecha'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['fecha']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="cantidad_galones" class="form-label">Cantidad x galones <span class="required-mark">*</span></label>
                <input type="number" min="0" step="0.01" class="form-control <?= isset($errors['cantidad_galones']) ? 'is-invalid' : '' ?>" id="cantidad_galones" name="cantidad_galones"
                       value="<?= htmlspecialchars((string)($old['cantidad_galones'] ?? '')) ?>">
                <?php if (isset($errors['cantidad_galones'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['cantidad_galones']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="mantenimiento" class="form-label">Mantenimiento <span class="required-mark">*</span></label>
                <select class="form-select <?= isset($errors['mantenimiento']) ? 'is-invalid' : '' ?>" id="mantenimiento" name="mantenimiento">
                    <option value="">-- Seleccione --</option>
                    <?php foreach (ControlAceite::MANTENIMIENTOS as $k => $label): ?>
                        <option value="<?= $k ?>" <?= ($old['mantenimiento'] ?? '') === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['mantenimiento'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['mantenimiento']) ?></div><?php endif; ?>
            </div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
                <a href="<?= BASE_URL ?>/control-aceites" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>
