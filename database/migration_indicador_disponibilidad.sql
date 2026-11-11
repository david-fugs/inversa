-- Indicador de disponibilidad y confiabilidad por equipo, base y día.

-- 1) Cantidad de unidades de cada equipo en su base.
ALTER TABLE `equipos`
    ADD COLUMN `cantidad` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `rpm`;

-- 2) Novedades diarias. Un registro por equipo y día.
--    `cantidad` es una copia de equipos.cantidad al momento de registrar, para que
--    el histórico no cambie si luego se modifica la cantidad del equipo.
--    Disponibilidad = (cantidad - inoperativo) / cantidad
--    Confiabilidad  = (cantidad - correctivo)  / cantidad
CREATE TABLE IF NOT EXISTS `disponibilidad_registros` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `base_id`     INT UNSIGNED NOT NULL,
    `equipo_id`   INT UNSIGNED NOT NULL,
    `fecha`       DATE NOT NULL,
    `cantidad`    INT UNSIGNED NOT NULL,
    `inoperativo` INT UNSIGNED NOT NULL DEFAULT 0,
    `correctivo`  INT UNSIGNED NOT NULL DEFAULT 0,
    `preventivo`  INT UNSIGNED NOT NULL DEFAULT 0,
    `user_id`     INT UNSIGNED NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_disp_equipo_fecha` (`equipo_id`, `fecha`),
    KEY `idx_disp_base_fecha` (`base_id`, `fecha`),
    CONSTRAINT `fk_disp_base`   FOREIGN KEY (`base_id`)   REFERENCES `bases` (`id`),
    CONSTRAINT `fk_disp_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`),
    CONSTRAINT `fk_disp_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
