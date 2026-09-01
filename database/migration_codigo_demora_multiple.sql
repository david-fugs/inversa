-- ============================================================
-- MIGRACIÓN: Código Demora múltiple
-- Fecha: 2026-09-01
-- Descripción: El formulario de Servicios de Vuelo ahora permite
--              seleccionar varios códigos de demora a la vez (se
--              guardan en `codigo_demora` separados por " - ",
--              por ejemplo "PE45 - AB12"). Se amplía la columna
--              para que no trunque combinaciones de varios códigos.
-- ============================================================

ALTER TABLE `flight_services`
    MODIFY COLUMN `codigo_demora` VARCHAR(191) NULL;

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
