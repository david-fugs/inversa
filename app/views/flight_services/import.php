<div class="page-actions">
    <a href="<?= BASE_URL ?>/flight-services" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h5><i class="bi bi-file-earmark-excel-fill"></i> Importar Servicios de Vuelo desde Excel</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">
            Sube el archivo <code>.xlsx</code> con el formato histórico "Informe Operacional y Servicios Prestados"
            (hoja "Informe Servicios", datos desde la fila 5). Se detiene automáticamente al llegar a la primera
            fila sin Año/Mes/Quincena.
        </p>
        <form method="POST" action="<?= BASE_URL ?>/flight-services/import" enctype="multipart/form-data" class="d-flex gap-2 align-items-end flex-wrap">
            <div>
                <label for="archivo_excel" class="form-label">Archivo Excel (.xlsx)</label>
                <input type="file" class="form-control" id="archivo_excel" name="archivo_excel" accept=".xlsx" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload"></i> Importar
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-clock-history"></i> Historial de Importaciones</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Archivo</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th class="text-center">Total Filas</th>
                        <th class="text-center">Exitosas</th>
                        <th class="text-center">Con Error</th>
                        <th class="text-center">Detalle</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($imports)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Aún no se ha realizado ninguna importación.</td></tr>
                    <?php else: ?>
                        <?php foreach ($imports as $imp): ?>
                            <tr>
                                <td><strong>#<?= $imp['id'] ?></strong></td>
                                <td><?= htmlspecialchars($imp['nombre_archivo']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($imp['created_at'])) ?></td>
                                <td><?= htmlspecialchars($imp['usuario_nombre']) ?></td>
                                <td class="text-center"><?= (int)$imp['total_filas'] ?></td>
                                <td class="text-center"><span class="cumple-si"><?= (int)$imp['filas_exitosas'] ?></span></td>
                                <td class="text-center">
                                    <?php if ((int)$imp['filas_error'] > 0): ?>
                                        <span class="cumple-no"><?= (int)$imp['filas_error'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$imp['filas_error'] > 0): ?>
                                        <a href="<?= BASE_URL ?>/flight-services/import/<?= $imp['id'] ?>/errors" class="btn btn-icon btn-outline-secondary btn-sm" title="Ver errores">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>/flight-services/import/<?= $imp['id'] ?>/delete" class="btn btn-icon btn-danger btn-sm" title="Eliminar importación y sus registros" data-confirm="¿Eliminar la importación &quot;<?= htmlspecialchars($imp['nombre_archivo']) ?>&quot; y los <?= (int)$imp['filas_exitosas'] ?> registro(s) de servicios de vuelo que creó? Esta acción no se puede deshacer.">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
