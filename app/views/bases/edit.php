<div class="page-actions">
    <a href="<?= BASE_URL ?>/bases" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card" style="max-width:520px;">
    <div class="card-header">
        <h5><i class="bi bi-pencil-square"></i> Editar Base</h5>
        <span class="badge badge-primary"># <?= $base['id'] ?></span>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/bases/edit/<?= $base['id'] ?>" novalidate>
            <div class="mb-3">
                <label for="nombre" class="form-label">
                    Nombre de la Base <span class="required-mark">*</span>
                </label>
                <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>"
                    id="nombre" name="nombre"
                    value="<?= htmlspecialchars($base['nombre']) ?>"
                    style="text-transform:uppercase;"
                    autofocus>
                <?php if (isset($errors['nombre'])): ?>
                    <div class="invalid-feedback"><?= $errors['nombre'] ?></div>
                <?php endif; ?>
            </div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Actualizar
                </button>
                <a href="<?= BASE_URL ?>/bases" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>
