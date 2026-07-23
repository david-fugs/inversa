/**
 * INVERSA - Operaciones Aeroportuarias
 * JavaScript principal - app.js
 */

'use strict';

/* ── Sidebar toggle (mobile) ──────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.getElementById('sidebar');
    const overlay       = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    /* ── Resaltar enlace activo en sidebar ─────────────── */
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && currentPath.startsWith(href) && href !== '/inversa/') {
            link.classList.add('active');
        } else if (href === currentPath) {
            link.classList.add('active');
        }
    });

    /* ── Auto-dismiss de alertas ───────────────────────── */
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alert) {
        const delay = parseInt(alert.dataset.autoDismiss) || 5000;
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity .5s';
            setTimeout(function () { alert.remove(); }, 500);
        }, delay);
    });

    /* ── Confirmar eliminación ─────────────────────────── */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.dataset.confirm || '¿Está seguro de eliminar este registro?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    /* ── Select2 global ────────────────────────────────── */
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            placeholder: 'Seleccione una opción',
            allowClear: true,
            language: {
                noResults: function () { return 'No se encontraron resultados'; },
                searching: function () { return 'Buscando...'; }
            }
        });
    }

    /* ── DataTables global ─────────────────────────────── */
    if (typeof $.fn.dataTable !== 'undefined') {
        $('.data-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                emptyTable: 'No hay registros disponibles'
            },
            responsive: true,
            dom: '<"row align-items-center mb-3"<"col-sm-6"l><"col-sm-6 text-end"f>>rtip',
            pageLength: 15,
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });
    }

    /* ── Cálculo automático tiempo de tránsito ─────────── */
    initTransitCalculation();

    /* ── Cálculo automático GPU ────────────────────────── */
    initGpuCalculation();

    /* ── Cálculo automático ACU ────────────────────────── */
    initAcuCalculation();

    /* ── Cálculo automático Ventiladores ───────────────── */
    initVentiladoresCalculation();

    /* ── Cálculo despacho según base ───────────────────── */
    initDespachoCalc();

    /* ── Validación horas conexión/desconexión vs hora real ── */
    initEquipmentHoursRangeValidation();

    /* ── Tipo de avión por aerolínea (AJAX) ────────────── */
    initAircraftByAirline();
});

/* ── Tiempo de tránsito ───────────────────────────────── */
function initTransitCalculation() {
    const horaItineradaLlegada = document.getElementById('hora_itinerada_llegada');
    const horaRealLlegada = document.getElementById('hora_real_llegada');
    const horaRealSalida  = document.getElementById('hora_real_salida');
    const tiempoDisplay   = document.getElementById('tiempo_transito_display');
    const tiempoInput     = document.getElementById('tiempo_transito');
    const cumpleDisplay   = document.getElementById('cumple_tiempo_display');
    const cumpleInput     = document.getElementById('cumple_tiempo');
    const demoraInput     = document.getElementById('demora_llegando');

    if (!horaRealLlegada || !horaRealSalida) return;

    function calcularTransito() {
        const llegada = timeToMinutes(horaRealLlegada.value);
        const salida  = timeToMinutes(horaRealSalida.value);

        if (llegada === null || salida === null) {
            if (tiempoDisplay) tiempoDisplay.textContent = '--';
            return;
        }

        let diff = salida - llegada;
        // Si la salida es al día siguiente
        if (diff < 0) diff += 1440;

        if (tiempoInput)   tiempoInput.value = diff;
        if (tiempoDisplay) tiempoDisplay.textContent = diff + ' min';

        // Verificar cumplimiento
        const tiempoCumplimiento = parseInt(
            document.getElementById('tiempo_cumplimiento_ref')?.value || '0'
        );

        if (tiempoCumplimiento > 0 && cumpleDisplay && cumpleInput) {
            const cumple = diff <= tiempoCumplimiento;
            cumpleInput.value = cumple ? '1' : '0';
            if (cumple) {
                cumpleDisplay.innerHTML = '<span class="cumple-si"><i class="bi bi-check-circle-fill"></i> SI</span>';
            } else {
                cumpleDisplay.innerHTML = '<span class="cumple-no"><i class="bi bi-x-circle-fill"></i> NO</span>';
            }
            // Mostrar/ocultar Código Demora y Observación de la Demora
            // (función definida en create.php/edit.php) según el resultado.
            if (typeof toggleDemoraFields === 'function') toggleDemoraFields();
        }
    }

    function calcularDemora() {
        if (!demoraInput) return;
        const itinerada = timeToMinutes(horaItineradaLlegada ? horaItineradaLlegada.value : '');
        const real      = timeToMinutes(horaRealLlegada.value);
        if (itinerada === null || real === null) { demoraInput.value = 0; return; }
        let diff = real - itinerada;
        if (diff < 0) diff = 0;
        demoraInput.value = diff;
    }

    horaRealLlegada.addEventListener('change', calcularTransito);
    horaRealLlegada.addEventListener('change', calcularDemora);
    horaRealSalida.addEventListener('change', calcularTransito);
    if (horaItineradaLlegada) horaItineradaLlegada.addEventListener('change', calcularDemora);
    calcularTransito();
    calcularDemora();
}

