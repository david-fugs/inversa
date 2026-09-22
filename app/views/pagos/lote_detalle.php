<?php $totalValor = array_sum(array_column($pagos, 'valor')); ?>

<div class="page-actions">
    <a href="<?= BASE_URL ?>/pagos" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <?php if (!empty($pagos)): ?>
        <a href="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>/combinado" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-pdf-fill"></i> Descargar PDF Combinado
        </a>
        <a href="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>/exportar" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar a Excel
        </a>
    <?php endif; ?>
    <?php if ($lote['estado'] === 'abierto'): ?>
        <a href="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>/cerrar" class="btn btn-success"
           data-confirm="¿Cerrar el lote '<?= htmlspecialchars($lote['consecutivo']) ?>'? Ya no podrá agregar más pagos.">
            <i class="bi bi-lock-fill"></i> Cerrar Lote
        </a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h5><i class="bi bi-cash-stack"></i> Lote <?= htmlspecialchars($lote['consecutivo']) ?></h5>
        <?php if ($lote['estado'] === 'abierto'): ?>
            <span class="badge badge-success">Abierto</span>
        <?php else: ?>
            <span class="badge badge-secondary">Cerrado</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p class="mb-0"><strong>Total pagos:</strong> <?= count($pagos) ?> &nbsp;|&nbsp;
           <strong>Valor total:</strong> $<?= number_format($totalValor, 2) ?></p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h5><i class="bi bi-list-check"></i> Pagos del Lote</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Proveedor</th>
                        <th>Banco</th>
                        <th>Tipo Producto</th>
                        <th>Nro. Producto</th>
                        <th>Fecha</th>
                        <th>Valor</th>
                        <th>Comprobante</th>
                        <?php if ($lote['estado'] === 'abierto'): ?>
                        <th class="text-center">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pagos)): ?>
                        <tr><td colspan="9" class="text-center text-muted">Aún no hay pagos en este lote.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($pagos as $p): ?>
                        <tr>
                            <td><?= $p['orden'] ?></td>
                            <td><?= htmlspecialchars($p['proveedor_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['banco_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['tipo_producto_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['numero_producto']) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                            <td>$<?= number_format((float)$p['valor'], 2) ?></td>
                            <td>
                                <?php foreach ($p['comprobantes'] as $c): ?>
                                    <a href="<?= BASE_URL ?>/pagos/comprobantes/<?= $c['id'] ?>/file" target="_blank"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Ver <?= htmlspecialchars($c['archivo_original']) ?>">
                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                    </a>
                                <?php endforeach; ?>
                            </td>
                            <?php if ($lote['estado'] === 'abierto'): ?>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/pagos/pagos/edit/<?= $p['id'] ?>"
                                   class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/pagos/pagos/delete/<?= $p['id'] ?>"
                                   class="btn btn-icon btn-danger btn-sm" title="Eliminar"
                                   data-confirm="¿Eliminar este pago del lote?">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (!empty($pagos)): ?>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end"><strong>Total</strong></td>
                        <td><strong>$<?= number_format($totalValor, 2) ?></strong></td>
                        <td colspan="<?= $lote['estado'] === 'abierto' ? 2 : 1 ?>"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php if ($lote['estado'] === 'abierto'): ?>
<div class="card" style="max-width:720px;">
    <div class="card-header">
        <h5><i class="bi bi-plus-circle-fill"></i> Agregar Pago</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>/pagos" enctype="multipart/form-data" novalidate>

            <div class="mb-3">
                <label for="proveedor_id" class="form-label">
                    Proveedor <span class="required-mark">*</span>
                </label>
                <select class="form-select select2 <?= isset($errors['proveedor_id']) ? 'is-invalid' : '' ?>" id="proveedor_id" name="proveedor_id">
                    <option value="">-- Seleccione un proveedor --</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>" <?= (int)($old['proveedor_id'] ?? 0) === (int)$prov['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prov['numero_identificacion'] . ' - ' . $prov['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['proveedor_id'])): ?>
                    <div class="invalid-feedback d-block"><?= $errors['proveedor_id'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="tipo_identificacion" class="form-label">
                    Tipo de Identificación <span class="required-mark">*</span>
                </label>
                <input type="text" class="form-control <?= isset($errors['tipo_identificacion']) ? 'is-invalid' : '' ?>"
                    id="tipo_identificacion" name="tipo_identificacion"
                    value="<?= htmlspecialchars($old['tipo_identificacion'] ?? '') ?>"
                    placeholder="Ej: 01, CC, NIT" maxlength="10">
                <?php if (isset($errors['tipo_identificacion'])): ?>
                    <div class="invalid-feedback"><?= $errors['tipo_identificacion'] ?></div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="banco_id" class="form-label">Banco <span class="required-mark">*</span></label>
                    <select class="form-select <?= isset($errors['banco_id']) ? 'is-invalid' : '' ?>" id="banco_id" name="banco_id">
                        <option value="">-- Banco --</option>
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
                <div class="col-md-6 mb-3">
                    <label for="tipo_producto_id" class="form-label">Tipo de Producto <span class="required-mark">*</span></label>
                    <select class="form-select <?= isset($errors['tipo_producto_id']) ? 'is-invalid' : '' ?>" id="tipo_producto_id" name="tipo_producto_id">
                        <option value="">-- Tipo de Producto --</option>
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
            </div>

            <div class="mb-3">
                <label for="numero_producto" class="form-label">
                    Número de Producto o Servicio <span class="required-mark">*</span>
                </label>
                <input type="text" class="form-control <?= isset($errors['numero_producto']) ? 'is-invalid' : '' ?>"
                    id="numero_producto" name="numero_producto"
                    value="<?= htmlspecialchars($old['numero_producto'] ?? '') ?>">
                <?php if (isset($errors['numero_producto'])): ?>
                    <div class="invalid-feedback"><?= $errors['numero_producto'] ?></div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fecha_pago" class="form-label">Fecha <span class="required-mark">*</span></label>
                    <input type="date" class="form-control <?= isset($errors['fecha_pago']) ? 'is-invalid' : '' ?>"
                        id="fecha_pago" name="fecha_pago"
                        value="<?= htmlspecialchars($old['fecha_pago'] ?? date('Y-m-d')) ?>">
                    <?php if (isset($errors['fecha_pago'])): ?>
                        <div class="invalid-feedback"><?= $errors['fecha_pago'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="valor_display" class="form-label">Valor del Pago <span class="required-mark">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" inputmode="numeric" autocomplete="off"
                            class="form-control <?= isset($errors['valor']) ? 'is-invalid' : '' ?>"
                            id="valor_display" placeholder="0">
                    </div>
                    <input type="hidden" id="valor" name="valor" value="<?= htmlspecialchars($old['valor'] ?? '') ?>">
                    <?php if (isset($errors['valor'])): ?>
                        <div class="invalid-feedback d-block"><?= $errors['valor'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="comprobante_pdf" class="form-label">
                    Comprobante(s) PDF <span class="required-mark">*</span>
                </label>
                <input type="file" accept="application/pdf" multiple class="form-control <?= isset($errors['comprobante_pdf']) ? 'is-invalid' : '' ?>"
                    id="comprobante_pdf" name="comprobante_pdf[]">
                <?php if (isset($errors['comprobante_pdf'])): ?>
                    <div class="invalid-feedback"><?= $errors['comprobante_pdf'] ?></div>
                <?php endif; ?>
                <small class="text-muted">Puede seleccionar varios archivos PDF. Tamaño máximo 2 MB por archivo.</small>
            </div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Agregar Pago
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
/* Precarga banco / tipo de producto / número de producto según proveedor
   seleccionado (los campos quedan editables). Los <select> de banco y
   tipo de producto se llenan aquí porque dependen del catálogo completo,
   que no se recorre en PHP para este formulario (solo se usa vía AJAX). */
// El <select> de proveedor se inicializa como Select2 en app.js (que se
// carga después de este bloque). Select2 dispara el "change" a través de
// jQuery, y jQuery no lo propaga como evento nativo del DOM en un
// <select> (no tiene método change() nativo), así que
// addEventListener('change', ...) nunca se entera. Por eso se espera a
// DOMContentLoaded (para que jQuery ya esté cargado) y se engancha con
// jQuery cuando está disponible, con addEventListener como respaldo.
document.addEventListener('DOMContentLoaded', function () {
    var proveedorSelect = document.getElementById('proveedor_id');
    if (!proveedorSelect) return;

    var tipoIdentInput      = document.getElementById('tipo_identificacion');
    var bancoSelect         = document.getElementById('banco_id');
    var tipoProductoSelect  = document.getElementById('tipo_producto_id');
    var numeroProductoInput = document.getElementById('numero_producto');

    function precargar(proveedorId) {
        if (!proveedorId) return;
        fetch(BASE_URL + '/proveedores/info/' + proveedorId)
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;
                if (tipoIdentInput) tipoIdentInput.value = data.tipo_identificacion;
                if (bancoSelect) bancoSelect.value = data.banco_id;
                if (tipoProductoSelect) tipoProductoSelect.value = data.tipo_producto_id;
                if (numeroProductoInput) numeroProductoInput.value = data.numero_producto;
            });
    }

    // Solo precargar automáticamente al cambiar de proveedor (no al
    // recargar la página tras un error de validación, para no pisar los
    // valores que el usuario ya había editado manualmente).
    function onProveedorChange() {
        precargar(proveedorSelect.value);
    }
    if (window.jQuery) {
        window.jQuery(proveedorSelect).on('change', onProveedorChange);
    } else {
        proveedorSelect.addEventListener('change', onProveedorChange);
    }
});

/* Valor del pago: el usuario ve el "$" y los puntos de miles solo como
   ayuda visual; lo que realmente se envía al servidor (campo oculto
   "valor") siempre queda como número plano, sin puntos. */
(function () {
    var display = document.getElementById('valor_display');
    var hidden  = document.getElementById('valor');
    if (!display || !hidden) return;

    function formatearMiles(digitos) {
        return digitos.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function sincronizar() {
        var digitos = display.value.replace(/\D/g, '');
        hidden.value = digitos;
        display.value = digitos ? formatearMiles(digitos) : '';
    }

    display.addEventListener('input', sincronizar);

    // Si ya había un valor (reintento tras un error de validación),
    // mostrarlo ya formateado.
    if (hidden.value) {
        display.value = formatearMiles(hidden.value.replace(/\D/g, ''));
    }
})();
</script>
