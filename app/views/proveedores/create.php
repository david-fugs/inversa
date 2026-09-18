<div class="page-actions">
    <a href="<?= BASE_URL ?>/proveedores" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card" style="max-width:640px;">
    <div class="card-header">
        <h5><i class="bi bi-plus-circle-fill"></i> Nuevo Proveedor</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/proveedores/create" novalidate>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="tipo_identificacion" class="form-label">
                        Tipo de Identificación <span class="required-mark">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['tipo_identificacion']) ? 'is-invalid' : '' ?>"
                        id="tipo_identificacion" name="tipo_identificacion"
                        value="<?= htmlspecialchars($old['tipo_identificacion'] ?? '') ?>"
                        placeholder="Ej: 01, CC, NIT"
                        maxlength="10"
                        autofocus>
                    <?php if (isset($errors['tipo_identificacion'])): ?>
                        <div class="invalid-feedback"><?= $errors['tipo_identificacion'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8 mb-3">
                    <label for="numero_identificacion" class="form-label">
                        Número de Identificación <span class="required-mark">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['numero_identificacion']) ? 'is-invalid' : '' ?>"
                        id="numero_identificacion" name="numero_identificacion"
                        value="<?= htmlspecialchars($old['numero_identificacion'] ?? '') ?>"
                        placeholder="Cédula o NIT">
                    <?php if (isset($errors['numero_identificacion'])): ?>
                        <div class="invalid-feedback"><?= $errors['numero_identificacion'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="nombre" class="form-label">
                    Nombre del Proveedor <span class="required-mark">*</span>
                </label>
                <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>"
                    id="nombre" name="nombre"
                    value="<?= htmlspecialchars($old['nombre'] ?? '') ?>">
                <?php if (isset($errors['nombre'])): ?>
                    <div class="invalid-feedback"><?= $errors['nombre'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="banco_id" class="form-label">
                    Banco <span class="required-mark">*</span>
                </label>
                <select class="form-select select2 <?= isset($errors['banco_id']) ? 'is-invalid' : '' ?>" id="banco_id" name="banco_id">
                    <option value="">-- Seleccione un banco --</option>
                    <?php foreach ($bancos as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= (int)($old['banco_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['codigo'] . ' - ' . $b['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['banco_id'])): ?>
                    <div class="invalid-feedback d-block"><?= $errors['banco_id'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="tipo_producto_id" class="form-label">
                    Tipo de Producto <span class="required-mark">*</span>
                </label>
                <select class="form-select select2 <?= isset($errors['tipo_producto_id']) ? 'is-invalid' : '' ?>" id="tipo_producto_id" name="tipo_producto_id">
                    <option value="">-- Seleccione un tipo de producto --</option>
                    <?php foreach ($tiposProducto as $tp): ?>
                        <option value="<?= $tp['id'] ?>" <?= (int)($old['tipo_producto_id'] ?? 0) === (int)$tp['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tp['codigo'] . ' - ' . $tp['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['tipo_producto_id'])): ?>
                    <div class="invalid-feedback d-block"><?= $errors['tipo_producto_id'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="numero_producto" class="form-label">
                    Número de Producto o Servicio <span class="required-mark">*</span>
                </label>
                <input type="text" class="form-control <?= isset($errors['numero_producto']) ? 'is-invalid' : '' ?>"
                    id="numero_producto" name="numero_producto"
                    value="<?= htmlspecialchars($old['numero_producto'] ?? '') ?>"
                    placeholder="Nro. de cuenta / servicio">
                <?php if (isset($errors['numero_producto'])): ?>
                    <div class="invalid-feedback"><?= $errors['numero_producto'] ?></div>
                <?php endif; ?>
            </div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Guardar
                </button>
                <a href="<?= BASE_URL ?>/proveedores" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>
