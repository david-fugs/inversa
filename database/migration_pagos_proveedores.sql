-- ============================================================
--  MIGRACIÓN: Módulo de Pagos a Proveedores
--  Reutiliza `roles`/`users` existentes (agrega roles nuevos) y
--  crea las tablas propias del módulo: bancos, tipos_producto,
--  proveedores, lotes_pago y pagos.
-- ============================================================

-- Roles nuevos para el módulo de pagos (login independiente en /pagos/login)
INSERT IGNORE INTO `roles` (`nombre`) VALUES ('Admin Pagos'), ('Operador Pagos');

-- ============================================================
-- TABLA: bancos
-- ============================================================
CREATE TABLE IF NOT EXISTS `bancos` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `codigo`     VARCHAR(20)   NOT NULL,
    `nombre`     VARCHAR(120)  NOT NULL,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_bancos_codigo` (`codigo`),
    UNIQUE KEY `uq_bancos_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: tipos_producto
-- ============================================================
CREATE TABLE IF NOT EXISTS `tipos_producto` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `codigo`     VARCHAR(20)   NOT NULL,
    `nombre`     VARCHAR(120)  NOT NULL,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tipos_producto_codigo` (`codigo`),
    UNIQUE KEY `uq_tipos_producto_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: proveedores
-- ============================================================
CREATE TABLE IF NOT EXISTS `proveedores` (
    `id`                     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `numero_identificacion`  VARCHAR(30)   NOT NULL COMMENT 'Cédula o NIT',
    `nombre`                 VARCHAR(150)  NOT NULL,
    `banco_id`               INT UNSIGNED  NOT NULL,
    `tipo_producto_id`       INT UNSIGNED  NOT NULL,
    `numero_producto`        VARCHAR(40)   NOT NULL COMMENT 'Nro. de cuenta/producto o servicio',
    `created_at`             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_proveedores_numero_identificacion` (`numero_identificacion`),
    KEY `fk_proveedores_banco` (`banco_id`),
    KEY `fk_proveedores_tipo_producto` (`tipo_producto_id`),
    CONSTRAINT `fk_proveedores_banco`
        FOREIGN KEY (`banco_id`) REFERENCES `bancos` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_proveedores_tipo_producto`
        FOREIGN KEY (`tipo_producto_id`) REFERENCES `tipos_producto` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: lotes_pago (agrupador por consecutivo escrito por el usuario)
-- ============================================================
CREATE TABLE IF NOT EXISTS `lotes_pago` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `consecutivo` VARCHAR(40)   NOT NULL COMMENT 'Código de lote escrito por el usuario',
    `estado`      ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto',
    `user_id`     INT UNSIGNED  NOT NULL COMMENT 'Usuario que creó el lote',
    `cerrado_at`  TIMESTAMP     NULL DEFAULT NULL,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_lotes_pago_consecutivo` (`consecutivo`),
    KEY `fk_lotes_pago_user` (`user_id`),
    CONSTRAINT `fk_lotes_pago_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: pagos (detalle: un pago individual dentro de un lote)
-- ============================================================
CREATE TABLE IF NOT EXISTS `pagos` (
    `id`                       INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `lote_pago_id`             INT UNSIGNED   NOT NULL,
    `proveedor_id`             INT UNSIGNED   NOT NULL,
    `banco_id`                 INT UNSIGNED   NOT NULL COMMENT 'Precargado del proveedor, editable',
    `tipo_producto_id`         INT UNSIGNED   NOT NULL COMMENT 'Precargado del proveedor, editable',
    `numero_producto`          VARCHAR(40)    NOT NULL COMMENT 'Precargado del proveedor, editable',
    `fecha_pago`               DATE           NOT NULL,
    `valor`                    DECIMAL(14,2)  NOT NULL,
    `comprobante_pdf`          VARCHAR(255)   NULL COMMENT 'Nombre físico en app/storage/pagos_comprobantes/',
    `comprobante_pdf_original` VARCHAR(255)   NULL COMMENT 'Nombre original del archivo subido',
    `orden`                    INT UNSIGNED   NOT NULL DEFAULT 0 COMMENT 'Orden de captura dentro del lote, usado para el PDF combinado',
    `user_id`                  INT UNSIGNED   NOT NULL COMMENT 'Usuario que registró el pago',
    `created_at`               TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`               TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_pagos_lote` (`lote_pago_id`),
    KEY `fk_pagos_proveedor` (`proveedor_id`),
    KEY `fk_pagos_banco` (`banco_id`),
    KEY `fk_pagos_tipo_producto` (`tipo_producto_id`),
    KEY `fk_pagos_user` (`user_id`),
    KEY `idx_pagos_lote_orden` (`lote_pago_id`, `orden`),
    CONSTRAINT `fk_pagos_lote`
        FOREIGN KEY (`lote_pago_id`) REFERENCES `lotes_pago` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_pagos_proveedor`
        FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_pagos_banco`
        FOREIGN KEY (`banco_id`) REFERENCES `bancos` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_pagos_tipo_producto`
        FOREIGN KEY (`tipo_producto_id`) REFERENCES `tipos_producto` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_pagos_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED: usuario administrador de pagos por defecto
-- Usuario: admin.pagos / Contraseña: CambiarAhora123 (cambiarla tras el primer login)
-- Hash generado con password_hash('CambiarAhora123', PASSWORD_BCRYPT, ['cost' => 12])
-- ============================================================
INSERT INTO `users` (`nombre_completo`, `cedula`, `usuario`, `password`, `rol_id`)
SELECT 'Administrador de Pagos', '00000000', 'admin.pagos',
       '$2y$12$dlG5xk.AJbSmnJyk51qA4.bSU8Lv7p7sSqAtt17PKUEF9342X0giG',
       (SELECT id FROM roles WHERE nombre = 'Admin Pagos')
WHERE NOT EXISTS (SELECT 1 FROM users WHERE usuario = 'admin.pagos');
