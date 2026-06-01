<div class="page-actions">
    <a href="<?= BASE_URL ?>/users" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card" style="max-width:680px;">
    <div class="card-header">
        <h5><i class="bi bi-pencil-square"></i> Editar Usuario</h5>
        <span class="badge badge-primary"># <?= $user['id'] ?></span>
    </div>
    <div class="card-body">

        <form method="POST" action="<?= BASE_URL ?>/users/edit/<?= $user['id'] ?>" novalidate>

            <div class="row g-3">

                <div class="col-12">
                    <label for="nombre_completo" class="form-label">
                        Nombre Completo <span class="required-mark">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['nombre_completo']) ? 'is-invalid' : '' ?>"
                        id="nombre_completo" name="nombre_completo"
                        value="<?= htmlspecialchars($user['nombre_completo']) ?>">
                    <?php if (isset($errors['nombre_completo'])): ?>
                        <div class="invalid-feedback"><?= $errors['nombre_completo'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="cedula" class="form-label">
                        Cédula <span class="required-mark">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['cedula']) ? 'is-invalid' : '' ?>"
                        id="cedula" name="cedula"
                        value="<?= htmlspecialchars($user['cedula']) ?>">
                    <?php if (isset($errors['cedula'])): ?>
                        <div class="invalid-feedback"><?= $errors['cedula'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="usuario" class="form-label">
                        Usuario <span class="required-mark">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['usuario']) ? 'is-invalid' : '' ?>"
                        id="usuario" name="usuario"
                        value="<?= htmlspecialchars($user['usuario']) ?>"
                        autocomplete="off">
                    <?php if (isset($errors['usuario'])): ?>
                        <div class="invalid-feedback"><?= $errors['usuario'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label for="rol_id" class="form-label">
                        Rol <span class="required-mark">*</span>
                    </label>
                    <select class="form-select <?= isset($errors['rol_id']) ? 'is-invalid' : '' ?>"
                            id="rol_id" name="rol_id">
                        <option value="">-- Seleccione un rol --</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= $rol['id'] ?>"
                                <?= $user['rol_id'] == $rol['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rol['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['rol_id'])): ?>
                        <div class="invalid-feedback"><?= $errors['rol_id'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <div class="alert alert-info" style="font-size:13px;">
                        <i class="bi bi-info-circle-fill"></i>
                        Deje los campos de contraseña vacíos si no desea cambiarla.
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Nueva Contraseña</label>
                    <input type="password"
                        class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                        id="password" name="password"
                        placeholder="Dejar vacío para no cambiar"
                        autocomplete="new-password">
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= $errors['password'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="password_confirm" class="form-label">Confirmar Nueva Contraseña</label>
                    <input type="password"
                        class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                        id="password_confirm" name="password_confirm"
                        placeholder="Repetir nueva contraseña">
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="invalid-feedback"><?= $errors['password_confirm'] ?></div>
                    <?php endif; ?>
                </div>

            </div>

            <hr class="divider">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Actualizar Usuario
                </button>
                <a href="<?= BASE_URL ?>/users" class="btn btn-light">Cancelar</a>
            </div>

        </form>
    </div>
</div>
