<div class="page-actions">
    <a href="<?= BASE_URL ?>/aircraft-types" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card" style="max-width:580px;">
    <div class="card-header">
        <h5><i class="bi bi-pencil-square"></i> Editar Tipo de Avión</h5>
        <span class="badge badge-primary"># <?= $type['id'] ?></span>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/aircraft-types/edit/<?= $type['id'] ?>" novalidate>

            <div class="row g-3">

                <div class="col-12">
                    <label for="airline_id" class="form-label">
                        Aerolínea <span class="required-mark">*</span>
                    </label>
                    <select class="form-select select2 <?= isset($errors['airline_id']) ? 'is-invalid' : '' ?>"
                            id="airline_id" name="airline_id">
                        <option value="">-- Seleccione aerolínea --</option>
                        <?php foreach ($airlines as $a): ?>
                            <option value="<?= $a['id'] ?>"
                                <?= $type['airline_id'] == $a['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['airline_id'])): ?>
                        <div class="invalid-feedback d-block"><?= $errors['airline_id'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label for="tipo" class="form-label">
                        Tipo de Avión <span class="required-mark">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['tipo']) ? 'is-invalid' : '' ?>"
                        id="tipo" name="tipo"
                        value="<?= htmlspecialchars($type['tipo']) ?>">
                    <?php if (isset($errors['tipo'])): ?>
                        <div class="invalid-feedback"><?= $errors['tipo'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label for="tiempo_cumplimiento" class="form-label">
                        Tiempo de Cumplimiento <span class="required-mark">*</span>
                    </label>
                    <select class="form-select <?= isset($errors['tiempo_cumplimiento']) ? 'is-invalid' : '' ?>"
                            id="tiempo_cumplimiento" name="tiempo_cumplimiento">
                        <?php foreach ($tiempoOptions as $t): ?>
                            <option value="<?= $t ?>"
                                <?= $type['tiempo_cumplimiento'] == $t ? 'selected' : '' ?>>
                                <?= $t ?> minutos
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['tiempo_cumplimiento'])): ?>
                        <div class="invalid-feedback"><?= $errors['tiempo_cumplimiento'] ?></div>
                    <?php endif; ?>
                </div>

            </div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Actualizar
                </button>
                <a href="<?= BASE_URL ?>/aircraft-types" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>
