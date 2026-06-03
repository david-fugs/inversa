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
                    $basesDisponibles = $baseColaborador ? [$baseColaborador] : FlightService::$bases;
                ?>
                <select class="form-select" id="base" name="base"
                    <?= $baseColaborador ? 'readonly style="pointer-events:none;background:var(--bg-body);"' : '' ?>>
                    <?php foreach ($basesDisponibles as $b): ?>
                        <option value="<?= $b ?>" <?= ($service['base'] === $b || $baseColaborador === $b) ? 'selected' : '' ?>><?= $b ?></option>
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
                <label class="form-label">Despacho</label>
                <div class="form-control d-flex align-items-center" style="background:var(--bg-body);">
                    <span id="despacho_display"><?= $service['despacho'] ? 'Sí' : 'No' ?></span>
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
                <select class="form-select select2" id="airline_id" name="airline_id">
                    <option value="">-- Seleccione aerolínea --</option>
                    <?php foreach ($airlines as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $service['airline_id'] == $a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="aircraft_type_id" class="form-label">Tipo de Avión <span class="required-mark">*</span></label>
                <select class="form-select select2" id="aircraft_type_id" name="aircraft_type_id">
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
                    <?php foreach (FlightService::$bases as $b): ?>
                        <option value="<?= $b ?>" <?= $service['base_destino'] === $b ? 'selected' : '' ?>><?= $b ?></option>
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
            <div class="col-md-3"></div>
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

<!-- ══ SECCIÓN 7: Equipos ════════════════════════════════ -->
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
                $gseOpciones     = ['ACU','TRA','CON','PAY','ASU','E318/A320'];
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
document.getElementById('dia').addEventListener('input', function() {
    const dia = parseInt(this.value) || 0;
    document.getElementById('quincena_display').value = dia <= 15 ? '1ª Quincena' : '2ª Quincena';
});

['vuelo_llegando','vuelo_saliendo','matricula'].forEach(function(id) {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
});

// GSE Inoperativo -> Afectó la operación (auto)
function updateAfectoOperacion() {
    const anyChecked = document.querySelectorAll('.gse-check:checked').length > 0;
    document.getElementById('afecto_operacion').value = anyChecked ? '1' : '0';
}
document.querySelectorAll('.gse-check').forEach(function(cb) {
    cb.addEventListener('change', updateAfectoOperacion);
});
updateAfectoOperacion();
</script>
