-- ============================================================
--  SEEDS: Módulo de Pagos a Proveedores (/pagos)
--  10 registros de cada tabla para pruebas.
--  Requiere haber ejecutado antes: migration_pagos_proveedores.sql
-- ============================================================

-- ============================================================
-- USUARIO OPERADOR DE PAGOS (además del admin.pagos ya creado en la migración)
-- Usuario: operador.pagos / Contraseña: CambiarAhora123
-- Hash generado con password_hash('CambiarAhora123', PASSWORD_BCRYPT, ['cost' => 12])
-- ============================================================
INSERT INTO `users` (`nombre_completo`, `cedula`, `usuario`, `password`, `rol_id`)
SELECT 'Operador de Pagos', '00000001', 'operador.pagos',
       '$2y$12$dlG5xk.AJbSmnJyk51qA4.bSU8Lv7p7sSqAtt17PKUEF9342X0giG',
       (SELECT id FROM roles WHERE nombre = 'Operador Pagos')
WHERE NOT EXISTS (SELECT 1 FROM users WHERE usuario = 'operador.pagos');

-- ============================================================
-- BANCOS (10)
-- ============================================================
INSERT IGNORE INTO `bancos` (`codigo`, `nombre`) VALUES
('BANCOL',   'Bancolombia'),
('DAVIV',    'Davivienda'),
('BOGOTA',   'Banco de Bogotá'),
('BBVA',     'BBVA Colombia'),
('AVVILLAS', 'Banco AV Villas'),
('OCCIDENTE','Banco de Occidente'),
('POPULAR',  'Banco Popular'),
('COLPATRIA','Scotiabank Colpatria'),
('NEQUI',    'Nequi'),
('DAVIPLATA','Daviplata');

-- ============================================================
-- TIPOS DE PRODUCTO (10)
-- ============================================================
INSERT IGNORE INTO `tipos_producto` (`codigo`, `nombre`) VALUES
('AHORROS',  'Cuenta de Ahorros'),
('CORRIENTE','Cuenta Corriente'),
('NEQUI',    'Billetera Nequi'),
('DAVIPLATA','Billetera Daviplata'),
('CONVENIO', 'Convenio de Recaudo'),
('TARJETA',  'Tarjeta Prepago'),
('GIRO',     'Giro / Efecty'),
('PSE',      'Botón PSE'),
('CRIPTO',   'Billetera Cripto'),
('OTRO',     'Otro Servicio');

-- ============================================================
-- PROVEEDORES (10)
-- ============================================================
INSERT IGNORE INTO `proveedores` (`numero_identificacion`, `nombre`, `banco_id`, `tipo_producto_id`, `numero_producto`)
SELECT '900111222', 'Suministros Aéreos SAS', b.id, t.id, '1234567890'
FROM bancos b, tipos_producto t WHERE b.codigo='BANCOL' AND t.codigo='AHORROS'
UNION ALL
SELECT '900222333', 'Combustibles del Caribe SA', b.id, t.id, '2234567890'
FROM bancos b, tipos_producto t WHERE b.codigo='DAVIV' AND t.codigo='CORRIENTE'
UNION ALL
SELECT '900333444', 'Catering Andino Ltda', b.id, t.id, '3234567890'
FROM bancos b, tipos_producto t WHERE b.codigo='BOGOTA' AND t.codigo='AHORROS'
UNION ALL
SELECT '900444555', 'Servicios de Rampa Express', b.id, t.id, '3001234567'
FROM bancos b, tipos_producto t WHERE b.codigo='NEQUI' AND t.codigo='NEQUI'
UNION ALL
SELECT '900555666', 'Mantenimiento Aeroportuario SAS', b.id, t.id, '4234567890'
FROM bancos b, tipos_producto t WHERE b.codigo='BBVA' AND t.codigo='CORRIENTE'
UNION ALL
SELECT '900666777', 'Transporte de Carga La Sabana', b.id, t.id, '3009876543'
FROM bancos b, tipos_producto t WHERE b.codigo='DAVIPLATA' AND t.codigo='DAVIPLATA'
UNION ALL
SELECT '900777888', 'Limpieza y Aseo Integral SAS', b.id, t.id, '5234567890'
FROM bancos b, tipos_producto t WHERE b.codigo='AVVILLAS' AND t.codigo='AHORROS'
UNION ALL
SELECT '900888999', 'Seguridad Privada del Aire', b.id, t.id, '6234567890'
FROM bancos b, tipos_producto t WHERE b.codigo='OCCIDENTE' AND t.codigo='CORRIENTE'
UNION ALL
SELECT '900999000', 'Repuestos y Herramientas GSE', b.id, t.id, 'CONV-778899'
FROM bancos b, tipos_producto t WHERE b.codigo='POPULAR' AND t.codigo='CONVENIO'
UNION ALL
SELECT '901000111', 'Consultoría Logística Andina', b.id, t.id, '7234567890'
FROM bancos b, tipos_producto t WHERE b.codigo='COLPATRIA' AND t.codigo='AHORROS';

