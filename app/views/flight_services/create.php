<div class="page-actions">
    <a href="<?= BASE_URL ?>/flight-services" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/flight-services/create" novalidate id="flightServiceForm">

    <!-- ══ SECCIÓN 1: Información general ══════════════════════ -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-calendar3"></i> Información General</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label for="anio" class="form-label">Año <span class="required-mark">*</span></label>
                    <input type="number" class="form-control <?= isset($errors['anio']) ? 'is-invalid' : '' ?>"
                        id="anio" name="anio"
                        value="<?= htmlspecialchars($old['anio'] ?? date('Y')) ?>"
                        min="2000" max="2100">
                    <?php if (isset($errors['anio'])): ?><div class="invalid-feedback"><?= $errors['anio'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="mes" class="form-label">Mes <span class="required-mark">*</span></label>
                    <select class="form-select <?= isset($errors['mes']) ? 'is-invalid' : '' ?>" id="mes" name="mes">
                        <option value="">-- Mes --</option>
                        <?php foreach (FlightService::$meses as $num => $nombre): ?>
                            <option value="<?= $num ?>" <?= ($old['mes'] ?? date('n')) == $num ? 'selected' : '' ?>>
                                <?= $nombre ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['mes'])): ?><div class="invalid-feedback"><?= $errors['mes'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="dia" class="form-label">Día <span class="required-mark">*</span></label>
                    <input type="number" class="form-control <?= isset($errors['dia']) ? 'is-invalid' : '' ?>"
                        id="dia" name="dia"
                        value="<?= htmlspecialchars($old['dia'] ?? date('j')) ?>"
                        min="1" max="31">
                    <?php if (isset($errors['dia'])): ?><div class="invalid-feedback"><?= $errors['dia'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Quincena</label>
                    <input type="text" class="form-control" id="quincena_display" readonly
                        value="<?= ($old['dia'] ?? date('j')) <= 15 ? '1ª Quincena' : '2ª Quincena' ?>"
                        style="background:var(--bg-body);">
                </div>

                <div class="col-md-6">
                    <label for="base" class="form-label">Base <span class="required-mark">*</span></label>
                    <?php
                    $baseColaborador = (Session::get('user_rol') === 'Colaborador') ? Session::get('user_base_asociada') : null;
                    $basesDisponibles = $baseColaborador ? [['nombre' => $baseColaborador]] : $bases;
                    ?>
                    <select class="form-select <?= isset($errors['base']) ? 'is-invalid' : '' ?>" id="base" name="base"
                        <?= $baseColaborador ? 'readonly style="pointer-events:none;background:var(--bg-body);"' : '' ?>>
                        <option value="">-- Seleccione base --</option>
                        <?php foreach ($basesDisponibles as $b): ?>
                            <?php $baseNombre = $b['nombre']; ?>
                            <option value="<?= htmlspecialchars($baseNombre) ?>" <?= ($old['base'] ?? $baseColaborador ?? '') === $baseNombre ? 'selected' : '' ?>>
                                <?= htmlspecialchars($baseNombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['base'])): ?><div class="invalid-feedback"><?= $errors['base'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="tipo_atencion" class="form-label">Tipo de Atención <span class="required-mark">*</span></label>
                    <select class="form-select <?= isset($errors['tipo_atencion']) ? 'is-invalid' : '' ?>" id="tipo_atencion" name="tipo_atencion">
                        <option value="">-- Seleccione --</option>
                        <?php foreach (FlightService::$tiposAtencion as $ta): ?>
                            <option value="<?= $ta ?>" <?= ($old['tipo_atencion'] ?? '') === $ta ? 'selected' : '' ?>><?= $ta ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['tipo_atencion'])): ?><div class="invalid-feedback"><?= $errors['tipo_atencion'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Despacho <small class="text-muted"></small></label>
                    <div class="form-control d-flex align-items-center" style="background:var(--bg-body);">
                        <span id="despacho_display">--</span>
                    </div>
                    <input type="hidden" name="despacho" id="despacho" value="<?= (int)($old['despacho'] ?? 0) ?>">
                </div>

            </div>
        </div>
    </div>

    <!-- ══ SECCIÓN 2: Operación ════════════════════════════════ -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-building2"></i> Operación</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <label for="airline_id" class="form-label">Aerolínea <span class="required-mark">*</span></label>
                    <select class="form-select <?= isset($errors['airline_id']) ? 'is-invalid' : '' ?>"
                        id="airline_id" name="airline_id">
                        <option value="">-- Seleccione aerolínea --</option>
                        <?php foreach ($airlines as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= ($old['airline_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="otra" <?= ($old['airline_id'] ?? '') === 'otra' ? 'selected' : '' ?>>
                            Otra (especificar)
                        </option>
                    </select>
                    <?php if (isset($errors['airline_id'])): ?><div class="invalid-feedback d-block"><?= $errors['airline_id'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="aircraft_type_id" class="form-label">Tipo de Avión <span class="required-mark">*</span></label>
                    <select class="form-select <?= isset($errors['aircraft_type_id']) ? 'is-invalid' : '' ?>"
                        id="aircraft_type_id" name="aircraft_type_id">
                        <option value="">-- Seleccione aerolínea primero --</option>
                    </select>
                    <input type="hidden" id="tiempo_cumplimiento_ref" value="">
                    <?php if (isset($errors['aircraft_type_id'])): ?><div class="invalid-feedback d-block"><?= $errors['aircraft_type_id'] ?></div><?php endif; ?>
                </div>

                <!-- Campos personalizados cuando se selecciona "Otra" aerolínea -->
                <div id="airline_custom_container" class="col-md-6" style="display:none;">
                    <label for="airline_custom_nombre" class="form-label">Nombre de la Aerolínea <span class="required-mark">*</span></label>
                    <input type="text" class="form-control" id="airline_custom_nombre" name="airline_custom_nombre"
                        value="<?= htmlspecialchars($old['airline_custom_nombre'] ?? '') ?>"
                        placeholder="Ej: Aerolínea XYZ" style="text-transform:uppercase;">
                </div>

                <div id="aircraft_type_custom_container" class="col-md-6" style="display:none;">
                    <label for="aircraft_type_custom" class="form-label">Tipo de Avión <span class="required-mark">*</span></label>
                    <input type="text" class="form-control" id="aircraft_type_custom" name="aircraft_type_custom"
                        value="<?= htmlspecialchars($old['aircraft_type_custom'] ?? '') ?>"
                        placeholder="Ej: Boeing 737" style="text-transform:uppercase;">
                </div>

                <div id="tiempo_cumplimiento_custom_container" class="col-md-3" style="display:none;">
                    <label for="tiempo_cumplimiento_custom" class="form-label">Tiempo de Cumplimiento (min) <span class="required-mark">*</span></label>
                    <input type="number" class="form-control <?= isset($errors['tiempo_cumplimiento_custom']) ? 'is-invalid' : '' ?>" id="tiempo_cumplimiento_custom" name="tiempo_cumplimiento_custom"
                        value="<?= htmlspecialchars($old['tiempo_cumplimiento_custom'] ?? '') ?>"
                        min="1" max="120" placeholder="Ej: 20, 25, 30, 40">
                    <small class="text-muted">Minutos permitidos para el cumplimiento operacional</small>
                    <?php if (isset($errors['tiempo_cumplimiento_custom'])): ?><div class="invalid-feedback d-block"><?= $errors['tiempo_cumplimiento_custom'] ?></div><?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- ══ SECCIÓN 3: Información del vuelo ═══════════════════ -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-airplane-fill"></i> Información del Vuelo</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label for="vuelo_llegando" class="form-label">N° Vuelo Llegando <span class="required-mark">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['vuelo_llegando']) ? 'is-invalid' : '' ?>"
                        id="vuelo_llegando" name="vuelo_llegando"
                        value="<?= htmlspecialchars($old['vuelo_llegando'] ?? '') ?>"
                        placeholder="Ej: AV1234" style="text-transform:uppercase;">
                    <?php if (isset($errors['vuelo_llegando'])): ?><div class="invalid-feedback"><?= $errors['vuelo_llegando'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="vuelo_saliendo" class="form-label">N° Vuelo Saliendo <span class="required-mark">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['vuelo_saliendo']) ? 'is-invalid' : '' ?>"
                        id="vuelo_saliendo" name="vuelo_saliendo"
                        value="<?= htmlspecialchars($old['vuelo_saliendo'] ?? '') ?>"
                        placeholder="Ej: AV1235" style="text-transform:uppercase;">
                    <?php if (isset($errors['vuelo_saliendo'])): ?><div class="invalid-feedback"><?= $errors['vuelo_saliendo'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="base_destino" class="form-label">Base Destino <span class="required-mark">*</span></label>
                    <select class="form-select <?= isset($errors['base_destino']) ? 'is-invalid' : '' ?>"
                        id="base_destino" name="base_destino">
                        <option value="">-- Destino --</option>
                        <?php foreach ($baseDestinos as $bd): ?>
                            <option value="<?= htmlspecialchars($bd['nombre']) ?>" <?= ($old['base_destino'] ?? '') === $bd['nombre'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bd['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['base_destino'])): ?><div class="invalid-feedback"><?= $errors['base_destino'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="matricula" class="form-label">Matrícula <span class="required-mark">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['matricula']) ? 'is-invalid' : '' ?>"
                        id="matricula" name="matricula"
                        value="<?= htmlspecialchars($old['matricula'] ?? '') ?>"
                        placeholder="Ej: HK-5678" style="text-transform:uppercase;">
                    <?php if (isset($errors['matricula'])): ?><div class="invalid-feedback"><?= $errors['matricula'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="pax_saliendo" class="form-label">Pax Saliendo</label>
                    <input type="number" class="form-control" id="pax_saliendo" name="pax_saliendo"
                        value="<?= (int)($old['pax_saliendo'] ?? 0) ?>" min="0">
                </div>

                <div class="col-md-3">
                    <label for="pax_cancelado" class="form-label">Pax Cancelado</label>
                    <input type="number" class="form-control" id="pax_cancelado" name="pax_cancelado"
                        value="<?= (int)($old['pax_cancelado'] ?? 0) ?>" min="0"
                        title="Se deshabilita automáticamente cuando el tipo de atención es Tránsito">
                </div>

                <div class="col-md-3">
                    <label for="ajes_transportados" class="form-label">Ajes Transportados</label>
                    <input type="number" class="form-control" id="ajes_transportados" name="ajes_transportados"
                        value="<?= (int)($old['ajes_transportados'] ?? 0) ?>" min="0">
                </div>

            </div>
        </div>
    </div>

    <!-- ══ SECCIÓN 4: Horarios y cálculos ════════════════════ -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-clock-fill"></i> Horarios</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label for="hora_itinerada_llegada" class="form-label">Hora Itinerada Llegada</label>
                    <input type="time" class="form-control" id="hora_itinerada_llegada" name="hora_itinerada_llegada"
                        value="<?= htmlspecialchars($old['hora_itinerada_llegada'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="hora_real_llegada" class="form-label">Hora Real Llegada</label>
                    <input type="time" class="form-control" id="hora_real_llegada" name="hora_real_llegada"
                        value="<?= htmlspecialchars($old['hora_real_llegada'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="demora_llegando" class="form-label">Demora Llegando (min)</label>
                    <input type="number" class="form-control" id="demora_llegando" name="demora_llegando"
                        value="<?= (int)($old['demora_llegando'] ?? 0) ?>" min="0"
                        readonly style="background:var(--bg-body);">
                </div>

                <div class="col-md-3">
                    <label for="hora_itinerada_salida" class="form-label">Hora Itinerada Salida</label>
                    <input type="time" class="form-control" id="hora_itinerada_salida" name="hora_itinerada_salida"
                        value="<?= htmlspecialchars($old['hora_itinerada_salida'] ?? '') ?>">
                </div>

                <div class="col-md-3" id="satena_hora_cierre_container" style="display:none;">
                    <label for="satena_hora_cierre_modulo" class="form-label">Hora Cierre Módulo (SATENA)</label>
                    <input type="time" class="form-control" id="satena_hora_cierre_modulo" name="satena_hora_cierre_modulo"
                        value="<?= htmlspecialchars($old['satena_hora_cierre_modulo'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="hora_real_salida" class="form-label">Hora Real Salida</label>
                    <input type="time" class="form-control" id="hora_real_salida" name="hora_real_salida"
                        value="<?= htmlspecialchars($old['hora_real_salida'] ?? '') ?>">
                </div>

                <!-- Cálculos automáticos -->
                <div class="col-md-3">
                    <label class="form-label">Tiempo de Tránsito</label>
                    <div class="form-control d-flex align-items-center" style="background:var(--bg-body);">
                        <span id="tiempo_transito_display" class="time-display">--</span>
                    </div>
                    <input type="hidden" name="tiempo_transito" id="tiempo_transito" value="">
                </div>

                <div class="col-md-3">
                    <label class="form-label">¿Cumple Tiempo?</label>
                    <div class="form-control d-flex align-items-center" style="background:var(--bg-body);">
                        <span id="cumple_tiempo_display">--</span>
                    </div>
                    <input type="hidden" name="cumple_tiempo" id="cumple_tiempo" value="">
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
                            value="<?= htmlspecialchars($old['codigo_demora'] ?? '') ?>"
                            placeholder="Ej: D001" style="text-transform:uppercase;">
                    </div>
                    <div class="col-md-9">
                        <label for="observacion_demora" class="form-label">Observación de la Demora</label>
                        <textarea class="form-control" id="observacion_demora" name="observacion_demora"
                            placeholder="Describa el motivo de la demora..." rows="2"><?= htmlspecialchars($old['observacion_demora'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ SECCIÓN 5: GPU ═════════════════════════════════════ -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-lightning-charge-fill"></i> GPU (Unidad de Potencia en Tierra)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="hora_conexion_gpu" class="form-label">Hora Conexión GPU</label>
                    <input type="time" class="form-control" id="hora_conexion_gpu" name="hora_conexion_gpu"
                        value="<?= htmlspecialchars($old['hora_conexion_gpu'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="hora_desconexion_gpu" class="form-label">Hora Desconexión GPU</label>
                    <input type="time" class="form-control" id="hora_desconexion_gpu" name="hora_desconexion_gpu"
                        value="<?= htmlspecialchars($old['hora_desconexion_gpu'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="tiempo_gpu" class="form-label">Tiempo GPU (min)</label>
                    <input type="number" class="form-control" id="tiempo_gpu" name="tiempo_gpu"
                        value="<?= htmlspecialchars($old['tiempo_gpu'] ?? '') ?>" readonly style="background:var(--bg-body);">
                </div>
                <div class="col-md-2">
                    <label for="fracciones_adc_gpu" class="form-label">Fracciones ADC GPU</label>
                    <input type="number" step="0.01" class="form-control" id="fracciones_adc_gpu" name="fracciones_adc_gpu"
                        value="<?= number_format((float)($old['fracciones_adc_gpu'] ?? 0), 2) ?>"
                        readonly style="background:var(--bg-body);">
                </div>
                <!-- <div class="col-md-2">
                    <label for="fracciones_adicionales_gpu" class="form-label">Fracciones Adicionales GPU</label>
                    <input type="number" step="0.01" class="form-control" id="fracciones_adicionales_gpu" name="fracciones_adicionales_gpu"
                        value="<?= number_format((float)($old['fracciones_adicionales_gpu'] ?? 0), 2) ?>"
                        readonly style="background:var(--bg-body);">
                </div> -->
            </div>
        </div>
    </div>

    <!-- ══ SECCIÓN 6: ACU ════════════════════════════════════ -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-wind"></i> ACU (Unidad de Aire Acondicionado)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">ACU</label>
                    <select class="form-select" id="acu" name="acu">
                        <option value="0" <?= !($old['acu'] ?? 0) ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= ($old['acu'] ?? 0) ? 'selected' : '' ?>>Sí</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="hora_conexion_acu" class="form-label">Hora Conexión ACU</label>
                    <input type="time" class="form-control" id="hora_conexion_acu" name="hora_conexion_acu"
                        value="<?= htmlspecialchars($old['hora_conexion_acu'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="hora_desconexion_acu" class="form-label">Hora Desconexión ACU</label>
                    <input type="time" class="form-control" id="hora_desconexion_acu" name="hora_desconexion_acu"
                        value="<?= htmlspecialchars($old['hora_desconexion_acu'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="tiempo_acu" class="form-label">Tiempo ACU (min)</label>
                    <input type="number" class="form-control" id="tiempo_acu" name="tiempo_acu"
                        value="<?= htmlspecialchars($old['tiempo_acu'] ?? '') ?>" readonly style="background:var(--bg-body);">
                </div>
                <div class="col-md-3">
                    <label for="fracciones_hora_acu" class="form-label">Fracciones Hora ACU</label>
                    <input type="number" step="0.01" class="form-control" id="fracciones_hora_acu" name="fracciones_hora_acu"
                        value="<?= number_format((float)($old['fracciones_hora_acu'] ?? 0), 2) ?>" readonly style="background:var(--bg-body);">
                </div>
                <div class="col-md-3">
                    <label for="fracciones_15min_acu" class="form-label">Fracciones 15 min ACU</label>
                    <input type="number" step="0.01" class="form-control" id="fracciones_15min_acu" name="fracciones_15min_acu"
                        value="<?= number_format((float)($old['fracciones_15min_acu'] ?? 0), 2) ?>" readonly style="background:var(--bg-body);">
                </div>
            </div>

            <div id="acu-fracciones-container"></div>
        </div>
    </div>

    <!-- ══ SECCIÓN 6B: Ventiladores ═════════════════════════ -->
    <div class="card" id="ventiladores-card">
        <div class="card-header">
            <h5><i class="bi bi-fan"></i> Ventiladores</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Ventiladores</label>
                    <select class="form-select" id="ventiladores_activo" name="ventiladores_activo">
                        <option value="0" <?= !($old['ventiladores_activo'] ?? 0) ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= ($old['ventiladores_activo'] ?? 0) ? 'selected' : '' ?>>Sí</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="hora_conexion_ventiladores" class="form-label">Hora Conexión Ventiladores</label>
                    <input type="time" class="form-control" id="hora_conexion_ventiladores" name="hora_conexion_ventiladores"
                        value="<?= htmlspecialchars($old['hora_conexion_ventiladores'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="hora_desconexion_ventiladores" class="form-label">Hora Desconexión Ventiladores</label>
                    <input type="time" class="form-control" id="hora_desconexion_ventiladores" name="hora_desconexion_ventiladores"
                        value="<?= htmlspecialchars($old['hora_desconexion_ventiladores'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="tiempo_ventiladores" class="form-label">Tiempo Ventiladores (min)</label>
                    <input type="number" class="form-control" id="tiempo_ventiladores" name="tiempo_ventiladores"
                        value="<?= htmlspecialchars($old['tiempo_ventiladores'] ?? '') ?>" readonly style="background:var(--bg-body);">
                </div>
                <div class="col-md-3 d-none">
                    <label for="fracciones_hora_ventiladores" class="form-label">Fracciones Hora Ventiladores</label>
                    <input type="number" step="0.01" class="form-control" id="fracciones_hora_ventiladores" name="fracciones_hora_ventiladores"
                        value="<?= number_format((float)($old['fracciones_hora_ventiladores'] ?? 0), 2) ?>" readonly style="background:var(--bg-body);">
                </div>
                <div class="col-md-3 d-none">
                    <label for="fracciones_15min_ventiladores" class="form-label">Fracciones 15 min Ventiladores</label>
                    <input type="number" step="0.01" class="form-control" id="fracciones_15min_ventiladores" name="fracciones_15min_ventiladores"
                        value="<?= number_format((float)($old['fracciones_15min_ventiladores'] ?? 0), 2) ?>" readonly style="background:var(--bg-body);">
                </div>
            </div>

            <div id="ventiladores-fracciones-container"></div>
        </div>
    </div>

    <!-- ══ SECCIÓN 7: Equipos y Servicios ════════════════════ -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-tools"></i> Equipos y Servicios</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label for="sillas_ruedas" class="form-label">Sillas de Ruedas</label>
                    <input type="number" class="form-control" id="sillas_ruedas" name="sillas_ruedas"
                        value="<?= (int)($old['sillas_ruedas'] ?? 0) ?>" min="0">
                </div>

                <!-- dNone por peticion cliente -->
                <div class="col-md-3 d-none">
                    <label for="ventiladores" class="form-label">Ventiladores</label>
                    <select class="form-select" id="ventiladores" name="ventiladores">
                        <option value="0" <?= !(int)($old['ventiladores'] ?? 0) ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= (int)($old['ventiladores'] ?? 0) ? 'selected' : '' ?>>Sí</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="rampa_escalera" class="form-label">Rampa Escalera</label>
                    <select class="form-select" id="rampa_escalera" name="rampa_escalera">
                        <option value="0" <?= !($old['rampa_escalera'] ?? 0) ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= ($old['rampa_escalera'] ?? 0) ? 'selected' : '' ?>>Sí</option>
                    </select>
                </div>

                <!-- se pone d none por peticion cliente  -->
                <div class="col-md-3 d-none">
                    <label for="equipajes_transportados" class="form-label">Equipajes Transportados</label>
                    <input type="number" class="form-control" id="equipajes_transportados" name="equipajes_transportados"
                        value="<?= (int)($old['equipajes_transportados'] ?? 0) ?>" min="0">
                </div>

                <div class="col-md-3">
                    <label for="remolque_aeronave" class="form-label">Remolque de Aeronave</label>
                    <input type="number" class="form-control" id="remolque_aeronave" name="remolque_aeronave"
                        value="<?= (int)($old['remolque_aeronave'] ?? 0) ?>" min="0">
                </div>

                <div class="col-md-3">
                    <label for="remolque_equipajes" class="form-label">Remolque de Equipajes</label>
                    <input type="number" class="form-control" id="remolque_equipajes" name="remolque_equipajes"
                        value="<?= (int)($old['remolque_equipajes'] ?? 0) ?>" min="0">
                </div>

                <div class="col-md-3">
                    <label for="potable" class="form-label">Potable</label>
                    <input type="number" class="form-control" id="potable" name="potable"
                        value="<?= (int)($old['potable'] ?? 0) ?>" min="0">
                </div>

                <div class="col-md-3">
                    <label for="drenaje" class="form-label">Drenaje</label>
                    <input type="number" class="form-control" id="drenaje" name="drenaje"
                        value="<?= (int)($old['drenaje'] ?? 0) ?>" min="0">
                </div>

            </div>
        </div>
    </div>

    <!-- ══ SECCIÓN 8: Servicios adicionales ══════════════════ -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-plus-square-fill"></i> Servicios Adicionales</h5>
        </div>
        <div class="card-body">
            <div id="adicionales-container"></div>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addAdicional()">
                <i class="bi bi-plus-circle"></i> Agregar adicional
            </button>
        </div>
    </div>

    <!-- ══ SECCIÓN 9: Observaciones ══════════════════════════ -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-chat-text-fill"></i> Observaciones Operativas</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <?php
                $gseOpciones     = ['ACU', 'TRA', 'CON', 'PAY', 'ASU', 'SVPFREE', 'PEP','GPU'];
                $gseSeleccionados = !empty($old['equipo_gse_inoperativo'])
                    ? array_map('trim', explode(',', $old['equipo_gse_inoperativo']))
                    : [];
                ?>
                <div class="col-md-9">
                    <label class="form-label">Cobertura de equipos GSE</label>
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
                        <option value="0" <?= !($old['afecto_operacion'] ?? 0) ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= ($old['afecto_operacion'] ?? 0) ? 'selected' : '' ?>>Sí</option>
                    </select>
                </div>

            </div>
            <!-- Campo RPN si afectó la operación -->
            <div id="rpn-field-container" style="display:none;">
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label for="rpn" class="form-label">RPM</label>
                        <input type="text" class="form-control" id="rpn" name="rpn"
                            value="<?= htmlspecialchars($old['rpn'] ?? '') ?>"
                            placeholder="Ingrese el RPM (alfanumérico)" style="text-transform:uppercase;">
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-12">
                    <label for="observaciones" class="form-label">Observaciones Generales</label>
                    <textarea class="form-control" id="observaciones" name="observaciones"
                        placeholder="Ingrese cualquier observación adicional sobre el servicio..."
                        rows="3"><?= htmlspecialchars($old['observaciones'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="d-flex gap-2 pb-4">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle-fill"></i> Guardar Servicio de Vuelo
        </button>
        <a href="<?= BASE_URL ?>/flight-services" class="btn btn-light">Cancelar</a>
    </div>

</form>

<script>
    // ═══ CONFIGURACIÓN Y UTILIDADES ═══════════════════════
    const AVIANCA_ID = 1; // ID de AVIANCA en la BD
    const BASES_ESPECIALES = ['AUC', 'UIB']; // Bases que fuerzan Despacho = No

    // Actualizar quincena al cambiar día
    document.getElementById('dia').addEventListener('input', function() {
        const dia = parseInt(this.value) || 0;
        document.getElementById('quincena_display').value = dia <= 15 ? '1ª Quincena' : '2ª Quincena';
    });

    // Convertir a mayúsculas campos de vuelo/matrícula
    ['vuelo_llegando', 'vuelo_saliendo', 'matricula', 'airline_custom_nombre', 'aircraft_type_custom'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });

    // ═══ CONTROL PRINCIPAL: AEROLÍNEA ═════════════════════════
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
        if (airlineValue === 'otra') {
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
            document.getElementById('tiempo_cumplimiento_custom').value = '';
        }

        // 2. DESPACHO AUTOMÁTICO (AVIANCA = Sí, salvo BASES ESPECIALES que siempre son No) ════
        const isAvianca = airlineValue == AVIANCA_ID;
        const baseSelect = document.getElementById('base');
        const baseValue = baseSelect ? baseSelect.value : '';
        const isBaseEspecial = BASES_ESPECIALES.includes(baseValue);

        // La base tiene prioridad: si es AUC o UIB, despacho siempre es No,
        // sin importar la aerolínea seleccionada (incluso si es AVIANCA).
        const tieneDespacho = isBaseEspecial ? false : isAvianca;
        if (tieneDespacho) {
            despachoDisplay.textContent = 'Sí';
            despachoInput.value = '1';
            despachoDisplay.style.color = '#198754'; // Verde
        } else {
            despachoDisplay.textContent = 'No';
            despachoInput.value = '0';
            despachoDisplay.style.color = '#dc3545'; // Rojo
        }

        // 3. SATENA - HORA DE CIERRE DE MÓDULO ═════════════════════
        const selectedOption = airlineSelect.options[airlineSelect.selectedIndex];
        const selectedText = selectedOption ? selectedOption.text : '';
        if (selectedText === 'SATENA') {
            satenaContainer.style.display = 'block';
        } else {
            satenaContainer.style.display = 'none';
            const satenaField = document.getElementById('satena_hora_cierre_modulo');
            if (satenaField) satenaField.value = '';
        }
    }

    // Escuchar cambios en aerolínea y base (ambos afectan el despacho)
    document.getElementById('airline_id').addEventListener('change', updateAllAirlineLogic);
    const baseSelect = document.getElementById('base');
    if (baseSelect) {
        baseSelect.addEventListener('change', updateAllAirlineLogic);
    }
    // Llamar al inicio
    updateAllAirlineLogic();

    // ═══ LÓGICA: PAX CANCELADO (desabilitar en Tránsito) ═══
    function updatePaxCanceladoState() {
        const tipoAtencion = document.getElementById('tipo_atencion').value;
        const paxCancelado = document.getElementById('pax_cancelado');

        if (tipoAtencion === 'Tránsito') {
            paxCancelado.disabled = true;
            paxCancelado.value = '0';
            paxCancelado.style.backgroundColor = '#e9ecef';
            paxCancelado.title = 'Deshabilitado: Los vuelos en Tránsito no pueden tener pasajeros cancelados';
        } else {
            paxCancelado.disabled = false;
            paxCancelado.style.backgroundColor = '';
            paxCancelado.title = '';
        }
    }
    document.getElementById('tipo_atencion').addEventListener('change', updatePaxCanceladoState);
    updatePaxCanceladoState();

    // ═══ LÓGICA: CUMPLE TIEMPO ════════════════════════════
    /*
     * Mejorado: Llegadas anticipadas se consideran como CUMPLIMIENTO
     * - Si hora_real_llegada < hora_itinerada_llegada → CUMPLIMIENTO ✅
     * - Usa tiempo_cumplimiento_custom si se selecciona "Otra"
     * - Usa tiempo_cumplimiento_ref si se selecciona aerolínea normal
     */
    function calculateTiempoAndCumple() {
        const horaRealLlegada = document.getElementById('hora_real_llegada').value;
        const horaRealSalida = document.getElementById('hora_real_salida').value;
        const horaItineradaLlegada = document.getElementById('hora_itinerada_llegada').value;
        const tiempoRef = document.getElementById('tiempo_cumplimiento_ref').value;
        const tiempoCustom = document.getElementById('tiempo_cumplimiento_custom').value;

        const tiempoTransitoDisplay = document.getElementById('tiempo_transito_display');
        const cumpleDisplay = document.getElementById('cumple_tiempo_display');
        const tiempoInput = document.getElementById('tiempo_transito');
        const cumpleInput = document.getElementById('cumple_tiempo');
        const demoraInput = document.getElementById('demora_llegando');

        // Calcular tiempo de tránsito
        if (horaRealLlegada && horaRealSalida) {
            const llegada = new Date(`2000-01-01 ${horaRealLlegada}`);
            const salida = new Date(`2000-01-01 ${horaRealSalida}`);
            let minutos = Math.round((salida - llegada) / 60000);

            // Si es negativo, es al día siguiente
            if (minutos < 0) {
                minutos += 24 * 60;
            }

            tiempoTransitoDisplay.textContent = minutos + ' min';
            tiempoInput.value = minutos;
        } else {
            tiempoTransitoDisplay.textContent = '--';
            tiempoInput.value = '';
        }

        // Determinar qué tiempo de cumplimiento usar
        // Si hay tiempo personalizado (Otra), usarlo; si no, usar el de la BD
        const tiempoAUsar = tiempoCustom ? parseInt(tiempoCustom) : (tiempoRef ? parseInt(tiempoRef) : null);

        // Calcular cumple tiempo CON CONSIDERACIÓN DE LLEGADAS ANTICIPADAS
        if (horaRealLlegada && horaItineradaLlegada && tiempoAUsar) {
            const [hR, mR] = horaRealLlegada.split(':').map(Number);
            const [hI, mI] = horaItineradaLlegada.split(':').map(Number);

            const minReal = hR * 60 + mR;
            const minItinerada = hI * 60 + mI;
            const demora = minReal - minItinerada;

            // Si llegó anticipada (negativo), es CUMPLIMIENTO
            let cumple;
            if (demora <= 0) {
                cumple = 1; // Llegada anticipada = cumplimiento
                if (demoraInput) demoraInput.value = 0;
            } else {
                // Si llegó tarde, verificar si está dentro del tiempo permitido
                cumple = demora <= tiempoAUsar ? 1 : 0;
                if (demoraInput) demoraInput.value = Math.max(0, demora);
            }

            cumpleInput.value = cumple;
            cumpleDisplay.textContent = cumple ? '✓ SÍ' : '✗ NO';
            cumpleDisplay.style.color = cumple ? '#198754' : '#dc3545';

            toggleDemoraFields();
        } else {
            cumpleDisplay.textContent = '--';
            cumpleInput.value = '';
        }
    }

    // Escuchar cambios en horarios Y en tiempo personalizado
    ['hora_real_llegada', 'hora_itinerada_llegada', 'tiempo_cumplimiento_custom'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', calculateTiempoAndCumple);
            el.addEventListener('input', calculateTiempoAndCumple);
        }
    });

    // También disparar cálculo cuando cambia el avión seleccionado
    document.getElementById('aircraft_type_id').addEventListener('change', calculateTiempoAndCumple);

    // ═══ LÓGICA: MOSTRAR/OCULTAR CAMPOS DE DEMORA ═════════
    function toggleDemoraFields() {
        const cumpleInput = document.getElementById('cumple_tiempo');
        const demoraContainer = document.getElementById('demora-fields-container');
        if (cumpleInput && demoraContainer) {
            const cumpleValue = cumpleInput.value;
            demoraContainer.style.display = cumpleValue === '0' ? 'block' : 'none';
        }
    }

    // ═══ LÓGICA: GSE Y OPERACIÓN ═════════════════════════
    function updateAfectoOperacion() {
        const anyChecked = document.querySelectorAll('.gse-check:checked').length > 0;
        document.getElementById('afecto_operacion').value = anyChecked ? '1' : '0';
        toggleRpnField();
    }
    document.querySelectorAll('.gse-check').forEach(function(cb) {
        cb.addEventListener('change', updateAfectoOperacion);
    });
    updateAfectoOperacion();

    // Mostrar/ocultar campo RPN
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

    // ═══ LÓGICA: DESHABILITAR SECCIONES EN CANCELADO ══════
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
</script>
