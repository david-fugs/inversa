-- ============================================================
-- Importación masiva de Proveedores desde Excel
-- ============================================================

CREATE TABLE IF NOT EXISTS `proveedor_imports` (
    `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `nombre_archivo`  VARCHAR(255)  NOT NULL,
    `total_filas`     INT UNSIGNED  NOT NULL DEFAULT 0,
    `filas_creadas`   INT UNSIGNED  NOT NULL DEFAULT 0,
    `filas_actualizadas` INT UNSIGNED NOT NULL DEFAULT 0,
    `filas_error`     INT UNSIGNED  NOT NULL DEFAULT 0,
    `user_id`         INT UNSIGNED  NOT NULL,
    `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_proveedor_import_user` (`user_id`),
    CONSTRAINT `fk_proveedor_import_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proveedor_import_errors` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `import_id`   INT UNSIGNED  NOT NULL,
    `fila`        INT UNSIGNED  NOT NULL COMMENT 'Número de fila en el Excel (1-indexado)',
    `mensaje`     TEXT          NOT NULL,
    `datos_fila`  TEXT          NULL COMMENT 'Volcado JSON de las celdas de la fila, para depurar',
    PRIMARY KEY (`id`),
    KEY `fk_proveedor_import_error_import` (`import_id`),
    CONSTRAINT `fk_proveedor_import_error_import`
        FOREIGN KEY (`import_id`) REFERENCES `proveedor_imports` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
