-- ============================================================
--  Migration: Agregar campo de observaciones generales
--  Agregado: 2026-06-22
-- ============================================================

ALTER TABLE flight_services
ADD COLUMN observaciones TEXT NULL COMMENT 'Observaciones generales del servicio'
AFTER equipo_gse_inoperativo;

-- Columna para aerolínea personalizada (cuando se selecciona "Otra")
ALTER TABLE flight_services
ADD COLUMN airline_custom_nombre VARCHAR(120) NULL COMMENT 'Nombre de aerolínea cuando se selecciona Otra'
AFTER airline_id;

ALTER TABLE flight_services
ADD COLUMN aircraft_type_custom VARCHAR(100) NULL COMMENT 'Tipo de avión personalizado cuando se selecciona Otra'
AFTER aircraft_type_id;

-- Ventiladores como sección separada (agregar columna activo si no existe)
ALTER TABLE flight_services
ADD COLUMN ventiladores_activo TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Ventiladores activados'
AFTER tiempo_acu;

ALTER TABLE flight_services
ADD COLUMN hora_conexion_ventiladores TIME NULL AFTER ventiladores_activo;

ALTER TABLE flight_services
ADD COLUMN hora_desconexion_ventiladores TIME NULL AFTER hora_conexion_ventiladores;

ALTER TABLE flight_services
ADD COLUMN tiempo_ventiladores SMALLINT UNSIGNED NULL AFTER hora_desconexion_ventiladores;

ALTER TABLE flight_services
ADD COLUMN fracciones_hora_ventiladores DECIMAL(8,2) NULL DEFAULT 0 AFTER tiempo_ventiladores;

ALTER TABLE flight_services
ADD COLUMN fracciones_15min_ventiladores DECIMAL(8,2) NULL DEFAULT 0 AFTER fracciones_hora_ventiladores;

-- Crear tabla para fracciones de ventiladores si no existe
CREATE TABLE IF NOT EXISTS `flight_service_ventiladores_fracciones` (
    `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `flight_service_id`   INT UNSIGNED  NOT NULL,
    `hora_conexion`       TIME          NULL,
    `hora_desconexion`    TIME          NULL,
    `tiempo`              SMALLINT UNSIGNED NULL,
    `fracciones_hora`     DECIMAL(8,2)  NULL DEFAULT 0,
    `fracciones_15min`    DECIMAL(8,2)  NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `fk_vent_fs` (`flight_service_id`),
    CONSTRAINT `fk_vent_fs`
        FOREIGN KEY (`flight_service_id`) REFERENCES `flight_services` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
