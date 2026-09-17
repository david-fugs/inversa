-- ============================================================
-- Vincula cada flight_service con la importación de Excel que lo
-- creó, para poder borrar en bloque todos los registros de una
-- importación (botón "Eliminar" en el historial de importaciones).
-- ============================================================

ALTER TABLE `flight_services`
    ADD COLUMN `import_id` INT UNSIGNED NULL AFTER `id`,
    ADD KEY `fk_fs_import` (`import_id`),
    ADD CONSTRAINT `fk_fs_import`
        FOREIGN KEY (`import_id`) REFERENCES `flight_service_imports` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE;
