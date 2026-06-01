<?php
/**
 * Configuración general de la aplicación
 */

// Base URL: se detecta automáticamente según dónde esté index.php
// Local  → http://localhost/inversa  → BASE_URL = '/inversa'
// Prod   → https://inversa.softepu.com → BASE_URL = ''
$_scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
define('BASE_URL', rtrim($_scriptDir === '/' ? '' : $_scriptDir, '/'));
unset($_scriptDir);

define('APP_NAME', 'Operaciones Aeroportuarias');
define('APP_VERSION', '1.0.0');

// Rutas del sistema
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Configuración de sesión
define('SESSION_LIFETIME', 3600); // 1 hora en segundos
define('SESSION_NAME', 'inversa_session');

// Zona horaria
date_default_timezone_set('America/Bogota');

// Mostrar errores solo en local
$_isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)
         || str_ends_with($_SERVER['HTTP_HOST'] ?? '', '.local');
define('DEBUG_MODE', $_isLocal);
unset($_isLocal);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
