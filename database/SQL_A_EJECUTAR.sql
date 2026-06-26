-- ============================================================
-- SQL A EJECUTAR PARA LA MIGRACIÓN
-- ============================================================
-- Copiar y ejecutar en PHPMyAdmin o cliente MySQL
-- ============================================================

-- Agregar columna para tipo de avión personalizado
ALTER TABLE `flight_services`
ADD COLUMN `aircraft_type_custom` VARCHAR(100) NULL
COMMENT 'Tipo de avión personalizado cuando se selecciona "Otro especificar"'
AFTER `aircraft_type_id`;

-- Agregar columna para hora de cierre de módulo SATENA
ALTER TABLE `flight_services`
ADD COLUMN `satena_hora_cierre_modulo` TIME NULL
COMMENT 'Hora de cierre en módulo para SATENA'
AFTER `hora_itinerada_salida`;

-- Verificar que los campos fueron creados
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'inversa'
  AND TABLE_NAME = 'flight_services'
  AND COLUMN_NAME IN ('aircraft_type_custom', 'satena_hora_cierre_modulo')
ORDER BY ORDINAL_POSITION;
