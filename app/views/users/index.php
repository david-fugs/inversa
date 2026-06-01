<div class="page-actions">
    <a href="<?= BASE_URL ?>/users/create" class="btn btn-primary">
        <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-people-fill"></i> Listado de Usuarios</h5>
        <span class="badge badge-primary"><?= count($users) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre Completo</th>
                        <th>Cédula</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Registrado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:34px;height:34px;background:var(--color-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="bi bi-person-fill" style="color:white;font-size:16px;"></i>
                                    </div>
                                    <strong><?= htmlspecialchars($u['nombre_completo']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['cedula']) ?></td>
                            <td><code><?= htmlspecialchars($u['usuario']) ?></code></td>
                            <td>
                                <?php if ($u['rol_nombre'] === 'Administrador'): ?>
                                    <span class="badge badge-secondary"><?= htmlspecialchars($u['rol_nombre']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-primary"><?= htmlspecialchars($u['rol_nombre']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/users/edit/<?= $u['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <?php if ($u['id'] != Session::get('user_id')): ?>
                                    <a href="<?= BASE_URL ?>/users/delete/<?= $u['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar al usuario '<?= htmlspecialchars($u['nombre_completo']) ?>'?">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