-- ============================================================
-- LOTES DE PAGO (10)
-- Todos asignados al usuario admin.pagos
-- ============================================================
INSERT IGNORE INTO `lotes_pago` (`consecutivo`, `estado`, `user_id`, `cerrado_at`)
SELECT 'LOTE-2026-0001', 'cerrado', u.id, '2026-01-15 10:00:00' FROM users u WHERE u.usuario='admin.pagos'
UNION ALL
SELECT 'LOTE-2026-0002', 'cerrado', u.id, '2026-02-10 10:00:00' FROM users u WHERE u.usuario='admin.pagos'
UNION ALL
SELECT 'LOTE-2026-0003', 'cerrado', u.id, '2026-03-05 10:00:00' FROM users u WHERE u.usuario='admin.pagos'
UNION ALL
SELECT 'LOTE-2026-0004', 'cerrado', u.id, '2026-03-20 10:00:00' FROM users u WHERE u.usuario='admin.pagos'
UNION ALL
SELECT 'LOTE-2026-0005', 'abierto', u.id, NULL FROM users u WHERE u.usuario='admin.pagos'
UNION ALL
SELECT 'LOTE-2026-0006', 'cerrado', u.id, '2026-04-18 10:00:00' FROM users u WHERE u.usuario='operador.pagos'
UNION ALL
SELECT 'LOTE-2026-0007', 'cerrado', u.id, '2026-05-02 10:00:00' FROM users u WHERE u.usuario='operador.pagos'
UNION ALL
SELECT 'LOTE-2026-0008', 'abierto', u.id, NULL FROM users u WHERE u.usuario='operador.pagos'
UNION ALL
SELECT 'LOTE-2026-0009', 'cerrado', u.id, '2026-06-11 10:00:00' FROM users u WHERE u.usuario='admin.pagos'
UNION ALL
SELECT 'LOTE-2026-0010', 'abierto', u.id, NULL FROM users u WHERE u.usuario='admin.pagos';

-- ============================================================
-- PAGOS (10)
-- Un pago por lote, con banco/tipo/numero_producto precargados del proveedor.
-- ============================================================
INSERT INTO `pagos`
    (`lote_pago_id`, `proveedor_id`, `banco_id`, `tipo_producto_id`, `numero_producto`, `fecha_pago`, `valor`, `orden`, `user_id`)
SELECT lp.id, p.id, p.banco_id, p.tipo_producto_id, p.numero_producto, '2026-01-15', 1500000.00, 1, lp.user_id
FROM lotes_pago lp, proveedores p
WHERE lp.consecutivo='LOTE-2026-0001' AND p.numero_identificacion='900111222'
  AND NOT EXISTS (SELECT 1 FROM pagos WHERE lote_pago_id=lp.id AND orden=1)
