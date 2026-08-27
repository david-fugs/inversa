-- ============================================================
-- MIGRACIÓN: Tarifas / Cobros GPU por aerolínea Y base
-- Fecha: 2026-08-27
-- Descripción: Agrega la columna `base_id` a `tarifas_gpu` para
--              poder configurar una tarifa distinta por aerolínea
--              según la base, además de la ya existente por
--              aerolínea.
--
--              - base_id = NULL  → la tarifa aplica a "Todas las
--                bases" para esa aerolínea (comportamiento anterior).
--              - base_id = N     → la tarifa aplica únicamente a la
--                aerolínea + esa base específica. Tiene prioridad
--                sobre la tarifa de "Todas las bases" de la misma
--                aerolínea si ambas existen.
--
--              La unicidad de la combinación aerolínea + base se
--              valida en la aplicación (TarifasGpuController), igual
--              que en la migración original, ya que MySQL no aplica
--              unicidad estricta sobre columnas NULL en índices
--              compuestos.
--
--              IMPORTANTE: ejecutar dentro de una transacción o hacer
--              respaldo de la tabla `tarifas_gpu` antes de correr en
--              producción.
-- ============================================================

-- 1) Agregar la columna base_id (nullable = "todas las bases")
ALTER TABLE `tarifas_gpu`
    ADD COLUMN `base_id` INT UNSIGNED NULL
        COMMENT 'NULL = aplica a todas las bases de la aerolínea'
        AFTER `airline_id`;

-- 2) Foreign key hacia bases (RESTRICT: no se puede borrar una base
--    que tenga tarifas configuradas específicamente para ella)
ALTER TABLE `tarifas_gpu`
    ADD CONSTRAINT `fk_tarifas_gpu_base`
        FOREIGN KEY (`base_id`) REFERENCES `bases` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- 3) La tabla original tenía UNIQUE KEY (airline_id) — una sola tarifa
--    por aerolínea. Ahora puede haber varias (una por base + una para
--    "todas las bases"), así que se reemplaza por un índice normal
--    para acelerar las búsquedas por aerolínea/base.
ALTER TABLE `tarifas_gpu`
    DROP INDEX `uq_tarifas_gpu_airline`,
    ADD INDEX `idx_tarifas_gpu_airline_base` (`airline_id`, `base_id`);

-- 4) Registros existentes en producción: dejarlos explícitamente como
--    "Todas las bases" (ya quedan así por el DEFAULT NULL del ALTER
--    anterior; este UPDATE es solo por seguridad/idempotencia).
UPDATE `tarifas_gpu` SET `base_id` = NULL WHERE `base_id` IS NOT NULL;

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
