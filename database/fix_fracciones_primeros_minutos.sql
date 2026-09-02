-- ============================================================
-- CORRECCIÓN: Fracciones GPU/ACU mal calculadas en el tramo de
--              "primeros minutos"
-- Fecha: 2026-09-02
-- ============================================================
--
-- Regla de negocio ANTERIOR (bug, dos variantes que existieron):
--   a) En cuanto el equipo (GPU o ACU) se conectaba, aunque fuera
--      solo unos minutos, ya se cobraba 1 fracción completa (el
--      tramo de "primeros minutos" se trataba como un cobro mínimo
--      fijo), incluso si el tiempo conectado no llegaba a alcanzar
--      esa cantidad de minutos.
--   b) Al superar el umbral de "primeros minutos", se sumaba una
--      fracción "base" de más (1 + fracciones de exceso) en vez de
--      cobrar únicamente las fracciones correspondientes al exceso.
--
-- Regla de negocio CORRECTA (ver public/js/app.js, funciones
-- calcularFraccionesGpuValor / calcularFraccionesAcuValores):
--   - Mientras el tiempo conectado sea MENOR O IGUAL a los
--     "primeros minutos" configurados para esa aerolínea/base, NO se
--     cobra ninguna fracción (0). Llegar justo al umbral tampoco
--     genera cobro.
--   - Recién cuando el tiempo SUPERA ese umbral se empieza a cobrar:
--     1 fracción por cada "fraccion_minutos" de exceso (o parte de
--     ellos), sin ningún cobro base adicional.
--     Ej: primeros=70, fraccion=15 → 70 min=0, 71 min=1, 85 min=1,
--         86 min=2.
--   - Si la aerolínea/base NO tiene "primeros_minutos" configurado
--     (solo maneja fracción), no cambia: se cuenta cada
--     "fraccion_minutos" desde el minuto 0.
--
-- Este script RECALCULA (no solo corrige hacia abajo) el valor
-- correcto de cada campo de fracciones, a partir del tiempo ya
-- guardado (tiempo_gpu / tiempo_acu / tiempo) y de la tarifa
-- VIGENTE en `tarifas_gpu` (el sistema no guarda un histórico de
-- tarifas por fecha, así que usa la configuración actual, igual que
-- ya hace el formulario en vivo).
--
-- La tarifa aplicable se resuelve igual que en la app
-- (TarifaGpu::findByAirlineBaseTipo): primero la ESPECÍFICA de
-- aerolínea + base; si esa fila existe, se usa (aunque su
-- primeros_minutos sea NULL) y NO se cae a la de "todas las bases".
-- Solo si no existe fila específica se usa la de aerolínea + "todas
-- las bases" (base_id IS NULL).
--
-- Solo toca registros donde SÍ hay una tarifa con "primeros_minutos"
-- configurado para esa aerolínea/base y tipo de cobro; el resto
-- queda intacto (sin tarifa inicial, el cálculo no tenía este bug).
--
-- IMPORTANTE: hacer un respaldo antes de ejecutar
--   mysqldump <BD> flight_services flight_service_gpu_fracciones
--             flight_service_acu_fracciones > backup_fracciones.sql
-- ============================================================

START TRANSACTION;

-- ────────────────────────────────────────────────────────────
-- 0. Vista previa: registros cuyo valor guardado no coincide con
--    el valor correcto (ejecutar antes de las UPDATE para revisar)
-- ────────────────────────────────────────────────────────────

-- GPU (servicio principal)
SELECT fs.id, fs.tiempo_gpu, fs.fracciones_adc_gpu AS actual,
       CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END AS primeros_minutos,
       CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.fraccion_minutos ELSE tg_gen.fraccion_minutos END AS fraccion_minutos,
       CASE
           WHEN fs.tiempo_gpu <= CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END THEN 0
           ELSE CEIL((fs.tiempo_gpu - CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END)
                     / (CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.fraccion_minutos ELSE tg_gen.fraccion_minutos END))
       END AS correcto
FROM flight_services fs
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp
    ON tg_esp.airline_id = fs.airline_id
   AND tg_esp.base_id = b.id
   AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen
    ON tg_gen.airline_id = fs.airline_id
   AND tg_gen.base_id IS NULL
   AND tg_gen.tipo_cobro = 'gpu'
WHERE fs.tiempo_gpu IS NOT NULL
  AND fs.tiempo_gpu > 0
  AND CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END IS NOT NULL
  AND fs.fracciones_adc_gpu <> CASE
           WHEN fs.tiempo_gpu <= CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END THEN 0
           ELSE CEIL((fs.tiempo_gpu - CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END)
                     / (CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.fraccion_minutos ELSE tg_gen.fraccion_minutos END))
       END;

