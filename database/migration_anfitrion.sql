-- ============================================================
-- MIGRACIÓN: Anfitrión en Servicios de Vuelo
-- Fecha: 2026-10-30
-- ============================================================
--
-- Permite registrar, en /flight-services/view/{id}, si el vuelo tuvo
-- anfitrión (SI/NO) y, en caso afirmativo, la fecha/hora de inicio y fin.
-- El total de minutos se calcula al guardar y se resume por aerolínea
-- en /facturacion (total de vuelos con anfitrión y horas/minutos).
-- Por defecto queda en 0 (No) para todos los servicios existentes.
-- ============================================================
ALTER TABLE `flight_services`
    ADD COLUMN `anfitrion`         TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Vuelo atendido con anfitrión (1=Sí, 0=No)',
    ADD COLUMN `anfitrion_inicio`  DATETIME NULL DEFAULT NULL
        COMMENT 'Fecha y hora de inicio del anfitrión',
    ADD COLUMN `anfitrion_fin`     DATETIME NULL DEFAULT NULL
        COMMENT 'Fecha y hora de fin del anfitrión',
    ADD COLUMN `anfitrion_minutos` INT NULL DEFAULT NULL
        COMMENT 'Total de minutos del anfitrión (fin - inicio)';

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
