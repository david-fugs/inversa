-- Tablas para administrar bases y bases destino.
-- Ejecutar una vez en la base de datos del proyecto.

CREATE TABLE IF NOT EXISTS `bases` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(10) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_bases_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `base_destinos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(10) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_base_destinos_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `bases` (`nombre`) VALUES
('AUC'),
('EJA'),
('EYP'),
('PPN'),
('PSO'),
('RCH'),
('TCO'),
('UIB'),
('VUP'),
('VVC'),
('MTR');

INSERT IGNORE INTO `base_destinos` (`nombre`) VALUES
('BAQ'),
('BOG'),
('CLO'),
('MDE');

INSERT IGNORE INTO `bases` (`nombre`)
SELECT DISTINCT UPPER(TRIM(`base`))
FROM `flight_services`
WHERE `base` IS NOT NULL AND TRIM(`base`) <> '';

INSERT IGNORE INTO `bases` (`nombre`)
SELECT DISTINCT UPPER(TRIM(`base_asociada`))
FROM `users`
WHERE `base_asociada` IS NOT NULL AND TRIM(`base_asociada`) <> '';

INSERT IGNORE INTO `base_destinos` (`nombre`)
SELECT DISTINCT UPPER(TRIM(`base_destino`))
FROM `flight_services`
WHERE `base_destino` IS NOT NULL AND TRIM(`base_destino`) <> '';
