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
    <a href="<?= BASE_URL ?>/pagos/lotes/<?= $lote['id'] ?>/delete" class="btn btn-danger"
       data-confirm="¿Eliminar el lote '<?= htmlspecialchars($lote['consecutivo']) ?>' con todos sus pagos y comprobantes? Esta acción no se puede deshacer.">
        <i class="bi bi-trash-fill"></i> Eliminar Lote
    </a>
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

<?php if ($lote['estado'] === 'abierto'):
    $formAction = BASE_URL . '/pagos/lotes/' . $lote['id'] . '/pagos';
    include __DIR__ . '/_pago_form.php';
endif; ?>
