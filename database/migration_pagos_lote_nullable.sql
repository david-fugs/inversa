-- ============================================================
--  MIGRACIÓN: Permite pagos sin lote asignado
--  Los pagos se registran primero (lote_pago_id NULL) y luego se
--  asignan a un lote arrastrándolos en /pagos/lotes/nuevo.
-- ============================================================
ALTER TABLE `pagos`
    MODIFY COLUMN `lote_pago_id` INT UNSIGNED NULL;
