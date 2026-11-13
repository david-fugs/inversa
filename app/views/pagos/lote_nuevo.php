<style>
    .pago-row[draggable="true"] { cursor: grab; }
    .pago-row.dragging { opacity: .4; }
    .pago-card.bloqueado { background: #f8f9fa; color: #6c757d; }
    .pago-card.asignado { background: #d1f2dc; }
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
    .pagos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 12px;
        padding: 0 16px 16px;
    }
    .pago-card {
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 12px;
        background: #fff;
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }
    .pago-card-head { display: flex; align-items: center; gap: 8px; min-width: 0; }
    .pago-card-ico { flex: 0 0 auto; min-width: 26px; text-align: center; color: var(--text-secondary); }
    .pago-card-prov { flex: 1 1 auto; min-width: 0; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pago-card-valor { flex: 0 0 auto; font-weight: 700; color: var(--color-primary); font-size: 14px; }
    .pago-card-datos { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 10px; margin: 0; font-size: 12.5px; }
    .pago-card-datos dt { font-size: 10.5px; text-transform: uppercase; color: var(--text-secondary); font-weight: 600; letter-spacing: .3px; }
    .pago-card-datos dd { margin: 0; overflow-wrap: anywhere; }
    .pago-card-comprobantes { border-top: 1px dashed var(--border-color); padding-top: 8px; }
    .pago-card-archivo { flex: 1 1 auto; min-width: 0; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pago-card-acciones { display: flex; gap: 6px; margin-top: auto; }
    @media (min-width: 1200px) {
        .lotes-sticky {
            position: sticky;
            top: 80px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
    }
</style>

<div class="page-actions">
    <a href="<?= BASE_URL ?>/pagos" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearLote">
        <i class="bi bi-plus-lg"></i> Crear Lote
    </button>
    <span id="accionesTodos" <?= empty($lotesAbiertos) ? 'style="display:none"' : '' ?>>
        <a href="<?= BASE_URL ?>/pagos/lotes/nuevo/combinado" data-combinar="<?= BASE_URL ?>/pagos/lotes/nuevo/comprobantes" class="btn btn-outline-primary">
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
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header">
                <h5><i class="bi bi-list-check"></i> Pagos</h5>
                <span class="badge badge-primary" title="Pendientes de asignar"><span id="contadorSinLote"><?= count($pagosSinLote) ?></span> sin lote</span>
            </div>
            <div class="card-body px-0">
                <p class="text-muted px-3 mb-2" style="font-size:13px;">
                    <i class="bi bi-arrows-move"></i> Arrastre un pago <strong>aprobado</strong> con el mouse y suéltelo sobre un lote para asignarlo. Los pagos pendientes o rechazados (con candado) no se pueden mover.
                </p>
                <p class="text-center text-muted px-3 mb-0" id="filaVacia" <?= empty($pagosSinLote) && empty($pagosAsignados) ? '' : 'style="display:none"' ?>>No hay pagos pendientes de asignar. Agregue uno con el formulario.</p>
                <div class="pagos-grid" id="tbodySinLote">
                    <?php foreach ($pagosSinLote as $p): ?>
                        <?php $aprobado = ($p['estado_revision'] ?? 'aprobado') === 'aprobado'; ?>
                        <div class="pago-card pago-row<?= $aprobado ? '' : ' bloqueado' ?>" <?= $aprobado ? 'draggable="true"' : '' ?> data-pago-id="<?= $p['id'] ?>">
                            <div class="pago-card-head">
                                <span class="pago-card-ico"><?= $aprobado ? '<i class="bi bi-grip-vertical"></i>' : '<i class="bi bi-lock-fill" title="Solo se pueden mover los pagos aprobados"></i>' ?></span>
                                <strong class="pago-card-prov" title="<?= htmlspecialchars($p['proveedor_nombre']) ?>"><?= htmlspecialchars($p['proveedor_nombre']) ?></strong>
                                <span class="pago-card-valor">$<?= number_format((float)$p['valor'], 2) ?></span>
                            </div>
                            <dl class="pago-card-datos">
                                <div><dt>Lote</dt><dd class="celda-lote"><span class="text-muted">Sin lote</span></dd></div>
                                <div><dt>Estado</dt><dd><?php require __DIR__ . '/_estado_revision.php'; ?>
                                    <?php if (!empty($p['creador_nombre']) && (int)$p['user_id'] !== (int)Session::get('user_id')): ?>
                                        <small class="d-block text-muted">Por <?= htmlspecialchars($p['creador_nombre']) ?></small>
                                    <?php endif; ?></dd></div>
                                <div><dt>Banco</dt><dd><?= htmlspecialchars($p['banco_nombre']) ?></dd></div>
                                <div><dt>Tipo producto</dt><dd><?= htmlspecialchars($p['tipo_producto_nombre']) ?></dd></div>
                                <div><dt>Nro. producto</dt><dd><?= htmlspecialchars($p['numero_producto']) ?></dd></div>
                                <div><dt>Fecha</dt><dd><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></dd></div>
                            </dl>
                            <div class="pago-card-comprobantes">
                                <?php if (empty($p['comprobantes'])): ?><small class="text-muted">Sin comprobantes</small><?php endif; ?>
                                <?php foreach ($p['comprobantes'] as $c): ?>
                                    <div class="mb-1"><a href="<?= BASE_URL ?>/pagos/comprobantes/<?= $c['id'] ?>/file" target="_blank" class="pago-card-archivo d-block" title="Ver <?= htmlspecialchars($c['archivo_original']) ?>"><i class="bi bi-file-earmark-pdf-fill"></i> <?= htmlspecialchars($c['archivo_original']) ?></a></div>
                                <?php endforeach; ?>
                            </div>
                            <div class="pago-card-acciones">
                                <?php if ($aprobado): ?>
                                    <a href="<?= BASE_URL ?>/pagos/pagos/edit/<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm" title="Editar"><i class="bi bi-pencil-fill"></i> Editar</a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>/pagos/pagos/delete/<?= $p['id'] ?>" class="btn btn-danger btn-sm" title="Eliminar" data-confirm="¿Eliminar este pago?"><i class="bi bi-trash-fill"></i> Eliminar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ($pagosAsignados as $p): ?>
                        <div class="pago-card pago-row asignado">
                            <div class="pago-card-head">
                                <span class="pago-card-ico text-success"><i class="bi bi-check-circle-fill"></i></span>
                                <strong class="pago-card-prov" title="<?= htmlspecialchars($p['proveedor_nombre']) ?>"><?= htmlspecialchars($p['proveedor_nombre']) ?></strong>
                                <span class="pago-card-valor">$<?= number_format((float)$p['valor'], 2) ?></span>
                            </div>
                            <dl class="pago-card-datos">
                                <div><dt>Lote</dt><dd class="celda-lote"><span class="badge bg-success">Lote <?= htmlspecialchars($p['lote_consecutivo']) ?></span></dd></div>
                                <div><dt>Estado</dt><dd><span class="badge bg-success">Aprobado</span></dd></div>
                                <div><dt>Banco</dt><dd><?= htmlspecialchars($p['banco_nombre']) ?></dd></div>
                                <div><dt>Tipo producto</dt><dd><?= htmlspecialchars($p['tipo_producto_nombre']) ?></dd></div>
                                <div><dt>Nro. producto</dt><dd><?= htmlspecialchars($p['numero_producto']) ?></dd></div>
                                <div><dt>Fecha</dt><dd><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></dd></div>
                            </dl>
                            <div class="pago-card-comprobantes">
                                <?php if (empty($p['comprobantes'])): ?><small class="text-muted">Sin comprobantes</small><?php endif; ?>
                                <?php foreach ($p['comprobantes'] as $c): ?>
                                    <div class="mb-1"><a href="<?= BASE_URL ?>/pagos/comprobantes/<?= $c['id'] ?>/file" target="_blank" class="pago-card-archivo d-block" title="Ver <?= htmlspecialchars($c['archivo_original']) ?>"><i class="bi bi-file-earmark-pdf-fill"></i> <?= htmlspecialchars($c['archivo_original']) ?></a></div>
                                <?php endforeach; ?>
                            </div>
                            <div class="pago-card-acciones text-muted">Asignado</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-3 lotes-sticky">
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

        <?php if (in_array(Session::get('user_rol'), ['Admin Pagos', 'Operador Pagos'], true)): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5><i class="bi bi-receipt-cutoff"></i> Egresos / Legalizaciones</h5>
                <span class="badge <?= empty($documentos) ? 'bg-warning text-dark' : 'bg-success' ?>"><?= count($documentos) ?></span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2" style="font-size:13px;">Van al inicio del PDF combinado.</p>
                <?php if (empty($lotesAbiertos)): ?>
                    <div class="alert alert-light border py-2 small">Cree un lote para poder asociarle documentos.</div>
                <?php elseif (empty($documentos)): ?>
                    <div class="alert alert-warning py-2">
                        <i class="bi bi-exclamation-triangle-fill"></i> Estos lotes no tienen comprobante de egreso ni legalización de anticipo asociados.
                    </div>
                <?php endif; ?>
                <?php foreach ($documentos as $d): ?>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge bg-primary"><?= htmlspecialchars(EgresoDocumento::TIPOS[$d['tipo']] ?? $d['tipo']) ?></span>
                        <small class="text-muted"><?= date('d/m/Y', strtotime($d['fecha'])) ?></small>
                        <?php if (!empty($d['egreso_id'])): ?><small class="text-muted">(egreso #<?= (int)$d['egreso_id'] ?>)</small><?php endif; ?>
                        <?php foreach ($d['archivos'] as $a): ?>
                            <a href="<?= BASE_URL ?>/pagos/egresos/archivos/<?= $a['id'] ?>/file" target="_blank"
                               class="btn btn-outline-primary btn-sm" title="Ver <?= htmlspecialchars($a['archivo_original']) ?>">
                                <i class="bi bi-file-earmark-pdf-fill"></i> <?= htmlspecialchars($a['archivo_original']) ?>
                            </a>
                        <?php endforeach; ?>
                        <?php foreach ($d['pagos'] as $p): ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($p['proveedor_nombre'] . ' · ' . $p['numero_producto']) ?></span>
                        <?php endforeach; ?>
                        <a href="<?= BASE_URL ?>/pagos/egresos/<?= $d['id'] ?>/edit" class="btn btn-icon btn-outline-secondary btn-sm" title="Editar documento">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/pagos/egresos/<?= $d['id'] ?>/delete" class="btn btn-icon btn-danger btn-sm" title="Eliminar documento"
                           data-confirm="¿Eliminar este documento y sus archivos? <?= $d['tipo'] === 'comprobante_egreso' && !empty($d['legalizaciones']) ? 'Sus legalizaciones quedarán sin egreso asociado. ' : '' ?>Esta acción no se puede deshacer.">
                            <i class="bi bi-trash-fill"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
                <?php if (!empty($lotesAbiertos)): ?>
                <button type="button" class="btn btn-outline-primary btn-sm mt-1"
                        data-docs-abrir="<?= BASE_URL ?>/pagos/lotes/nuevo/comprobantes">
                    <i class="bi bi-upload"></i> <?= empty($documentos) ? 'Subir documento' : 'Agregar documento' ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$formAction = BASE_URL . '/pagos/lotes/nuevo/pagos';
$comprobanteObligatorio = false;
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
                                <th>Nro. Producto</th><th>Fecha</th><th>Valor</th><th>Comprobante</th><th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="verLoteBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-outline-primary" id="verLotePdf"><i class="bi bi-file-earmark-pdf-fill"></i> Descargar PDF Combinado</a>
                <button type="button" class="btn btn-outline-primary" id="verLotePagosEsp"><i class="bi bi-ui-checks"></i> Descargar con pagos específicos</button>
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
        var ico = fila.querySelector('.pago-card-ico');
        ico.className = 'pago-card-ico text-success';
        ico.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
        var celdaLote = fila.querySelector('.celda-lote');
        celdaLote.innerHTML = '<span class="badge bg-success"></span>';
        celdaLote.firstChild.textContent = 'Lote ' + lote.consecutivo;
        var acciones = fila.querySelector('.pago-card-acciones');
        acciones.className = 'pago-card-acciones text-muted';
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
                document.getElementById('verLotePdf').dataset.combinar = data.comprobantes;
                document.getElementById('verLoteExcel').href = data.exportar;
                document.getElementById('verLotePagosEsp').dataset.pagosEspecificos = data.lote.id;
                document.getElementById('verLotePagosEsp').style.display = data.pagos.length ? '' : 'none';
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
                    td.colSpan = 9;
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
                        a.title = 'Ver ' + c.nombre;
                        a.style.fontSize = '12px';
                        a.innerHTML = '<i class="bi bi-file-earmark-pdf-fill"></i> ';
                        a.appendChild(document.createTextNode(c.nombre));
                        var d = document.createElement('div');
                        d.appendChild(a);
                        tdc.appendChild(d);
                    });
                    tr.appendChild(tdc);
                    var tda = document.createElement('td');
                    tda.className = 'text-center';
                    if (data.lote.estado === 'abierto') {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'btn btn-outline-warning btn-sm btn-devolver-pago';
                        b.dataset.pagoId = p.id;
                        b.title = 'Quitar del lote y devolver a sin asignar';
                        b.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Devolver';
                        tda.appendChild(b);
                    }
                    tr.appendChild(tda);
                    verBody.appendChild(tr);
                });
                modalVer.show();
            })
            .catch(function () { alert('Error de conexión al cargar el lote.'); });
    });

    verBody.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-devolver-pago');
        if (!btn || !confirm('¿Devolver este pago a "sin lote"? Podrá asignarlo de nuevo.')) return;
        btn.disabled = true;
        fetch(BASE_URL + '/pagos/pagos/' + btn.dataset.pagoId + '/desasignar', { method: 'POST' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) { alert(data.error || 'No se pudo devolver el pago.'); btn.disabled = false; return; }
                window.location.reload();
            })
            .catch(function () { alert('Error de conexión.'); btn.disabled = false; });
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
                input.value = '';
                if (!existente) {
                    // Recarga para que la tarjeta de Egresos / Legalizaciones tome el lote nuevo.
                    // Sin "?nuevo=1": ese parámetro limpia los lotes de la sesión.
                    window.location.href = BASE_URL + '/pagos/lotes/nuevo';
                    return;
                }
                bootstrap.Modal.getInstance(modalEl).hide();
            })
            .catch(function () {
                errorEl.textContent = 'Error de conexión.';
                input.classList.add('is-invalid');
            });
    });
});
</script>

<?php require __DIR__ . '/_modal_documentos_pdf.php'; ?>
<?php require __DIR__ . '/_modal_pagos_especificos.php'; ?>
