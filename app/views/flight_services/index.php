<?php
$esColaborador  = Session::get('user_rol') === 'Colaborador';
$puedeEditar    = (bool)Session::get('user_puede_editar');
?>
<div class="page-actions">
    <a href="<?= BASE_URL ?>/flight-services/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Servicio
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-clipboard2-pulse-fill"></i> Servicios de Vuelo</h5>
        <span class="badge badge-primary"><?= count($services) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" id="tableServices" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Base</th>
                        <th>Aerolínea</th>
                        <th>Vuelo</th>
                        <th>Matrícula</th>
                        <th>Tipo Avión</th>
                        <th>Tipo Atención</th>
                        <th>Tránsito</th>
                        <th>Cumple</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                        <tr>
                            <td><strong>#<?= $s['id'] ?></strong></td>
                            <td>
                                <?php
                                    $meses = FlightService::$meses;
                                    echo sprintf('%02d/%s/%s', $s['dia'], $meses[$s['mes']] ?? $s['mes'], $s['anio']);
                                ?>
                                <small class="d-block text-muted"><?= $s['quincena'] == 1 ? '1ª Quincena' : '2ª Quincena' ?></small>
                            </td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($s['base']) ?></span></td>
                            <td><?= htmlspecialchars($s['airline_nombre']) ?></td>
                            <td>
                                <div>
                                    <small class="text-muted">↓</small> <strong><?= htmlspecialchars($s['vuelo_llegando']) ?></strong>
                                </div>
                                <div>
                                    <small class="text-muted">↑</small> <?= htmlspecialchars($s['vuelo_saliendo']) ?>
                                </div>
                            </td>
                            <td><code><?= htmlspecialchars($s['matricula']) ?></code></td>
                            <td><?= htmlspecialchars($s['aircraft_tipo']) ?></td>
                            <td>
                                <span class="badge badge-info"><?= htmlspecialchars($s['tipo_atencion']) ?></span>
                            </td>
                            <td>
                                <?php if ($s['tiempo_transito'] !== null): ?>
                                    <span class="time-display"><?= $s['tiempo_transito'] ?> min</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['cumple_tiempo'] === null): ?>
                                    <span class="text-muted">—</span>
                                <?php elseif ($s['cumple_tiempo']): ?>
                                    <span class="cumple-si"><i class="bi bi-check-circle-fill"></i> SI</span>
                                <?php else: ?>
                                    <span class="cumple-no"><i class="bi bi-x-circle-fill"></i> NO</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/flight-services/view/<?= $s['id'] ?>"
                                       class="btn btn-icon btn-outline-primary btn-sm" title="Ver detalle">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <?php if (!$esColaborador || $puedeEditar): ?>
                                    <a href="<?= BASE_URL ?>/flight-services/edit/<?= $s['id'] ?>"
                                       class="btn btn-icon btn-outline-secondary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!$esColaborador): ?>
                                    <a href="<?= BASE_URL ?>/flight-services/delete/<?= $s['id'] ?>"
                                       class="btn btn-icon btn-danger btn-sm"
                                       title="Eliminar"
                                       data-confirm="¿Está seguro de eliminar el servicio #<?= $s['id'] ?>?">
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
