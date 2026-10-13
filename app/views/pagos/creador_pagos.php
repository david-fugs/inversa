<?php
/** Pantalla del Creador Pagos. Requiere: $misPagos, $proveedores, $bancos, $tiposProducto, $errors, $old. */
?>
<?php
$urlActual = BASE_URL . '/pagos/lotes/nuevo?tab=' . $tab . '&page=' . $pagina;
?>
<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'activos' ? 'active' : '' ?>" href="<?= BASE_URL ?>/pagos/lotes/nuevo?tab=activos">
            <i class="bi bi-list-check"></i> Activos <span class="badge bg-light text-dark"><?= (int)$resumen['activos'] ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'archivados' ? 'active' : '' ?>" href="<?= BASE_URL ?>/pagos/lotes/nuevo?tab=archivados">
            <i class="bi bi-archive-fill"></i> Archivados <span class="badge bg-light text-dark"><?= (int)$resumen['archivados'] ?></span>
        </a>
    </li>
</ul>

<div class="card mb-3">
    <div class="card-header">
        <h5><i class="bi bi-list-check"></i> <?= $tab === 'archivados' ? 'Pagos archivados' : 'Mis pagos' ?></h5>
        <span class="badge badge-primary"><?= (int)($tab === 'archivados' ? $resumen['archivados'] : $resumen['activos']) ?> pagos</span>
    </div>
    <div class="card-body p-0">
        <p class="text-muted px-3 pt-3 mb-2" style="font-size:13px;">
            Los pagos que registre quedan <strong>pendientes</strong> hasta que el revisor los apruebe o rechace.
            Puede corregir un pago mientras no esté asignado a un lote; al editarlo vuelve a revisión.
            Si el revisor rechaza un pago, vuelve a esta lista aunque lo haya archivado.
        </p>
        <div class="table-wrapper">
            <table class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th><th>Proveedor</th><th>Banco</th><th>Tipo Producto</th><th>Nro. Producto</th>
                        <th>Fecha</th><th>Valor</th><th>Comprobante</th><th>Estado</th><th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($misPagos)): ?>
                        <tr><td colspan="10" class="text-center text-muted"><?= $tab === 'archivados' ? 'No tiene pagos archivados.' : 'Aún no ha registrado pagos.' ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($misPagos as $p): ?>
                        <tr>
                            <td><?= (int)$p['id'] ?></td>
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
                            <td class="text-center">
                                <?php if ($p['lote_pago_id'] === null): ?>
                                    <a href="<?= BASE_URL ?>/pagos/pagos/edit/<?= $p['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">En lote</span>
                                <?php endif; ?>
                                <form method="POST" action="<?= BASE_URL ?>/pagos/pagos/<?= $p['id'] ?>/archivar" class="d-inline">
                                    <input type="hidden" name="valor" value="<?= $tab === 'archivados' ? '0' : '1' ?>">
                                    <input type="hidden" name="volver" value="<?= htmlspecialchars($urlActual) ?>">
                                    <button type="submit" class="btn btn-icon btn-outline-secondary btn-sm"
                                            title="<?= $tab === 'archivados' ? 'Restaurar' : 'Archivar' ?>">
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
        $paginaUrl = fn(int $n) => BASE_URL . '/pagos/lotes/nuevo?tab=' . $tab . '&page=' . $n;
        require __DIR__ . '/_paginacion.php';
        ?>
    </div>
</div>

<?php
$formAction = BASE_URL . '/pagos/lotes/nuevo/pagos';
$comprobanteObligatorio = true;
include __DIR__ . '/_pago_form.php';
