-- ============================================================
-- MIGRACIÓN: ACU - "cobrar a partir de N servicios por mes" y
--            nuevo criterio de "Fracciones Hora ACU"
-- Fecha: 2026-10-08
-- ============================================================
--
-- NUEVAS REGLAS (ver public/js/app.js: calcularFraccionesAcuValores)
--
-- 1) tarifas_gpu.acu_cobrar_desde_servicios (solo aplica a tipo 'acu'):
--      0  = se cobra siempre (fórmula normal).
--      N  = el ACU solo se cobra desde el servicio N del mes. Los
--           servicios ACU anteriores del mes quedan en 0/0.
--    La posición se cuenta POR AEROLÍNEA dentro del mes (anio+mes),
--    solo servicios con tiempo_acu > 0, ordenados por día y luego por id.
--    Si la tarifa ACU es de una base específica, solo cuentan servicios
--    de esa base; si es "todas las bases", cuentan todos los de la aerolínea.
--    Ej: N=3 → servicios 1 y 2 del mes no cobran; el 3 y siguientes sí.
--
-- 2) "Fracciones Hora ACU" ahora SÍ se cobra en el tramo de primeros
--    minutos (a diferencia de GPU):
--      - Con primeros_minutos (ej. 60): tiempo de 1 a 60 min → hora=1,
--        fracciones=0. Si supera 60: hora=1 y fracciones =
--        CEIL((tiempo-60)/fraccion_minutos).
--      - Sin primeros_minutos: hora=0 y fracciones = CEIL(tiempo/fraccion).
--
-- El script RECALCULA fracciones_hora_acu / fracciones_15min_acu de
-- flight_services y fracciones_hora / fracciones_15min de
-- flight_service_acu_fracciones usando la tarifa ACU VIGENTE (específica
-- de aerolínea+base; si no existe, la de "todas las bases"). Servicios
-- cuya aerolínea/base no tiene tarifa ACU NO se tocan.
--
-- Las filas "Agregar otro ACU" siguen el mismo criterio de cobro que el
-- servicio al que pertenecen (si el servicio no cobra, ellas tampoco).
--
-- IMPORTANTE: respaldo antes de ejecutar:
--   mysqldump <BD> tarifas_gpu flight_services flight_service_acu_fracciones > backup_acu.sql
-- ============================================================

-- (El DDL —ALTER/CREATE/DROP— hace COMMIT implícito, por eso va antes de
--  START TRANSACTION; la transacción cubre solo el recálculo de datos.)

-- ────────────────────────────────────────────────────────────
-- 1. Nueva columna (0 = cobrar siempre; no altera tarifas existentes)
-- ────────────────────────────────────────────────────────────
ALTER TABLE `tarifas_gpu`
    ADD COLUMN `acu_cobrar_desde_servicios` INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Solo ACU: cobrar desde el servicio N del mes por aerolínea (0 = siempre)'
        AFTER `fraccion_minutos`;

-- ────────────────────────────────────────────────────────────
-- 2. Tabla de trabajo con la tarifa ACU aplicable y la posición
--    mensual de cada servicio
-- ────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `tmp_acu_recalculo`;
CREATE TABLE `tmp_acu_recalculo` (
    `fs_id`      INT UNSIGNED NOT NULL PRIMARY KEY,
    `primeros`   INT NULL,
    `fraccion`   INT NOT NULL,
    `desde`      INT NOT NULL,
    `pos`        INT NOT NULL
) ENGINE=InnoDB;

START TRANSACTION;

INSERT INTO `tmp_acu_recalculo` (fs_id, primeros, fraccion, desde, pos)
SELECT x.id, x.primeros, x.fraccion, x.desde,
       1 + (
           SELECT COUNT(*) FROM flight_services p
           WHERE p.airline_id = x.airline_id
             AND p.anio = x.anio AND p.mes = x.mes
             AND p.tiempo_acu > 0
             AND (p.dia < x.dia OR (p.dia = x.dia AND p.id < x.id))
             AND (x.base_especifica = 0 OR p.base = x.base)
       ) AS pos
