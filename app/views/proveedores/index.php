<div class="page-actions">
    <a href="<?= BASE_URL ?>/proveedores/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Proveedor
    </a>
    <a href="<?= BASE_URL ?>/proveedores/import" class="btn btn-light">
        <i class="bi bi-file-earmark-excel-fill"></i> Subir Excel Proveedores
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-person-vcard-fill"></i> Listado de Proveedores</h5>
        <span class="badge badge-primary"><?= count($proveedores) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo Ident.</th>
                        <th>Identificación</th>
                        <th>Nombre</th>
                        <th>Banco</th>
                        <th>Tipo de Producto</th>
                        <th>Número de Producto</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proveedores as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['tipo_identificacion']) ?></td>
                            <td><?= htmlspecialchars($p['numero_identificacion']) ?></td>
                            <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($p['banco_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['tipo_producto_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['numero_producto']) ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/proveedores/edit/<?= $p['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/proveedores/delete/<?= $p['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar el proveedor '<?= htmlspecialchars($p['nombre']) ?>'?">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