/* ── Cálculo de fracciones GPU según tarifa de la aerolínea ──
 * Regla de negocio (tarifas_gpu):
 *  - Si la aerolínea tiene "primeros_minutos" configurado, ese tramo
 *    completo cuenta como 1 fracción. Cada "fraccion_minutos"
 *    adicionales (o parte de ellos) suma 1 fracción más.
 *    Ej: primeros=60, fraccion=15 → 60 min=1, 61 min=2, 76 min=3
 *  - Si la aerolínea NO tiene "primeros_minutos" (solo maneja
 *    fracción), las fracciones se cuentan directamente cada
 *    "fraccion_minutos" desde el minuto 0.
 *  - Sin tarifa configurada para la aerolínea → 0 fracciones.
 */
function calcularFraccionesGpuValor(tiempoMin, tarifa) {
    const fraccion = tarifa ? parseInt(tarifa.fraccion_minutos, 10) : 0;
    if (!fraccion || fraccion <= 0) return 0;
    if (!tiempoMin || tiempoMin <= 0) return 0;

    const primeros = tarifa && tarifa.primeros_minutos !== null && tarifa.primeros_minutos !== undefined
        ? parseInt(tarifa.primeros_minutos, 10)
        : 0;

    if (primeros > 0) {
        if (tiempoMin <= primeros) return 1;
        return 1 + Math.ceil((tiempoMin - primeros) / fraccion);
    }

    return Math.ceil(tiempoMin / fraccion);
}

/* ── Cálculo GPU ──────────────────────────────────────── */
function initGpuCalculation() {
    const conexion      = document.getElementById('hora_conexion_gpu');
    const desconexion   = document.getElementById('hora_desconexion_gpu');
    const tiempoGpu     = document.getElementById('tiempo_gpu');
    const fracGpu       = document.getElementById('fracciones_adc_gpu');
    const fracAdicGpu   = document.getElementById('fracciones_adicionales_gpu');
    const airlineSelect = document.getElementById('airline_id');

    if (!conexion || !desconexion) return;

    let tarifaActual = null;

    function recalcularFracciones() {
        const diff = parseInt(tiempoGpu ? tiempoGpu.value : '0', 10) || 0;
        const val  = calcularFraccionesGpuValor(diff, tarifaActual);
        if (fracGpu)     fracGpu.value     = val.toFixed(2);
        if (fracAdicGpu) fracAdicGpu.value = val.toFixed(2);
    }

    function cargarTarifaAerolinea(airlineId) {
        if (!airlineId || airlineId === 'otra') {
            tarifaActual = null;
            recalcularFracciones();
            return;
        }
        fetch(BASE_URL + '/tarifas-cobros/by-airline/' + airlineId)
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                tarifaActual = data;
                recalcularFracciones();
            })
            .catch(function () {
                tarifaActual = null;
                recalcularFracciones();
            });
    }

    function calcular() {
        const c = timeToMinutes(conexion.value);
        const d = timeToMinutes(desconexion.value);
        if (c === null || d === null) return;
        let diff = d - c;
        if (diff < 0) diff += 1440;
        if (tiempoGpu) tiempoGpu.value = diff;
        recalcularFracciones();
    }

    conexion.addEventListener('change', calcular);
    desconexion.addEventListener('change', calcular);

    if (airlineSelect) {
        airlineSelect.addEventListener('change', function () {
            cargarTarifaAerolinea(this.value);
        });
        // Cargar tarifa inicial si ya hay una aerolínea seleccionada (edición o reintento con errores)
        if (airlineSelect.value) {
            cargarTarifaAerolinea(airlineSelect.value);
        }
    }
}

