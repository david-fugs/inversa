-- ============================================================
-- MIGRACIÓN: Catálogo de Código Demoras
-- Fecha: 2026-08-28
-- Descripción: Crea la tabla `codigo_demoras` (catálogo de códigos
--              de demora con su descripción) y agrega la columna
--              `codigo_demora_id` a `flight_services` para que el
--              campo "Código Demora" del formulario de servicios de
--              vuelo seleccione un código del catálogo en lugar de
--              texto libre.
--
--              La columna `codigo_demora` (texto, ya existente en
--              flight_services) se conserva sin cambios por
--              compatibilidad con datos históricos y reportes; al
--              guardar un servicio se sincroniza automáticamente
--              con el código del catálogo seleccionado.
-- ============================================================

CREATE TABLE IF NOT EXISTS `codigo_demoras` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`      VARCHAR(20)  NOT NULL,
    `descripcion` VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_codigo_demoras_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `flight_services`
    ADD COLUMN `codigo_demora_id` INT UNSIGNED NULL
        COMMENT 'FK a codigo_demoras; codigo_demora (texto) se conserva por compatibilidad histórica'
        AFTER `codigo_demora`;

ALTER TABLE `flight_services`
    ADD CONSTRAINT `fk_fs_codigo_demora`
        FOREIGN KEY (`codigo_demora_id`) REFERENCES `codigo_demoras` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL;

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
