<?php
/** Insignia del estado de revisión de un pago. Requiere $p['estado_revision'] y opcional $p['motivo_rechazo']. */
$estadoRev = $p['estado_revision'] ?? 'aprobado';
$clases = ['pendiente' => 'bg-warning text-dark', 'aprobado' => 'bg-success', 'rechazado' => 'bg-danger'];
$textos = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];
?>
<span class="badge <?= $clases[$estadoRev] ?? 'bg-secondary' ?>"><?= $textos[$estadoRev] ?? htmlspecialchars($estadoRev) ?></span>
<?php if ($estadoRev === 'rechazado' && !empty($p['motivo_rechazo'])): ?>
    <small class="d-block text-danger mt-1">Motivo: <?= htmlspecialchars($p['motivo_rechazo']) ?></small>
<?php endif; ?>
