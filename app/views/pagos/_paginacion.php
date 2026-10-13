<?php
/** Paginador. Requiere: $pagina, $totalPaginas, $paginaUrl (callable int => url). */
if ($totalPaginas <= 1) return;
$desde = max(1, $pagina - 2);
$hasta = min($totalPaginas, $pagina + 2);
?>
<nav class="d-flex justify-content-center py-3" aria-label="Paginación">
    <ul class="pagination mb-0">
        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($paginaUrl(max(1, $pagina - 1))) ?>" aria-label="Anterior">&laquo;</a>
        </li>
        <?php if ($desde > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($paginaUrl(1)) ?>">1</a></li>
            <?php if ($desde > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $desde; $i <= $hasta; $i++): ?>
            <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($paginaUrl($i)) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($hasta < $totalPaginas): ?>
            <?php if ($hasta < $totalPaginas - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($paginaUrl($totalPaginas)) ?>"><?= $totalPaginas ?></a></li>
        <?php endif; ?>
        <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($paginaUrl(min($totalPaginas, $pagina + 1))) ?>" aria-label="Siguiente">&raquo;</a>
        </li>
    </ul>
</nav>
