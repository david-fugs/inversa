-- ============================================================
-- MIGRACIÓN: soporte de múltiples comprobantes PDF por pago
-- ============================================================
-- Antes: cada fila de `pagos` guardaba un único comprobante en las
-- columnas `comprobante_pdf` / `comprobante_pdf_original`.
-- Ahora: los comprobantes viven en una tabla hija `pago_comprobantes`
-- (uno o varios archivos por pago). Las columnas antiguas en `pagos`
-- se dejan intactas (no se usan más en el código, se conservan como
-- respaldo histórico; se pueden eliminar en una migración posterior
-- una vez verificado en producción).

CREATE TABLE IF NOT EXISTS `pago_comprobantes` (
    `id`                INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `pago_id`           INT UNSIGNED   NOT NULL,
    `archivo`           VARCHAR(255)   NOT NULL COMMENT 'Nombre físico en app/storage/pagos_comprobantes/',
    `archivo_original`  VARCHAR(255)   NOT NULL COMMENT 'Nombre original del archivo subido',
    `orden`             INT UNSIGNED   NOT NULL DEFAULT 0 COMMENT 'Orden dentro del pago, usado para el PDF combinado',
    `created_at`        TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pago_comprobantes_pago_orden` (`pago_id`, `orden`),
    CONSTRAINT `fk_pago_comprobantes_pago`
        FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar los comprobantes ya existentes (uno por pago) a la tabla nueva.
INSERT INTO `pago_comprobantes` (`pago_id`, `archivo`, `archivo_original`, `orden`)
SELECT `id`, `comprobante_pdf`, COALESCE(`comprobante_pdf_original`, `comprobante_pdf`), 0
FROM `pagos`
WHERE `comprobante_pdf` IS NOT NULL AND `comprobante_pdf` <> '';
