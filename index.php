<?php
/**
 * Front Controller - Punto de entrada único de la aplicación
 * Toda petición pasa por aquí gracias al .htaccess
 */

// Cargar configuración
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Autoload de clases del core y controladores
spl_autoload_register(function (string $className): void {
    $paths = [
        APP_PATH . '/core/',
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
    ];
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Iniciar sesión
Session::start();

// Crear router y registrar rutas
$router = new Router();

// ─── Raíz ───────────────────────────────────────────────────────────
$router->get('/', 'AuthController', 'index');

// ─── Autenticación ──────────────────────────────────────────────────
$router->get('/auth/login',    'AuthController', 'loginForm');
$router->post('/auth/login',   'AuthController', 'login');
$router->get('/auth/logout',   'AuthController', 'logout');
$router->get('/auth/change-password',  'AuthController', 'changePasswordForm');
$router->post('/auth/change-password', 'AuthController', 'changePassword');

// ─── Usuarios ───────────────────────────────────────────────────────
$router->get('/users',                      'UsersController', 'index');
$router->get('/users/create',               'UsersController', 'createForm');
$router->post('/users/create',              'UsersController', 'store');
$router->get('/users/edit/{id}',            'UsersController', 'editForm');
$router->post('/users/edit/{id}',           'UsersController', 'update');
$router->get('/users/toggle-editar/{id}',   'UsersController', 'toggleEditar');
$router->get('/users/delete/{id}',          'UsersController', 'delete');

// ─── Aerolíneas ─────────────────────────────────────────────────────
$router->get('/airlines',              'AirlinesController', 'index');
$router->get('/airlines/create',       'AirlinesController', 'createForm');
$router->post('/airlines/create',      'AirlinesController', 'store');
$router->get('/airlines/edit/{id}',    'AirlinesController', 'editForm');
$router->post('/airlines/edit/{id}',   'AirlinesController', 'update');
$router->get('/airlines/delete/{id}',  'AirlinesController', 'delete');

// ─── Tipos de avión ─────────────────────────────────────────────────
$router->get('/aircraft-types',              'AircraftTypesController', 'index');
$router->get('/aircraft-types/create',       'AircraftTypesController', 'createForm');
$router->post('/aircraft-types/create',      'AircraftTypesController', 'store');
$router->get('/aircraft-types/edit/{id}',    'AircraftTypesController', 'editForm');
$router->post('/aircraft-types/edit/{id}',   'AircraftTypesController', 'update');
$router->get('/aircraft-types/delete/{id}',  'AircraftTypesController', 'delete');
$router->get('/aircraft-types/by-airline/{airline_id}', 'AircraftTypesController', 'byAirline');

// ─── Tarifas / Cobros GPU ───────────────────────────────────────────
$router->get('/tarifas-cobros',              'TarifasGpuController', 'index');
$router->post('/tarifas-cobros/create',      'TarifasGpuController', 'store');
$router->post('/tarifas-cobros/edit/{id}',   'TarifasGpuController', 'update');
$router->get('/tarifas-cobros/delete/{id}',  'TarifasGpuController', 'delete');
$router->get('/tarifas-cobros/by-airline/{airline_id}', 'TarifasGpuController', 'byAirline');

// ─── Bases ──────────────────────────────────────────────────────────
$router->get('/bases',              'BasesController', 'index');
$router->get('/bases/create',       'BasesController', 'createForm');
$router->post('/bases/create',      'BasesController', 'store');
$router->get('/bases/edit/{id}',    'BasesController', 'editForm');
$router->post('/bases/edit/{id}',   'BasesController', 'update');
$router->get('/bases/delete/{id}',  'BasesController', 'delete');

// ─── Bases destino ──────────────────────────────────────────────────
$router->get('/base-destinos',              'BaseDestinosController', 'index');
$router->get('/base-destinos/create',       'BaseDestinosController', 'createForm');
$router->post('/base-destinos/create',      'BaseDestinosController', 'store');
$router->get('/base-destinos/edit/{id}',    'BaseDestinosController', 'editForm');
$router->post('/base-destinos/edit/{id}',   'BaseDestinosController', 'update');
$router->get('/base-destinos/delete/{id}',  'BaseDestinosController', 'delete');

// ─── Servicios de vuelo ─────────────────────────────────────────────
$router->get('/flight-services',              'FlightServicesController', 'index');
$router->get('/flight-services/create',       'FlightServicesController', 'createForm');
$router->post('/flight-services/create',      'FlightServicesController', 'store');
$router->get('/flight-services/view/{id}',    'FlightServicesController', 'detail');
$router->get('/flight-services/edit/{id}',    'FlightServicesController', 'editForm');
$router->post('/flight-services/edit/{id}',   'FlightServicesController', 'update');
$router->get('/flight-services/delete/{id}',  'FlightServicesController', 'delete');

// Despachar la petición
$router->dispatch();
