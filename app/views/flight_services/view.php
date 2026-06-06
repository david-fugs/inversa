<div class="page-actions">
    <a href="<?= BASE_URL ?>/flight-services" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
    <a href="<?= BASE_URL ?>/flight-services/edit/<?= $service['id'] ?>" class="btn btn-outline-secondary">
        <i class="bi bi-pencil-fill"></i> Editar
    </a>
    <a href="<?= BASE_URL ?>/flight-services/delete/<?= $service['id'] ?>"
       class="btn btn-danger"
       data-confirm="¿Está seguro de eliminar el servicio #<?= $service['id'] ?>?">
        <i class="bi bi-trash-fill"></i> Eliminar
    </a>
</div>

<!-- Encabezado -->
<div class="card mb-3" style="border-left:4px solid var(--color-secondary);">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));border-radius:14px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-airplane-fill" style="color:white;font-size:28px;"></i>
                </div>
            </div>
            <div class="col">
                <h4 class="mb-1">Servicio de Vuelo #<?= $service['id'] ?></h4>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge badge-primary"><?= htmlspecialchars($service['base']) ?></span>
                    <span class="badge badge-secondary"><?= htmlspecialchars($service['airline_nombre']) ?></span>
                    <span class="badge badge-info"><?= htmlspecialchars($service['tipo_atencion']) ?></span>
                    <?php if ($service['cumple_tiempo'] !== null): ?>
                        <?php if ($service['cumple_tiempo']): ?>
                            <span class="cumple-si"><i class="bi bi-check-circle-fill"></i> CUMPLE TIEMPO</span>
                        <?php else: ?>
                            <span class="cumple-no"><i class="bi bi-x-circle-fill"></i> NO CUMPLE TIEMPO</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-auto text-end">
                <p class="mb-0" style="font-size:13px;color:var(--text-secondary);">
                    <?= sprintf('%02d/%s/%s — %s Quincena',
                        $service['dia'],
                        FlightService::$meses[$service['mes']] ?? $service['mes'],
                        $service['anio'],
                        $service['quincena'] == 1 ? '1ª' : '2ª') ?>
                </p>
                <small style="color:var(--text-muted);">Registrado por: <?= htmlspecialchars($service['registrado_por']) ?></small>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

<!-- Información del vuelo -->
<div class="col-md-6">
    <div class="card h-100">
        <div class="card-header"><h5><i class="bi bi-airplane"></i> Información del Vuelo</h5></div>
        <div class="card-body">
            <table class="table table-sm" style="font-size:14px;">
                <tr><td class="text-muted" style="width:50%">Vuelo Llegando</td><td><strong><?= htmlspecialchars($service['vuelo_llegando']) ?></strong></td></tr>
                <tr><td class="text-muted">Vuelo Saliendo</td><td><strong><?= htmlspecialchars($service['vuelo_saliendo']) ?></strong></td></tr>
                <tr><td class="text-muted">Base Destino</td><td><span class="badge badge-primary"><?= htmlspecialchars($service['base_destino']) ?></span></td></tr>
                <tr><td class="text-muted">Matrícula</td><td><code><?= htmlspecialchars($service['matricula']) ?></code></td></tr>
                <tr><td class="text-muted">Tipo de Avión</td><td><?= htmlspecialchars($service['aircraft_tipo']) ?></td></tr>
                <tr><td class="text-muted">Pax Saliendo</td><td><?= $service['pax_saliendo'] ?></td></tr>
                <tr><td class="text-muted">Pax Cancelado</td><td><?= $service['pax_cancelado'] ?></td></tr>
            </table>
        </div>
    </div>
</div>