/* ── Cálculo ACU ──────────────────────────────────────── */
function initAcuCalculation() {
    const conexion     = document.getElementById('hora_conexion_acu');
    const desconexion  = document.getElementById('hora_desconexion_acu');
    const tiempoAcu    = document.getElementById('tiempo_acu');
    const acuSelect    = document.getElementById('acu');

    if (!conexion || !desconexion) return;

    function calcular() {
        const c = timeToMinutes(conexion.value);
        const d = timeToMinutes(desconexion.value);
        if (c === null || d === null) return;
        let diff = d - c;
        if (diff < 0) diff += 1440;
        if (tiempoAcu) tiempoAcu.value = diff;

        // Fracciones hora ACU: 1 si ACU=Sí, 0 si No
        const fracHora = document.getElementById('fracciones_hora_acu');
        const frac15   = document.getElementById('fracciones_15min_acu');
        const acuVal   = acuSelect ? parseInt(acuSelect.value) : 0;
        if (fracHora) fracHora.value = acuVal ? '1.00' : '0.00';
        // Fracciones 15 min ACU: cada bloque de 15 min (1-15=1, 16-30=2, 31-45=3, ...) redondeando hacia arriba
        if (frac15)   frac15.value   = diff > 0 ? Math.ceil(diff / 15).toFixed(2) : '0.00';
    }

    function calcularFracHora() {
        const fracHora = document.getElementById('fracciones_hora_acu');
        const acuVal   = acuSelect ? parseInt(acuSelect.value) : 0;
        if (fracHora) fracHora.value = acuVal ? '1.00' : '0.00';
    }

    conexion.addEventListener('change', calcular);
    desconexion.addEventListener('change', calcular);
    if (acuSelect) acuSelect.addEventListener('change', calcularFracHora);
}

/* ── Cálculo automático Ventiladores ─────────────────── */
function initVentiladoresCalculation() {
    const conexion     = document.getElementById('hora_conexion_ventiladores');
    const desconexion  = document.getElementById('hora_desconexion_ventiladores');
    const tiempoVent   = document.getElementById('tiempo_ventiladores');
    const ventSelect   = document.getElementById('ventiladores_activo');

    if (!conexion || !desconexion) return;

    function calcular() {
        const c = timeToMinutes(conexion.value);
        const d = timeToMinutes(desconexion.value);
        if (c === null || d === null) return;
        let diff = d - c;
        if (diff < 0) diff += 1440;
        if (tiempoVent) tiempoVent.value = diff;

        // Fracciones hora Ventiladores: 1 si Ventiladores=Sí, 0 si No
        const fracHora = document.getElementById('fracciones_hora_ventiladores');
        const frac15   = document.getElementById('fracciones_15min_ventiladores');
        const ventVal  = ventSelect ? parseInt(ventSelect.value) : 0;
        if (fracHora) fracHora.value = ventVal ? '1.00' : '0.00';
        // Fracciones 15 min Ventiladores = (tiempo_ventiladores - 60) / 15
        if (frac15)   frac15.value   = ((diff - 60) / 15).toFixed(2);
    }

    function calcularFracHora() {
        const fracHora = document.getElementById('fracciones_hora_ventiladores');
        const ventVal  = ventSelect ? parseInt(ventSelect.value) : 0;
        if (fracHora) fracHora.value = ventVal ? '1.00' : '0.00';
    }

    conexion.addEventListener('change', calcular);
    desconexion.addEventListener('change', calcular);
    if (ventSelect) ventSelect.addEventListener('change', calcularFracHora);
}

/* ── Validación horas conexión/desconexión vs hora real ──
 * Las horas de conexión y desconexión de GPU, ACU y Ventiladores
 * deben estar dentro del rango [hora_real_llegada, hora_real_salida].
 */
