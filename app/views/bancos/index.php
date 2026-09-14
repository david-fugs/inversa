<div class="page-actions">
    <?php if (Session::get('user_rol') === 'Admin Pagos'): ?>
    <a href="<?= BASE_URL ?>/bancos/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Banco
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-bank"></i> Listado de Bancos</h5>
        <span class="badge badge-primary"><?= count($bancos) ?> registros</span>
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
                    <?php foreach ($bancos as $b): ?>
                        <tr>
                            <td><?= $b['id'] ?></td>
                            <td><strong><?= htmlspecialchars($b['codigo']) ?></strong></td>
                            <td><?= htmlspecialchars($b['nombre']) ?></td>
                            <td><?= date('d/m/Y', strtotime($b['created_at'])) ?></td>
                            <?php if (Session::get('user_rol') === 'Admin Pagos'): ?>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/bancos/edit/<?= $b['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/bancos/delete/<?= $b['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar el banco '<?= htmlspecialchars($b['nombre']) ?>'?">
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
