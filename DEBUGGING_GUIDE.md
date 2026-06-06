# 🔧 GUÍA DE DEBUGGING - Flight Services

## 📋 Resumen de cambios

### 1. ✅ Mejoras en el Controlador (FlightServicesController.php)
- Ahora registra **TODOS** los errores en un archivo de log
- Ruta: `logs/flight_services.log`
- Formato: `[FECHA] | [TIPO] | [MENSAJE] | [ARCHIVO] | [LINEA]`

### 2. ✅ Visor de Logs Web
- **URL:** `http://localhost/inversa/view-logs.php`
- Muestra todos los errores registrados
- Permite limpiar logs
- **IMPORTANTE:** Solo funciona en localhost (máquina local)

### 3. ✅ Debug Helper JavaScript
- Archivo: `public/js/debug.js`
- Captura errores de JavaScript en tiempo real
- Muestra en la consola del navegador (F12)

## 🚨 EL ERROR 500 EN PRODUCCIÓN

**CAUSA:** Las columnas de Ventiladores no existen en la base de datos de producción

**SOLUCIÓN:** Ejecuta este SQL en phpMyAdmin en tu hosting:

```sql
ALTER TABLE `flight_services` ADD COLUMN `codigo_demora` VARCHAR(20) NULL AFTER `cumple_tiempo`;
ALTER TABLE `flight_services` ADD COLUMN `observacion_demora` TEXT NULL AFTER `codigo_demora`;
ALTER TABLE `flight_services` ADD COLUMN `rpn` VARCHAR(50) NULL AFTER `afecto_operacion`;
ALTER TABLE `flight_services` ADD COLUMN `ventiladores_activo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fracciones_15min_acu`;
ALTER TABLE `flight_services` ADD COLUMN `hora_conexion_ventiladores` TIME NULL AFTER `ventiladores_activo`;
ALTER TABLE `flight_services` ADD COLUMN `hora_desconexion_ventiladores` TIME NULL AFTER `hora_conexion_ventiladores`;
ALTER TABLE `flight_services` ADD COLUMN `tiempo_ventiladores` SMALLINT UNSIGNED NULL AFTER `hora_desconexion_ventiladores`;
ALTER TABLE `flight_services` ADD COLUMN `fracciones_hora_ventiladores` DECIMAL(8,2) NULL DEFAULT 0 AFTER `tiempo_ventiladores`;
ALTER TABLE `flight_services` ADD COLUMN `fracciones_15min_ventiladores` DECIMAL(8,2) NULL DEFAULT 0 AFTER `fracciones_hora_ventiladores`;
CREATE TABLE IF NOT EXISTS `flight_service_ventiladores_fracciones` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `flight_service_id` INT UNSIGNED NOT NULL,
    `hora_conexion` TIME NULL,
    `hora_desconexion` TIME NULL,
    `tiempo` SMALLINT UNSIGNED NULL,
    `fracciones_hora` DECIMAL(8,2) NULL DEFAULT 0,
    `fracciones_15min` DECIMAL(8,2) NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `fk_ventiladores_fs` (`flight_service_id`),
    CONSTRAINT `fk_ventiladores_fs`
        FOREIGN KEY (`flight_service_id`) REFERENCES `flight_services` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 📍 CÓMO VER LOS ERRORES

### En Desarrollo (localhost)

1. **Abre la consola del navegador:** `F12` → Pestaña `Console`
2. Intenta guardar el formulario
3. Verás el error detallado con el mensaje exacto

### En Producción (servidor remoto)

1. Ve a: `https://inversa.softepu.com/view-logs.php` (NO FUNCIONA - solo localhost)
2. En su lugar, usa SSH o FTP para descargar:
   - `/logs/flight_services.log`
3. O ve a phpMyAdmin y ejecuta:
   ```sql
   SELECT * FROM flight_services LIMIT 1;
   ```
   Para verificar que las columnas existen

## 🔍 INTERPRETACIÓN DE ERRORES COMUNES

### Error: "SQLSTATE[42S22]: Column not found"
**Causa:** Columnas de ventiladores no existen
**Solución:** Ejecuta el SQL de migraciones en producción

### Error: "Column count doesn't match value count"
**Causa:** Número de parámetros no coincide con número de columnas
**Solución:** Verificar que el INSERT statement tiene la cantidad correcta de `?`

### Error: "Call to undefined function"
**Causa:** Falta una función en app.js o el archivo no se cargó
**Solución:** Asegúrate que `public/js/app.js` está subido correctamente

## ✅ PASOS PARA RESOLVER

1. ✅ **PRIMERO:** Ejecuta el SQL en producción
2. ✅ **SEGUNDO:** Intenta guardar un registro
3. ✅ **TERCERO:** Si aún hay error, mira la consola (F12)
4. ✅ **CUARTO:** Comparte la URL o screenshot del error

## 📊 ARCHIVOS MODIFICADOS

- `app/controllers/FlightServicesController.php` - Mejorado logging
- `logs/` - Directorio nuevo para archivos de log
- `view-logs.php` - Visor web de logs
- `public/js/debug.js` - Helper de debugging

## 🎯 PRÓXIMOS PASOS

Una vez ejecutes el SQL en producción, todo debería funcionar. 
Si persiste el error:

1. Abre la consola (F12)
2. Intenta guardar
3. Comparte el error exacto que ves

¡Estamos aquí para ayudar! 🚀