function initEquipmentHoursRangeValidation() {
    const horaRealLlegada = document.getElementById('hora_real_llegada');
    const horaRealSalida  = document.getElementById('hora_real_salida');
    if (!horaRealLlegada || !horaRealSalida) return;

    const EQUIPOS = [
        { conexion: 'hora_conexion_gpu', desconexion: 'hora_desconexion_gpu' },
        { conexion: 'hora_conexion_acu', desconexion: 'hora_desconexion_acu' },
        { conexion: 'hora_conexion_ventiladores', desconexion: 'hora_desconexion_ventiladores' }
    ];

    function getFeedback(field) {
        let feedback = field.nextElementSibling;
        if (!feedback || !feedback.classList.contains('equipment-hour-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback equipment-hour-feedback';
            field.insertAdjacentElement('afterend', feedback);
        }
        return feedback;
    }

    function validateField(fieldId) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        const min = timeToMinutes(horaRealLlegada.value);
        const max = timeToMinutes(horaRealSalida.value);
        const val = timeToMinutes(field.value);
        const feedback = getFeedback(field);

        if (val === null || min === null || max === null) {
            field.classList.remove('is-invalid');
            return;
        }

        let maxAjustado = max;
        if (maxAjustado < min) maxAjustado += 1440; // la salida es al día siguiente
        let valAjustado = val;
        if (valAjustado < min) valAjustado += 1440;

        const fueraDeRango = valAjustado < min || valAjustado > maxAjustado;

        if (fueraDeRango) {
            feedback.textContent = `Debe estar entre la hora real de llegada (${horaRealLlegada.value}) y la hora real de salida (${horaRealSalida.value}).`;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    }

    function validateAll() {
        EQUIPOS.forEach(function (eq) {
            validateField(eq.conexion);
            validateField(eq.desconexion);
        });
    }

    horaRealLlegada.addEventListener('change', validateAll);
    horaRealSalida.addEventListener('change', validateAll);

    EQUIPOS.forEach(function (eq) {
        [eq.conexion, eq.desconexion].forEach(function (id) {
            const field = document.getElementById(id);
            if (field) field.addEventListener('change', validateAll);
        });
    });

    validateAll();
}

/* ── Tipos de avión por aerolínea ─────────────────────── */
function initAircraftByAirline() {
    const airlineSelect  = document.getElementById('airline_id');
    const aircraftSelect = document.getElementById('aircraft_type_id');

    if (!airlineSelect || !aircraftSelect) return;

    // Escuchar cambios en aerolínea
    airlineSelect.addEventListener('change', function () {
        const airlineId = this.value;
        aircraftSelect.innerHTML = '<option value="">Cargando...</option>';

        if (!airlineId || airlineId === 'otra') {
            aircraftSelect.innerHTML = '<option value="">-- Seleccione aerolínea primero --</option>';
            const ref = document.getElementById('tiempo_cumplimiento_ref');
            if (ref) ref.value = '';
            return;
        }

        fetch(BASE_URL + '/aircraft-types/by-airline/' + airlineId)
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                aircraftSelect.innerHTML = '<option value="">-- Seleccione tipo de avión --</option>';
                data.forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.tipo + ' (' + item.tiempo_cumplimiento + ' min)';
                    opt.dataset.tiempo = item.tiempo_cumplimiento;
                    aircraftSelect.appendChild(opt);
                });
            })
            .catch(function (err) {
                console.error('Error cargando tipos de avión:', err);
                aircraftSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    });

    // Actualizar tiempo de cumplimiento al cambiar tipo de avión
    aircraftSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const ref = document.getElementById('tiempo_cumplimiento_ref');
        if (ref && selected && selected.dataset.tiempo) {
            ref.value = selected.dataset.tiempo;
            const llegada = document.getElementById('hora_real_llegada');
            if (llegada) llegada.dispatchEvent(new Event('input'));
        }
    });
}

/* ── Cálculo de despacho según base ──────────────────── */
function initDespachoCalc() {
    // NO ejecutar en página de edición (edit.php tiene su propia lógica)
    if (window.location.pathname.includes('/edit/')) {
        console.log('initDespachoCalc: Deshabilitado en página de edición');
        return;
    }

    const baseSelect      = document.getElementById('base');
    const despachoInput   = document.getElementById('despacho');
    const despachoDisplay = document.getElementById('despacho_display');
    const BASES_DESPACHO  = ['VUP', 'PPN', 'EJA', 'VVC', 'EYP'];

    if (!baseSelect) return;

    function calcular() {
        const esSi = BASES_DESPACHO.includes(baseSelect.value);
        if (despachoInput)   despachoInput.value       = esSi ? '1' : '0';
        if (despachoDisplay) despachoDisplay.textContent = esSi ? 'Sí' : 'No';
    }

    baseSelect.addEventListener('change', calcular);
    calcular();
}

