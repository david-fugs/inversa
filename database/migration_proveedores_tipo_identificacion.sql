-- ============================================================
--  MIGRACIÓN: Agrega "Tipo de Identificación" a proveedores
--  Campo de texto libre (letras y números), ej: 01, CC, NIT.
-- ============================================================
ALTER TABLE `proveedores`
    ADD COLUMN `tipo_identificacion` VARCHAR(10) NOT NULL DEFAULT ''
    AFTER `id`;