UNION ALL
SELECT lp.id, p.id, p.banco_id, p.tipo_producto_id, p.numero_producto, '2026-02-10', 2350000.00, 1, lp.user_id
FROM lotes_pago lp, proveedores p
WHERE lp.consecutivo='LOTE-2026-0002' AND p.numero_identificacion='900222333'
  AND NOT EXISTS (SELECT 1 FROM pagos WHERE lote_pago_id=lp.id AND orden=1)
UNION ALL
SELECT lp.id, p.id, p.banco_id, p.tipo_producto_id, p.numero_producto, '2026-03-05', 980000.00, 1, lp.user_id
FROM lotes_pago lp, proveedores p
WHERE lp.consecutivo='LOTE-2026-0003' AND p.numero_identificacion='900333444'
  AND NOT EXISTS (SELECT 1 FROM pagos WHERE lote_pago_id=lp.id AND orden=1)
UNION ALL
SELECT lp.id, p.id, p.banco_id, p.tipo_producto_id, p.numero_producto, '2026-03-20', 450000.00, 1, lp.user_id
FROM lotes_pago lp, proveedores p
WHERE lp.consecutivo='LOTE-2026-0004' AND p.numero_identificacion='900444555'
  AND NOT EXISTS (SELECT 1 FROM pagos WHERE lote_pago_id=lp.id AND orden=1)
UNION ALL
SELECT lp.id, p.id, p.banco_id, p.tipo_producto_id, p.numero_producto, '2026-04-01', 3200000.00, 1, lp.user_id
FROM lotes_pago lp, proveedores p
WHERE lp.consecutivo='LOTE-2026-0005' AND p.numero_identificacion='900555666'
  AND NOT EXISTS (SELECT 1 FROM pagos WHERE lote_pago_id=lp.id AND orden=1)
UNION ALL
SELECT lp.id, p.id, p.banco_id, p.tipo_producto_id, p.numero_producto, '2026-04-18', 1750000.00, 1, lp.user_id
FROM lotes_pago lp, proveedores p
WHERE lp.consecutivo='LOTE-2026-0006' AND p.numero_identificacion='900666777'
  AND NOT EXISTS (SELECT 1 FROM pagos WHERE lote_pago_id=lp.id AND orden=1)
UNION ALL
SELECT lp.id, p.id, p.banco_id, p.tipo_producto_id, p.numero_producto, '2026-05-02', 620000.00, 1, lp.user_id
FROM lotes_pago lp, proveedores p
WHERE lp.consecutivo='LOTE-2026-0007' AND p.numero_identificacion='900777888'
  AND NOT EXISTS (SELECT 1 FROM pagos WHERE lote_pago_id=lp.id AND orden=1)
UNION ALL
SELECT lp.id, p.id, p.banco_id, p.tipo_producto_id, p.numero_producto, '2026-05-20', 4100000.00, 1, lp.user_id
FROM lotes_pago lp, proveedores p
WHERE lp.consecutivo='LOTE-2026-0008' AND p.numero_identificacion='900888999'
  AND NOT EXISTS (SELECT 1 FROM pagos WHERE lote_pago_id=lp.id AND orden=1)
UNION ALL
SELECT lp.id, p.id, p.banco_id, p.tipo_producto_id, p.numero_producto, '2026-06-11', 890000.00, 1, lp.user_id
FROM lotes_pago lp, proveedores p
WHERE lp.consecutivo='LOTE-2026-0009' AND p.numero_identificacion='900999000'
  AND NOT EXISTS (SELECT 1 FROM pagos WHERE lote_pago_id=lp.id AND orden=1)
UNION ALL
SELECT lp.id, p.id, p.banco_id, p.tipo_producto_id, p.numero_producto, '2026-07-03', 1275000.00, 1, lp.user_id
FROM lotes_pago lp, proveedores p
WHERE lp.consecutivo='LOTE-2026-0010' AND p.numero_identificacion='901000111'
  AND NOT EXISTS (SELECT 1 FROM pagos WHERE lote_pago_id=lp.id AND orden=1);
