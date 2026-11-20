-- Acrónimo / siglas de equipos: equipos de la misma base con el mismo acrónimo
-- se unen en el Indicador de Disponibilidad aunque su nombre sea diferente.
ALTER TABLE `equipos`
    ADD COLUMN `acronimo` VARCHAR(30) NULL DEFAULT NULL AFTER `nombre`,
    ADD KEY `idx_equipos_acronimo` (`base_id`, `acronimo`);
