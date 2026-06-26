-- ============================================================
-- MIGRACIÓN: Actualización Flight Services
-- Fecha: 2026-06-26
-- Descripción: Agregar campos para tipo de avión personalizado
--              y hora de cierre de módulo SATENA
-- ============================================================

-- 1. Agregar columna para tipo de avión personalizado (cuando se elige "otro")
ALTER TABLE `flight_services`
ADD COLUMN `aircraft_type_custom` VARCHAR(100) NULL
COMMENT 'Tipo de avión personalizado cuando se selecciona "Otro especificar"'
AFTER `aircraft_type_id`;

-- 2. Agregar columna para hora de cierre de módulo SATENA
ALTER TABLE `flight_services`
ADD COLUMN `satena_hora_cierre_modulo` TIME NULL
COMMENT 'Hora de cierre en módulo para SATENA'
AFTER `hora_itinerada_salida`;

-- ============================================================
-- ÍNDICES OPCIONALES (para mejor rendimiento)
-- ============================================================
-- ALTER TABLE `flight_services`
-- ADD KEY `idx_fs_aircraft_type_custom` (`aircraft_type_custom`);

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
