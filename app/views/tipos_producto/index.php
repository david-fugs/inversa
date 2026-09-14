<div class="page-actions">
    <?php if (Session::get('user_rol') === 'Admin Pagos'): ?>
    <a href="<?= BASE_URL ?>/tipos-producto/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Tipo de Producto
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-tags-fill"></i> Listado de Tipos de Producto</h5>
        <span class="badge badge-primary"><?= count($tiposProducto) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Fecha Registro</th>
                        <?php if (Session::get('user_rol') === 'Admin Pagos'): ?>
                        <th class="text-center">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tiposProducto as $tp): ?>
                        <tr>
                            <td><?= $tp['id'] ?></td>
                            <td><strong><?= htmlspecialchars($tp['codigo']) ?></strong></td>
                            <td><?= htmlspecialchars($tp['nombre']) ?></td>
                            <td><?= date('d/m/Y', strtotime($tp['created_at'])) ?></td>
                            <?php if (Session::get('user_rol') === 'Admin Pagos'): ?>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/tipos-producto/edit/<?= $tp['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/tipos-producto/delete/<?= $tp['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar el tipo de producto '<?= htmlspecialchars($tp['nombre']) ?>'?">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
