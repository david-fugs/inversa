-- Orden de cada PDF dentro del PDF combinado de un lote.
-- 0 = sin orden definido (va al final, por pago y orden de carga).
ALTER TABLE `pago_comprobantes`
    ADD COLUMN `orden_pdf` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `orden`;
