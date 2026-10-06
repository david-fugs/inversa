-- ============================================================
-- MIGRACIÓN: Código demora único por aerolínea
-- Fecha: 2026-10-06
-- Descripción: Reemplaza el índice UNIQUE (codigo) por
--              UNIQUE (airline_id, codigo) para permitir el mismo
--              código en aerolíneas diferentes.
-- ============================================================

ALTER TABLE `codigo_demoras`
    DROP INDEX `uq_codigo_demoras_codigo`,
    ADD UNIQUE KEY `uq_codigo_demoras_airline_codigo` (`airline_id`, `codigo`);

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
