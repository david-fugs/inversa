-- Equipos, Control de aceites y Combustibles de equipos (módulo de Pagos).

CREATE TABLE IF NOT EXISTS `equipos` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `base_id`    INT UNSIGNED NOT NULL,
    `nombre`     VARCHAR(120) NOT NULL,
    `rpm`        INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_equipos_base_nombre` (`base_id`, `nombre`),
    CONSTRAINT `fk_equipos_base` FOREIGN KEY (`base_id`) REFERENCES `bases` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `control_aceites` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `base_id`          INT UNSIGNED NOT NULL,
    `equipo_id`        INT UNSIGNED NOT NULL,
    `fecha`            DATE NOT NULL,
    `cantidad_galones` DECIMAL(10,2) NOT NULL,
    `mantenimiento`    ENUM('correctivo','preventivo') NOT NULL,
    `user_id`          INT UNSIGNED NOT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_control_aceites_fecha` (`fecha`),
    CONSTRAINT `fk_aceites_base`   FOREIGN KEY (`base_id`)   REFERENCES `bases` (`id`),
    CONSTRAINT `fk_aceites_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`),
    CONSTRAINT `fk_aceites_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `combustibles_equipos` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `base_id`          INT UNSIGNED NOT NULL,
    `equipo_id`        INT UNSIGNED NOT NULL,
    `fecha`            DATE NOT NULL,
    `cantidad_galones` DECIMAL(10,2) NOT NULL,
    `horometro`        VARCHAR(50) NOT NULL,
    `user_id`          INT UNSIGNED NOT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_combustibles_equipos_fecha` (`fecha`),
    CONSTRAINT `fk_comb_base`   FOREIGN KEY (`base_id`)   REFERENCES `bases` (`id`),
    CONSTRAINT `fk_comb_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`),
    CONSTRAINT `fk_comb_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
