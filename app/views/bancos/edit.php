<div class="page-actions">
    <a href="<?= BASE_URL ?>/bancos" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card" style="max-width:520px;">
    <div class="card-header">
        <h5><i class="bi bi-pencil-square"></i> Editar Banco</h5>
        <span class="badge badge-primary"># <?= $banco['id'] ?></span>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/bancos/edit/<?= $banco['id'] ?>" novalidate>

            <div class="mb-3">
                <label for="codigo" class="form-label">
                    Código <span class="required-mark">*</span>
                </label>
                <input type="text" class="form-control <?= isset($errors['codigo']) ? 'is-invalid' : '' ?>"
                    id="codigo" name="codigo"
                    value="<?= htmlspecialchars($banco['codigo']) ?>"
                    autofocus>
                <?php if (isset($errors['codigo'])): ?>
                    <div class="invalid-feedback"><?= $errors['codigo'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="nombre" class="form-label">
                    Nombre del Banco <span class="required-mark">*</span>
                </label>
                <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>"
                    id="nombre" name="nombre"
                    value="<?= htmlspecialchars($banco['nombre']) ?>">
                <?php if (isset($errors['nombre'])): ?>
                    <div class="invalid-feedback"><?= $errors['nombre'] ?></div>
                <?php endif; ?>
            </div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Actualizar
                </button>
                <a href="<?= BASE_URL ?>/bancos" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>
