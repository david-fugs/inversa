<style>
    .pago-row[draggable="true"] { cursor: grab; }
    .pago-row.dragging { opacity: .4; }
    .pago-row.asignado, .pago-row.asignado > td { background: #d1f2dc; }
    .lote-drop {
        border: 2px dashed var(--bs-border-color, #dee2e6);
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 10px;
        transition: background .15s, border-color .15s;
    }
    .lote-drop.over { background: #e8f1ff; border-color: #0d6efd; }
    .lote-drop.cerrado { background: #f1f3f5; }
    .lote-drop.recibido { background: #e6f6ea; border-color: #198754; }
</style>

<div class="page-actions">
    <a href="<?= BASE_URL ?>/pagos" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearLote">
        <i class="bi bi-plus-lg"></i> Crear Lote
    </button>
    <span id="accionesTodos" <?= empty($lotesAbiertos) ? 'style="display:none"' : '' ?>>
        <a href="<?= BASE_URL ?>/pagos/lotes/nuevo/combinado" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-pdf-fill"></i> PDF Combinado (todos)
        </a>
        <a href="<?= BASE_URL ?>/pagos/lotes/nuevo/exportar" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Excel (todos)
        </a>
        <a href="<?= BASE_URL ?>/pagos/lotes/nuevo/cerrar" class="btn btn-success"
           data-confirm="¿Cerrar todos los lotes abiertos de esta pantalla? Ya no podrá agregarles más pagos.">
            <i class="bi bi-lock-fill"></i> Cerrar todos los lotes
        </a>
    </span>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h5><i class="bi bi-list-check"></i> Pagos</h5>
                <span class="badge badge-primary" title="Pendientes de asignar"><span id="contadorSinLote"><?= count($pagosSinLote) ?></span> sin lote</span>
            </div>
            <div class="card-body p-0">
                <p class="text-muted px-3 pt-3 mb-2" style="font-size:13px;">
                    <i class="bi bi-arrows-move"></i> Arrastre un pago con el mouse y suéltelo sobre un lote para asignarlo.
                </p>
                <div class="table-wrapper">
                    <table class="table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:28px"></th>
                                <th>Lote</th>
                                <th>Proveedor</th>
                                <th>Banco</th>
                                <th>Tipo Producto</th>
                                <th>Nro. Producto</th>
                                <th>Fecha</th>
                                <th>Valor</th>
                                <th>Comprobante</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodySinLote">
                            <tr id="filaVacia" <?= empty($pagosSinLote) && empty($pagosAsignados) ? '' : 'style="display:none"' ?>>
                                <td colspan="10" class="text-center text-muted">No hay pagos pendientes de asignar. Agregue uno con el formulario.</td>
                            </tr>
                            <?php foreach ($pagosSinLote as $p): ?>
                                <tr class="pago-row" draggable="true" data-pago-id="<?= $p['id'] ?>">
                                    <td class="text-muted"><i class="bi bi-grip-vertical"></i></td>
                                    <td class="celda-lote"><span class="text-muted">Sin lote</span></td>
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
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>/pagos/pagos/edit/<?= $p['id'] ?>"
                                           class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/pagos/pagos/delete/<?= $p['id'] ?>"
                                           class="btn btn-icon btn-danger btn-sm" title="Eliminar"
                                           data-confirm="¿Eliminar este pago?">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php foreach ($pagosAsignados as $p): ?>
                                <tr class="pago-row asignado">
                                    <td class="text-success"><i class="bi bi-check-circle-fill"></i></td>
                                    <td class="celda-lote"><span class="badge bg-success">Lote <?= htmlspecialchars($p['lote_consecutivo']) ?></span></td>
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
                                    <td class="text-center text-muted">Asignado</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h5><i class="bi bi-cash-stack"></i> Lotes de este registro</h5>
            </div>
            <div class="card-body">
                <div id="listaLotes">
                    <?php foreach ($lotesAbiertos as $l): ?>
                        <div class="lote-drop <?= $l['estado'] === 'cerrado' ? 'cerrado' : '' ?>" data-lote-id="<?= $l['id'] ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <strong><?= htmlspecialchars($l['consecutivo']) ?>
                                    <?php if ($l['estado'] === 'cerrado'): ?><span class="badge badge-secondary">Cerrado</span><?php endif; ?>
                                </strong>
                                <button type="button" class="btn btn-icon btn-outline-primary btn-sm btn-ver-lote" title="Ver lote">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                            <small class="text-muted">
                                <span class="lote-pagos"><?= (int)$l['total_pagos'] ?></span> pagos &middot;
                                <span class="lote-valor">$<?= number_format((float)$l['total_valor'], 2) ?></span>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted mb-0" id="sinLotes" style="font-size:13px;<?= empty($lotesAbiertos) ? '' : 'display:none' ?>">
                    Aún no ha creado lotes. Use "Crear Lote" para crear uno.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$formAction = BASE_URL . '/pagos/lotes/nuevo/pagos';
include __DIR__ . '/_pago_form.php';
?>

<!-- Modal Crear Lote -->
<div class="modal fade" id="modalCrearLote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="formCrearLote" novalidate>
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle-fill"></i> Crear Lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <label for="consecutivo" class="form-label">Consecutivo <span class="required-mark">*</span></label>
                <input type="text" class="form-control" id="consecutivo" name="consecutivo" placeholder="Ej: LOTE-2026-001" maxlength="40">
                <div class="invalid-feedback" id="errorConsecutivo"></div>
                <small class="text-muted">Si ya existe un lote abierto con ese consecutivo se reutiliza.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ver Lote -->
<div class="modal fade" id="modalVerLote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-stack"></i> Lote <span id="verLoteTitulo"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="verLoteResumen"></p>
                <div class="table-wrapper">
                    <table class="table" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th><th>Proveedor</th><th>Banco</th><th>Tipo Producto</th>
                                <th>Nro. Producto</th><th>Fecha</th><th>Valor</th><th>Comprobante</th>
                            </tr>
                        </thead>
                        <tbody id="verLoteBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-outline-primary" id="verLotePdf"><i class="bi bi-file-earmark-pdf-fill"></i> Descargar PDF Combinado</a>
                <a href="#" class="btn btn-outline-success" id="verLoteExcel"><i class="bi bi-file-earmark-excel-fill"></i> Exportar a Excel</a>
                <a href="#" class="btn btn-success" id="verLoteCerrar" data-confirm="¿Cerrar este lote? Ya no podrá agregar más pagos."><i class="bi bi-lock-fill"></i> Cerrar Lote</a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tbody      = document.getElementById('tbodySinLote');
    var filaVacia  = document.getElementById('filaVacia');
    var contador   = document.getElementById('contadorSinLote');
    var listaLotes = document.getElementById('listaLotes');
    var sinLotes   = document.getElementById('sinLotes');
    var arrastrado = null;

    function actualizarVacio() {
        contador.textContent = tbody.querySelectorAll('.pago-row:not(.asignado)').length;
        filaVacia.style.display = tbody.querySelectorAll('.pago-row').length === 0 ? '' : 'none';
    }

    function marcarAsignado(fila, lote) {
        fila.classList.remove('dragging');
        fila.classList.add('asignado');
        fila.removeAttribute('draggable');
        fila.querySelector('td').innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
        var celdaLote = fila.querySelector('.celda-lote');
        celdaLote.innerHTML = '<span class="badge bg-success"></span>';
        celdaLote.firstChild.textContent = 'Lote ' + lote.consecutivo;
        var acciones = fila.lastElementChild;
        acciones.className = 'text-center text-muted';
        acciones.textContent = 'Asignado';
    }

    tbody.addEventListener('dragstart', function (e) {
        var fila = e.target.closest('.pago-row');
        if (!fila) return;
        arrastrado = fila;
        fila.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', fila.dataset.pagoId);
    });
    tbody.addEventListener('dragend', function () {
        if (arrastrado) arrastrado.classList.remove('dragging');
        arrastrado = null;
    });

    function zonaDe(e) {
        var z = e.target.closest('.lote-drop');
        return z && !z.classList.contains('cerrado') ? z : null;
    }

    listaLotes.addEventListener('dragover', function (e) {
        var zona = zonaDe(e);
        if (!zona || !arrastrado) return;
        e.preventDefault();
        zona.classList.add('over');
    });
    listaLotes.addEventListener('dragleave', function (e) {
        var zona = zonaDe(e);
        if (zona && !zona.contains(e.relatedTarget)) zona.classList.remove('over');
    });
    listaLotes.addEventListener('drop', function (e) {
        var zona = zonaDe(e);
        if (!zona || !arrastrado) return;
        e.preventDefault();
        zona.classList.remove('over');

        var fila = arrastrado;
        var body = new URLSearchParams({ lote_id: zona.dataset.loteId });
        fetch(BASE_URL + '/pagos/pagos/' + fila.dataset.pagoId + '/asignar', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) { alert(data.error || 'No se pudo asignar el pago.'); return; }
                marcarAsignado(fila, data.lote);
                actualizarVacio();
                actualizarLote(zona, data.lote);
                zona.classList.add('recibido');
                setTimeout(function () { zona.classList.remove('recibido'); }, 1200);
            })
            .catch(function () { alert('Error de conexión al asignar el pago.'); });
    });

    function actualizarLote(zona, lote) {
        zona.querySelector('.lote-pagos').textContent = lote.total_pagos;
        zona.querySelector('.lote-valor').textContent = lote.total_valor;
    }

    function agregarZona(lote) {
        var zona = document.createElement('div');
        zona.className = 'lote-drop';
        zona.dataset.loteId = lote.id;
        zona.innerHTML =
            '<div class="d-flex justify-content-between align-items-start">' +
                '<strong></strong>' +
                '<button type="button" class="btn btn-icon btn-outline-primary btn-sm btn-ver-lote" title="Ver lote"><i class="bi bi-eye-fill"></i></button>' +
            '</div>' +
            '<small class="text-muted"><span class="lote-pagos"></span> pagos &middot; <span class="lote-valor"></span></small>';
        zona.querySelector('strong').textContent = lote.consecutivo;
        actualizarLote(zona, lote);
        listaLotes.prepend(zona);
        sinLotes.style.display = 'none';
        document.getElementById('accionesTodos').style.display = '';
    }

    var modalVer = new bootstrap.Modal(document.getElementById('modalVerLote'));
    var verBody  = document.getElementById('verLoteBody');

    function celda(tr, texto) {
        var td = document.createElement('td');
        td.textContent = texto;
        tr.appendChild(td);
        return td;
    }

    listaLotes.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-ver-lote');
        if (!btn) return;
        var loteId = btn.closest('.lote-drop').dataset.loteId;
        fetch(BASE_URL + '/pagos/lotes/' + loteId + '/modal')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) { alert(data.error || 'No se pudo cargar el lote.'); return; }
                document.getElementById('verLoteTitulo').textContent = data.lote.consecutivo;
                document.getElementById('verLoteResumen').innerHTML =
                    '<strong>Total pagos:</strong> ' + data.lote.total_pagos +
                    ' &nbsp;|&nbsp; <strong>Valor total:</strong> <span id="verLoteValor"></span>';
                document.getElementById('verLoteValor').textContent = data.lote.total_valor;
                document.getElementById('verLotePdf').href = data.combinado;
                document.getElementById('verLoteExcel').href = data.exportar;
                var hayPagos = data.pagos.length > 0;
                var cerrar = document.getElementById('verLoteCerrar');
                cerrar.href = BASE_URL + '/pagos/lotes/' + data.lote.id + '/cerrar?volver=nuevo';
                cerrar.style.display = hayPagos && data.lote.estado === 'abierto' ? '' : 'none';
                document.getElementById('verLotePdf').style.display = hayPagos ? '' : 'none';
                document.getElementById('verLoteExcel').style.display = hayPagos ? '' : 'none';

                verBody.innerHTML = '';
                if (!hayPagos) {
                    var vacio = document.createElement('tr');
                    var td = celda(vacio, 'Aún no hay pagos en este lote.');
                    td.colSpan = 8;
                    td.className = 'text-center text-muted';
                    verBody.appendChild(vacio);
                }
                data.pagos.forEach(function (p) {
                    var tr = document.createElement('tr');
                    [p.orden, p.proveedor, p.banco, p.tipo_producto, p.numero_producto, p.fecha, p.valor].forEach(function (v) { celda(tr, v); });
                    var tdc = document.createElement('td');
                    p.comprobantes.forEach(function (c) {
                        var a = document.createElement('a');
                        a.href = c.url;
                        a.target = '_blank';
                        a.className = 'btn btn-icon btn-outline-primary btn-sm';
                        a.title = 'Ver ' + c.nombre;
                        a.innerHTML = '<i class="bi bi-file-earmark-pdf-fill"></i>';
                        tdc.appendChild(a);
                    });
                    tr.appendChild(tdc);
                    verBody.appendChild(tr);
                });
                modalVer.show();
            })
            .catch(function () { alert('Error de conexión al cargar el lote.'); });
    });

    var modalEl = document.getElementById('modalCrearLote');
    var form    = document.getElementById('formCrearLote');
    var input   = document.getElementById('consecutivo');
    var errorEl = document.getElementById('errorConsecutivo');

    modalEl.addEventListener('shown.bs.modal', function () { input.focus(); });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        input.classList.remove('is-invalid');
        fetch(BASE_URL + '/pagos/lotes/crear', { method: 'POST', body: new URLSearchParams({ consecutivo: input.value }) })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    errorEl.textContent = data.error;
                    input.classList.add('is-invalid');
                    return;
                }
                var existente = listaLotes.querySelector('.lote-drop[data-lote-id="' + data.lote.id + '"]');
                if (!existente) agregarZona(data.lote);
                input.value = '';
                bootstrap.Modal.getInstance(modalEl).hide();
            })
            .catch(function () {
                errorEl.textContent = 'Error de conexión.';
                input.classList.add('is-invalid');
            });
    });
});
</script>
