-- ============================================================
-- MIGRACIÓN: Archivado de pagos (creador/revisor) y módulo
--            "Egresos / Legalizaciones"
-- Fecha: 2026-10-13
-- ============================================================
--
-- 1) pagos.archivado_creador / archivado_revisor: cada rol archiva a su
--    manera sin afectar la vista del otro. Si el revisor RECHAZA un pago,
--    se desarchiva para el creador (vuelve a su lista de activos).
--
-- 2) Egresos / Legalizaciones: documentos (comprobante de egreso o
--    legalización de anticipo) con uno o varios archivos PDF, asociados
--    de forma OPCIONAL a uno o varios lotes de pago. Al descargar el PDF
--    combinado de un lote, estos documentos van primero.
-- ============================================================

ALTER TABLE `pagos`
    ADD COLUMN `archivado_creador` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN `archivado_revisor` TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `egresos_documentos` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tipo`        ENUM('comprobante_egreso','legalizacion_anticipo') NOT NULL,
    `fecha`       DATE         NOT NULL,
    `user_id`     INT UNSIGNED NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_egresos_fecha` (`fecha`),
    CONSTRAINT `fk_egresos_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `egresos_archivos` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `documento_id`     INT UNSIGNED NOT NULL,
    `archivo`          VARCHAR(255) NOT NULL COMMENT 'Nombre físico en app/storage/egresos/',
    `archivo_original` VARCHAR(255) NOT NULL,
    `orden`            INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_egresos_archivos_doc` (`documento_id`, `orden`),
    CONSTRAINT `fk_egresos_archivos_doc`
        FOREIGN KEY (`documento_id`) REFERENCES `egresos_documentos` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `egresos_lotes` (
    `documento_id`  INT UNSIGNED NOT NULL,
    `lote_pago_id`  INT UNSIGNED NOT NULL,
    PRIMARY KEY (`documento_id`, `lote_pago_id`),
    CONSTRAINT `fk_egresos_lotes_doc`
        FOREIGN KEY (`documento_id`) REFERENCES `egresos_documentos` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_egresos_lotes_lote`
        FOREIGN KEY (`lote_pago_id`) REFERENCES `lotes_pago` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