/* ── Filas dinámicas GPU ──────────────────────────────── */
function addGpuRow() {
    const container = document.getElementById('gpu-fracciones-container');
    const idx = container.querySelectorAll('.dynamic-row').length;
    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.innerHTML = `
        <button type="button" class="btn-remove-row" onclick="this.closest('.dynamic-row').remove()">
            <i class="bi bi-x"></i>
        </button>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Hora Conexión</label>
                <input type="time" class="form-control" name="gpu_fracciones[${idx}][hora_conexion]"
                    oninput="calcFraccionGpu(this)">
            </div>
            <div class="col-md-3">
                <label class="form-label">Hora Desconexión</label>
                <input type="time" class="form-control" name="gpu_fracciones[${idx}][hora_desconexion]"
                    oninput="calcFraccionGpu(this)">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tiempo (min)</label>
                <input type="number" class="form-control" name="gpu_fracciones[${idx}][tiempo]" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Fracciones ADC GPU</label>
                <input type="number" step="0.01" class="form-control" name="gpu_fracciones[${idx}][fracciones_adc]" value="0">
            </div>
        </div>`;
    container.appendChild(row);
}

function calcFraccionGpu(anyInput) {
    const row            = anyInput.closest('.dynamic-row');
    const conexionInp    = row.querySelector('input[name$="[hora_conexion]"]');
    const desconexionInp = row.querySelector('input[name$="[hora_desconexion]"]');
    const tiempoInp      = row.querySelector('input[name$="[tiempo]"]');
    const c = timeToMinutes(conexionInp ? conexionInp.value : '');
    const d = timeToMinutes(desconexionInp ? desconexionInp.value : '');
    if (c !== null && d !== null) {
        let diff = d - c;
        if (diff < 0) diff += 1440;
        if (tiempoInp) tiempoInp.value = diff;
    }
}

/* ── Filas dinámicas ACU ──────────────────────────────── */
function addAcuRow() {
    const container = document.getElementById('acu-fracciones-container');
    const idx = container.querySelectorAll('.dynamic-row').length;
    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.innerHTML = `
        <button type="button" class="btn-remove-row" onclick="this.closest('.dynamic-row').remove()">
            <i class="bi bi-x"></i>
        </button>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Hora Conexión</label>
                <input type="time" class="form-control" name="acu_fracciones[${idx}][hora_conexion]"
                    oninput="calcFraccionAcu(this)">
            </div>
            <div class="col-md-3">
                <label class="form-label">Hora Desconexión</label>
                <input type="time" class="form-control" name="acu_fracciones[${idx}][hora_desconexion]"
                    oninput="calcFraccionAcu(this)">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tiempo (min)</label>
                <input type="number" class="form-control" name="acu_fracciones[${idx}][tiempo]" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fracc. Hora</label>
                <input type="number" step="0.01" class="form-control" name="acu_fracciones[${idx}][fracciones_hora]" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fracc. 15 min</label>
                <input type="number" step="0.01" class="form-control" name="acu_fracciones[${idx}][fracciones_15min]" readonly>
            </div>
        </div>`;
    container.appendChild(row);
}

function calcFraccionAcu(anyInput) {
    const row            = anyInput.closest('.dynamic-row');
    const conexionInp    = row.querySelector('input[name$="[hora_conexion]"]');
    const desconexionInp = row.querySelector('input[name$="[hora_desconexion]"]');
    const tiempoInp      = row.querySelector('input[name$="[tiempo]"]');
    const fracHoraInp    = row.querySelector('input[name$="[fracciones_hora]"]');
    const frac15Inp      = row.querySelector('input[name$="[fracciones_15min]"]');
    const c = timeToMinutes(conexionInp ? conexionInp.value : '');
    const d = timeToMinutes(desconexionInp ? desconexionInp.value : '');
    if (c !== null && d !== null) {
        let diff = d - c;
        if (diff < 0) diff += 1440;
        if (tiempoInp)    tiempoInp.value    = diff;
        if (fracHoraInp)  fracHoraInp.value  = (diff / 60).toFixed(2);
        if (frac15Inp)    frac15Inp.value    = (diff / 15).toFixed(2);
    }
}

