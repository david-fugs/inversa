<?php $totalValor = array_sum(array_column($pagos, 'valor')); ?>

<style>
    .lote-drop {
        border: 2px dashed var(--bs-border-color, #dee2e6);
        border-radius: 8px;
        padding: 12px 14px;
    }
    .lote-drop.cerrado { background: #f1f3f5; }
    .pagos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 12px;
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
    .pago-card-num {
        flex: 0 0 auto; min-width: 26px; height: 26px; border-radius: 50%;
        background: var(--color-primary); color: #fff; font-size: 12px; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .pago-card-prov { flex: 1 1 auto; min-width: 0; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pago-card-valor { flex: 0 0 auto; font-weight: 700; color: var(--color-primary); font-size: 14px; }
    .pago-card-datos { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 10px; margin: 0; font-size: 12.5px; }
    .pago-card-datos dt { font-size: 10.5px; text-transform: uppercase; color: var(--text-secondary); font-weight: 600; letter-spacing: .3px; }
    .pago-card-datos dd { margin: 0; overflow-wrap: anywhere; }
    .pago-card-comprobantes { border-top: 1px dashed var(--border-color); padding-top: 8px; }
    .pago-card-archivo { flex: 1 1 auto; min-width: 0; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pago-card-acciones { display: flex; gap: 6px; margin-top: auto; }
    .pagos-total { display: flex; justify-content: space-between; gap: 8px; margin-top: 14px; padding-top: 10px; border-top: 2px solid var(--border-color); }
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
    <?php if (!empty($pagos)): ?>
        <a href="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>/combinado" data-combinar="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>/comprobantes" class="btn btn-outline-primary">
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
    <a href="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>/delete" class="btn btn-danger"
       data-confirm="¿Eliminar el lote '<?= htmlspecialchars($lote['consecutivo']) ?>' con todos sus pagos y comprobantes? Esta acción no se puede deshacer.">
        <i class="bi bi-trash-fill"></i> Eliminar Lote
    </a>
</div>

<div class="row">
<div class="col-xl-8">
<div class="card mb-3">
    <div class="card-header">
        <h5><i class="bi bi-list-check"></i> Pagos</h5>
    </div>
    <div class="card-body">
        <?php if (empty($pagos)): ?>
            <p class="text-center text-muted mb-0">Aún no hay pagos en este lote.</p>
        <?php else: ?>
        <div class="pagos-grid">
            <?php foreach ($pagos as $p): ?>
                <div class="pago-card">
                    <div class="pago-card-head">
                        <span class="pago-card-num"><?= (int)$p['orden'] ?></span>
                        <strong class="pago-card-prov" title="<?= htmlspecialchars($p['proveedor_nombre']) ?>"><?= htmlspecialchars($p['proveedor_nombre']) ?></strong>
                        <span class="pago-card-valor">$<?= number_format((float)$p['valor'], 2) ?></span>
                    </div>
                    <dl class="pago-card-datos">
                        <div><dt>Banco</dt><dd><?= htmlspecialchars($p['banco_nombre']) ?></dd></div>
                        <div><dt>Tipo producto</dt><dd><?= htmlspecialchars($p['tipo_producto_nombre']) ?></dd></div>
                        <div><dt>Nro. producto</dt><dd><?= htmlspecialchars($p['numero_producto']) ?></dd></div>
                        <div><dt>Fecha</dt><dd><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></dd></div>
                    </dl>
                    <div class="pago-card-comprobantes">
                        <?php if (empty($p['comprobantes'])): ?>
                            <small class="text-muted">Sin comprobantes</small>
                        <?php endif; ?>
                        <?php foreach ($p['comprobantes'] as $c): ?>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <a href="<?= BASE_URL ?>/pagos/comprobantes/<?= $c['id'] ?>/file" target="_blank"
                                   class="pago-card-archivo" title="Ver <?= htmlspecialchars($c['archivo_original']) ?>">
                                    <i class="bi bi-file-earmark-pdf-fill"></i> <?= htmlspecialchars($c['archivo_original']) ?>
                                </a>
                                <?php if ($lote['estado'] === 'abierto'): ?>
                                    <a href="<?= BASE_URL ?>/pagos/comprobantes/<?= $c['id'] ?>/delete"
                                       class="btn btn-icon btn-danger btn-sm" title="Eliminar <?= htmlspecialchars($c['archivo_original']) ?>"
                                       data-confirm="¿Eliminar el comprobante '<?= htmlspecialchars($c['archivo_original'], ENT_QUOTES) ?>'?">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($lote['estado'] === 'abierto'): ?>
                    <div class="pago-card-acciones">
                        <a href="<?= BASE_URL ?>/pagos/pagos/edit/<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm" title="Editar">
                            <i class="bi bi-pencil-fill"></i> Editar
                        </a>
                        <a href="<?= BASE_URL ?>/pagos/pagos/delete/<?= $p['id'] ?>" class="btn btn-danger btn-sm" title="Eliminar"
                           data-confirm="¿Eliminar este pago del lote?">
                            <i class="bi bi-trash-fill"></i> Eliminar
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="pagos-total">
            <span><?= count($pagos) ?> pago(s)</span>
            <span>Total: <strong>$<?= number_format($totalValor, 2) ?></strong></span>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<div class="col-xl-4">
    <div class="card mb-3 lotes-sticky">
        <div class="card-header">
            <h5><i class="bi bi-cash-stack"></i> Lote de este registro</h5>
        </div>
        <div class="card-body">
            <div class="lote-drop <?= $lote['estado'] === 'cerrado' ? 'cerrado' : '' ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <strong><?= htmlspecialchars($lote['consecutivo']) ?>
                        <?php if ($lote['estado'] === 'cerrado'): ?><span class="badge badge-secondary">Cerrado</span>
                        <?php else: ?><span class="badge badge-success">Abierto</span><?php endif; ?>
                    </strong>
                    <button type="button" class="btn btn-icon btn-outline-primary btn-sm" title="Ver lote" data-ver-lote="<?= $lote['id'] ?>">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
                <small class="text-muted">
                    <?= count($pagos) ?> pagos &middot; $<?= number_format($totalValor, 2) ?>
                </small>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5><i class="bi bi-receipt-cutoff"></i> Egresos / Legalizaciones</h5>
            <span class="badge <?= empty($documentos) ? 'bg-warning text-dark' : 'bg-success' ?>"><?= count($documentos) ?></span>
        </div>
        <div class="card-body">
            <p class="text-muted mb-2" style="font-size:13px;">Van al inicio del PDF combinado.</p>
            <?php if (empty($documentos)): ?>
                <div class="alert alert-warning py-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> Este lote no tiene comprobante de egreso ni legalización de anticipo asociados.
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
                    <a href="<?= BASE_URL ?>/pagos/egresos/<?= $d['id'] ?>/delete?lote=<?= (int)$lote['id'] ?>" class="btn btn-icon btn-danger btn-sm" title="Eliminar documento"
                       data-confirm="¿Eliminar este documento y sus archivos? <?= $d['tipo'] === 'comprobante_egreso' && !empty($d['legalizaciones']) ? 'Sus legalizaciones quedarán sin egreso asociado. ' : '' ?>Esta acción no se puede deshacer.">
                        <i class="bi bi-trash-fill"></i>
                    </a>
                </div>
            <?php endforeach; ?>
            <button type="button" class="btn btn-outline-primary btn-sm mt-1"
                    data-docs-abrir="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>/comprobantes">
                <i class="bi bi-upload"></i> <?= empty($documentos) ? 'Subir documento' : 'Agregar documento' ?>
            </button>
        </div>
    </div>
</div>
</div>

<?php if ($lote['estado'] === 'abierto'):
    $formAction = BASE_URL . '/pagos/lotes/' . $lote['id'] . '/pagos';
    include __DIR__ . '/_pago_form.php';
endif; ?>

<script>document.body.dataset.recargarDocs = '1';</script>
<?php require __DIR__ . '/_modal_documentos_pdf.php'; ?>
<?php require __DIR__ . '/_modal_pagos_especificos.php'; ?>
<?php require __DIR__ . '/_modal_ver_lote.php'; ?>
