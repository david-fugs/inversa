<div class="page-actions">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTarifa" onclick="abrirModalCrearTarifa()">
        <i class="bi bi-plus-lg"></i> Nueva Tarifa
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-cash-coin"></i> Listado de Tarifas / Cobros GPU</h5>
        <span class="badge badge-primary"><?= count($tarifas) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Aerolínea</th>
                        <th>Tarifa Primeros Minutos</th>
                        <th>Tarifa por Fracción</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tarifas as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td>
                                <span class="badge badge-secondary"><?= htmlspecialchars($t['airline_nombre']) ?></span>
                            </td>
                            <td>
                                <?php if ($t['primeros_minutos'] !== null): ?>
                                    <span class="badge badge-info">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= (int)$t['primeros_minutos'] ?> minutos
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted"><small>Sin tarifa inicial</small></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    cada <?= (int)$t['fraccion_minutos'] ?> minutos
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-icon btn-outline-primary btn-sm" title="Editar"
                                        data-bs-toggle="modal" data-bs-target="#modalTarifa"
                                        onclick='abrirModalEditarTarifa(<?= htmlspecialchars(json_encode([
                                            "id"               => (int)$t["id"],
                                            "airline_id"       => (int)$t["airline_id"],
                                            "primeros_minutos" => $t["primeros_minutos"] !== null ? (int)$t["primeros_minutos"] : null,
                                            "fraccion_minutos" => (int)$t["fraccion_minutos"],
                                        ]), ENT_QUOTES) ?>)'>
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <a href="<?= BASE_URL ?>/tarifas-cobros/delete/<?= $t['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar la tarifa GPU de '<?= htmlspecialchars($t['airline_nombre']) ?>'?">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══ MODAL: Crear / Editar Tarifa GPU ═══════════════════ -->
<div class="modal fade" id="modalTarifa" tabindex="-1" aria-labelledby="modalTarifaTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formTarifa" action="<?= BASE_URL ?>/tarifas-cobros/create" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTarifaTitle">
                        <i class="bi bi-plus-circle-fill"></i> Nueva Tarifa GPU
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="tarifa_airline_id" class="form-label">
                            Aerolínea <span class="required-mark">*</span>
                        </label>
                        <select class="form-select <?= isset($errors['airline_id']) ? 'is-invalid' : '' ?>"
                                id="tarifa_airline_id" name="airline_id">
                            <option value="">-- Seleccione aerolínea --</option>
                            <?php foreach ($airlines as $a): ?>
                                <option value="<?= $a['id'] ?>"
                                    <?= ($old['airline_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['airline_id'])): ?>
                            <div class="invalid-feedback d-block"><?= $errors['airline_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="tarifa_primeros_minutos" class="form-label">Tarifa para los primeros</label>
                        <div class="input-group">
                            <input type="number" step="1" min="1"
                                class="form-control <?= isset($errors['primeros_minutos']) ? 'is-invalid' : '' ?>"
                                id="tarifa_primeros_minutos" name="primeros_minutos"
                                value="<?= htmlspecialchars((string)($old['primeros_minutos'] ?? '')) ?>"
                                placeholder="Ej: 60">
                            <span class="input-group-text">minutos</span>
                            <?php if (isset($errors['primeros_minutos'])): ?>
                                <div class="invalid-feedback"><?= $errors['primeros_minutos'] ?></div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Deje vacío si la aerolínea no maneja tarifa para los primeros minutos (solo fracción).</small>
                    </div>

                    <div class="mb-3">
                        <label for="tarifa_fraccion_minutos" class="form-label">
                            Tarifa por fracción, cada <span class="required-mark">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="1" min="1"
                                class="form-control <?= isset($errors['fraccion_minutos']) ? 'is-invalid' : '' ?>"
                                id="tarifa_fraccion_minutos" name="fraccion_minutos"
                                value="<?= htmlspecialchars((string)($old['fraccion_minutos'] ?? '')) ?>"
                                placeholder="Ej: 15">
                            <span class="input-group-text">minutos</span>
                            <?php if (isset($errors['fraccion_minutos'])): ?>
                                <div class="invalid-feedback"><?= $errors['fraccion_minutos'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="modalTarifaSubmit">
                        <i class="bi bi-check-lg"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalCrearTarifa() {
    document.getElementById('formTarifa').action = '<?= BASE_URL ?>/tarifas-cobros/create';
    document.getElementById('modalTarifaTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Nueva Tarifa GPU';
    document.getElementById('modalTarifaSubmit').innerHTML = '<i class="bi bi-check-lg"></i> Guardar';
    document.getElementById('tarifa_airline_id').value = '';
    document.getElementById('tarifa_primeros_minutos').value = '';
    document.getElementById('tarifa_fraccion_minutos').value = '';
}

function abrirModalEditarTarifa(t) {
    document.getElementById('formTarifa').action = '<?= BASE_URL ?>/tarifas-cobros/edit/' + t.id;
    document.getElementById('modalTarifaTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Editar Tarifa GPU';
    document.getElementById('modalTarifaSubmit').innerHTML = '<i class="bi bi-check-lg"></i> Actualizar';
    document.getElementById('tarifa_airline_id').value = t.airline_id;
    document.getElementById('tarifa_primeros_minutos').value = (t.primeros_minutos === null || t.primeros_minutos === undefined) ? '' : t.primeros_minutos;
    document.getElementById('tarifa_fraccion_minutos').value = t.fraccion_minutos;
}

<?php if (!empty($errors)): ?>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($openModal === 'edit' && isset($old['id'])): ?>
        abrirModalEditarTarifa({
            id: <?= (int)$old['id'] ?>,
            airline_id: <?= json_encode($old['airline_id'] ?? '') ?>,
            primeros_minutos: <?= $old['primeros_minutos'] !== null ? (int)$old['primeros_minutos'] : 'null' ?>,
            fraccion_minutos: <?= json_encode($old['fraccion_minutos'] ?? '') ?>
        });
    <?php else: ?>
        abrirModalCrearTarifa();
        document.getElementById('tarifa_airline_id').value = <?= json_encode($old['airline_id'] ?? '') ?>;
        document.getElementById('tarifa_primeros_minutos').value = <?= json_encode((string)($old['primeros_minutos'] ?? '')) ?>;
        document.getElementById('tarifa_fraccion_minutos').value = <?= json_encode((string)($old['fraccion_minutos'] ?? '')) ?>;
    <?php endif; ?>
    var modalEl = document.getElementById('modalTarifa');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
});
<?php endif; ?>
</script>
