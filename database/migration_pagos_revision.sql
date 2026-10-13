-- ============================================================
-- MIGRACIÓN: Flujo de revisión de pagos (Creador / Revisor / Lotes)
-- Fecha: 2026-10-13
-- ============================================================
--
-- Roles nuevos del módulo de Pagos a Proveedores:
--   * Creador Pagos : solo registra pagos (sin lote) y edita los suyos.
--   * Revisor Pagos : ve los pagos de los creadores y los aprueba/rechaza.
--   * Admin/Operador Pagos (existentes): crean lotes y arrastran SOLO los
--     pagos aprobados; los rechazados/pendientes no pueden moverse.
--
-- Los pagos que ya existen se marcan como 'aprobado' para no bloquear
-- el flujo actual. Los pagos creados por Admin/Operador Pagos se guardan
-- directamente como 'aprobado'.
-- ============================================================

INSERT INTO `roles` (`nombre`)
SELECT 'Creador Pagos' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `nombre` = 'Creador Pagos');

INSERT INTO `roles` (`nombre`)
SELECT 'Revisor Pagos' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `nombre` = 'Revisor Pagos');

ALTER TABLE `pagos`
    ADD COLUMN `estado_revision` ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente'
        COMMENT 'Resultado de la revisión del pago',
    ADD COLUMN `revisado_por` INT UNSIGNED NULL,
    ADD COLUMN `revisado_at` DATETIME NULL,
    ADD COLUMN `motivo_rechazo` VARCHAR(255) NULL;

UPDATE `pagos` SET `estado_revision` = 'aprobado';
