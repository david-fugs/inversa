-- ============================================================
--  MIGRACIÓN: Agrega "Tipo de Identificación" a pagos
--  Se precarga del proveedor seleccionado, pero queda editable
--  (mismo patrón que banco_id / tipo_producto_id / numero_producto).
-- ============================================================
ALTER TABLE `pagos`
    ADD COLUMN `tipo_identificacion` VARCHAR(10) NOT NULL DEFAULT ''
    AFTER `proveedor_id`;
