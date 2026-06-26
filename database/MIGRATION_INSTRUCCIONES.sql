-- ============================================================
-- MIGRACIÓN: Actualización Flight Services - Cambios Solicitados
-- Fecha: 2026-06-26
-- ============================================================
--
-- CAMBIOS REALIZADOS:
-- 1. Agregar campo para tipo de avión personalizado (cuando se elige "Otro")
-- 2. Agregar campo para hora de cierre de módulo SATENA
-- 3. Lógica automática de Despacho (solo SI si es AVIANCA)
-- 4. Lógica de cumplimiento considerando llegadas anticipadas
--
-- ============================================================

-- ════════════════════════════════════════════════════════════
-- 1. AGREGAR CAMPOS A LA TABLA flight_services
-- ════════════════════════════════════════════════════════════

-- Columna para tipo de avión personalizado cuando se selecciona "Otro especificar"
ALTER TABLE `flight_services`
ADD COLUMN IF NOT EXISTS `aircraft_type_custom` VARCHAR(100) NULL
COMMENT 'Tipo de avión personalizado cuando se selecciona "Otro especificar"'
AFTER `aircraft_type_id`;

-- Columna para hora de cierre en módulo SATENA
ALTER TABLE `flight_services`
ADD COLUMN IF NOT EXISTS `satena_hora_cierre_modulo` TIME NULL
COMMENT 'Hora de cierre en módulo para SATENA'
AFTER `hora_itinerada_salida`;

-- ════════════════════════════════════════════════════════════
-- 2. NOTAS IMPORTANTES
-- ════════════════════════════════════════════════════════════
--
-- LÓGICA IMPLEMENTADA EN EL FORMULARIO (JavaScript):
--
-- a) Campo "Otro especificar" en Aerolínea:
--    - Cuando se selecciona "Otro (especificar)" en la aerolínea
--    - Se muestra un campo de texto para escribir el tipo de avión
--    - El campo "Tipo de Avión" queda deshabilitado
--    - Al seleccionar otra aerolínea, el campo vuelve a habilitarse
--
-- b) Despacho automático (solo AVIANCA):
--    - Si se selecciona AVIANCA → Despacho = SI
--    - Si se selecciona otra aerolínea → Despacho = NO
--    - El campo es solo lectura
--
-- c) Hora de cierre de módulo SATENA:
--    - Campo de tipo TIME que solo aparece cuando se selecciona SATENA
--    - Se oculta automáticamente si se cambia a otra aerolínea
--
-- d) Cumplimiento con llegadas anticipadas:
--    - Cuando hora_real_llegada < hora_itinerada_llegada
--    - Se considera como CUMPLIMIENTO (no como demora)
--    - demora_llegando se establece en 0
--    - cumple_tiempo se establece en 1 (SI)
--
-- ════════════════════════════════════════════════════════════
-- 3. CAMPOS GUARDADOS EN BASE DE DATOS
-- ════════════════════════════════════════════════════════════
--
-- aircraft_type_custom:         Texto del tipo de avión personalizado
-- satena_hora_cierre_modulo:    Hora de cierre de módulo (formato TIME)
-- despacho:                      Actualizado automáticamente según aerolínea
-- cumple_tiempo:                 Lógica mejorada con llegadas anticipadas
--
-- ════════════════════════════════════════════════════════════
-- 4. VALIDACIÓN DE DATOS
-- ════════════════════════════════════════════════════════════
--
-- aircraft_type_custom:
--   - Se valida que si airline_id = 'otro', este campo sea obligatorio
--   - Si se elige una aerolínea normal, aircraft_type_id debe ser obligatorio
--
-- satena_hora_cierre_modulo:
--   - Es opcional
--   - Solo tiene sentido si la aerolínea es SATENA
--   - Tipo TIME (HH:MM)
--
-- ════════════════════════════════════════════════════════════
-- 5. ARCHIVOS MODIFICADOS
-- ════════════════════════════════════════════════════════════
--
-- 1. app/views/flight_services/edit.php
--    - Agregado campo "aircraft_type_custom"
--    - Agregado campo "satena_hora_cierre_modulo"
--    - Actualizado JavaScript para lógica automática
--    - Mejora en cálculo de cumplimiento
--
-- 2. app/controllers/FlightServicesController.php
--    - Agregado recolección de satena_hora_cierre_modulo en collectFormData()
--
-- 3. app/models/FlightService.php
--    - Actualizado método create() para insertar nuevo campo
--    - Actualizado método update() para actualizar nuevo campo
--
-- ════════════════════════════════════════════════════════════
-- FIN DE LA MIGRACIÓN
-- ════════════════════════════════════════════════════════════
