-- Permite varios equipos con el mismo nombre en una base si tienen distinto RPM.
-- La unicidad pasa de (base_id, nombre) a (base_id, nombre, rpm).
-- Se crea primero el índice nuevo porque la FK fk_equipos_base necesita un índice sobre base_id.

ALTER TABLE `equipos`
    ADD UNIQUE KEY `uk_equipos_base_nombre_rpm` (`base_id`, `nombre`, `rpm`);

ALTER TABLE `equipos`
    DROP INDEX `uk_equipos_base_nombre`;
