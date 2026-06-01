-- ============================================================
--  INVERSA - Plataforma de Operaciones Aeroportuarias
--  Script SQL - Base de Datos Completa
--  Versión: 1.0.0
-- ============================================================



-- ============================================================
-- TABLA: roles
-- ============================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nombre`     VARCHAR(60)     NOT NULL,
    `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `nombre_completo` VARCHAR(120)  NOT NULL,
    `cedula`          VARCHAR(20)   NOT NULL,
    `usuario`         VARCHAR(60)   NOT NULL,
    `password`        VARCHAR(255)  NOT NULL,
    `rol_id`          INT UNSIGNED  NOT NULL,
    `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_cedula`  (`cedula`),
    UNIQUE KEY `uq_users_usuario` (`usuario`),
    KEY `fk_users_rol` (`rol_id`),
    CONSTRAINT `fk_users_rol`
        FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: airlines
-- ============================================================
CREATE TABLE IF NOT EXISTS `airlines` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `nombre`     VARCHAR(120)  NOT NULL,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_airlines_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: aircraft_types
-- ============================================================
CREATE TABLE IF NOT EXISTS `aircraft_types` (
    `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `airline_id`           INT UNSIGNED  NOT NULL,
    `tipo`                 VARCHAR(100)  NOT NULL,
    `tiempo_cumplimiento`  TINYINT UNSIGNED NOT NULL COMMENT 'Minutos: 20, 25, 30, 40',
    `created_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_aircraft_airline` (`airline_id`),
    CONSTRAINT `fk_aircraft_airline`
        FOREIGN KEY (`airline_id`) REFERENCES `airlines` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: flight_services  (registro principal)
-- ============================================================
CREATE TABLE IF NOT EXISTS `flight_services` (
    `id`                      INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    -- Información General
    `anio`                    YEAR              NOT NULL,
    `mes`                     TINYINT UNSIGNED  NOT NULL COMMENT '1-12',
    `quincena`                TINYINT UNSIGNED  NOT NULL COMMENT '1=Primera, 2=Segunda',
    `dia`                     TINYINT UNSIGNED  NOT NULL COMMENT '1-31',
    `base`                    VARCHAR(10)       NOT NULL,
    -- Operación
    `airline_id`              INT UNSIGNED      NOT NULL,
    `tipo_atencion`           VARCHAR(40)       NOT NULL,
    -- Información del Vuelo
    `vuelo_llegando`          VARCHAR(20)       NOT NULL,
    `base_destino`            VARCHAR(10)       NOT NULL,
    `matricula`               VARCHAR(20)       NOT NULL,
    `aircraft_type_id`        INT UNSIGNED      NOT NULL,
    `pax_saliendo`            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `pax_cancelado`           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `vuelo_saliendo`          VARCHAR(20)       NOT NULL,
    -- Horarios
    `hora_itinerada_llegada`  TIME              NULL,
    `demora_llegando`         SMALLINT UNSIGNED NULL DEFAULT 0 COMMENT 'Minutos',
    `hora_itinerada_salida`   TIME              NULL,
    `hora_real_llegada`       TIME              NULL,
    `hora_real_salida`        TIME              NULL,
    -- Cálculos (se guardan para reportes)
    `tiempo_transito`         SMALLINT          NULL COMMENT 'Minutos calculados',
    `cumple_tiempo`           TINYINT(1)        NULL COMMENT '1=SI, 0=NO',
    -- GPU
    `hora_conexion_gpu`       TIME              NULL,
    `hora_desconexion_gpu`    TIME              NULL,
    `tiempo_gpu`              SMALLINT UNSIGNED NULL,
    `fracciones_adc_gpu`      DECIMAL(8,2)      NULL DEFAULT 0,
    -- ACU
    `acu`                     TINYINT(1)        NOT NULL DEFAULT 0,
    `hora_conexion_acu`       TIME              NULL,
    `hora_desconexion_acu`    TIME              NULL,
    `tiempo_acu`              SMALLINT UNSIGNED NULL,
    `fracciones_hora_acu`     DECIMAL(8,2)      NULL DEFAULT 0,
    `fracciones_15min_acu`    DECIMAL(8,2)      NULL DEFAULT 0,
    -- Equipos y servicios
    `sillas_ruedas`           TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `ventiladores`            TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `rampa_escalera`          TINYINT(1)        NOT NULL DEFAULT 0,
    `equipajes_transportados` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `remolque_aeronave`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `remolque_equipajes`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `potable`                 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `drenaje`                 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    -- Observaciones
    `equipo_gse_inoperativo`  TEXT              NULL,
    `afecto_operacion`        TINYINT(1)        NOT NULL DEFAULT 0,
    -- Auditoría
    `user_id`                 INT UNSIGNED      NOT NULL,
    `created_at`              TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_fs_airline`       (`airline_id`),
    KEY `fk_fs_aircraft_type` (`aircraft_type_id`),
    KEY `fk_fs_user`          (`user_id`),
    KEY `idx_fs_fecha`        (`anio`, `mes`, `dia`),
    KEY `idx_fs_base`         (`base`),
    CONSTRAINT `fk_fs_airline`
        FOREIGN KEY (`airline_id`)      REFERENCES `airlines`       (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_fs_aircraft_type`
        FOREIGN KEY (`aircraft_type_id`) REFERENCES `aircraft_types` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_fs_user`
        FOREIGN KEY (`user_id`)         REFERENCES `users`           (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: flight_service_gpu_fracciones
-- ============================================================
CREATE TABLE IF NOT EXISTS `flight_service_gpu_fracciones` (
    `id`                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `flight_service_id`  INT UNSIGNED  NOT NULL,
    `hora_conexion`      TIME          NULL,
    `hora_desconexion`   TIME          NULL,
    `tiempo`             SMALLINT UNSIGNED NULL,
    `fracciones_adc`     DECIMAL(8,2)  NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `fk_gpu_fs` (`flight_service_id`),
    CONSTRAINT `fk_gpu_fs`
        FOREIGN KEY (`flight_service_id`) REFERENCES `flight_services` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: flight_service_acu_fracciones
-- ============================================================
CREATE TABLE IF NOT EXISTS `flight_service_acu_fracciones` (
    `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `flight_service_id`   INT UNSIGNED  NOT NULL,
    `hora_conexion`       TIME          NULL,
    `hora_desconexion`    TIME          NULL,
    `tiempo`              SMALLINT UNSIGNED NULL,
    `fracciones_hora`     DECIMAL(8,2)  NULL DEFAULT 0,
    `fracciones_15min`    DECIMAL(8,2)  NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `fk_acu_fs` (`flight_service_id`),
    CONSTRAINT `fk_acu_fs`
        FOREIGN KEY (`flight_service_id`) REFERENCES `flight_services` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: flight_service_adicionales
-- ============================================================
CREATE TABLE IF NOT EXISTS `flight_service_adicionales` (
    `id`                 INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `flight_service_id`  INT UNSIGNED      NOT NULL,
    `servicio`           VARCHAR(60)       NOT NULL,
    `cantidad`           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `fk_adicional_fs` (`flight_service_id`),
    CONSTRAINT `fk_adicional_fs`
        FOREIGN KEY (`flight_service_id`) REFERENCES `flight_services` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Roles
INSERT IGNORE INTO `roles` (`nombre`) VALUES
    ('Administrador'),
    ('Operador');

-- Usuario administrador por defecto
-- Contraseña: Admin1234! (cambiar en producción)
INSERT IGNORE INTO `users`
    (`nombre_completo`, `cedula`, `usuario`, `password`, `rol_id`)
VALUES (
    'Administrador del Sistema',
    '0000000000',
    'admin',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    1
);
