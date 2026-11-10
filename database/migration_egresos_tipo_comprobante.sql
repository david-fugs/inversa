-- Tipo "Comprobante de pago" en Egresos / Legalizaciones (igual que la legalizacion de anticipo)
-- y orden de los archivos de egresos dentro del PDF combinado.
-- Seguro de ejecutar aunque ya se haya corrido la version anterior ('comprobante').
ALTER TABLE `egresos_documentos`
    MODIFY COLUMN `tipo` ENUM('comprobante_egreso','legalizacion_anticipo','comprobante','comprobante_pago') NOT NULL;

UPDATE `egresos_documentos` SET `tipo` = 'comprobante_pago' WHERE `tipo` = 'comprobante' OR `tipo` = '';

ALTER TABLE `egresos_documentos`
    MODIFY COLUMN `tipo` ENUM('comprobante_egreso','legalizacion_anticipo','comprobante_pago') NOT NULL;

ALTER TABLE `egresos_archivos`
    ADD COLUMN `orden_pdf` INT NOT NULL DEFAULT 0;
