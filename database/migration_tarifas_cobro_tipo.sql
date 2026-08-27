-- ============================================================
-- MIGRACIÓN: Tipo de cobro en Tarifas / Cobros (GPU / ACU)
-- Fecha: 2026-08-27
-- Descripción: `tarifas_gpu` deja de ser exclusiva de la planta
--              eléctrica (GPU); ahora también sirve para configurar
--              la tarifa de Aire Acondicionado (ACU) por aerolínea
--              y base. Se agrega la columna `tipo_cobro` para
--              distinguir cuál de las dos es cada registro.
--
--              - tipo_cobro = 'gpu' → Planta eléctrica (comportamiento
--                y datos existentes, quedan así por el DEFAULT).
--              - tipo_cobro = 'acu' → Aire acondicionado.
--
--              Con esto, una misma combinación aerolínea + base puede
--              tener HASTA DOS tarifas: una de tipo 'gpu' y otra de
--              tipo 'acu'. La unicidad de aerolínea + base + tipo se
--              sigue validando en la aplicación (TarifasGpuController),
--              igual que en las migraciones anteriores de esta tabla.
--
--              IMPORTANTE: ejecutar dentro de una transacción o hacer
--              respaldo de la tabla `tarifas_gpu` antes de correr en
--              producción. Requiere haber aplicado antes
--              migration_tarifas_gpu_bases.sql (columna base_id).
-- ============================================================

-- 1) Agregar la columna tipo_cobro (todo lo existente hasta hoy es GPU)
ALTER TABLE `tarifas_gpu`
    ADD COLUMN `tipo_cobro` ENUM('gpu','acu') NOT NULL DEFAULT 'gpu'
        COMMENT 'gpu = Planta eléctrica, acu = Aire acondicionado'
        AFTER `base_id`;

-- 2) El índice de búsqueda (airline_id, base_id) pasa a incluir el tipo,
--    ya que ahora puede haber una tarifa GPU y otra ACU para la misma
--    combinación aerolínea + base.
ALTER TABLE `tarifas_gpu`
    DROP INDEX `idx_tarifas_gpu_airline_base`,
    ADD INDEX `idx_tarifas_gpu_airline_base_tipo` (`airline_id`, `base_id`, `tipo_cobro`);

-- 3) Registros existentes: dejarlos explícitamente como 'gpu' (ya
--    quedan así por el DEFAULT del ALTER anterior; este UPDATE es
--    solo por seguridad/idempotencia).
UPDATE `tarifas_gpu` SET `tipo_cobro` = 'gpu' WHERE `tipo_cobro` <> 'gpu';

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
