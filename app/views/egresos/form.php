<?php
/** Formulario de Egreso / Legalización (crear y editar). Requiere: $doc (null al crear), $lotes, $errors, $old. */
$tipos   = EgresoDocumento::TIPOS;
$accion  = $doc ? BASE_URL . '/pagos/egresos/' . $doc['id'] . '/edit' : BASE_URL . '/pagos/egresos/nuevo';
$sel     = array_map('intval', (array)($old['lote_ids'] ?? []));
?>
<div class="page-actions">
    <a href="<?= BASE_URL ?>/pagos/egresos" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card" style="max-width:720px;">
    <div class="card-header">
        <h5><i class="bi bi-receipt-cutoff"></i> <?= $doc ? 'Editar documento #' . (int)$doc['id'] : 'Nuevo documento' ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $accion ?>" enctype="multipart/form-data" novalidate>

            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo <span class="required-mark">*</span></label>
                <select class="form-select <?= isset($errors['tipo']) ? 'is-invalid' : '' ?>" id="tipo" name="tipo">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($tipos as $k => $label): ?>
                        <option value="<?= $k ?>" <?= ($old['tipo'] ?? '') === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['tipo'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['tipo']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha <span class="required-mark">*</span></label>
                <input type="date" class="form-control <?= isset($errors['fecha']) ? 'is-invalid' : '' ?>" id="fecha" name="fecha"
                       value="<?= htmlspecialchars($old['fecha'] ?? date('Y-m-d')) ?>">
                <?php if (isset($errors['fecha'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['fecha']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="lotes" class="form-label">Lotes</label>
                <select class="form-select select2" id="lotes" name="lotes[]" multiple data-placeholder="Seleccione uno o varios lotes (opcional)" style="width:100%">
                    <?php foreach ($lotes as $l): ?>
                        <option value="<?= (int)$l['id'] ?>" <?= in_array((int)$l['id'], $sel, true) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['consecutivo']) ?><?= $l['estado'] === 'cerrado' ? ' (cerrado)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Opcional. Si no lo asocia a un lote, el documento aparecerá con una alerta en el listado.</small>
            </div>

            <?php if ($doc && !empty($doc['archivos'])): ?>
                <div class="mb-3">
                    <label class="form-label">Archivos actuales</label>
                    <ul class="list-group">
                        <?php foreach ($doc['archivos'] as $a): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="<?= BASE_URL ?>/pagos/egresos/archivos/<?= $a['id'] ?>/file" target="_blank">
                                    <i class="bi bi-file-earmark-pdf-fill"></i> <?= htmlspecialchars($a['archivo_original']) ?>
                                </a>
                                <a href="<?= BASE_URL ?>/pagos/egresos/archivos/<?= $a['id'] ?>/delete" class="btn btn-icon btn-danger btn-sm"
                                   title="Eliminar archivo" data-confirm="¿Eliminar este archivo?">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label for="archivos" class="form-label">
                    <?= $doc ? 'Agregar archivos PDF' : 'Archivos PDF' ?>
                    <?php if (!$doc): ?><span class="required-mark">*</span><?php endif; ?>
                </label>
                <input type="file" class="form-control <?= isset($errors['archivos']) ? 'is-invalid' : '' ?>" id="archivos"
                       name="archivos[]" accept="application/pdf,.pdf" multiple>
                <?php if (isset($errors['archivos'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['archivos']) ?></div><?php endif; ?>
                <small class="text-muted">Puede seleccionar uno o varios PDF (máx. 5 MB cada uno).</small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
                <a href="<?= BASE_URL ?>/pagos/egresos" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>
