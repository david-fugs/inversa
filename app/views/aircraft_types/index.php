<div class="page-actions">
    <a href="<?= BASE_URL ?>/aircraft-types/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Tipo de Avión
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-airplane"></i> Listado de Tipos de Avión</h5>
        <span class="badge badge-primary"><?= count($types) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Aerolínea</th>
                        <th>Tipo de Avión</th>
                        <th>Tiempo Cumplimiento</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($types as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td>
                                <span class="badge badge-secondary"><?= htmlspecialchars($t['airline_nombre']) ?></span>
                            </td>
                            <td><strong><?= htmlspecialchars($t['tipo']) ?></strong></td>
                            <td>
                                <span class="badge badge-info">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= $t['tiempo_cumplimiento'] ?> minutos
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/aircraft-types/edit/<?= $t['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/aircraft-types/delete/<?= $t['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar el tipo '<?= htmlspecialchars($t['tipo']) ?>'?">
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
