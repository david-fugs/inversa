-- ============================================================
-- MIGRACIÓN: Permiso de edición limitado a hasta 4 servicios de vuelo
-- ============================================================
-- Ahora `puede_editar_servicio_id` guarda los IDs separados por comas
-- (ej. '11382,11395'), máximo 4. NULL = puede editar todos.
-- Los valores existentes (un solo ID) siguen siendo válidos.
-- ============================================================
ALTER TABLE `users`
    MODIFY COLUMN `puede_editar_servicio_id` VARCHAR(60) NULL DEFAULT NULL
        COMMENT 'IDs de servicios de vuelo editables separados por coma (máx. 4); NULL = todos';
