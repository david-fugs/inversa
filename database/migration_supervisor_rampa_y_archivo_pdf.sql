-- ============================================================
-- MIGRACIÓN: Rol "Líder SVC" + archivo PDF adjunto
--            en Servicios de Vuelo
-- Fecha: 2026-09-02
-- ============================================================
--
-- 1) Nuevo rol "Líder SVC": igual que Colaborador, requiere
--    una "Base Asociada" (ver app/controllers/UsersController.php,
--    roleRequiresBaseAsociada()). Solo puede VER los servicios de
--    vuelo de su base y subir/eliminar el archivo PDF adjunto de
--    cada uno; no puede crear, editar ni eliminar registros.
-- ============================================================
INSERT IGNORE INTO `roles` (`nombre`) VALUES ('Líder SVC');

-- ============================================================
-- 2) Archivo PDF adjunto por servicio de vuelo (máx. 2 MB, un solo
--    archivo por registro).
--    - `archivo_pdf`: nombre físico (único) del archivo guardado en
--      app/storage/flight_services/ (esa carpeta ya queda bloqueada
--      a acceso web directo por la regla existente en el .htaccess
--      raíz: `RewriteRule ^(app|config|database)/ - [F,L]`; el
--      archivo solo se sirve a través de
--      FlightServicesController::downloadFile()).
--    - `archivo_pdf_original`: nombre original con el que se subió,
--      para mostrarlo/descargarlo con un nombre legible.
-- ============================================================
ALTER TABLE `flight_services`
    ADD COLUMN `archivo_pdf` VARCHAR(255) NULL
        COMMENT 'Nombre físico del PDF adjunto en app/storage/flight_services/',
    ADD COLUMN `archivo_pdf_original` VARCHAR(255) NULL
        COMMENT 'Nombre original del archivo subido';

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