<!-- Horarios -->
<div class="col-md-6">
    <div class="card h-100">
        <div class="card-header"><h5><i class="bi bi-clock-fill"></i> Horarios</h5></div>
        <div class="card-body">
            <table class="table table-sm" style="font-size:14px;">
                <tr><td class="text-muted" style="width:55%">Hora Itinerada Llegada</td><td><span class="time-display"><?= $service['hora_itinerada_llegada'] ?? '—' ?></span></td></tr>
                <tr><td class="text-muted">Demora Llegando</td><td><?= $service['demora_llegando'] ?> min</td></tr>
                <tr><td class="text-muted">Hora Itinerada Salida</td><td><span class="time-display"><?= $service['hora_itinerada_salida'] ?? '—' ?></span></td></tr>
                <tr><td class="text-muted">Hora Real Llegada</td><td><span class="time-display"><?= $service['hora_real_llegada'] ?? '—' ?></span></td></tr>
                <tr><td class="text-muted">Hora Real Salida</td><td><span class="time-display"><?= $service['hora_real_salida'] ?? '—' ?></span></td></tr>
                <tr><td class="text-muted">Tiempo de Tránsito</td><td>
                    <?php if ($service['tiempo_transito'] !== null): ?>
                        <strong class="time-display"><?= $service['tiempo_transito'] ?> min</strong>
                        <small class="text-muted">(objetivo: <?= $service['tiempo_cumplimiento'] ?> min)</small>
                    <?php else: ?>—<?php endif; ?>
                </td></tr>
                <?php if (!$service['cumple_tiempo'] && !empty($service['codigo_demora'])): ?>
                <tr><td colspan="2"><hr class="my-2"></td></tr>
                <tr><td class="text-muted"><strong>Código Demora</strong></td><td><code><?= htmlspecialchars($service['codigo_demora']) ?></code></td></tr>
                <tr><td class="text-muted"><strong>Observación Demora</strong></td><td><?= htmlspecialchars($service['observacion_demora'] ?? '') ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- GPU -->
