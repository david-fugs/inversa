<div class="page-actions">
    <a href="<?= BASE_URL ?>/equipos/nuevo" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Equipo
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-gear-wide-connected"></i> Listado de Equipos</h5>
        <span class="badge badge-primary"><?= count($equipos) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th><th>Base</th><th>Nombre</th><th>RPM</th><th>Cantidad</th><th>Fecha Registro</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos as $e): ?>
                        <tr>
                            <td><?= (int)$e['id'] ?></td>
                            <td><span class="badge bg-success"><?= htmlspecialchars($e['base_nombre']) ?></span></td>
                            <td><strong><?= htmlspecialchars($e['nombre']) ?></strong></td>
                            <td><?= number_format((int)$e['rpm'], 0, ',', '.') ?></td>
                            <td><?= (int)$e['cantidad'] ?></td>
                            <td><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/equipos/<?= (int)$e['id'] ?>/edit" class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/equipos/<?= (int)$e['id'] ?>/delete" class="btn btn-icon btn-danger btn-sm" title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar el equipo '<?= htmlspecialchars($e['nombre']) ?>'?">
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
