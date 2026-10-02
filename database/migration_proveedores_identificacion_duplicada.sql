-- Permite varios proveedores con el mismo número de identificación
ALTER TABLE `proveedores`
    DROP INDEX `uq_proveedores_numero_identificacion`,
    ADD INDEX `idx_proveedores_numero_identificacion` (`numero_identificacion`);
