<div class="page-actions">
    <a href="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver al Lote
    </a>
</div>

<div class="card" style="max-width:720px;">
    <div class="card-header">
        <h5><i class="bi bi-pencil-square"></i> Editar Pago</h5>
        <span class="badge badge-primary">Lote <?= htmlspecialchars($lote['consecutivo']) ?></span>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/pagos/pagos/edit/<?= $pago['id'] ?>" enctype="multipart/form-data" novalidate>

            <div class="mb-3">
                <label for="proveedor_id" class="form-label">
                    Proveedor <span class="required-mark">*</span>
                </label>
                <select class="form-select select2 <?= isset($errors['proveedor_id']) ? 'is-invalid' : '' ?>" id="proveedor_id" name="proveedor_id">
                    <option value="">-- Seleccione un proveedor --</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>" <?= (int)$pago['proveedor_id'] === (int)$prov['id'] ? 'selected' : '' ?>>
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
                    value="<?= htmlspecialchars($pago['tipo_identificacion']) ?>"
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
                            <option value="<?= $b['id'] ?>" <?= (int)$pago['banco_id'] === (int)$b['id'] ? 'selected' : '' ?>>
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
                            <option value="<?= $tp['id'] ?>" <?= (int)$pago['tipo_producto_id'] === (int)$tp['id'] ? 'selected' : '' ?>>
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
                    value="<?= htmlspecialchars($pago['numero_producto']) ?>">
                <?php if (isset($errors['numero_producto'])): ?>
                    <div class="invalid-feedback"><?= $errors['numero_producto'] ?></div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fecha_pago" class="form-label">Fecha <span class="required-mark">*</span></label>
                    <input type="date" class="form-control <?= isset($errors['fecha_pago']) ? 'is-invalid' : '' ?>"
                        id="fecha_pago" name="fecha_pago"
                        value="<?= htmlspecialchars(substr($pago['fecha_pago'], 0, 10)) ?>">
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
                    <input type="hidden" id="valor" name="valor" value="<?= htmlspecialchars((string)(int)$pago['valor']) ?>">
                    <?php if (isset($errors['valor'])): ?>
                        <div class="invalid-feedback d-block"><?= $errors['valor'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="comprobante_pdf" class="form-label">Comprobante PDF</label>
                <?php if (!empty($pago['comprobante_pdf_original'])): ?>
                    <p class="mb-1" style="font-size:13px;">
                        Actual: <a href="<?= BASE_URL ?>/pagos/pagos/<?= $pago['id'] ?>/file" target="_blank">
                            <?= htmlspecialchars($pago['comprobante_pdf_original']) ?>
                        </a>
                    </p>
                <?php endif; ?>
                <input type="file" accept="application/pdf" class="form-control <?= isset($errors['comprobante_pdf']) ? 'is-invalid' : '' ?>"
                    id="comprobante_pdf" name="comprobante_pdf">
                <?php if (isset($errors['comprobante_pdf'])): ?>
                    <div class="invalid-feedback"><?= $errors['comprobante_pdf'] ?></div>
                <?php endif; ?>
                <small class="text-muted">Déjelo vacío para conservar el comprobante actual. Tamaño máximo 2 MB.</small>
            </div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Actualizar
                </button>
                <a href="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
/* Precarga banco / tipo de producto / número de producto si el usuario
   cambia el proveedor (mismo comportamiento que al agregar un pago).
   El <select> de proveedor se inicializa como Select2 en app.js (cargado
   después de este bloque): dispara el "change" vía jQuery, que no lo
   propaga como evento nativo en un <select>, así que
   addEventListener('change', ...) no se entera. Se espera a
   DOMContentLoaded (jQuery ya cargado) y se engancha con jQuery cuando
   está disponible, con addEventListener como respaldo. */
document.addEventListener('DOMContentLoaded', function () {
    var proveedorSelect = document.getElementById('proveedor_id');
    if (!proveedorSelect) return;

    var tipoIdentInput      = document.getElementById('tipo_identificacion');
    var bancoSelect         = document.getElementById('banco_id');
    var tipoProductoSelect  = document.getElementById('tipo_producto_id');
    var numeroProductoInput = document.getElementById('numero_producto');

    function onProveedorChange() {
        if (!proveedorSelect.value) return;
        fetch(BASE_URL + '/proveedores/info/' + proveedorSelect.value)
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;
                if (tipoIdentInput) tipoIdentInput.value = data.tipo_identificacion;
                if (bancoSelect) bancoSelect.value = data.banco_id;
                if (tipoProductoSelect) tipoProductoSelect.value = data.tipo_producto_id;
                if (numeroProductoInput) numeroProductoInput.value = data.numero_producto;
            });
    }

    if (window.jQuery) {
        window.jQuery(proveedorSelect).on('change', onProveedorChange);
    } else {
        proveedorSelect.addEventListener('change', onProveedorChange);
    }
});

/* Valor del pago: el "$" y los puntos de miles son solo ayuda visual;
   el campo oculto "valor" es el que se envía, siempre como número plano. */
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

    if (hidden.value) {
        display.value = formatearMiles(hidden.value.replace(/\D/g, ''));
    }
})();
</script>
