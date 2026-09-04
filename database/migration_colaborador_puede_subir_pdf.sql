-- ============================================================
-- MIGRACIÓN: Permiso individual "Puede subir PDF" para usuarios
-- Fecha: 2026-09-04
-- ============================================================
--
-- Permite marcar, por usuario (pensado para el rol Colaborador),
-- si puede subir/eliminar el archivo PDF adjunto de un servicio de
-- vuelo, igual que ya pueden hacerlo Administrador y Líder SVC.
-- Por defecto queda desactivado (0) para todos los usuarios.
-- Ver app/controllers/UsersController.php (store/update) y
-- app/controllers/FlightServicesController.php (puedeGestionarArchivo()).
-- ============================================================
ALTER TABLE `users`
    ADD COLUMN `puede_subir_pdf` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Permite subir/eliminar el PDF adjunto en Servicios de Vuelo';

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
