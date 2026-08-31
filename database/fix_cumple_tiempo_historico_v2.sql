-- ============================================================
-- FIX v2: Recalcular cumple_tiempo para registros históricos
-- Fecha: 2026-08-31
-- Motivo: La fórmula anterior (ver fix_cumple_tiempo_historico.sql)
--         anclaba el tránsito a la hora de llegada (real o
--         itinerada, la que fuera más tardía) y nunca consideraba
--         la hora itinerada de SALIDA. La nueva regla dice que
--         SOLO es demora cuando ocurren ambas cosas a la vez:
--           - Salió tarde  (hora_real_salida > hora_itinerada_salida)
--           - El tránsito real (llegada real -> salida real) excede
--             el tiempo de cumplimiento del tipo de avión (o el
--             personalizado si la aerolínea es "Otra").
--         En cualquier otro caso (salió a tiempo/antes, o el
--         tránsito no excede el estipulado) se considera que SÍ
--         cumple, aunque haya llegado tarde o el tránsito interno
--         sea mayor al estipulado.
-- ============================================================

-- 0) Backup de seguridad antes de tocar datos existentes
CREATE TABLE IF NOT EXISTS flight_services_backup_cumple_tiempo_20260831 AS
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
    fs.hora_itinerada_salida, fs.hora_real_salida,
    fs.tiempo_transito,
    COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom) AS tiempo_permitido,
    fs.cumple_tiempo AS cumple_actual,
    IF(
        TIME_TO_SEC(fs.hora_real_salida) > TIME_TO_SEC(fs.hora_itinerada_salida)
        AND fs.tiempo_transito > COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom),
        0, 1
    ) AS cumple_correcto
FROM flight_services fs
LEFT JOIN aircraft_types at ON fs.aircraft_type_id = at.id
WHERE fs.hora_itinerada_salida IS NOT NULL
  AND fs.hora_real_salida      IS NOT NULL
  AND fs.tiempo_transito       IS NOT NULL
  AND COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom) IS NOT NULL
HAVING cumple_actual <> cumple_correcto OR cumple_actual IS NULL;

-- ============================================================
-- 2) UPDATE: recalcular cumple_tiempo con la fórmula correcta
-- ============================================================
UPDATE flight_services fs
LEFT JOIN aircraft_types at ON fs.aircraft_type_id = at.id
SET fs.cumple_tiempo = IF(
    TIME_TO_SEC(fs.hora_real_salida) > TIME_TO_SEC(fs.hora_itinerada_salida)
    AND fs.tiempo_transito > COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom),
    0, 1
)
WHERE fs.hora_itinerada_salida IS NOT NULL
  AND fs.hora_real_salida      IS NOT NULL
  AND fs.tiempo_transito       IS NOT NULL
  AND COALESCE(at.tiempo_cumplimiento, fs.tiempo_cumplimiento_custom) IS NOT NULL;

-- ============================================================
-- 3) VERIFICACIÓN: cuántos registros cambiaron de valor
-- ============================================================
SELECT
    b.id,
    b.cumple_tiempo AS cumple_anterior,
    fs.cumple_tiempo AS cumple_nuevo
FROM flight_services_backup_cumple_tiempo_20260831 b
JOIN flight_services fs ON fs.id = b.id
WHERE b.cumple_tiempo <> fs.cumple_tiempo
   OR (b.cumple_tiempo IS NULL AND fs.cumple_tiempo IS NOT NULL)
   OR (b.cumple_tiempo IS NOT NULL AND fs.cumple_tiempo IS NULL);
