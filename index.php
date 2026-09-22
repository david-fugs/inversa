<?php
/**
 * Front Controller - Punto de entrada único de la aplicación
 * Toda petición pasa por aquí gracias al .htaccess
 */

// Cargar configuración
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Autoload de Composer (solo usado por PdfMerger, para unir PDFs del módulo de Pagos)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

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

// ─── Código Demoras ─────────────────────────────────────────────────
$router->get('/codigo-demoras',              'CodigoDemorasController', 'index');
$router->post('/codigo-demoras/create',      'CodigoDemorasController', 'store');
$router->post('/codigo-demoras/edit/{id}',   'CodigoDemorasController', 'update');
$router->get('/codigo-demoras/delete/{id}',  'CodigoDemorasController', 'delete');

// ─── Bases destino ──────────────────────────────────────────────────
$router->get('/base-destinos',              'BaseDestinosController', 'index');
$router->get('/base-destinos/create',       'BaseDestinosController', 'createForm');
$router->post('/base-destinos/create',      'BaseDestinosController', 'store');
$router->get('/base-destinos/edit/{id}',    'BaseDestinosController', 'editForm');
$router->post('/base-destinos/edit/{id}',   'BaseDestinosController', 'update');
$router->get('/base-destinos/delete/{id}',  'BaseDestinosController', 'delete');

// ─── Servicios de vuelo ─────────────────────────────────────────────
$router->get('/flight-services',              'FlightServicesController', 'index');
$router->get('/flight-services/data',         'FlightServicesController', 'data');
$router->get('/flight-services/export',       'FlightServicesController', 'export');
$router->get('/flight-services/dashboard',    'FlightServicesController', 'dashboard');
$router->get('/flight-services/create',       'FlightServicesController', 'createForm');
$router->post('/flight-services/create',      'FlightServicesController', 'store');
$router->get('/flight-services/view/{id}',    'FlightServicesController', 'detail');
$router->get('/flight-services/edit/{id}',    'FlightServicesController', 'editForm');
$router->post('/flight-services/edit/{id}',   'FlightServicesController', 'update');
$router->get('/flight-services/delete/{id}',  'FlightServicesController', 'delete');
$router->post('/flight-services/upload-file/{id}', 'FlightServicesController', 'uploadFile');
$router->get('/flight-services/delete-file/{id}',  'FlightServicesController', 'deleteFile');
$router->get('/flight-services/file/{id}',         'FlightServicesController', 'downloadFile');

// ─── Importación masiva de Servicios de Vuelo desde Excel ────────────
$router->get('/flight-services/import',              'FlightServiceImportController', 'form');
$router->post('/flight-services/import',             'FlightServiceImportController', 'upload');
$router->get('/flight-services/import/{id}/errors',  'FlightServiceImportController', 'errors');
$router->get('/flight-services/import/{id}/delete',  'FlightServiceImportController', 'deleteImport');

// ─── Autenticación módulo de Pagos a Proveedores ─────────────────────
$router->get('/pagos/login',   'PagosAuthController', 'loginForm');
$router->post('/pagos/login',  'PagosAuthController', 'login');
$router->get('/pagos/logout',  'PagosAuthController', 'logout');

// ─── Pagos: Bancos ────────────────────────────────────────────────────
$router->get('/bancos',              'BancosController', 'index');
$router->get('/bancos/create',       'BancosController', 'createForm');
$router->post('/bancos/create',      'BancosController', 'store');
$router->get('/bancos/edit/{id}',    'BancosController', 'editForm');
$router->post('/bancos/edit/{id}',   'BancosController', 'update');
$router->get('/bancos/delete/{id}',  'BancosController', 'delete');

// ─── Pagos: Tipos de Producto ─────────────────────────────────────────
$router->get('/tipos-producto',              'TiposProductoController', 'index');
$router->get('/tipos-producto/create',       'TiposProductoController', 'createForm');
$router->post('/tipos-producto/create',      'TiposProductoController', 'store');
$router->get('/tipos-producto/edit/{id}',    'TiposProductoController', 'editForm');
$router->post('/tipos-producto/edit/{id}',   'TiposProductoController', 'update');
$router->get('/tipos-producto/delete/{id}',  'TiposProductoController', 'delete');

// ─── Pagos: Proveedores ────────────────────────────────────────────────
$router->get('/proveedores',              'ProveedoresController', 'index');
$router->get('/proveedores/create',       'ProveedoresController', 'createForm');
$router->post('/proveedores/create',      'ProveedoresController', 'store');
$router->get('/proveedores/edit/{id}',    'ProveedoresController', 'editForm');
$router->post('/proveedores/edit/{id}',   'ProveedoresController', 'update');
$router->get('/proveedores/delete/{id}',  'ProveedoresController', 'delete');
$router->get('/proveedores/info/{id}',    'ProveedoresController', 'infoJson');

// ─── Pagos: Lotes de pago ───────────────────────────────────────────────
$router->get('/pagos',                        'PagosController', 'index');
$router->get('/pagos/lotes/nuevo',            'PagosController', 'nuevoLoteForm');
$router->post('/pagos/lotes/verificar',       'PagosController', 'verificarConsecutivo');
$router->get('/pagos/lotes/{id}/cerrar',      'PagosController', 'cerrarLote');
$router->get('/pagos/lotes/{id}/combinado',   'PagosController', 'descargarCombinado');
$router->get('/pagos/lotes/{id}/exportar',    'PagosController', 'exportarExcel');
$router->get('/pagos/lotes/{id}',             'PagosController', 'detalle');
$router->post('/pagos/lotes/{id}/pagos',      'PagosController', 'agregarPago');
$router->get('/pagos/pagos/delete/{id}',      'PagosController', 'eliminarPago');
$router->get('/pagos/pagos/edit/{id}',        'PagosController', 'editarPagoForm');
$router->post('/pagos/pagos/edit/{id}',       'PagosController', 'actualizarPago');
$router->get('/pagos/comprobantes/{id}/file', 'PagosController', 'descargarComprobante');

// Despachar la petición
$router->dispatch();
