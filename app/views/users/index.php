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
                        <th>Base</th>
                        <th>Puede Editar</th>
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
                            <td>
                                <?= $u['base_asociada'] ? '<span class="badge badge-info">' . htmlspecialchars($u['base_asociada']) . '</span>' : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="text-center">
                                <?php if ($u['rol_nombre'] === 'Colaborador'): ?>
                                    <?php if ($u['puede_editar']): ?>
                                        <span class="cumple-si"><i class="bi bi-check-circle-fill"></i> Sí<?= !empty($u['puede_editar_servicio_id']) ? ' (vuelo #' . (int)$u['puede_editar_servicio_id'] . ')' : '' ?></span>
                                    <?php else: ?>
                                        <span class="cumple-no"><i class="bi bi-x-circle-fill"></i> No</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/users/edit/<?= $u['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <?php if ($u['rol_nombre'] === 'Colaborador'): ?>
                                    <button type="button"
                                       class="btn btn-icon btn-sm btn-permiso-edicion <?= $u['puede_editar'] ? 'btn-warning' : 'btn-outline-warning' ?>"
                                       title="<?= $u['puede_editar'] ? 'Permiso de edición (activo)' : 'Dar permiso de edición' ?>"
                                       data-user-id="<?= (int)$u['id'] ?>"
                                       data-nombre="<?= htmlspecialchars($u['nombre_completo']) ?>"
                                       data-activo="<?= $u['puede_editar'] ? '1' : '0' ?>"
                                       data-servicio-id="<?= htmlspecialchars((string)($u['puede_editar_servicio_id'] ?? '')) ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <?php endif; ?>
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

<!-- Modal: permiso de edición (opcionalmente limitado a un vuelo) -->
<div class="modal fade" id="modalPermisoEdicion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" id="formPermisoEdicion" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Permiso de edición</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Usuario: <strong id="permisoEdicionNombre"></strong></p>
                <label for="permisoServicioId" class="form-label">ID del vuelo (opcional)</label>
                <input type="number" min="1" class="form-control" name="servicio_id" id="permisoServicioId" placeholder="Vacío = puede editar todos los vuelos">
                <div class="form-text">Si indica un ID, el usuario solo podrá editar ese servicio en Servicios de Vuelo.</div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="accion" value="quitar" class="btn btn-outline-danger me-auto" id="btnQuitarPermiso">Quitar permiso</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" name="accion" value="conceder" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
<script>
window.addEventListener('load', function () {
    const modalEl = document.getElementById('modalPermisoEdicion');
    const modal = new bootstrap.Modal(modalEl);
    document.querySelectorAll('.btn-permiso-edicion').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('formPermisoEdicion').action = BASE_URL + '/users/permiso-edicion/' + btn.dataset.userId;
            document.getElementById('permisoEdicionNombre').textContent = btn.dataset.nombre;
            document.getElementById('permisoServicioId').value = btn.dataset.servicioId;
            document.getElementById('btnQuitarPermiso').hidden = btn.dataset.activo !== '1';
            modal.show();
        });
    });
});
</script>