-- GPU (filas "Agregar otro GPU")
SELECT gf.id, gf.flight_service_id, gf.tiempo, gf.fracciones_adc AS actual,
       CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END AS primeros_minutos,
       CASE
           WHEN gf.tiempo <= CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END THEN 0
           ELSE CEIL((gf.tiempo - CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END)
                     / (CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.fraccion_minutos ELSE tg_gen.fraccion_minutos END))
       END AS correcto
FROM flight_service_gpu_fracciones gf
JOIN flight_services fs ON fs.id = gf.flight_service_id
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp
    ON tg_esp.airline_id = fs.airline_id
   AND tg_esp.base_id = b.id
   AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen
    ON tg_gen.airline_id = fs.airline_id
   AND tg_gen.base_id IS NULL
   AND tg_gen.tipo_cobro = 'gpu'
WHERE gf.tiempo IS NOT NULL
  AND gf.tiempo > 0
  AND CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END IS NOT NULL
  AND gf.fracciones_adc <> CASE
           WHEN gf.tiempo <= CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END THEN 0
           ELSE CEIL((gf.tiempo - CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END)
                     / (CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.fraccion_minutos ELSE tg_gen.fraccion_minutos END))
       END;

-- ACU: "Fracciones Hora ACU" queda siempre en 0 con la regla nueva
-- (el cobro fijo al alcanzar el umbral ya no existe). Vista previa
-- de lo que va a quedar en 0 (servicio principal + filas adicionales):
SELECT fs.id, fs.tiempo_acu, fs.fracciones_hora_acu AS actual
FROM flight_services fs
WHERE fs.fracciones_hora_acu <> 0;

SELECT af.id, af.flight_service_id, af.tiempo, af.fracciones_hora AS actual
FROM flight_service_acu_fracciones af
WHERE af.fracciones_hora <> 0;

-- ────────────────────────────────────────────────────────────
-- 1. Corrección: Fracciones ADC GPU (servicio principal)
-- ────────────────────────────────────────────────────────────
UPDATE flight_services fs
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp
    ON tg_esp.airline_id = fs.airline_id
   AND tg_esp.base_id = b.id
   AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen
    ON tg_gen.airline_id = fs.airline_id
   AND tg_gen.base_id IS NULL
   AND tg_gen.tipo_cobro = 'gpu'
SET fs.fracciones_adc_gpu = CASE
        WHEN fs.tiempo_gpu <= CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END THEN 0
        ELSE CEIL((fs.tiempo_gpu - CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END)
                  / (CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.fraccion_minutos ELSE tg_gen.fraccion_minutos END))
    END
WHERE fs.tiempo_gpu IS NOT NULL
  AND fs.tiempo_gpu > 0
  AND CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END IS NOT NULL;

-- ────────────────────────────────────────────────────────────
-- 2. Corrección: Fracciones ADC GPU (filas "Agregar otro GPU")
-- ────────────────────────────────────────────────────────────
UPDATE flight_service_gpu_fracciones gf
JOIN flight_services fs ON fs.id = gf.flight_service_id
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp
    ON tg_esp.airline_id = fs.airline_id
   AND tg_esp.base_id = b.id
   AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen
    ON tg_gen.airline_id = fs.airline_id
   AND tg_gen.base_id IS NULL
   AND tg_gen.tipo_cobro = 'gpu'
SET gf.fracciones_adc = CASE
        WHEN gf.tiempo <= CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END THEN 0
        ELSE CEIL((gf.tiempo - CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END)
                  / (CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.fraccion_minutos ELSE tg_gen.fraccion_minutos END))
    END
WHERE gf.tiempo IS NOT NULL
  AND gf.tiempo > 0
  AND CASE WHEN tg_esp.id IS NOT NULL THEN tg_esp.primeros_minutos ELSE tg_gen.primeros_minutos END IS NOT NULL;

-- ────────────────────────────────────────────────────────────
-- 3. Corrección: Fracciones Hora ACU (servicio principal)
--    Con la regla nueva este campo siempre queda en 0 (el cobro fijo
--    al alcanzar el umbral ya no existe; ver comentario arriba).
--    "fracciones_15min_acu" NO se toca: su fórmula no tenía este bug.
-- ────────────────────────────────────────────────────────────
UPDATE flight_services fs
SET fs.fracciones_hora_acu = 0
WHERE fs.fracciones_hora_acu <> 0;

-- ────────────────────────────────────────────────────────────
-- 4. Corrección: Fracciones Hora ACU (filas "Agregar otro ACU")
-- ────────────────────────────────────────────────────────────
UPDATE flight_service_acu_fracciones af
SET af.fracciones_hora = 0
WHERE af.fracciones_hora <> 0;

-- ────────────────────────────────────────────────────────────
-- Revisar los resultados y luego COMMIT (o ROLLBACK si algo no
-- cuadra con las consultas de vista previa del paso 0).
-- ────────────────────────────────────────────────────────────
COMMIT;
-- ROLLBACK;
