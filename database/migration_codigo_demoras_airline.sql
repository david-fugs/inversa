-- ============================================================
-- MIGRACIÓN: Asociar Código Demoras a una aerolínea
-- Fecha: 2026-09-30
-- Descripción: Agrega `airline_id` a `codigo_demoras` para que cada
--              código pertenezca a una aerolínea (por ahora AVIANCA,
--              CLIC y SATENA). Se aseguran las 3 aerolíneas en el
--              catálogo `airlines`.
--
--              Los códigos existentes quedan con airline_id NULL
--              ("Sin aerolínea") hasta que se les asigne una desde
--              el módulo Código Demoras.
-- ============================================================

INSERT IGNORE INTO `airlines` (`nombre`) VALUES ('AVIANCA'), ('CLIC'), ('SATENA');

ALTER TABLE `codigo_demoras`
    ADD COLUMN `airline_id` INT UNSIGNED NULL
        COMMENT 'FK a airlines; NULL = códigos previos sin asignar'
        AFTER `descripcion`;

ALTER TABLE `codigo_demoras`
    ADD CONSTRAINT `fk_codigo_demoras_airline`
        FOREIGN KEY (`airline_id`) REFERENCES `airlines` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL;

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