<div class="col-md-6">
    <div class="card h-100">
        <div class="card-header"><h5><i class="bi bi-lightning-charge-fill"></i> GPU</h5></div>
        <div class="card-body">
            <table class="table table-sm" style="font-size:14px;">
                <tr><td class="text-muted" style="width:55%">Hora Conexión</td><td><?= $service['hora_conexion_gpu'] ?? '—' ?></td></tr>
                <tr><td class="text-muted">Hora Desconexión</td><td><?= $service['hora_desconexion_gpu'] ?? '—' ?></td></tr>
                <tr><td class="text-muted">Tiempo GPU</td><td><?= $service['tiempo_gpu'] !== null ? $service['tiempo_gpu'] . ' min' : '—' ?></td></tr>
                <tr><td class="text-muted">Fracciones ADC</td><td><?= $service['fracciones_adc_gpu'] ?? 0 ?></td></tr>
            </table>
            <?php if (!empty($service['gpu_fracciones'])): ?>
                <p class="mt-2 mb-1" style="font-size:12px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;">Fracciones adicionales</p>
                <?php foreach ($service['gpu_fracciones'] as $gf): ?>
                    <div style="background:var(--bg-body);border-radius:6px;padding:8px 12px;margin-bottom:6px;font-size:13px;">
                        <?= $gf['hora_conexion'] ?> → <?= $gf['hora_desconexion'] ?>
                        <span class="ms-2 badge badge-info"><?= $gf['tiempo'] ?> min</span>
                        <span class="ms-1 text-muted">ADC: <?= $gf['fracciones_adc'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ACU -->
<div class="col-md-6">
    <div class="card h-100">
        <div class="card-header"><h5><i class="bi bi-wind"></i> ACU</h5></div>
        <div class="card-body">
            <table class="table table-sm" style="font-size:14px;">
                <tr><td class="text-muted" style="width:55%">ACU</td><td><?= $service['acu'] ? '<span class="indicator-si"><i class="bi bi-check-circle-fill"></i> Sí</span>' : '<span class="indicator-no"><i class="bi bi-x-circle-fill"></i> No</span>' ?></td></tr>
                <tr><td class="text-muted">Hora Conexión</td><td><?= $service['hora_conexion_acu'] ?? '—' ?></td></tr>
                <tr><td class="text-muted">Hora Desconexión</td><td><?= $service['hora_desconexion_acu'] ?? '—' ?></td></tr>
                <tr><td class="text-muted">Tiempo ACU</td><td><?= $service['tiempo_acu'] !== null ? $service['tiempo_acu'] . ' min' : '—' ?></td></tr>
                <tr><td class="text-muted">Fracciones Hora</td><td><?= $service['fracciones_hora_acu'] ?? 0 ?></td></tr>
                <tr><td class="text-muted">Fracciones 15 min</td><td><?= $service['fracciones_15min_acu'] ?? 0 ?></td></tr>
            </table>
        </div>
    </div>
</div>

<!-- Ventiladores -->
<div class="col-md-6">
    <div class="card h-100">
        <div class="card-header"><h5><i class="bi bi-fan"></i> Ventiladores</h5></div>
        <div class="card-body">
            <table class="table table-sm" style="font-size:14px;">
                <tr><td class="text-muted" style="width:55%">Ventiladores</td><td><?= $service['ventiladores_activo'] ? '<span class="indicator-si"><i class="bi bi-check-circle-fill"></i> Sí</span>' : '<span class="indicator-no"><i class="bi bi-x-circle-fill"></i> No</span>' ?></td></tr>
                <tr><td class="text-muted">Hora Conexión</td><td><?= $service['hora_conexion_ventiladores'] ?? '—' ?></td></tr>
                <tr><td class="text-muted">Hora Desconexión</td><td><?= $service['hora_desconexion_ventiladores'] ?? '—' ?></td></tr>
                <tr><td class="text-muted">Tiempo Ventiladores</td><td><?= $service['tiempo_ventiladores'] !== null ? $service['tiempo_ventiladores'] . ' min' : '—' ?></td></tr>
                <tr><td class="text-muted">Fracciones Hora</td><td><?= $service['fracciones_hora_ventiladores'] ?? 0 ?></td></tr>
                <tr><td class="text-muted">Fracciones 15 min</td><td><?= $service['fracciones_15min_ventiladores'] ?? 0 ?></td></tr>
            </table>
        </div>
    </div>
</div>
<div class="col-md-6">
    <div class="card">
        <div class="card-header"><h5><i class="bi bi-tools"></i> Equipos y Servicios</h5></div>
        <div class="card-body">
            <div class="row g-2">
                <?php
                $equipos = [
                    'sillas_ruedas' => 'Sillas de Ruedas',
                    'ventiladores' => 'Ventiladores',
                    'equipajes_transportados' => 'Equipajes Transportados',
                    'remolque_aeronave' => 'Remolque Aeronave',
                    'remolque_equipajes' => 'Remolque Equipajes',
                    'potable' => 'Potable',
                    'drenaje' => 'Drenaje',
                ];
                foreach ($equipos as $key => $label):
                ?>
                <div class="col-6">
                    <div style="background:var(--bg-body);border-radius:8px;padding:10px 12px;">
                        <small class="text-muted d-block" style="font-size:11px;"><?= $label ?></small>
                        <strong><?= $service[$key] ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="col-6">
                    <div style="background:var(--bg-body);border-radius:8px;padding:10px 12px;">
                        <small class="text-muted d-block" style="font-size:11px;">Rampa Escalera</small>
                        <?= $service['rampa_escalera']
                            ? '<span class="indicator-si"><i class="bi bi-check-circle-fill"></i> Sí</span>'
                            : '<span class="indicator-no"><i class="bi bi-x-circle-fill"></i> No</span>' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Adicionales -->
<?php if (!empty($service['adicionales'])): ?>
<div class="col-md-6">
    <div class="card">
        <div class="card-header"><h5><i class="bi bi-plus-square-fill"></i> Servicios Adicionales</h5></div>
        <div class="card-body">
            <table class="table table-sm" style="font-size:14px;">
                <thead><tr><th>Servicio</th><th>Cantidad</th></tr></thead>
                <tbody>
                    <?php foreach ($service['adicionales'] as $ad): ?>
                    <tr>
                        <td><?= htmlspecialchars($ad['servicio']) ?></td>
                        <td><strong><?= $ad['cantidad'] ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Observaciones -->
<?php if (!empty($service['equipo_gse_inoperativo'])): ?>
<div class="col-12">
    <div class="card">
        <div class="card-header"><h5><i class="bi bi-chat-text-fill"></i> Observaciones Operativas</h5></div>
        <div class="card-body">
            <p class="mb-2"><strong>Equipo GSE Inoperativo:</strong>
                <?php foreach (explode(',', $service['equipo_gse_inoperativo']) as $item): ?>
                    <span class="badge badge-info ms-1"><?= htmlspecialchars(trim($item)) ?></span>
                <?php endforeach; ?>
            </p>
            <p class="mb-0"><strong>¿Afectó la operación?</strong>
                <?= $service['afecto_operacion']
                    ? '<span class="indicator-si ms-2"><i class="bi bi-check-circle-fill"></i> Sí</span>'
                    : '<span class="indicator-no ms-2"><i class="bi bi-x-circle-fill"></i> No</span>' ?>
            </p>
            <?php if ($service['afecto_operacion'] && !empty($service['rpn'])): ?>
            <p class="mb-0 mt-2"><strong>RPN:</strong> <code><?= htmlspecialchars($service['rpn']) ?></code></p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

</div>