/* ── Filas dinámicas Ventiladores ────────────────────── */
function addVentiladoresRow() {
    const container = document.getElementById('ventiladores-fracciones-container');
    const idx = container.querySelectorAll('.dynamic-row').length;
    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.innerHTML = `
        <button type="button" class="btn-remove-row" onclick="this.closest('.dynamic-row').remove()">
            <i class="bi bi-x"></i>
        </button>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Hora Conexión</label>
                <input type="time" class="form-control" name="ventiladores_fracciones[${idx}][hora_conexion]"
                    oninput="calcFraccionVentiladores(this)">
            </div>
            <div class="col-md-3">
                <label class="form-label">Hora Desconexión</label>
                <input type="time" class="form-control" name="ventiladores_fracciones[${idx}][hora_desconexion]"
                    oninput="calcFraccionVentiladores(this)">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tiempo (min)</label>
                <input type="number" class="form-control" name="ventiladores_fracciones[${idx}][tiempo]" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fracc. Hora</label>
                <input type="number" step="0.01" class="form-control" name="ventiladores_fracciones[${idx}][fracciones_hora]" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fracc. 15 min</label>
                <input type="number" step="0.01" class="form-control" name="ventiladores_fracciones[${idx}][fracciones_15min]" readonly>
            </div>
        </div>`;
    container.appendChild(row);
}

function calcFraccionVentiladores(anyInput) {
    const row            = anyInput.closest('.dynamic-row');
    const conexionInp    = row.querySelector('input[name$="[hora_conexion]"]');
    const desconexionInp = row.querySelector('input[name$="[hora_desconexion]"]');
    const tiempoInp      = row.querySelector('input[name$="[tiempo]"]');
    const fracHoraInp    = row.querySelector('input[name$="[fracciones_hora]"]');
    const frac15Inp      = row.querySelector('input[name$="[fracciones_15min]"]');
    const c = timeToMinutes(conexionInp ? conexionInp.value : '');
    const d = timeToMinutes(desconexionInp ? desconexionInp.value : '');
    if (c !== null && d !== null) {
        let diff = d - c;
        if (diff < 0) diff += 1440;
        if (tiempoInp)    tiempoInp.value    = diff;
        if (fracHoraInp)  fracHoraInp.value  = (diff / 60).toFixed(2);
        if (frac15Inp)    frac15Inp.value    = (diff / 15).toFixed(2);
    }
}

/* ── Adicionales dinámicos ────────────────────────────── */
function addAdicional() {
    const container = document.getElementById('adicionales-container');
    const idx = container.querySelectorAll('.dynamic-row').length;
    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.innerHTML = `
        <button type="button" class="btn-remove-row" onclick="this.closest('.dynamic-row').remove()">
            <i class="bi bi-x"></i>
        </button>
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Servicio Adicional</label>
                <select class="form-select" name="adicionales[${idx}][servicio]" required>
                    <option value="">-- Seleccione --</option>
                    <option value="Traslado de carga">Traslado de carga</option>
                    <option value="Arrancador ASU">Arrancador ASU</option>
                    <option value="Hora hombre">Hora hombre</option>
                    <option value="Pernocta">Pernocta</option>
                    <option value="Cinta transportadora / Conveyor">Cinta transportadora / Conveyor</option>
                    <option value="Escalera">Escalera</option>
                    <option value="Drenado">Drenado</option>
                    <option value="Remolque">Remolque</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cantidad</label>
                <input type="number" class="form-control" name="adicionales[${idx}][cantidad]" min="1" value="1">
            </div>
        </div>`;
    container.appendChild(row);
}

/* ── Utilidad: convertir HH:MM a minutos ──────────────── */
function timeToMinutes(timeStr) {
    if (!timeStr || !timeStr.includes(':')) return null;
    const parts = timeStr.split(':');
    return parseInt(parts[0]) * 60 + parseInt(parts[1]);
}

/* ── Constante BASE_URL para JS ───────────────────────── */
const BASE_URL = document.querySelector('meta[name="base-url"]')?.content ?? '';
