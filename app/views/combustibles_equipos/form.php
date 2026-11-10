<?php
/** Formulario de combustible de equipos. Requiere: $registro (o null), $bases, $equipos, $errors, $old. */
$accion = $registro ? BASE_URL . '/combustibles-equipos/' . (int)$registro['id'] . '/edit' : BASE_URL . '/combustibles-equipos/nuevo';
?>
<div class="page-actions">
    <a href="<?= BASE_URL ?>/combustibles-equipos" class="btn btn-light"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card" style="max-width:560px;">
    <div class="card-header">
        <h5><i class="bi bi-fuel-pump-fill"></i> <?= $registro ? 'Editar Tanqueo' : 'Nuevo Tanqueo' ?></h5>
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
                <label for="cantidad_galones" class="form-label">Cantidad de tanqueo x galones <span class="required-mark">*</span></label>
                <input type="number" min="0" step="0.01" class="form-control <?= isset($errors['cantidad_galones']) ? 'is-invalid' : '' ?>" id="cantidad_galones" name="cantidad_galones"
                       value="<?= htmlspecialchars((string)($old['cantidad_galones'] ?? '')) ?>">
                <?php if (isset($errors['cantidad_galones'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['cantidad_galones']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="horometro" class="form-label">Horómetro <span class="required-mark">*</span></label>
                <input type="text" maxlength="50" class="form-control <?= isset($errors['horometro']) ? 'is-invalid' : '' ?>" id="horometro" name="horometro"
                       value="<?= htmlspecialchars((string)($old['horometro'] ?? '')) ?>">
                <?php if (isset($errors['horometro'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['horometro']) ?></div><?php endif; ?>
            </div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
                <a href="<?= BASE_URL ?>/combustibles-equipos" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>
