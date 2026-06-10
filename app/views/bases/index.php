<div class="page-actions">
    <a href="<?= BASE_URL ?>/bases/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nueva Base
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-geo-alt-fill"></i> Listado de Bases</h5>
        <span class="badge badge-primary"><?= count($bases) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre de la Base</th>
                        <th>Fecha Registro</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bases as $base): ?>
                        <tr>
                            <td><?= $base['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="bi bi-geo-alt-fill" style="color:white;font-size:15px;"></i>
                                    </div>
                                    <strong><?= htmlspecialchars($base['nombre']) ?></strong>
                                </div>
                            </td>
                            <td><?= date('d/m/Y', strtotime($base['created_at'])) ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/bases/edit/<?= $base['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/bases/delete/<?= $base['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar la base '<?= htmlspecialchars($base['nombre']) ?>'?">
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
