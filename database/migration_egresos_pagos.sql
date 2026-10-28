-- ============================================================
-- MIGRACIÓN: Asociar Egresos / Legalizaciones a pagos específicos
-- Fecha: 2026-10-28
-- ============================================================
-- Un documento (comprobante de egreso o legalización de anticipo) puede
-- cubrir uno o varios pagos. Al asociarlo a un pago, su lote también
-- queda asociado (egresos_lotes) para que siga saliendo en el PDF del lote.
-- ============================================================

CREATE TABLE IF NOT EXISTS `egresos_pagos` (
    `documento_id`  INT UNSIGNED NOT NULL,
    `pago_id`       INT UNSIGNED NOT NULL,
    PRIMARY KEY (`documento_id`, `pago_id`),
    KEY `idx_egresos_pagos_pago` (`pago_id`),
    CONSTRAINT `fk_egresos_pagos_doc`
        FOREIGN KEY (`documento_id`) REFERENCES `egresos_documentos` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_egresos_pagos_pago`
        FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
