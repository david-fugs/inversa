<?php
/** Listado de Egresos / Legalizaciones. Requiere: $documentos, $tipoFiltro, $sinLote, $pagina, $totalPaginas. */
$tipos = EgresoDocumento::TIPOS;
?>
<div class="page-actions">
    <a href="<?= BASE_URL ?>/pagos/egresos/nuevo" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Documento
    </a>
</div>

<?php if ($sinLote > 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Hay <strong><?= (int)$sinLote ?></strong> documento<?= $sinLote === 1 ? '' : 's' ?> sin lote asociado.
        Asócielos a un lote desde el botón de editar para que se incluyan en el PDF combinado.
    </div>
<?php endif; ?>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $tipoFiltro === null ? 'active' : '' ?>" href="<?= BASE_URL ?>/pagos/egresos">Todos</a></li>
    <?php foreach ($tipos as $k => $label): ?>
        <li class="nav-item"><a class="nav-link <?= $tipoFiltro === $k ? 'active' : '' ?>" href="<?= BASE_URL ?>/pagos/egresos?tipo=<?= $k ?>"><?= $label ?></a></li>
    <?php endforeach; ?>
</ul>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-receipt-cutoff"></i> Egresos / Legalizaciones</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th><th>Tipo</th><th>Fecha</th><th>Lotes</th><th>Archivos</th>
                        <th>Registrado por</th><th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documentos)): ?>
                        <tr><td colspan="7" class="text-center text-muted">Aún no hay documentos registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($documentos as $d): ?>
                        <tr>
                            <td><?= (int)$d['id'] ?></td>
                            <td>
                                <span class="badge <?= $d['tipo'] === 'comprobante_egreso' ? 'bg-primary' : 'bg-info text-dark' ?>">
                                    <?= htmlspecialchars($tipos[$d['tipo']] ?? $d['tipo']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($d['fecha'])) ?></td>
                            <td>
                                <?php if (empty($d['lotes'])): ?>
                                    <span class="badge bg-danger" title="Este documento no se incluirá en ningún PDF combinado">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Sin lote asociado
                                    </span>
                                <?php else: ?>
                                    <?php foreach ($d['lotes'] as $l): ?>
                                        <a href="<?= BASE_URL ?>/pagos/lotes/<?= (int)$l['id'] ?>" class="badge bg-success text-decoration-none">
                                            <?= htmlspecialchars($l['consecutivo']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php foreach ($d['archivos'] as $a): ?>
                                    <a href="<?= BASE_URL ?>/pagos/egresos/archivos/<?= $a['id'] ?>/file" target="_blank"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Ver <?= htmlspecialchars($a['archivo_original']) ?>">
                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                    </a>
                                <?php endforeach; ?>
                            </td>
                            <td><?= htmlspecialchars($d['registrado_por']) ?></td>
                            <td class="text-center" style="white-space:nowrap;">
                                <a href="<?= BASE_URL ?>/pagos/egresos/<?= $d['id'] ?>/edit" class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/pagos/egresos/<?= $d['id'] ?>/delete" class="btn btn-icon btn-danger btn-sm" title="Eliminar"
                                   data-confirm="¿Eliminar este documento y sus archivos? Esta acción no se puede deshacer.">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $qTipo = $tipoFiltro ? '&tipo=' . $tipoFiltro : '';
        $paginaUrl = fn(int $n) => BASE_URL . '/pagos/egresos?page=' . $n . $qTipo;
        require APP_PATH . '/views/pagos/_paginacion.php';
        ?>
    </div>
</div>
