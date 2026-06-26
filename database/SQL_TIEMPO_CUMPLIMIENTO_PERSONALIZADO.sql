-- ============================================================
-- SQL: Agregar campo para tiempo de cumplimiento personalizado
-- Fecha: 2026-06-26
-- Descripción: Cuando se selecciona "Otra" aerolínea, se puede
--              ingresar un tiempo de cumplimiento personalizado
--              que se usará en todos los cálculos de horarios
-- ============================================================

-- Agregar columna para tiempo de cumplimiento personalizado
ALTER TABLE `flight_services`
ADD COLUMN `tiempo_cumplimiento_custom` TINYINT UNSIGNED NULL
COMMENT 'Tiempo de cumplimiento personalizado (en minutos) cuando se selecciona "Otra" aerolínea'
AFTER `aircraft_type_custom`;

-- Verificar que la columna fue creada correctamente
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'inversa'
  AND TABLE_NAME = 'flight_services'
  AND COLUMN_NAME = 'tiempo_cumplimiento_custom';
