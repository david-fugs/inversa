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
                <select class="form-select <?= isset($errors['base']) ? 'is-invalid' : '' ?>" id="base" name="base">
                    <option value="">-- Seleccione base --</option>
                    <?php foreach (FlightService::$bases as $b): ?>
                        <option value="<?= $b ?>" <?= ($old['base'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
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
                <select class="form-select select2 <?= isset($errors['airline_id']) ? 'is-invalid' : '' ?>"
                        id="airline_id" name="airline_id">
                    <option value="">-- Seleccione aerolínea --</option>
                    <?php foreach ($airlines as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= ($old['airline_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['airline_id'])): ?><div class="invalid-feedback d-block"><?= $errors['airline_id'] ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="aircraft_type_id" class="form-label">Tipo de Avión <span class="required-mark">*</span></label>
                <select class="form-select select2 <?= isset($errors['aircraft_type_id']) ? 'is-invalid' : '' ?>"
                        id="aircraft_type_id" name="aircraft_type_id">
                    <option value="">-- Seleccione aerolínea primero --</option>
                </select>
                <input type="hidden" id="tiempo_cumplimiento_ref" value="">
                <?php if (isset($errors['aircraft_type_id'])): ?><div class="invalid-feedback d-block"><?= $errors['aircraft_type_id'] ?></div><?php endif; ?>
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
                    <?php foreach (FlightService::$bases as $b): ?>
                        <option value="<?= $b ?>" <?= ($old['base_destino'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
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
                    value="<?= (int)($old['pax_cancelado'] ?? 0) ?>" min="0">
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
                <label for="demora_llegando" class="form-label">Demora Llegando (min)</label>
                <input type="number" class="form-control" id="demora_llegando" name="demora_llegando"
                    value="<?= (int)($old['demora_llegando'] ?? 0) ?>" min="0">
            </div>

            <div class="col-md-3">
                <label for="hora_itinerada_salida" class="form-label">Hora Itinerada Salida</label>
                <input type="time" class="form-control" id="hora_itinerada_salida" name="hora_itinerada_salida"
                    value="<?= htmlspecialchars($old['hora_itinerada_salida'] ?? '') ?>">
            </div>

            <div class="col-md-3"></div>

            <div class="col-md-3">
                <label for="hora_real_llegada" class="form-label">Hora Real Llegada</label>
                <input type="time" class="form-control" id="hora_real_llegada" name="hora_real_llegada"
                    value="<?= htmlspecialchars($old['hora_real_llegada'] ?? '') ?>">
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
            <div class="col-md-4">
                <label for="fracciones_adc_gpu" class="form-label">Fracciones ADC GPU</label>
                <input type="number" step="0.01" class="form-control" id="fracciones_adc_gpu" name="fracciones_adc_gpu"
                    value="<?= number_format((float)($old['fracciones_adc_gpu'] ?? 0), 2) ?>">
            </div>
        </div>

        <!-- Fracciones dinámicas GPU -->
        <div id="gpu-fracciones-container"></div>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addGpuRow()">
            <i class="bi bi-plus-circle"></i> Agregar fracción GPU
        </button>
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
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addAcuRow()">
            <i class="bi bi-plus-circle"></i> Agregar fracción ACU
        </button>
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

            <div class="col-md-3">
                <label for="ventiladores" class="form-label">Ventiladores</label>
                <input type="number" class="form-control" id="ventiladores" name="ventiladores"
                    value="<?= (int)($old['ventiladores'] ?? 0) ?>" min="0">
            </div>

            <div class="col-md-3">
                <label for="rampa_escalera" class="form-label">Rampa Escalera</label>
                <select class="form-select" id="rampa_escalera" name="rampa_escalera">
                    <option value="0" <?= !($old['rampa_escalera'] ?? 0) ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= ($old['rampa_escalera'] ?? 0) ? 'selected' : '' ?>>Sí</option>
                </select>
            </div>

            <div class="col-md-3">
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

            <div class="col-md-9">
                <label for="equipo_gse_inoperativo" class="form-label">Equipo GSE Inoperativo</label>
                <textarea class="form-control" id="equipo_gse_inoperativo" name="equipo_gse_inoperativo"
                    rows="3" placeholder="Describa el equipo inoperativo..."><?= htmlspecialchars($old['equipo_gse_inoperativo'] ?? '') ?></textarea>
            </div>

            <div class="col-md-3">
                <label for="afecto_operacion" class="form-label">¿Afectó la operación?</label>
                <select class="form-select" id="afecto_operacion" name="afecto_operacion">
                    <option value="0" <?= !($old['afecto_operacion'] ?? 0) ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= ($old['afecto_operacion'] ?? 0) ? 'selected' : '' ?>>Sí</option>
                </select>
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
// Actualizar quincena al cambiar día
document.getElementById('dia').addEventListener('input', function() {
    const dia = parseInt(this.value) || 0;
    document.getElementById('quincena_display').value = dia <= 15 ? '1ª Quincena' : '2ª Quincena';
});

// Convertir a mayúsculas campos de vuelo/matrícula
['vuelo_llegando','vuelo_saliendo','matricula'].forEach(function(id) {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
});
</script>
