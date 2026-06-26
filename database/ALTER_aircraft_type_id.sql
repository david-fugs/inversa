-- Permitir NULL en aircraft_type_id para soportar aerolíneas personalizadas ("Otra")
ALTER TABLE flight_services MODIFY aircraft_type_id INT UNSIGNED NULL;

-- Verificar el cambio
SHOW COLUMNS FROM flight_services WHERE FIELD = 'aircraft_type_id';
