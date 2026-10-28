-- ============================================================
-- MIGRACIÓN: Legalización de anticipo asociada a un comprobante de egreso
-- Fecha: 2026-10-28
-- ============================================================
-- egreso_id solo se usa en documentos tipo 'legalizacion_anticipo' y apunta
-- al comprobante de egreso al que pertenece. Si se elimina el egreso, la
-- legalización queda sin asociar (SET NULL).
-- ============================================================

ALTER TABLE `egresos_documentos`
    ADD COLUMN `egreso_id` INT UNSIGNED NULL AFTER `tipo`,
    ADD KEY `idx_egresos_egreso` (`egreso_id`),
    ADD CONSTRAINT `fk_egresos_egreso`
        FOREIGN KEY (`egreso_id`) REFERENCES `egresos_documentos` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL;
