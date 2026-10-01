<?php
// Columnas: una por aerolínea; si hay códigos sin asignar se agrega una columna extra
$columnas = [];
foreach ($airlines as $a) {
    $columnas[(int)$a['id']] = ['id' => (int)$a['id'], 'nombre' => $a['nombre'], 'items' => []];
}
$sinAerolinea = [];
foreach ($codigoDemoras as $c) {
    $aid = (int)($c['airline_id'] ?? 0);
    if (isset($columnas[$aid])) {
        $columnas[$aid]['items'][] = $c;
    } else {
        $sinAerolinea[] = $c;
    }
}
if (!empty($sinAerolinea)) {
    $columnas[0] = ['id' => 0, 'nombre' => 'Sin aerolínea', 'items' => $sinAerolinea];
}
$colClass = count($columnas) > 3 ? 'col-xl-3' : 'col-xl-4';
?>

<div class="page-actions">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCodigoDemora" onclick="abrirModalCrearCodigoDemora()">
        <i class="bi bi-plus-lg"></i> Nuevo Código Demora
    </button>
</div>

<div class="row g-3">
    <?php foreach ($columnas as $col):
        $logoFile = 'logo_' . strtolower($col['nombre']) . '.png';
        $tieneLogo = $col['id'] > 0 && is_file(ROOT_PATH . '/img/' . $logoFile);
    ?>
    <div class="col-12 col-lg-6 <?= $colClass ?>">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-3">
                    <?php if ($tieneLogo): ?>
                        <img src="<?= BASE_URL ?>/img/<?= $logoFile ?>" alt="<?= htmlspecialchars($col['nombre']) ?>" style="height:64px;max-width:200px;object-fit:contain;<?= strtolower($col['nombre']) === 'avianca' ? 'background:linear-gradient(90deg,#e8001c 0%,#d4004a 55%,#b0007f 100%);padding:8px 16px;border-radius:8px;' : '' ?>">
                    <?php endif; ?>
                    <h5 class="mb-0"><?= htmlspecialchars($col['nombre']) ?></h5>
                </div>
                <span class="badge badge-primary"><?= count($col['items']) ?> registros</span>
            </div>
            <div class="card-body p-0">
                <div class="table-wrapper">
                    <table class="table data-table" data-hide-length style="width:100%">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($col['items'] as $c): ?>
                                <tr>
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
                                                    "airline_id"  => (int)($c["airline_id"] ?? 0),
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
    </div>
    <?php endforeach; ?>
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
                        <label for="codigo_demora_airline" class="form-label">
                            Aerolínea <span class="required-mark">*</span>
                        </label>
                        <select class="form-select <?= isset($errors['airline_id']) ? 'is-invalid' : '' ?>"
                            id="codigo_demora_airline" name="airline_id">
                            <option value="">Seleccione...</option>
                            <?php foreach ($airlines as $a): ?>
                                <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['airline_id'])): ?>
                            <div class="invalid-feedback d-block"><?= $errors['airline_id'] ?></div>
                        <?php endif; ?>
                    </div>

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
function abrirModalCrearCodigoDemora(airlineId) {
    document.getElementById('formCodigoDemora').action = '<?= BASE_URL ?>/codigo-demoras/create';
    document.getElementById('modalCodigoDemoraTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Nuevo Código Demora';
    document.getElementById('modalCodigoDemoraSubmit').innerHTML = '<i class="bi bi-check-lg"></i> Guardar';
    document.getElementById('codigo_demora_airline').value = airlineId || '';
    document.getElementById('codigo_demora_codigo').value = '';
    document.getElementById('codigo_demora_descripcion').value = '';
}

function abrirModalEditarCodigoDemora(c) {
    document.getElementById('formCodigoDemora').action = '<?= BASE_URL ?>/codigo-demoras/edit/' + c.id;
    document.getElementById('modalCodigoDemoraTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Editar Código Demora';
    document.getElementById('modalCodigoDemoraSubmit').innerHTML = '<i class="bi bi-check-lg"></i> Actualizar';
    document.getElementById('codigo_demora_airline').value = c.airline_id || '';
    document.getElementById('codigo_demora_codigo').value = c.codigo || '';
    document.getElementById('codigo_demora_descripcion').value = c.descripcion || '';
}

<?php if (!empty($errors)): ?>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($openModal === 'edit' && isset($old['id'])): ?>
        abrirModalEditarCodigoDemora({
            id: <?= (int)$old['id'] ?>,
            codigo: <?= json_encode($old['codigo'] ?? '') ?>,
            descripcion: <?= json_encode($old['descripcion'] ?? '') ?>,
            airline_id: <?= (int)($old['airline_id'] ?? 0) ?>
        });
    <?php else: ?>
        abrirModalCrearCodigoDemora(<?= (int)($old['airline_id'] ?? 0) ?>);
        document.getElementById('codigo_demora_codigo').value = <?= json_encode($old['codigo'] ?? '') ?>;
        document.getElementById('codigo_demora_descripcion').value = <?= json_encode($old['descripcion'] ?? '') ?>;
    <?php endif; ?>
    var modalEl = document.getElementById('modalCodigoDemora');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
});
<?php endif; ?>
</script>
