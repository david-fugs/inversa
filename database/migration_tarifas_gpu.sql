-- ============================================================
-- MIGRACIÓN: Tarifas / Cobros GPU por aerolínea
-- Fecha: 2026-07-15
-- Descripción: Nueva tabla para configurar, por aerolínea, la
--              tarifa de fracciones GPU usada en el cálculo
--              automático de "Fracciones ADC GPU" en
--              /flight-services/create y /flight-services/edit.
--
--              - primeros_minutos: minutos cubiertos por la
--                primera fracción (NULL si la aerolínea no maneja
--                tarifa inicial y solo cobra por fracción).
--              - fraccion_minutos: minutos que dura cada fracción
--                adicional (o cada fracción, si no hay tarifa
--                inicial).
--
--              Ej: Avianca → primeros_minutos=60, fraccion_minutos=15
--                  60 min = 1 fracción, 61 min = 2, 76 min = 3...
-- ============================================================

CREATE TABLE IF NOT EXISTS `tarifas_gpu` (
    `id`                INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `airline_id`        INT UNSIGNED      NOT NULL,
    `primeros_minutos`  SMALLINT UNSIGNED NULL     COMMENT 'Minutos cubiertos por la primera fracción (NULL = sin tarifa inicial, solo fracción)',
    `fraccion_minutos`  SMALLINT UNSIGNED NOT NULL COMMENT 'Minutos que dura cada fracción (adicional o única)',
    `created_at`        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tarifas_gpu_airline` (`airline_id`),
    CONSTRAINT `fk_tarifas_gpu_airline`
        FOREIGN KEY (`airline_id`) REFERENCES `airlines` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
