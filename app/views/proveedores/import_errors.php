<div class="page-actions">
    <a href="<?= BASE_URL ?>/proveedores/import" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver a Importaciones
    </a>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <h5 class="mb-1">Importación #<?= $import['id'] ?> — <?= htmlspecialchars($import['nombre_archivo']) ?></h5>
        <div class="d-flex gap-3 flex-wrap text-muted" style="font-size:14px;">
            <span><i class="bi bi-calendar3"></i> <?= date('d/m/Y H:i', strtotime($import['created_at'])) ?></span>
            <span><i class="bi bi-person"></i> <?= htmlspecialchars($import['usuario_nombre']) ?></span>
            <span><i class="bi bi-list-ol"></i> <?= (int)$import['total_filas'] ?> filas totales</span>
            <span class="cumple-si"><i class="bi bi-check-circle-fill"></i> <?= (int)$import['filas_creadas'] ?> creados</span>
            <span><i class="bi bi-arrow-repeat"></i> <?= (int)$import['filas_actualizadas'] ?> actualizados</span>
            <span class="cumple-no"><i class="bi bi-x-circle-fill"></i> <?= (int)$import['filas_error'] ?> con error</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-exclamation-triangle-fill"></i> Filas con Error</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>Fila (Excel)</th>
                        <th>Motivo del error</th>
                        <th>Datos de la fila</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($errors)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">Esta importación no tuvo errores.</td></tr>
                    <?php else: ?>
                        <?php foreach ($errors as $err): ?>
                            <?php $datos = json_decode($err['datos_fila'] ?? '[]', true) ?: []; ?>
                            <tr>
                                <td><strong><?= (int)$err['fila'] ?></strong></td>
                                <td><?= htmlspecialchars($err['mensaje']) ?></td>
                                <td>
                                    <small class="text-muted" style="word-break:break-word;">
                                        <?php
                                        $pares = [];
                                        foreach ($datos as $col => $val) {
                                            if ($val === null || $val === '') continue;
                                            $pares[] = $col . '=' . (is_array($val) ? json_encode($val) : $val);
                                        }
                                        echo htmlspecialchars(implode(' | ', $pares));
                                        ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
