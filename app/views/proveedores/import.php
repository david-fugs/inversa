<div class="page-actions">
    <a href="<?= BASE_URL ?>/proveedores" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h5><i class="bi bi-file-earmark-excel-fill"></i> Importar Proveedores desde Excel</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-1">
            Sube el archivo <code>.xlsx</code> con las columnas: Tipo de Identificación, Número de Identificación,
            Nombre, Apellido, Código del Banco, Tipo de Producto o Servicio (código) y Número del Producto o Servicio
            (hoja "Hoja1", datos desde la fila 2).
        </p>
        <p class="text-muted">
            "Nombre" y "Apellido" se combinan para formar el nombre del proveedor. Si el código de banco o de tipo
            de producto no existe todavía, se crea automáticamente. Si el número de identificación ya existe, el
            proveedor se actualiza en lugar de duplicarse.
        </p>
        <form method="POST" action="<?= BASE_URL ?>/proveedores/import" enctype="multipart/form-data" class="d-flex gap-2 align-items-end flex-wrap">
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
                        <th class="text-center">Creados</th>
                        <th class="text-center">Actualizados</th>
                        <th class="text-center">Con Error</th>
                        <th class="text-center">Detalle</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($imports)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">Aún no se ha realizado ninguna importación.</td></tr>
                    <?php else: ?>
                        <?php foreach ($imports as $imp): ?>
                            <tr>
                                <td><strong>#<?= $imp['id'] ?></strong></td>
                                <td><?= htmlspecialchars($imp['nombre_archivo']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($imp['created_at'])) ?></td>
                                <td><?= htmlspecialchars($imp['usuario_nombre']) ?></td>
                                <td class="text-center"><?= (int)$imp['total_filas'] ?></td>
                                <td class="text-center"><span class="cumple-si"><?= (int)$imp['filas_creadas'] ?></span></td>
                                <td class="text-center"><?= (int)$imp['filas_actualizadas'] ?></td>
                                <td class="text-center">
                                    <?php if ((int)$imp['filas_error'] > 0): ?>
                                        <span class="cumple-no"><?= (int)$imp['filas_error'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$imp['filas_error'] > 0): ?>
                                        <a href="<?= BASE_URL ?>/proveedores/import/<?= $imp['id'] ?>/errors" class="btn btn-icon btn-outline-secondary btn-sm" title="Ver errores">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>/proveedores/import/<?= $imp['id'] ?>/delete" class="btn btn-icon btn-danger btn-sm" title="Eliminar registro de importación" data-confirm="¿Eliminar el registro de la importación &quot;<?= htmlspecialchars($imp['nombre_archivo']) ?>&quot;? Esto solo borra el historial, no los proveedores creados o actualizados.">
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
