-- ============================================================
-- MIGRACIÓN: Soporte para GPU con más de 24 horas
-- Agrega un flag para indicar que el tiempo GPU (principal o de una
-- fracción adicional) debe calcularse sumando 24 horas extra al
-- tiempo entre conexión y desconexión (para casos donde el equipo
-- permaneció conectado más de un día completo).
-- ============================================================

ALTER TABLE `flight_services`
    ADD COLUMN `gpu_mas_24h` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fracciones_adicionales_gpu`;

ALTER TABLE `flight_service_gpu_fracciones`
    ADD COLUMN `mas_24h` TINYINT(1) NOT NULL DEFAULT 0 AFTER `observacion`;
