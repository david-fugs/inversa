<?php
/** Formulario de Egreso / Legalización (crear y editar). Requiere: $doc (null al crear), $lotes, $errors, $old. */
$tipos   = EgresoDocumento::TIPOS;
$accion  = $doc ? BASE_URL . '/pagos/egresos/' . $doc['id'] . '/edit' : BASE_URL . '/pagos/egresos/nuevo';
$sel     = array_map('intval', (array)($old['lote_ids'] ?? []));
$selPagos = array_map('intval', (array)($old['pago_ids'] ?? []));
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

            <div class="mb-3" id="grupoEgreso" style="display:none;">
                <label for="egreso_id" class="form-label">Comprobante de egreso asociado <span class="required-mark">*</span></label>
                <select class="form-select <?= isset($errors['egreso_id']) ? 'is-invalid' : '' ?>" id="egreso_id" name="egreso_id">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($egresos as $e): ?>
                        <option value="<?= (int)$e['id'] ?>" <?= (int)($old['egreso_id'] ?? 0) === (int)$e['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars('#' . $e['id'] . ' · ' . date('d/m/Y', strtotime($e['fecha'])) . ($e['archivo'] ? ' · ' . $e['archivo'] : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['egreso_id'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['egreso_id']) ?></div><?php endif; ?>
                <small class="text-muted">Si no elige lotes ni pagos, la legalización toma los del comprobante de egreso.</small>
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

            <div class="mb-3">
                <label for="pagos" class="form-label">Pagos</label>
                <select class="form-select select2" id="pagos" name="pagos[]" multiple data-placeholder="Seleccione uno o varios pagos (opcional)" style="width:100%">
                    <?php foreach ($pagos as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" data-lote="<?= (int)$p['lote_pago_id'] ?>" <?= in_array((int)$p['id'], $selPagos, true) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['lote_consecutivo'] . ' · ' . $p['proveedor_nombre'] . ' · ' . $p['numero_producto'] . ' · $' . number_format((float)$p['valor'], 2)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Opcional. Si elige lotes, solo se listan los pagos de esos lotes (los más nuevos primero). Un mismo documento puede cubrir varios pagos y el lote de cada pago se asocia automáticamente.</small>
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

            <script>
            // Con lotes elegidos, el selector de pagos solo ofrece los pagos de esos lotes.
            document.addEventListener('DOMContentLoaded', function () {
                if (!window.jQuery) return;
                var $lotes = jQuery('#lotes'), $pagos = jQuery('#pagos');
                var todos = $pagos.find('option').map(function () {
                    return { id: this.value, texto: this.textContent.trim(), lote: this.getAttribute('data-lote') };
                }).get();

                function reconstruir(quitarFuera) {
                    var lotes = ($lotes.val() || []).map(String);
                    var elegidos = ($pagos.val() || []).map(String);
                    if (quitarFuera && lotes.length) {
                        elegidos = elegidos.filter(function (id) {
                            return todos.some(function (p) { return p.id === id && lotes.indexOf(p.lote) !== -1; });
                        });
                    }
                    $pagos.empty();
                    todos.forEach(function (p) {
                        var visible = !lotes.length || lotes.indexOf(p.lote) !== -1 || elegidos.indexOf(p.id) !== -1;
                        if (!visible) return;
                        var o = new Option(p.texto, p.id, false, elegidos.indexOf(p.id) !== -1);
                        o.setAttribute('data-lote', p.lote);
                        $pagos.append(o);
                    });
                    $pagos.trigger('change');
                }

                $lotes.on('change', function () { reconstruir(true); });
                reconstruir(false);
            });
            </script>

            <script>
            (function () {
                var tipo = document.getElementById('tipo');
                var grupo = document.getElementById('grupoEgreso');
                function mostrar() { grupo.style.display = tipo.value === 'legalizacion_anticipo' ? '' : 'none'; }
                tipo.addEventListener('change', mostrar);
                mostrar();
            })();
            </script>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
                <a href="<?= BASE_URL ?>/pagos/egresos" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>
