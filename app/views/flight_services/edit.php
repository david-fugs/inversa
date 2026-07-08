<div class="page-actions">
    <a href="<?= BASE_URL ?>/flight-services/view/<?= $service['id'] ?>" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Ver detalle
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/flight-services/edit/<?= $service['id'] ?>" novalidate id="flightServiceForm">

<!-- ══ SECCIÓN 1: Información general ══════════════════════ -->
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-calendar3"></i> Información General</h5>
        <span class="badge badge-secondary">Editando #<?= $service['id'] ?></span>
    </div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-3">
                <label for="anio" class="form-label">Año <span class="required-mark">*</span></label>
                <input type="number" class="form-control <?= isset($errors['anio']) ? 'is-invalid' : '' ?>"
                    id="anio" name="anio" value="<?= $service['anio'] ?>" min="2000" max="2100">
                <?php if (isset($errors['anio'])): ?><div class="invalid-feedback"><?= $errors['anio'] ?></div><?php endif; ?>
            </div>

            <div class="col-md-3">
                <label for="mes" class="form-label">Mes <span class="required-mark">*</span></label>
                <select class="form-select" id="mes" name="mes">
                    <?php foreach (FlightService::$meses as $num => $nombre): ?>
                        <option value="<?= $num ?>" <?= $service['mes'] == $num ? 'selected' : '' ?>><?= $nombre ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="dia" class="form-label">Día <span class="required-mark">*</span></label>
                <input type="number" class="form-control" id="dia" name="dia"
                    value="<?= $service['dia'] ?>" min="1" max="31">
            </div>

            <div class="col-md-3">
                <label class="form-label">Quincena</label>
                <input type="text" class="form-control" id="quincena_display" readonly
                    value="<?= $service['quincena'] == 1 ? '1ª Quincena' : '2ª Quincena' ?>"
                    style="background:var(--bg-body);">
            </div>

            <div class="col-md-6">
                <label for="base" class="form-label">Base <span class="required-mark">*</span></label>
                <?php
                    $baseColaborador = (Session::get('user_rol') === 'Colaborador') ? Session::get('user_base_asociada') : null;
                    $basesDisponibles = $baseColaborador ? [['nombre' => $baseColaborador]] : $bases;
                ?>
                <select class="form-select" id="base" name="base"
                    <?= $baseColaborador ? 'readonly style="pointer-events:none;background:var(--bg-body);"' : '' ?>>
                    <?php foreach ($basesDisponibles as $b): ?>
                        <?php $baseNombre = $b['nombre']; ?>
                        <option value="<?= htmlspecialchars($baseNombre) ?>" <?= ($service['base'] === $baseNombre || $baseColaborador === $baseNombre) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($baseNombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="tipo_atencion" class="form-label">Tipo de Atención <span class="required-mark">*</span></label>
                <select class="form-select" id="tipo_atencion" name="tipo_atencion">
                    <?php foreach (FlightService::$tiposAtencion as $ta): ?>
                        <option value="<?= $ta ?>" <?= $service['tipo_atencion'] === $ta ? 'selected' : '' ?>><?= $ta ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Despacho <small class="text-muted">(Automático si es AVIANCA)</small></label>
                <div class="form-control d-flex align-items-center" style="background:var(--bg-body);">
                    <span id="despacho_display">--</span>
                </div>
                <input type="hidden" name="despacho" id="despacho" value="<?= (int)$service['despacho'] ?>">
            </div>

        </div>
    </div>
</div>

<!-- ══ SECCIÓN 2: Operación ════════════════════════════════ -->
<div class="card">
    <div class="card-header"><h5><i class="bi bi-building2"></i> Operación</h5></div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <label for="airline_id" class="form-label">Aerolínea <span class="required-mark">*</span></label>
                <select class="form-select" id="airline_id" name="airline_id">
                    <option value="">-- Seleccione aerolínea --</option>
                    <?php foreach ($airlines as $a): ?>
                        <option value="<?= $a['id'] ?>" data-nombre="<?= htmlspecialchars($a['nombre']) ?>" <?= $service['airline_id'] == $a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="otra" <?= $service['airline_id'] == 'otra' ? 'selected' : '' ?>>Otra (especificar)</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="aircraft_type_id" class="form-label">Tipo de Avión <span class="required-mark">*</span></label>
                <select class="form-select" id="aircraft_type_id" name="aircraft_type_id">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($aircraftTypes as $at): ?>
                        <option value="<?= $at['id'] ?>"
                            data-tiempo="<?= $at['tiempo_cumplimiento'] ?>"
                            <?= $service['aircraft_type_id'] == $at['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($at['tipo']) ?> (<?= $at['tiempo_cumplimiento'] ?> min)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" id="tiempo_cumplimiento_ref" value="<?= $service['tiempo_cumplimiento'] ?>">
            </div>

            <div class="col-md-6" id="airline_custom_container" style="display:none;">
                <label for="airline_custom_nombre" class="form-label">Nombre de la Aerolínea <span class="required-mark">*</span></label>
                <input type="text" class="form-control" id="airline_custom_nombre" name="airline_custom_nombre"
                    value="<?= htmlspecialchars($service['airline_custom_nombre'] ?? '') ?>"
                    placeholder="Ej: Aerolínea XYZ" style="text-transform:uppercase;">
            </div>

            <div class="col-md-6" id="aircraft_type_custom_container" style="display:none;">
                <label for="aircraft_type_custom" class="form-label">Especificar Tipo de Avión</label>
                <input type="text" class="form-control" id="aircraft_type_custom" name="aircraft_type_custom"
                    value="<?= htmlspecialchars($service['aircraft_type_custom'] ?? '') ?>"
                    placeholder="Ej: Boeing 737, Airbus A320">
            </div>

            <div class="col-md-3" id="tiempo_cumplimiento_custom_container" style="display:none;">
                <label for="tiempo_cumplimiento_custom" class="form-label">Tiempo de Cumplimiento (min) <span class="required-mark">*</span></label>
                <input type="number" class="form-control <?= isset($errors['tiempo_cumplimiento_custom']) ? 'is-invalid' : '' ?>" id="tiempo_cumplimiento_custom" name="tiempo_cumplimiento_custom"
                    value="<?= htmlspecialchars($service['tiempo_cumplimiento_custom'] ?? '') ?>"
                    min="1" max="120" placeholder="Ej: 20, 25, 30, 40">
                <small class="text-muted">Minutos permitidos para el cumplimiento operacional</small>
                <?php if (isset($errors['tiempo_cumplimiento_custom'])): ?><div class="invalid-feedback d-block"><?= $errors['tiempo_cumplimiento_custom'] ?></div><?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- ══ SECCIÓN 3: Información del vuelo ═══════════════════ -->
<div class="card">
    <div class="card-header"><h5><i class="bi bi-airplane-fill"></i> Información del Vuelo</h5></div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-3">
                <label for="vuelo_llegando" class="form-label">N° Vuelo Llegando <span class="required-mark">*</span></label>
                <input type="text" class="form-control" id="vuelo_llegando" name="vuelo_llegando"
                    value="<?= htmlspecialchars($service['vuelo_llegando']) ?>" style="text-transform:uppercase;">
            </div>
            <div class="col-md-3">
                <label for="vuelo_saliendo" class="form-label">N° Vuelo Saliendo <span class="required-mark">*</span></label>
                <input type="text" class="form-control" id="vuelo_saliendo" name="vuelo_saliendo"
                    value="<?= htmlspecialchars($service['vuelo_saliendo']) ?>" style="text-transform:uppercase;">
            </div>
            <div class="col-md-3">
                <label for="base_destino" class="form-label">Base Destino <span class="required-mark">*</span></label>
                <select class="form-select" id="base_destino" name="base_destino">
                    <option value="">-- Destino --</option>
                    <?php foreach ($baseDestinos as $bd): ?>
                        <option value="<?= htmlspecialchars($bd['nombre']) ?>" <?= $service['base_destino'] === $bd['nombre'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($bd['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="matricula" class="form-label">Matrícula <span class="required-mark">*</span></label>
                <input type="text" class="form-control" id="matricula" name="matricula"
                    value="<?= htmlspecialchars($service['matricula']) ?>" style="text-transform:uppercase;">
            </div>
            <div class="col-md-3">
                <label for="pax_saliendo" class="form-label">Pax Saliendo</label>
                <input type="number" class="form-control" id="pax_saliendo" name="pax_saliendo"
                    value="<?= $service['pax_saliendo'] ?>" min="0">
            </div>
            <div class="col-md-3">
                <label for="pax_cancelado" class="form-label">Pax Cancelado</label>
                <input type="number" class="form-control" id="pax_cancelado" name="pax_cancelado"
                    value="<?= $service['pax_cancelado'] ?>" min="0">
            </div>
            <div class="col-md-3">
                <label for="ajes_transportados" class="form-label">Ajes Transportados</label>
                <input type="number" class="form-control" id="ajes_transportados" name="ajes_transportados"
                    value="<?= (int)($service['ajes_transportados'] ?? 0) ?>" min="0">
            </div>

        </div>
    </div>
</div>

<!-- ══ SECCIÓN 4: Horarios ═══════════════════════════════ -->
<div class="card">
    <div class="card-header"><h5><i class="bi bi-clock-fill"></i> Horarios</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="hora_itinerada_llegada" class="form-label">Hora Itinerada Llegada</label>
                <input type="time" class="form-control" id="hora_itinerada_llegada" name="hora_itinerada_llegada"
                    value="<?= $service['hora_itinerada_llegada'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label for="demora_llegando" class="form-label">Demora Llegando (min)</label>
                <input type="number" class="form-control" id="demora_llegando" name="demora_llegando"
                    value="<?= $service['demora_llegando'] ?>" min="0"
                    readonly style="background:var(--bg-body);">
            </div>
            <div class="col-md-3">
                <label for="hora_itinerada_salida" class="form-label">Hora Itinerada Salida</label>
                <input type="time" class="form-control" id="hora_itinerada_salida" name="hora_itinerada_salida"
                    value="<?= $service['hora_itinerada_salida'] ?? '' ?>">
            </div>
            <div class="col-md-3" id="satena_hora_cierre_container" style="display:none;">
                <label for="satena_hora_cierre_modulo" class="form-label">Hora Cierre Módulo (SATENA)</label>
                <input type="time" class="form-control" id="satena_hora_cierre_modulo" name="satena_hora_cierre_modulo"
                    value="<?= $service['satena_hora_cierre_modulo'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label for="hora_real_llegada" class="form-label">Hora Real Llegada</label>
                <input type="time" class="form-control" id="hora_real_llegada" name="hora_real_llegada"
                    value="<?= $service['hora_real_llegada'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label for="hora_real_salida" class="form-label">Hora Real Salida</label>
                <input type="time" class="form-control" id="hora_real_salida" name="hora_real_salida"
                    value="<?= $service['hora_real_salida'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tiempo de Tránsito</label>
                <div class="form-control d-flex align-items-center" style="background:var(--bg-body);">
                    <span id="tiempo_transito_display" class="time-display">
                        <?= $service['tiempo_transito'] !== null ? $service['tiempo_transito'] . ' min' : '--' ?>
                    </span>
                </div>
                <input type="hidden" name="tiempo_transito" id="tiempo_transito" value="<?= $service['tiempo_transito'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">¿Cumple Tiempo?</label>
                <div class="form-control d-flex align-items-center" style="background:var(--bg-body);">
                    <span id="cumple_tiempo_display">
                        <?php if ($service['cumple_tiempo'] !== null): ?>
                            <?php if ($service['cumple_tiempo']): ?>
                                <span class="cumple-si"><i class="bi bi-check-circle-fill"></i> SI</span>
                            <?php else: ?>
                                <span class="cumple-no"><i class="bi bi-x-circle-fill"></i> NO</span>
                            <?php endif; ?>
                        <?php else: ?>--<?php endif; ?>
                    </span>
                </div>
                <input type="hidden" name="cumple_tiempo" id="cumple_tiempo" value="<?= $service['cumple_tiempo'] ?? '' ?>">
            </div>
        </div>
        <!-- Campos adicionales si NO cumple tiempo -->
        <div id="demora-fields-container" style="display:none;">
            <div class="row g-3 mt-2">
                <div class="col-12">
                    <hr class="my-2">
                    <p class="text-muted mb-3"><i class="bi bi-info-circle"></i> <small>Se debe completar la información de la demora</small></p>
                </div>
                <div class="col-md-3">
                    <label for="codigo_demora" class="form-label">Código Demora</label>
                    <input type="text" class="form-control" id="codigo_demora" name="codigo_demora"
                        value="<?= htmlspecialchars($service['codigo_demora'] ?? '') ?>"
                        placeholder="Ej: D001" style="text-transform:uppercase;">
                </div>
                <div class="col-md-9">
                    <label for="observacion_demora" class="form-label">Observación de la Demora</label>
                    <textarea class="form-control" id="observacion_demora" name="observacion_demora"
                        placeholder="Describa el motivo de la demora..." rows="2"><?= htmlspecialchars($service['observacion_demora'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ SECCIÓN 5: GPU ════════════════════════════════════ -->
<div class="card">
    <div class="card-header"><h5><i class="bi bi-lightning-charge-fill"></i> GPU</h5></div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label for="hora_conexion_gpu" class="form-label">Hora Conexión GPU</label>
                <input type="time" class="form-control" id="hora_conexion_gpu" name="hora_conexion_gpu"
                    value="<?= $service['hora_conexion_gpu'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label for="hora_desconexion_gpu" class="form-label">Hora Desconexión GPU</label>
                <input type="time" class="form-control" id="hora_desconexion_gpu" name="hora_desconexion_gpu"
                    value="<?= $service['hora_desconexion_gpu'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label for="tiempo_gpu" class="form-label">Tiempo GPU (min)</label>
                <input type="number" class="form-control" id="tiempo_gpu" name="tiempo_gpu"
                    value="<?= $service['tiempo_gpu'] ?? '' ?>" readonly style="background:var(--bg-body);">
            </div>
            <div class="col-md-2">
                <label for="fracciones_adc_gpu" class="form-label">Fracciones ADC GPU</label>
                <input type="number" step="0.01" class="form-control" id="fracciones_adc_gpu" name="fracciones_adc_gpu"
                    value="<?= number_format((float)($service['fracciones_adc_gpu'] ?? 0), 2) ?>"
                    readonly style="background:var(--bg-body);">
            </div>
            <div class="col-md-2">
                <label for="fracciones_adicionales_gpu" class="form-label">Fracciones Adicionales GPU</label>
                <input type="number" step="0.01" class="form-control" id="fracciones_adicionales_gpu" name="fracciones_adicionales_gpu"
                    value="<?= number_format((float)($service['fracciones_adicionales_gpu'] ?? 0), 2) ?>"
                    readonly style="background:var(--bg-body);">
            </div>
        </div>
    </div>
</div>

<!-- ══ SECCIÓN 6: ACU ════════════════════════════════════ -->
<div class="card">
    <div class="card-header"><h5><i class="bi bi-wind"></i> ACU</h5></div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">ACU</label>
                <select class="form-select" id="acu" name="acu">
                    <option value="0" <?= !$service['acu'] ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= $service['acu'] ? 'selected' : '' ?>>Sí</option>
                </select>
            </div>
            <div class="col-md-3"><label for="hora_conexion_acu" class="form-label">Hora Conexión ACU</label>
                <input type="time" class="form-control" id="hora_conexion_acu" name="hora_conexion_acu" value="<?= $service['hora_conexion_acu'] ?? '' ?>"></div>
            <div class="col-md-3"><label for="hora_desconexion_acu" class="form-label">Hora Desconexión ACU</label>
                <input type="time" class="form-control" id="hora_desconexion_acu" name="hora_desconexion_acu" value="<?= $service['hora_desconexion_acu'] ?? '' ?>"></div>
            <div class="col-md-3"><label for="tiempo_acu" class="form-label">Tiempo ACU (min)</label>
                <input type="number" class="form-control" id="tiempo_acu" name="tiempo_acu" value="<?= $service['tiempo_acu'] ?? '' ?>" readonly style="background:var(--bg-body);"></div>
            <div class="col-md-3"><label for="fracciones_hora_acu" class="form-label">Fracciones Hora ACU</label>
                <input type="number" step="0.01" class="form-control" id="fracciones_hora_acu" name="fracciones_hora_acu" value="<?= number_format((float)($service['fracciones_hora_acu'] ?? 0), 2) ?>" readonly style="background:var(--bg-body);"></div>
            <div class="col-md-3"><label for="fracciones_15min_acu" class="form-label">Fracciones 15 min ACU</label>
                <input type="number" step="0.01" class="form-control" id="fracciones_15min_acu" name="fracciones_15min_acu" value="<?= number_format((float)($service['fracciones_15min_acu'] ?? 0), 2) ?>" readonly style="background:var(--bg-body);"></div>
        </div>
        <div id="acu-fracciones-container">
            <?php foreach ($service['acu_fracciones'] as $i => $af): ?>
            <div class="dynamic-row">
                <button type="button" class="btn-remove-row" onclick="this.closest('.dynamic-row').remove()"><i class="bi bi-x"></i></button>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Hora Conexión</label>
                        <input type="time" class="form-control" name="acu_fracciones[<?= $i ?>][hora_conexion]" value="<?= $af['hora_conexion'] ?? '' ?>"></div>
                    <div class="col-md-3"><label class="form-label">Hora Desconexión</label>
                        <input type="time" class="form-control" name="acu_fracciones[<?= $i ?>][hora_desconexion]" value="<?= $af['hora_desconexion'] ?? '' ?>" oninput="calcFraccionAcu(this)"></div>
                    <div class="col-md-2"><label class="form-label">Tiempo (min)</label>
                        <input type="number" class="form-control" name="acu_fracciones[<?= $i ?>][tiempo]" value="<?= $af['tiempo'] ?? '' ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Fracc. Hora</label>
                        <input type="number" step="0.01" class="form-control" name="acu_fracciones[<?= $i ?>][fracciones_hora]" value="<?= $af['fracciones_hora'] ?? 0 ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Fracc. 15 min</label>
                        <input type="number" step="0.01" class="form-control" name="acu_fracciones[<?= $i ?>][fracciones_15min]" value="<?= $af['fracciones_15min'] ?? 0 ?>" readonly></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addAcuRow()">
            <i class="bi bi-plus-circle"></i> Agregar fracción ACU
        </button>
    </div>
</div>

<!-- ══ SECCIÓN 6B: Ventiladores ═════════════════════════ -->
<div class="card" id="ventiladores-card">
    <div class="card-header"><h5><i class="bi bi-fan"></i> Ventiladores</h5></div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Ventiladores</label>
                <select class="form-select" id="ventiladores_activo" name="ventiladores_activo">
                    <option value="0" <?= !$service['ventiladores_activo'] ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= $service['ventiladores_activo'] ? 'selected' : '' ?>>Sí</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="hora_conexion_ventiladores" class="form-label">Hora Conexión Ventiladores</label>
                <input type="time" class="form-control" id="hora_conexion_ventiladores" name="hora_conexion_ventiladores"
                    value="<?= $service['hora_conexion_ventiladores'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label for="hora_desconexion_ventiladores" class="form-label">Hora Desconexión Ventiladores</label>
                <input type="time" class="form-control" id="hora_desconexion_ventiladores" name="hora_desconexion_ventiladores"
                    value="<?= $service['hora_desconexion_ventiladores'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label for="tiempo_ventiladores" class="form-label">Tiempo Ventiladores (min)</label>
                <input type="number" class="form-control" id="tiempo_ventiladores" name="tiempo_ventiladores"
                    value="<?= $service['tiempo_ventiladores'] ?? '' ?>" readonly style="background:var(--bg-body);">
            </div>
            <div class="col-md-3">
                <label for="fracciones_hora_ventiladores" class="form-label">Fracciones Hora Ventiladores</label>
                <input type="number" step="0.01" class="form-control" id="fracciones_hora_ventiladores" name="fracciones_hora_ventiladores"
                    value="<?= number_format((float)($service['fracciones_hora_ventiladores'] ?? 0), 2) ?>" readonly style="background:var(--bg-body);">
            </div>
            <div class="col-md-3">
                <label for="fracciones_15min_ventiladores" class="form-label">Fracciones 15 min Ventiladores</label>
                <input type="number" step="0.01" class="form-control" id="fracciones_15min_ventiladores" name="fracciones_15min_ventiladores"
                    value="<?= number_format((float)($service['fracciones_15min_ventiladores'] ?? 0), 2) ?>" readonly style="background:var(--bg-body);">
            </div>
        </div>
        <div id="ventiladores-fracciones-container">
            <?php foreach ($service['ventiladores_fracciones'] ?? [] as $i => $vf): ?>
            <div class="dynamic-row">
                <button type="button" class="btn-remove-row" onclick="this.closest('.dynamic-row').remove()"><i class="bi bi-x"></i></button>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Hora Conexión</label>
                        <input type="time" class="form-control" name="ventiladores_fracciones[<?= $i ?>][hora_conexion]" value="<?= $vf['hora_conexion'] ?? '' ?>"></div>
                    <div class="col-md-3"><label class="form-label">Hora Desconexión</label>
                        <input type="time" class="form-control" name="ventiladores_fracciones[<?= $i ?>][hora_desconexion]" value="<?= $vf['hora_desconexion'] ?? '' ?>" oninput="calcFraccionVentiladores(this)"></div>
                    <div class="col-md-2"><label class="form-label">Tiempo (min)</label>
                        <input type="number" class="form-control" name="ventiladores_fracciones[<?= $i ?>][tiempo]" value="<?= $vf['tiempo'] ?? '' ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Fracc. Hora</label>
                        <input type="number" step="0.01" class="form-control" name="ventiladores_fracciones[<?= $i ?>][fracciones_hora]" value="<?= $vf['fracciones_hora'] ?? 0 ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Fracc. 15 min</label>
                        <input type="number" step="0.01" class="form-control" name="ventiladores_fracciones[<?= $i ?>][fracciones_15min]" value="<?= $vf['fracciones_15min'] ?? 0 ?>" readonly></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addVentiladoresRow()">
            <i class="bi bi-plus-circle"></i> Agregar fracción Ventiladores
        </button>
    </div>
</div>
<div class="card">
    <div class="card-header"><h5><i class="bi bi-tools"></i> Equipos y Servicios</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><label for="sillas_ruedas" class="form-label">Sillas de Ruedas</label>
                <input type="number" class="form-control" id="sillas_ruedas" name="sillas_ruedas" value="<?= $service['sillas_ruedas'] ?>" min="0"></div>
            <div class="col-md-3"><label for="ventiladores" class="form-label">Ventiladores</label>
                <select class="form-select" id="ventiladores" name="ventiladores">
                    <option value="0" <?= !$service['ventiladores'] ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= $service['ventiladores'] ? 'selected' : '' ?>>Sí</option>
                </select></div>
            <div class="col-md-3"><label for="rampa_escalera" class="form-label">Rampa Escalera</label>
                <select class="form-select" id="rampa_escalera" name="rampa_escalera">
                    <option value="0" <?= !$service['rampa_escalera'] ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= $service['rampa_escalera'] ? 'selected' : '' ?>>Sí</option>
                </select></div>
            <div class="col-md-3"><label for="equipajes_transportados" class="form-label">Equipajes Transportados</label>
                <input type="number" class="form-control" id="equipajes_transportados" name="equipajes_transportados" value="<?= $service['equipajes_transportados'] ?>" min="0"></div>
            <div class="col-md-3"><label for="remolque_aeronave" class="form-label">Remolque Aeronave</label>
                <input type="number" class="form-control" id="remolque_aeronave" name="remolque_aeronave" value="<?= $service['remolque_aeronave'] ?>" min="0"></div>
            <div class="col-md-3"><label for="remolque_equipajes" class="form-label">Remolque Equipajes</label>
                <input type="number" class="form-control" id="remolque_equipajes" name="remolque_equipajes" value="<?= $service['remolque_equipajes'] ?>" min="0"></div>
            <div class="col-md-3"><label for="potable" class="form-label">Potable</label>
                <input type="number" class="form-control" id="potable" name="potable" value="<?= $service['potable'] ?>" min="0"></div>
            <div class="col-md-3"><label for="drenaje" class="form-label">Drenaje</label>
                <input type="number" class="form-control" id="drenaje" name="drenaje" value="<?= $service['drenaje'] ?>" min="0"></div>
        </div>
    </div>
</div>

<!-- ══ SECCIÓN 8: Adicionales ════════════════════════════ -->
<div class="card">
    <div class="card-header"><h5><i class="bi bi-plus-square-fill"></i> Servicios Adicionales</h5></div>
    <div class="card-body">
        <div id="adicionales-container">
            <?php foreach ($service['adicionales'] as $i => $ad): ?>
            <div class="dynamic-row">
                <button type="button" class="btn-remove-row" onclick="this.closest('.dynamic-row').remove()"><i class="bi bi-x"></i></button>
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Servicio Adicional</label>
                        <select class="form-select" name="adicionales[<?= $i ?>][servicio]">
                            <?php foreach (FlightService::$serviciosAdicionales as $sa): ?>
                                <option value="<?= $sa ?>" <?= $ad['servicio'] === $sa ? 'selected' : '' ?>><?= $sa ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cantidad</label>
                        <input type="number" class="form-control" name="adicionales[<?= $i ?>][cantidad]" value="<?= $ad['cantidad'] ?>" min="1">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addAdicional()">
            <i class="bi bi-plus-circle"></i> Agregar adicional
        </button>
    </div>
</div>

<!-- ══ SECCIÓN 9: Observaciones ══════════════════════════ -->
<div class="card">
    <div class="card-header"><h5><i class="bi bi-chat-text-fill"></i> Observaciones Operativas</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <?php
                $gseOpciones     = ['ACU', 'TRA', 'CON', 'PAY', 'ASU','SVPFREE', 'PEP','GPU'];
                $gseSeleccionados = !empty($service['equipo_gse_inoperativo'])
                    ? array_map('trim', explode(',', $service['equipo_gse_inoperativo']))
                    : [];
            ?>
            <div class="col-md-9">
                <label class="form-label">Equipo GSE Inoperativo</label>
                <div class="border rounded p-3 d-flex flex-wrap gap-3" style="background:var(--bg-body);">
                    <?php foreach ($gseOpciones as $gse): ?>
                        <?php $gseId = 'gse_' . str_replace('/', '_', $gse); ?>
                        <div class="form-check">
                            <input class="form-check-input gse-check" type="checkbox"
                                name="equipo_gse_inoperativo[]"
                                id="<?= $gseId ?>" value="<?= $gse ?>"
                                <?= in_array($gse, $gseSeleccionados) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="<?= $gseId ?>"><?= $gse ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-3">
                <label for="afecto_operacion" class="form-label">¿Afectó la operación?</label>
                <select class="form-select" id="afecto_operacion" name="afecto_operacion"
                    style="pointer-events:none;background:var(--bg-body);">
                    <option value="0" <?= !$service['afecto_operacion'] ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= $service['afecto_operacion'] ? 'selected' : '' ?>>Sí</option>
                </select>
            </div>
        </div>
        <!-- Campo RPN si afectó la operación -->
        <div id="rpn-field-container" style="display:none;">
            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <label for="rpn" class="form-label">RPM</label>
                    <input type="text" class="form-control" id="rpn" name="rpn"
                        value="<?= htmlspecialchars($service['rpn'] ?? '') ?>"
                        placeholder="Ingrese el RPM (alfanumérico)" style="text-transform:uppercase;">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 pb-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle-fill"></i> Actualizar Servicio de Vuelo
    </button>
    <a href="<?= BASE_URL ?>/flight-services/view/<?= $service['id'] ?>" class="btn btn-light">Cancelar</a>
</div>

</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ══ CONFIGURACIÓN Y UTILIDADES ═══════════════════════
    const AVIANCA_ID = 1; // ID de AVIANCA en la BD
    const BASES_ESPECIALES = ['UC', 'UIB']; // Bases especiales para despacho

    // Actualizar quincena al cambiar día
    const diaField = document.getElementById('dia');
    if (diaField) {
        diaField.addEventListener('input', function() {
            const dia = parseInt(this.value) || 0;
            document.getElementById('quincena_display').value = dia <= 15 ? '1ª Quincena' : '2ª Quincena';
        });
    }

    // Convertir a mayúsculas campos de vuelo/matrícula
    ['vuelo_llegando','vuelo_saliendo','matricula','airline_custom_nombre','aircraft_type_custom'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
    });

    // ══ CONTROL PRINCIPAL: AEROLÍNEA ══════════════════════════
    function updateAllAirlineLogic() {
        const airlineSelect = document.getElementById('airline_id');
        const airlineValue = airlineSelect.value;

        // Elementos a controlar
        const customNameContainer = document.getElementById('airline_custom_container');
        const customTypeContainer = document.getElementById('aircraft_type_custom_container');
        const tiempoCustomContainer = document.getElementById('tiempo_cumplimiento_custom_container');
        const aircraftSelect = document.getElementById('aircraft_type_id');
        const despachoDisplay = document.getElementById('despacho_display');
        const despachoInput = document.getElementById('despacho');
        const satenaContainer = document.getElementById('satena_hora_cierre_container');

        // 1. MOSTRAR/OCULTAR CAMPOS DE "OTRA" ══════════════════════
        if (airlineValue === 'otra' || airlineValue == 'otra') {
            customNameContainer.style.display = 'block';
            customTypeContainer.style.display = 'block';
            tiempoCustomContainer.style.display = 'block';
            aircraftSelect.disabled = true;
            aircraftSelect.style.display = 'none';
        } else {
            customNameContainer.style.display = 'none';
            customTypeContainer.style.display = 'none';
            tiempoCustomContainer.style.display = 'none';
            aircraftSelect.disabled = false;
            aircraftSelect.style.display = 'block';
            const tiempoCustomField = document.getElementById('tiempo_cumplimiento_custom');
            if (tiempoCustomField) tiempoCustomField.value = '';
        }

        // 2. DESPACHO AUTOMÁTICO (AVIANCA o BASES ESPECIALES) ════════════════════
        const isAvianca = airlineValue == AVIANCA_ID;
        const baseSelect = document.getElementById('base');
        const baseValue = baseSelect ? baseSelect.value : '';
        const isBaseEspecial = BASES_ESPECIALES.includes(baseValue);

        const tieneDespacho = isAvianca || isBaseEspecial;
        console.log('updateAllAirlineLogic - tieneDespacho:', tieneDespacho);
        if (tieneDespacho) {
            console.log('→ Estableciendo despacho a SÍ');
            despachoDisplay.textContent = 'Sí';
            despachoInput.value = '1';
            despachoDisplay.style.color = '#198754'; // Verde
        } else {
            console.log('→ Estableciendo despacho a NO');
            despachoDisplay.textContent = 'No';
            despachoInput.value = '0';
            despachoDisplay.style.color = '#dc3545'; // Rojo
        }
        console.log('→ FINAL despacho_display.textContent =', despachoDisplay.textContent);

        // Monitorear cambios al despacho
        const observer = new MutationObserver((mutations) => {
            console.log('⚠️ CAMBIO DETECTADO en despacho_display! Nuevo valor:', despachoDisplay.textContent);
        });
        observer.observe(despachoDisplay, { childList: true, characterData: true, subtree: true });

        // 3. SATENA - HORA DE CIERRE DE MÓDULO ═════════════════════
        const selectedOption = airlineSelect.options[airlineSelect.selectedIndex];
        const selectedText = selectedOption ? selectedOption.text : '';
        if (selectedText === 'SATENA') {
            if (satenaContainer) satenaContainer.style.display = 'block';
        } else {
            if (satenaContainer) satenaContainer.style.display = 'none';
            const satenaField = document.getElementById('satena_hora_cierre_modulo');
            if (satenaField) satenaField.value = '';
        }
    }

    // Escuchar cambios en aerolínea y base (ambos afectan el despacho)
    const airlineSelectElement = document.getElementById('airline_id');
    if (airlineSelectElement) {
        airlineSelectElement.addEventListener('change', updateAllAirlineLogic);
    }
    const baseSelectElement = document.getElementById('base');
    if (baseSelectElement) {
        baseSelectElement.addEventListener('change', updateAllAirlineLogic);
    }
    // Llamar al inicio para precarga
    updateAllAirlineLogic();

    // ══ CÁLCULO DE CUMPLIMIENTO CON LLEGADAS ANTICIPADAS ══
    function calcularCumplimiento() {
        const horaItinerada = document.getElementById('hora_itinerada_llegada').value;
        const horaReal = document.getElementById('hora_real_llegada').value;
        const tiempoRef = document.getElementById('tiempo_cumplimiento_ref').value;
        const tiempoCustom = document.getElementById('tiempo_cumplimiento_custom').value;
        const cumpleInput = document.getElementById('cumple_tiempo');
        const cumpleDisplay = document.getElementById('cumple_tiempo_display');
        const demoraInput = document.getElementById('demora_llegando');

        // Determinar qué tiempo de cumplimiento usar
        const tiempoAUsar = tiempoCustom ? parseInt(tiempoCustom) : (tiempoRef ? parseInt(tiempoRef) : null);

        if (!horaItinerada || !horaReal || !tiempoAUsar) {
            cumpleInput.value = '';
            cumpleDisplay.innerHTML = '--';
            demoraInput.value = '';
            return;
        }

        const [hI, mI] = horaItinerada.split(':').map(Number);
        const [hR, mR] = horaReal.split(':').map(Number);

        const minItinerada = hI * 60 + mI;
        const minReal = hR * 60 + mR;
        const demora = minReal - minItinerada;

        let cumple;
        if (demora <= 0) {
            cumple = 1;
            demoraInput.value = 0;
        } else {
            cumple = demora <= tiempoAUsar ? 1 : 0;
            demoraInput.value = Math.max(0, demora);
        }

        cumpleInput.value = cumple;
        if (cumple === 1) {
            cumpleDisplay.innerHTML = '<span class="cumple-si"><i class="bi bi-check-circle-fill"></i> SI</span>';
        } else {
            cumpleDisplay.innerHTML = '<span class="cumple-no"><i class="bi bi-x-circle-fill"></i> NO</span>';
        }

        toggleDemoraFields();
    }

    // Escuchar cambios en horarios Y en tiempo personalizado
    ['hora_itinerada_llegada', 'hora_real_llegada', 'tiempo_cumplimiento_custom'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calcularCumplimiento);
        if (el) el.addEventListener('change', calcularCumplimiento);
    });

    // Mostrar/ocultar campos de demora cuando NO cumple tiempo
    function toggleDemoraFields() {
        const cumpleInput = document.getElementById('cumple_tiempo');
        const demoraContainer = document.getElementById('demora-fields-container');
        if (cumpleInput && demoraContainer) {
            const cumpleValue = cumpleInput.value;
            demoraContainer.style.display = cumpleValue === '0' ? 'block' : 'none';
        }
    }

    const observer = new MutationObserver(toggleDemoraFields);
    const cumpleInput = document.getElementById('cumple_tiempo');
    if (cumpleInput) {
        observer.observe(cumpleInput, { attributes: true });
        toggleDemoraFields();
    }

    // GSE Inoperativo -> Afectó la operación (auto)
    function updateAfectoOperacion() {
        const anyChecked = document.querySelectorAll('.gse-check:checked').length > 0;
        document.getElementById('afecto_operacion').value = anyChecked ? '1' : '0';
        toggleRpnField();
    }

    document.querySelectorAll('.gse-check').forEach(function(cb) {
        cb.addEventListener('change', updateAfectoOperacion);
    });
    updateAfectoOperacion();

    // Mostrar/ocultar campo RPN cuando afectó la operación
    function toggleRpnField() {
        const afectoInput = document.getElementById('afecto_operacion');
        const rpnContainer = document.getElementById('rpn-field-container');
        if (afectoInput && rpnContainer) {
            rpnContainer.style.display = afectoInput.value === '1' ? 'block' : 'none';
        }
    }

    const afectoInput = document.getElementById('afecto_operacion');
    if (afectoInput) {
        afectoInput.addEventListener('change', toggleRpnField);
        toggleRpnField();
    }

    // Deshabilitar secciones cuando tipo_atencion = "Cancelado"
    function toggleCanceladoSecciones() {
        const tipoAtencion = document.getElementById('tipo_atencion');
        const isCancelado = tipoAtencion && tipoAtencion.value === 'Cancelado';

        const fieldsToDisable = ['hora_conexion_gpu', 'hora_desconexion_gpu', 'tiempo_gpu', 'acu', 'hora_conexion_acu', 'hora_desconexion_acu', 'tiempo_acu', 'ventiladores_activo', 'hora_conexion_ventiladores', 'hora_desconexion_ventiladores', 'tiempo_ventiladores', 'sillas_ruedas', 'rampa_escalera', 'remolque_aeronave', 'remolque_equipajes', 'potable', 'drenaje', 'afecto_operacion', 'rpn'];

        fieldsToDisable.forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                if (isCancelado) {
                    field.disabled = true;
                    field.removeAttribute('required');
                } else {
                    field.disabled = false;
                }
            }
        });

        document.querySelectorAll('.gse-check').forEach(function(cb) {
            cb.disabled = isCancelado;
        });
    }

    const tipoAtencionSelect = document.getElementById('tipo_atencion');
    if (tipoAtencionSelect) {
        tipoAtencionSelect.addEventListener('change', toggleCanceladoSecciones);
        toggleCanceladoSecciones();
    }
});
</script>
