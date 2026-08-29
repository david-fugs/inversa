<div class="page-actions">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCodigoDemora" onclick="abrirModalCrearCodigoDemora()">
        <i class="bi bi-plus-lg"></i> Nuevo Código Demora
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-clock-history"></i> Listado de Código Demoras</h5>
        <span class="badge badge-primary"><?= count($codigoDemoras) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" id="tablaCodigoDemoras" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($codigoDemoras as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($c['codigo']) ?></span></td>
                            <td><?= htmlspecialchars($c['descripcion']) ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-icon btn-outline-primary btn-sm" title="Editar"
                                        data-bs-toggle="modal" data-bs-target="#modalCodigoDemora"
                                        onclick='abrirModalEditarCodigoDemora(<?= htmlspecialchars(json_encode([
                                            "id"          => (int)$c["id"],
                                            "codigo"      => $c["codigo"],
                                            "descripcion" => $c["descripcion"],
                                        ]), ENT_QUOTES) ?>)'>
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <a href="<?= BASE_URL ?>/codigo-demoras/delete/<?= $c['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar el código de demora '<?= htmlspecialchars($c['codigo']) ?>'?">
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

<!-- ══ MODAL: Crear / Editar Código Demora ═══════════════════ -->
<div class="modal fade" id="modalCodigoDemora" tabindex="-1" aria-labelledby="modalCodigoDemoraTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formCodigoDemora" action="<?= BASE_URL ?>/codigo-demoras/create" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCodigoDemoraTitle">
                        <i class="bi bi-plus-circle-fill"></i> Nuevo Código Demora
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="codigo_demora_codigo" class="form-label">
                            Código Demora <span class="required-mark">*</span>
                        </label>
                        <input type="text"
                            class="form-control <?= isset($errors['codigo']) ? 'is-invalid' : '' ?>"
                            id="codigo_demora_codigo" name="codigo"
                            value="<?= htmlspecialchars($old['codigo'] ?? '') ?>"
                            placeholder="Ej: D001" style="text-transform:uppercase;" maxlength="20">
                        <?php if (isset($errors['codigo'])): ?>
                            <div class="invalid-feedback d-block"><?= $errors['codigo'] ?></div>
                        <?php endif; ?>
                        <small class="text-muted">Puede contener letras y números (sin espacios).</small>
                    </div>

                    <div class="mb-3">
                        <label for="codigo_demora_descripcion" class="form-label">
                            Descripción <span class="required-mark">*</span>
                        </label>
                        <textarea class="form-control <?= isset($errors['descripcion']) ? 'is-invalid' : '' ?>"
                            id="codigo_demora_descripcion" name="descripcion" rows="3"
                            maxlength="255"><?= htmlspecialchars($old['descripcion'] ?? '') ?></textarea>
                        <?php if (isset($errors['descripcion'])): ?>
                            <div class="invalid-feedback d-block"><?= $errors['descripcion'] ?></div>
                        <?php endif; ?>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="modalCodigoDemoraSubmit">
                        <i class="bi bi-check-lg"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalCrearCodigoDemora() {
    document.getElementById('formCodigoDemora').action = '<?= BASE_URL ?>/codigo-demoras/create';
    document.getElementById('modalCodigoDemoraTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Nuevo Código Demora';
    document.getElementById('modalCodigoDemoraSubmit').innerHTML = '<i class="bi bi-check-lg"></i> Guardar';
    document.getElementById('codigo_demora_codigo').value = '';
    document.getElementById('codigo_demora_descripcion').value = '';
}

function abrirModalEditarCodigoDemora(c) {
    document.getElementById('formCodigoDemora').action = '<?= BASE_URL ?>/codigo-demoras/edit/' + c.id;
    document.getElementById('modalCodigoDemoraTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Editar Código Demora';
    document.getElementById('modalCodigoDemoraSubmit').innerHTML = '<i class="bi bi-check-lg"></i> Actualizar';
    document.getElementById('codigo_demora_codigo').value = c.codigo || '';
    document.getElementById('codigo_demora_descripcion').value = c.descripcion || '';
}

<?php if (!empty($errors)): ?>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($openModal === 'edit' && isset($old['id'])): ?>
        abrirModalEditarCodigoDemora({
            id: <?= (int)$old['id'] ?>,
            codigo: <?= json_encode($old['codigo'] ?? '') ?>,
            descripcion: <?= json_encode($old['descripcion'] ?? '') ?>
        });
    <?php else: ?>
        abrirModalCrearCodigoDemora();
        document.getElementById('codigo_demora_codigo').value = <?= json_encode($old['codigo'] ?? '') ?>;
        document.getElementById('codigo_demora_descripcion').value = <?= json_encode($old['descripcion'] ?? '') ?>;
    <?php endif; ?>
    var modalEl = document.getElementById('modalCodigoDemora');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
});
<?php endif; ?>

window.addEventListener('load', function () {
    $('#tablaCodigoDemoras').DataTable();
});
</script>
