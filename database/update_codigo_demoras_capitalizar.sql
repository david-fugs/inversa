-- ============================================================
-- UPDATE: Normaliza `descripcion` en toda la tabla `codigo_demoras`
-- Primera letra en mayúscula y el resto en minúscula.
-- ============================================================

SET NAMES utf8mb4;

UPDATE `codigo_demoras`
SET `descripcion` = CONCAT(
    UPPER(LEFT(TRIM(`descripcion`), 1)),
    LOWER(SUBSTRING(TRIM(`descripcion`), 2))
);
