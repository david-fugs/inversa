-- ============================================================
-- CORRECCIÓN: Fracciones ADC GPU de registros creados desde el formulario
--             con la regla antigua (cobraban 1 fracción desde el primer minuto)
-- ============================================================
--
-- Regla correcta (ver public/js/app.js, calcularFraccionesGpuValor):
--   tiempo <= primeros_minutos       -> 0 fracciones
--   tiempo  > primeros_minutos       -> CEIL((tiempo - primeros) / fraccion_minutos)
--   Ej. Avianca (70 / 15): 38 min = 0, 71 min = 1, 86 min = 2.
--
-- ALCANCE (a diferencia de fix_fracciones_primeros_minutos.sql):
--   * Solo registros creados a mano desde el formulario: import_id IS NULL
--     y creados ANTES del 2026-09-17 (fecha de la carga masiva histórica).
--   * NO toca los registros importados desde Excel (import_id NOT NULL) ni la
--     carga histórica del 2026-09-17: el importador los guarda "tal cual" vienen
--     del Excel por indicación del cliente.
--   * Solo corrige fracciones_adc_gpu (y las filas de "Agregar otro GPU");
--     no toca fracciones_adicionales_gpu.
--   * Solo donde la tarifa GPU de esa aerolínea/base tiene primeros_minutos.
--
-- Tarifa aplicable: la específica de aerolínea+base si existe; si no, la de
-- "todas las bases" (igual que TarifaGpu::findByAirlineBaseTipo).
--
-- Hacer respaldo antes:
--   mysqldump <BD> flight_services flight_service_gpu_fracciones > backup_fracciones_gpu.sql
-- ============================================================

START TRANSACTION;

-- 0. Vista previa de lo que cambia
SELECT fs.id, fs.anio, fs.mes, fs.dia, fs.base, fs.tiempo_gpu,
       fs.fracciones_adc_gpu AS actual,
       CASE WHEN fs.tiempo_gpu <= IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) THEN 0
            ELSE CEIL((fs.tiempo_gpu - IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos))
                      / IF(tg_esp.id IS NOT NULL, tg_esp.fraccion_minutos, tg_gen.fraccion_minutos))
       END AS correcto
FROM flight_services fs
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp ON tg_esp.airline_id = fs.airline_id AND tg_esp.base_id = b.id AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen ON tg_gen.airline_id = fs.airline_id AND tg_gen.base_id IS NULL AND tg_gen.tipo_cobro = 'gpu'
WHERE fs.import_id IS NULL
  AND fs.created_at < '2026-09-17'
  AND fs.tiempo_gpu > 0
  AND IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) IS NOT NULL
HAVING actual <> correcto
ORDER BY fs.id;

-- 1. GPU principal
UPDATE flight_services fs
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp ON tg_esp.airline_id = fs.airline_id AND tg_esp.base_id = b.id AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen ON tg_gen.airline_id = fs.airline_id AND tg_gen.base_id IS NULL AND tg_gen.tipo_cobro = 'gpu'
SET fs.fracciones_adc_gpu = CASE
        WHEN fs.tiempo_gpu <= IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) THEN 0
        ELSE CEIL((fs.tiempo_gpu - IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos))
                  / IF(tg_esp.id IS NOT NULL, tg_esp.fraccion_minutos, tg_gen.fraccion_minutos))
    END
WHERE fs.import_id IS NULL
  AND fs.created_at < '2026-09-17'
  AND fs.tiempo_gpu > 0
  AND IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) IS NOT NULL;

-- 2. Filas "Agregar otro GPU" de esos mismos registros
UPDATE flight_service_gpu_fracciones gf
JOIN flight_services fs ON fs.id = gf.flight_service_id
LEFT JOIN bases b ON b.nombre = fs.base
LEFT JOIN tarifas_gpu tg_esp ON tg_esp.airline_id = fs.airline_id AND tg_esp.base_id = b.id AND tg_esp.tipo_cobro = 'gpu'
LEFT JOIN tarifas_gpu tg_gen ON tg_gen.airline_id = fs.airline_id AND tg_gen.base_id IS NULL AND tg_gen.tipo_cobro = 'gpu'
SET gf.fracciones_adc = CASE
        WHEN gf.tiempo <= IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) THEN 0
        ELSE CEIL((gf.tiempo - IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos))
                  / IF(tg_esp.id IS NOT NULL, tg_esp.fraccion_minutos, tg_gen.fraccion_minutos))
    END
WHERE fs.import_id IS NULL
  AND fs.created_at < '2026-09-17'
  AND gf.tiempo > 0
  AND IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos) IS NOT NULL;

-- Revisar y luego COMMIT (o ROLLBACK)
COMMIT;
-- ROLLBACK;
