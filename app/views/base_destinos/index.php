<div class="page-actions">
    <a href="<?= BASE_URL ?>/base-destinos/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nueva Base Destino
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-signpost-split-fill"></i> Listado de Bases Destino</h5>
        <span class="badge badge-primary"><?= count($baseDestinos) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre de la Base Destino</th>
                        <th>Fecha Registro</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($baseDestinos as $baseDestino): ?>
                        <tr>
                            <td><?= $baseDestino['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="bi bi-signpost-split-fill" style="color:white;font-size:15px;"></i>
                                    </div>
                                    <strong><?= htmlspecialchars($baseDestino['nombre']) ?></strong>
                                </div>
                            </td>
                            <td><?= date('d/m/Y', strtotime($baseDestino['created_at'])) ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/base-destinos/edit/<?= $baseDestino['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/base-destinos/delete/<?= $baseDestino['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar la base destino '<?= htmlspecialchars($baseDestino['nombre']) ?>'?">
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
