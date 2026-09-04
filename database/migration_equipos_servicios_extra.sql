-- ============================================================
-- MIGRACIÓN: Nuevos equipos/servicios en Servicios de Vuelo
-- Fecha: 2026-09-04
-- ============================================================
--
-- Cinco campos numéricos nuevos en la sección "Equipos y Servicios"
-- (create/edit/view/export), igual que sillas_ruedas, potable, etc.
-- ============================================================
ALTER TABLE `flight_services`
    ADD COLUMN `air_starter` INT NOT NULL DEFAULT 0
        COMMENT 'Arranque de Motores "Air Starter"',
    ADD COLUMN `pay_mower` INT NOT NULL DEFAULT 0
        COMMENT 'Pay Mower',
    ADD COLUMN `aseo_aeronaves` INT NOT NULL DEFAULT 0
        COMMENT 'Aseo a las Aeronaves',
    ADD COLUMN `equipos_carga_descargue` INT NOT NULL DEFAULT 0
        COMMENT 'Equipos para Carga y Descargue de Mercancías',
    ADD COLUMN `atencion_pasajeros` INT NOT NULL DEFAULT 0
        COMMENT 'Atención a Pasajeros';

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
