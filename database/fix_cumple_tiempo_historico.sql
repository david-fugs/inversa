-- ============================================================
-- FIX: Recalcular cumple_tiempo para registros históricos
-- Fecha: 2026-07-29
-- Motivo: La fórmula anterior calculaba cumple_tiempo solo con
--         la demora de LLEGADA (hora_real_llegada vs
--         hora_itinerada_llegada) y no consideraba la hora de
--         SALIDA real en absoluto. La fórmula correcta ancla el
--         tránsito a la hora itinerada de llegada cuando el vuelo
--         llegó anticipado (no regala minutos extra de plataforma)
--         y a la hora real de llegada cuando llegó tarde, y compara
--         ese tránsito efectivo (hora_real_salida - referencia)
--         contra el tiempo de cumplimiento del tipo de avión (o el
--         personalizado si la aerolínea es "Otra").
-- ============================================================

-- 0) Backup de seguridad antes de tocar datos existentes
CREATE TABLE IF NOT EXISTS flight_services_backup_cumple_tiempo_20260729 AS
SELECT id, cumple_tiempo, tiempo_transito, demora_llegando
FROM flight_services;

-- ============================================================
-- 1) VISTA PREVIA: registros cuyo cumple_tiempo cambiará
--    (ejecutar y revisar ANTES del UPDATE)
-- ============================================================
SELECT
    fs.id,
    fs.anio, fs.mes, fs.dia, fs.base,
    fs.vuelo_llegando, fs.vuelo_saliendo, fs.matricula,
    fs.hora_itinerada_llegada, fs.hora_real_llegada,
    fs.hora_itinerada_salida, fs.hora_real_salida,
    COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom) AS tiempo_permitido,
    fs.cumple_tiempo AS cumple_actual,
    IF(
        (
            TIME_TO_SEC(fs.hora_real_salida)
            - GREATEST(TIME_TO_SEC(fs.hora_real_llegada), TIME_TO_SEC(fs.hora_itinerada_llegada))
            + IF(
                TIME_TO_SEC(fs.hora_real_salida) < GREATEST(TIME_TO_SEC(fs.hora_real_llegada), TIME_TO_SEC(fs.hora_itinerada_llegada)),
                86400, 0
              )
        ) DIV 60
        <= COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom),
        1, 0
    ) AS cumple_correcto
FROM flight_services fs
LEFT JOIN aircraft_types at ON fs.aircraft_type_id = at.id
WHERE fs.hora_itinerada_llegada IS NOT NULL
  AND fs.hora_real_llegada  IS NOT NULL
  AND fs.hora_real_salida   IS NOT NULL
  AND COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom) IS NOT NULL
HAVING cumple_actual <> cumple_correcto OR cumple_actual IS NULL;

-- ============================================================
-- 2) UPDATE: recalcular cumple_tiempo con la fórmula correcta
-- ============================================================
UPDATE flight_services fs
LEFT JOIN aircraft_types at ON fs.aircraft_type_id = at.id
SET fs.cumple_tiempo = IF(
    (
        TIME_TO_SEC(fs.hora_real_salida)
        - GREATEST(TIME_TO_SEC(fs.hora_real_llegada), TIME_TO_SEC(fs.hora_itinerada_llegada))
        + IF(
            TIME_TO_SEC(fs.hora_real_salida) < GREATEST(TIME_TO_SEC(fs.hora_real_llegada), TIME_TO_SEC(fs.hora_itinerada_llegada)),
            86400, 0
          )
    ) DIV 60
    <= COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom),
    1, 0
)
WHERE fs.hora_itinerada_llegada IS NOT NULL
  AND fs.hora_real_llegada  IS NOT NULL
  AND fs.hora_real_salida   IS NOT NULL
  AND COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom) IS NOT NULL;

-- ============================================================
-- 3) VERIFICACIÓN: cuántos registros cambiaron de valor
-- ============================================================
SELECT
    b.id,
    b.cumple_tiempo AS cumple_anterior,
    fs.cumple_tiempo AS cumple_nuevo
FROM flight_services_backup_cumple_tiempo_20260729 b
JOIN flight_services fs ON fs.id = b.id
WHERE b.cumple_tiempo <> fs.cumple_tiempo
   OR (b.cumple_tiempo IS NULL AND fs.cumple_tiempo IS NOT NULL)
   OR (b.cumple_tiempo IS NOT NULL AND fs.cumple_tiempo IS NULL);
