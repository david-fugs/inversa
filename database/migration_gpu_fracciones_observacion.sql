-- ============================================================
-- MIGRACIÓN: Observación en fracciones adicionales de GPU
-- Agrega un campo de observación a cada fracción adicional de GPU
-- (hasta 3 fracciones adicionales por servicio, controlado en la app).
-- ============================================================

ALTER TABLE `flight_service_gpu_fracciones`
    ADD COLUMN `observacion` VARCHAR(255) NULL AFTER `fracciones_adc`;
