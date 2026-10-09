-- ============================================================
-- MIGRACIÓN: Permiso de edición limitado a un servicio de vuelo
-- Fecha: 2026-10-08
-- ============================================================
--
-- Al dar "permiso de edición" a un Colaborador se puede indicar,
-- opcionalmente, el ID de un servicio de vuelo. Si tiene valor, el
-- usuario solo podrá editar ese servicio; si es NULL, puede editar todos.
-- ============================================================
ALTER TABLE `users`
    ADD COLUMN `puede_editar_servicio_id` INT NULL DEFAULT NULL
        COMMENT 'Si no es NULL, el colaborador solo puede editar este servicio de vuelo';
