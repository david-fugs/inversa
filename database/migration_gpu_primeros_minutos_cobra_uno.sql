-- ============================================================
-- MIGRACIÓN: GPU - el tramo de "Tarifa para los primeros" ahora SÍ se cobra
-- Fecha: 2026-10-21
-- ============================================================
--
-- NUEVA REGLA (ver public/js/app.js, calcularFraccionesGpuValor), igual que ACU:
--   tiempo <= 0                       -> 0
--   tiempo <= primeros_minutos        -> 1
--   tiempo  > primeros_minutos        -> 1 + CEIL((tiempo - primeros) / fraccion_minutos)
--   Ej. Avianca (70 / 15): 69 min = 1, 70 min = 1, 71 min = 2, 86 min = 3.
--
-- Regla anterior (la que se reemplaza): hasta primeros_minutos = 0 y luego
-- CEIL((tiempo - primeros) / fraccion). Es decir, cada resultado con tarifa
-- "primeros" sube exactamente +1.
--
-- ALCANCE
--   * Solo donde la tarifa GPU de la aerolínea/base tiene primeros_minutos.
--     Tarifas sin primeros_minutos (solo fracción) NO cambian.
--   * Recalcula flight_services.fracciones_adc_gpu y las filas de
--     "Agregar otro GPU" (flight_service_gpu_fracciones.fracciones_adc).
--   * No toca fracciones_adicionales_gpu.
--   * Por defecto NO toca los registros importados desde Excel
--     (import_id NOT NULL): el importador los guarda "tal cual" vienen del
--     Excel por indicación del cliente. Para incluirlos, cambiar
--     @incluir_importados a 1 en el paso 0.
--
-- Tarifa aplicable: la específica de aerolínea+base si existe; si no, la de
-- "todas las bases" (igual que TarifaGpu::findByAirlineBaseTipo).
--
-- Es idempotente: se calcula desde tiempo_gpu y la tarifa, no desde el valor actual.
--
-- Hacer respaldo antes:
--   mysqldump <BD> flight_services flight_service_gpu_fracciones > backup_fracciones_gpu.sql
-- ============================================================

-- 0. Parámetro: 0 = solo registros manuales, 1 = también importados de Excel
SET @incluir_importados = 0;

START TRANSACTION;

-- 1. Vista previa del servicio principal (solo filas que cambian)
SELECT fs.id, fs.import_id, fs.airline_id, fs.anio, fs.mes, fs.dia, fs.base, fs.tiempo_gpu,
       fs.fracciones_adc_gpu AS actual,
       CASE WHEN fs.tiempo_gpu <= IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) THEN 1
            ELSE 1 + CEIL((fs.tiempo_gpu - IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos))
                          / IF(tg_esp.id IS NOT NULL, tg_esp.fraccion_minutos, tg_gen.fraccion_minutos))
       END AS nuevo
FROM flight_services fs
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp ON tg_esp.airline_id = fs.airline_id AND tg_esp.base_id = b.id AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen ON tg_gen.airline_id = fs.airline_id AND tg_gen.base_id IS NULL AND tg_gen.tipo_cobro = 'gpu'
WHERE (fs.import_id IS NULL OR @incluir_importados = 1)
  AND fs.tiempo_gpu > 0
  AND IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) IS NOT NULL
HAVING actual <> nuevo
ORDER BY fs.id;

-- 2. GPU principal
UPDATE flight_services fs
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp ON tg_esp.airline_id = fs.airline_id AND tg_esp.base_id = b.id AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen ON tg_gen.airline_id = fs.airline_id AND tg_gen.base_id IS NULL AND tg_gen.tipo_cobro = 'gpu'
SET fs.fracciones_adc_gpu = CASE
        WHEN fs.tiempo_gpu <= IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) THEN 1
        ELSE 1 + CEIL((fs.tiempo_gpu - IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos))
                      / IF(tg_esp.id IS NOT NULL, tg_esp.fraccion_minutos, tg_gen.fraccion_minutos))
    END
WHERE (fs.import_id IS NULL OR @incluir_importados = 1)
  AND fs.tiempo_gpu > 0
  AND IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) IS NOT NULL;

-- 3. Filas "Agregar otro GPU" de esos mismos registros
UPDATE flight_service_gpu_fracciones gf
JOIN flight_services fs ON fs.id = gf.flight_service_id
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp ON tg_esp.airline_id = fs.airline_id AND tg_esp.base_id = b.id AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen ON tg_gen.airline_id = fs.airline_id AND tg_gen.base_id IS NULL AND tg_gen.tipo_cobro = 'gpu'
SET gf.fracciones_adc = CASE
        WHEN gf.tiempo <= IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) THEN 1
        ELSE 1 + CEIL((gf.tiempo - IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos))
                      / IF(tg_esp.id IS NOT NULL, tg_esp.fraccion_minutos, tg_gen.fraccion_minutos))
    END
WHERE (fs.import_id IS NULL OR @incluir_importados = 1)
  AND gf.tiempo > 0
  AND IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) IS NOT NULL;

-- 4. Verificación: no debe quedar ninguna fila con tiempo > 0 y fracciones = 0
--    en tarifas con primeros_minutos (dentro del alcance elegido).
SELECT COUNT(*) AS principales_en_cero
FROM flight_services fs
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp ON tg_esp.airline_id = fs.airline_id AND tg_esp.base_id = b.id AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen ON tg_gen.airline_id = fs.airline_id AND tg_gen.base_id IS NULL AND tg_gen.tipo_cobro = 'gpu'
WHERE (fs.import_id IS NULL OR @incluir_importados = 1)
  AND fs.tiempo_gpu > 0 AND fs.fracciones_adc_gpu < 1
  AND IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) IS NOT NULL;

-- Revisar la vista previa y la verificación, luego COMMIT (o ROLLBACK)
COMMIT;
-- ROLLBACK;
