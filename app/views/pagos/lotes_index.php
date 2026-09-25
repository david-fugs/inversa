<div class="page-actions">
    <a href="<?= BASE_URL ?>/pagos/lotes/nuevo?nuevo=1" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Lote de Pago
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-cash-stack"></i> Lotes de Pago</h5>
        <span class="badge badge-primary"><?= count($lotes) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Consecutivo</th>
                        <th>Estado</th>
                        <th>Pagos</th>
                        <th>Valor Total</th>
                        <th>Fecha</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lotes as $l): ?>
                        <tr>
                            <td><?= $l['id'] ?></td>
                            <td><strong><?= htmlspecialchars($l['consecutivo']) ?></strong></td>
                            <td>
                                <?php if ($l['estado'] === 'abierto'): ?>
                                    <span class="badge badge-success">Abierto</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Cerrado</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$l['total_pagos'] ?></td>
                            <td>$<?= number_format((float)$l['total_valor'], 2) ?></td>
                            <td><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/pagos/lotes/<?= $l['id'] ?>"
                                   class="btn btn-icon btn-outline-primary btn-sm" title="Ver detalle">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/pagos/lotes/<?= $l['id'] ?>/delete"
                                   class="btn btn-icon btn-danger btn-sm" title="Eliminar lote"
                                   data-confirm="¿Eliminar el lote '<?= htmlspecialchars($l['consecutivo']) ?>' con todos sus pagos y comprobantes? Esta acción no se puede deshacer.">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
