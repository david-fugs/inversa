<?php
/** Pantalla del Revisor Pagos.
 *  Requiere: $pagos, $tab, $resumen, $pagina, $totalPaginas, $total. */
$tabs = [
    'pendiente'  => ['Pendientes',  'bi-hourglass-split',     'warning'],
    'aprobado'   => ['Aprobados',   'bi-check-circle-fill',   'success'],
    'rechazado'  => ['Rechazados',  'bi-x-circle-fill',       'danger'],
    'todos'      => ['Todos',       'bi-list-ul',             'primary'],
    'archivados' => ['Archivados',  'bi-archive-fill',        'secondary'],
];
$urlActual = BASE_URL . '/pagos/revision?estado=' . $tab . '&page=' . $pagina;
?>
<style>
    .rev-tabs { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
    .rev-tab {
        display:flex; align-items:center; gap:10px; padding:10px 16px; border-radius:12px;
        background:#fff; border:1px solid var(--bs-border-color, #dee2e6); color:#495057;
        text-decoration:none; font-weight:600; font-size:14px;
        box-shadow:0 1px 2px rgba(0,0,0,.04); transition:transform .12s, box-shadow .12s, border-color .12s;
    }
    .rev-tab:hover { transform:translateY(-1px); box-shadow:0 4px 10px rgba(0,0,0,.08); color:#212529; }
    .rev-tab i { font-size:16px; }
    .rev-tab .rev-count {
        min-width:26px; text-align:center; padding:2px 8px; border-radius:999px;
        font-size:12px; font-weight:700; background:#eef0f3; color:#495057;
    }
    .rev-tab.active { color:#fff; border-color:transparent; }
    .rev-tab.active .rev-count { background:rgba(255,255,255,.25); color:#fff; }
    .rev-tab.active.t-warning   { background:linear-gradient(135deg,#e8a900,#f4c542); color:#3d2f00; }
    .rev-tab.active.t-warning .rev-count { background:rgba(0,0,0,.12); color:#3d2f00; }
    .rev-tab.active.t-success   { background:linear-gradient(135deg,#198754,#2fb36f); }
    .rev-tab.active.t-danger    { background:linear-gradient(135deg,#c0392b,#e74c3c); }
    .rev-tab.active.t-primary   { background:linear-gradient(135deg,#0d6efd,#4a90f7); }
    .rev-tab.active.t-secondary { background:linear-gradient(135deg,#5c636a,#848b92); }
</style>

<nav class="rev-tabs" aria-label="Estado de revisión">
    <?php foreach ($tabs as $k => [$label, $icono, $color]): ?>
        <a class="rev-tab t-<?= $color ?> <?= $tab === $k ? 'active' : '' ?>" href="<?= BASE_URL ?>/pagos/revision?estado=<?= $k ?>">
            <i class="bi <?= $icono ?>"></i>
            <span><?= $label ?></span>
            <span class="rev-count"><?= (int)($resumen[$k] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-clipboard-check-fill"></i> <?= $tab === 'archivados' ? 'Pagos archivados' : 'Pagos para revisión' ?></h5>
        <span class="badge badge-primary"><?= (int)$total ?> pagos</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th><th>Creado por</th><th>Proveedor</th><th>Banco</th><th>Tipo Producto</th>
                        <th>Nro. Producto</th><th>Fecha</th><th>Valor</th><th>Comprobantes</th><th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pagos)): ?>
                        <tr><td colspan="11" class="text-center text-muted">No hay pagos en esta pestaña.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($pagos as $p): ?>
                        <tr>
                            <td><?= (int)$p['id'] ?></td>
                            <td><?= htmlspecialchars($p['creador_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['proveedor_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['banco_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['tipo_producto_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['numero_producto']) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                            <td>$<?= number_format((float)$p['valor'], 2) ?></td>
                            <td>
                                <?php foreach ($p['comprobantes'] as $c): ?>
                                    <a href="<?= BASE_URL ?>/pagos/comprobantes/<?= $c['id'] ?>/file" target="_blank"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Ver <?= htmlspecialchars($c['archivo_original']) ?>">
                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                    </a>
                                <?php endforeach; ?>
                            </td>
                            <td><?php require __DIR__ . '/_estado_revision.php'; ?></td>
                            <td class="text-center" style="white-space:nowrap;">
                                <?php if ($p['lote_pago_id'] !== null): ?>
                                    <span class="badge bg-secondary">En lote <?= htmlspecialchars($p['lote_consecutivo']) ?></span>
                                <?php else: ?>
                                    <?php if ($p['estado_revision'] !== 'aprobado'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/pagos/pagos/<?= $p['id'] ?>/revisar" class="d-inline">
                                        <input type="hidden" name="accion" value="aprobar">
                                        <button type="submit" class="btn btn-success btn-sm" title="Aprobar">
                                            <i class="bi bi-check-lg"></i> Aprobar
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($p['estado_revision'] !== 'rechazado'): ?>
                                    <button type="button" class="btn btn-danger btn-sm btn-rechazar"
                                            data-pago-id="<?= (int)$p['id'] ?>" title="Rechazar">
                                        <i class="bi bi-x-lg"></i> Rechazar
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <form method="POST" action="<?= BASE_URL ?>/pagos/pagos/<?= $p['id'] ?>/archivar" class="d-inline">
                                    <input type="hidden" name="valor" value="<?= $tab === 'archivados' ? '0' : '1' ?>">
                                    <input type="hidden" name="volver" value="<?= htmlspecialchars($urlActual) ?>">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm"
                                            title="<?= $tab === 'archivados' ? 'Restaurar a la bandeja' : 'Archivar' ?>">
                                        <i class="bi <?= $tab === 'archivados' ? 'bi-box-arrow-up' : 'bi-archive' ?>"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $paginaUrl = fn(int $n) => BASE_URL . '/pagos/revision?estado=' . $tab . '&page=' . $n;
        require __DIR__ . '/_paginacion.php';
        ?>
    </div>
</div>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" id="formRechazar">
            <input type="hidden" name="accion" value="rechazar">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-x-circle-fill"></i> Rechazar pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <label for="motivoRechazo" class="form-label">Motivo del rechazo <span class="required-mark">*</span></label>
                <textarea class="form-control" id="motivoRechazo" name="motivo" rows="3" maxlength="255" required></textarea>
                <small class="text-muted">El pago vuelve a la lista del creador (aunque lo tuviera archivado) con este motivo para que lo corrija.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Rechazar</button>
            </div>
        </form>
    </div>
</div>
<script>
window.addEventListener('load', function () {
    var modal = new bootstrap.Modal(document.getElementById('modalRechazar'));
    document.querySelectorAll('.btn-rechazar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('formRechazar').action = BASE_URL + '/pagos/pagos/' + btn.dataset.pagoId + '/revisar';
            document.getElementById('motivoRechazo').value = '';
            modal.show();
        });
    });
});
</script>
