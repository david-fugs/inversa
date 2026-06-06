-- ============================================================
-- MIGRACIONES NUEVAS - Flight Services Avanzado
-- Agregar campos para:
-- 1. Código y Observación de demora cuando NO cumple tiempo
-- 2. Campo RPN cuando afecta la operación
-- 3. Sistema completo de Ventiladores (similar a ACU)
-- ============================================================

-- ════════════════════════════════════════════════════════════
-- 1. AGREGAR COLUMNAS A flight_services
-- ════════════════════════════════════════════════════════════

ALTER TABLE `flight_services` ADD COLUMN `codigo_demora` VARCHAR(20) NULL AFTER `cumple_tiempo` COMMENT 'Código de demora cuando NO cumple tiempo';

ALTER TABLE `flight_services` ADD COLUMN `observacion_demora` TEXT NULL AFTER `codigo_demora` COMMENT 'Observación de demora cuando NO cumple tiempo';

ALTER TABLE `flight_services` ADD COLUMN `rpn` VARCHAR(50) NULL AFTER `afecto_operacion` COMMENT 'RPN cuando Afectó la operación = Sí';

-- Columnas para Ventiladores (similar a ACU)
ALTER TABLE `flight_services` ADD COLUMN `ventiladores_activo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fracciones_15min_acu` COMMENT 'Similar a ACU: 1=Sí, 0=No';

ALTER TABLE `flight_services` ADD COLUMN `hora_conexion_ventiladores` TIME NULL AFTER `ventiladores_activo`;

ALTER TABLE `flight_services` ADD COLUMN `hora_desconexion_ventiladores` TIME NULL AFTER `hora_conexion_ventiladores`;

ALTER TABLE `flight_services` ADD COLUMN `tiempo_ventiladores` SMALLINT UNSIGNED NULL AFTER `hora_desconexion_ventiladores` COMMENT 'Tiempo en minutos';

ALTER TABLE `flight_services` ADD COLUMN `fracciones_hora_ventiladores` DECIMAL(8,2) NULL DEFAULT 0 AFTER `tiempo_ventiladores` COMMENT 'Fracciones por hora';

ALTER TABLE `flight_services` ADD COLUMN `fracciones_15min_ventiladores` DECIMAL(8,2) NULL DEFAULT 0 AFTER `fracciones_hora_ventiladores` COMMENT 'Fracciones por 15 minutos';

-- ════════════════════════════════════════════════════════════
-- 2. CREAR TABLA PARA FRACCIONES DE VENTILADORES
-- ════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `flight_service_ventiladores_fracciones` (
    `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `flight_service_id`   INT UNSIGNED  NOT NULL,
    `hora_conexion`       TIME          NULL,
    `hora_desconexion`    TIME          NULL,
    `tiempo`              SMALLINT UNSIGNED NULL,
    `fracciones_hora`     DECIMAL(8,2)  NULL DEFAULT 0,
    `fracciones_15min`    DECIMAL(8,2)  NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `fk_ventiladores_fs` (`flight_service_id`),
    CONSTRAINT `fk_ventiladores_fs`
        FOREIGN KEY (`flight_service_id`) REFERENCES `flight_services` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fracciones adicionales de Ventiladores';

-- ════════════════════════════════════════════════════════════
-- FIN DE MIGRACIONES
-- ════════════════════════════════════════════════════════════