FROM (
    SELECT fs.id, fs.airline_id, fs.anio, fs.mes, fs.dia, fs.base,
           IF(tg_esp.id IS NOT NULL, 1, 0)                                              AS base_especifica,
           IF(tg_esp.id IS NOT NULL, tg_esp.primeros_minutos, tg_gen.primeros_minutos)  AS primeros,
           IF(tg_esp.id IS NOT NULL, tg_esp.fraccion_minutos, tg_gen.fraccion_minutos)  AS fraccion,
           IF(tg_esp.id IS NOT NULL, tg_esp.acu_cobrar_desde_servicios, tg_gen.acu_cobrar_desde_servicios) AS desde
    FROM flight_services fs
    LEFT JOIN bases b ON b.nombre = fs.base
    LEFT JOIN tarifas_gpu tg_esp
        ON tg_esp.airline_id = fs.airline_id
       AND tg_esp.base_id = b.id
       AND tg_esp.tipo_cobro = 'acu'
    LEFT JOIN tarifas_gpu tg_gen
        ON tg_gen.airline_id = fs.airline_id
       AND tg_gen.base_id IS NULL
       AND tg_gen.tipo_cobro = 'acu'
    WHERE tg_esp.id IS NOT NULL OR tg_gen.id IS NOT NULL
) x;

-- ────────────────────────────────────────────────────────────
-- 3. Vista previa (revisar antes del COMMIT): servicio principal
-- ────────────────────────────────────────────────────────────
SELECT fs.id, fs.anio, fs.mes, fs.dia, fs.tiempo_acu, t.pos, t.desde, t.primeros, t.fraccion,
       fs.fracciones_hora_acu  AS hora_actual,
       fs.fracciones_15min_acu AS fracc_actual,
       CASE WHEN fs.tiempo_acu IS NULL OR fs.tiempo_acu <= 0 THEN 0
            WHEN t.desde > 0 AND t.pos < t.desde THEN 0
            WHEN t.primeros IS NOT NULL THEN 1 ELSE 0 END AS hora_nueva,
       CASE WHEN fs.tiempo_acu IS NULL OR fs.tiempo_acu <= 0 THEN 0
            WHEN t.desde > 0 AND t.pos < t.desde THEN 0
            WHEN t.primeros IS NOT NULL THEN IF(fs.tiempo_acu > t.primeros, CEIL((fs.tiempo_acu - t.primeros) / t.fraccion), 0)
            ELSE CEIL(fs.tiempo_acu / t.fraccion) END AS fracc_nueva
FROM flight_services fs
JOIN tmp_acu_recalculo t ON t.fs_id = fs.id
WHERE fs.tiempo_acu > 0
ORDER BY fs.airline_id, fs.anio, fs.mes, fs.dia, fs.id;

-- ────────────────────────────────────────────────────────────
-- 4. Recalcular servicio principal
-- ────────────────────────────────────────────────────────────
UPDATE flight_services fs
JOIN tmp_acu_recalculo t ON t.fs_id = fs.id
SET fs.fracciones_hora_acu = CASE
        WHEN fs.tiempo_acu IS NULL OR fs.tiempo_acu <= 0 THEN 0
        WHEN t.desde > 0 AND t.pos < t.desde THEN 0
        WHEN t.primeros IS NOT NULL THEN 1
        ELSE 0 END,
    fs.fracciones_15min_acu = CASE
        WHEN fs.tiempo_acu IS NULL OR fs.tiempo_acu <= 0 THEN 0
        WHEN t.desde > 0 AND t.pos < t.desde THEN 0
        WHEN t.primeros IS NOT NULL THEN IF(fs.tiempo_acu > t.primeros, CEIL((fs.tiempo_acu - t.primeros) / t.fraccion), 0)
        ELSE CEIL(fs.tiempo_acu / t.fraccion) END;

-- ────────────────────────────────────────────────────────────
-- 5. Recalcular filas "Agregar otro ACU" (mismo criterio de cobro
--    que el servicio al que pertenecen)
-- ────────────────────────────────────────────────────────────
UPDATE flight_service_acu_fracciones af
JOIN tmp_acu_recalculo t ON t.fs_id = af.flight_service_id
SET af.fracciones_hora = CASE
        WHEN af.tiempo IS NULL OR af.tiempo <= 0 THEN 0
        WHEN t.desde > 0 AND t.pos < t.desde THEN 0
        WHEN t.primeros IS NOT NULL THEN 1
        ELSE 0 END,
    af.fracciones_15min = CASE
        WHEN af.tiempo IS NULL OR af.tiempo <= 0 THEN 0
        WHEN t.desde > 0 AND t.pos < t.desde THEN 0
        WHEN t.primeros IS NOT NULL THEN IF(af.tiempo > t.primeros, CEIL((af.tiempo - t.primeros) / t.fraccion), 0)
        ELSE CEIL(af.tiempo / t.fraccion) END;

-- Revisar la vista previa del paso 3 y luego COMMIT (o ROLLBACK para
-- deshacer solo el recálculo; la columna nueva queda creada).
COMMIT;
-- ROLLBACK;

DROP TABLE `tmp_acu_recalculo`;
