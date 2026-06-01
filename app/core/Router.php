<?php
/**
 * Router - Enrutador principal de la aplicación MVC
 * Parsea la URL y despacha al controlador/acción correspondiente
 */

class Router {
    private array $routes = [];

    /**
     * Registrar una ruta GET
     */
    public function get(string $path, string $controller, string $action): void {
        $this->routes[] = [
            'method'     => 'GET',
            'path'       => $path,
            'controller' => $controller,
            'action'     => $action,
        ];
    }

    /**
     * Registrar una ruta POST
     */
    public function post(string $path, string $controller, string $action): void {
        $this->routes[] = [
            'method'     => 'POST',
            'path'       => $path,
            'controller' => $controller,
            'action'     => $action,
        ];
    }

    /**
     * Despachar la solicitud actual
     */
    public function dispatch(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Eliminar base URL del path
        $basePath = BASE_URL;
        if (strpos($requestUri, $basePath) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        $requestUri = '/' . trim($requestUri, '/');
        if ($requestUri === '/') {
            $requestUri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            $pattern = $this->buildPattern($route['path']);
            if (preg_match($pattern, $requestUri, $matches)) {
                // Extraer parámetros nombrados
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $controllerName = $route['controller'];
                $actionName     = $route['action'];

                $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';
                if (!file_exists($controllerFile)) {
                    $this->notFound();
                    return;
                }
                require_once $controllerFile;

                if (!class_exists($controllerName)) {
                    $this->notFound();
                    return;
                }

                $controller = new $controllerName();
                if (!method_exists($controller, $actionName)) {
                    $this->notFound();
                    return;
                }

                call_user_func_array([$controller, $actionName], $params);
                return;
            }
        }

        $this->notFound();
    }

    /**
     * Convertir patrón de ruta en regex
     * Ejemplo: /users/{id} → /^\/users\/(?P<id>[^\/]+)$/
     */
    private function buildPattern(string $path): string {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^\/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Respuesta 404
     */
    private function notFound(): void {
        http_response_code(404);
        echo '<!DOCTYPE html><html><head><title>404 - Página no encontrada</title>
        <meta charset="utf-8">
        <link rel="stylesheet" href="' . BASE_URL . '/public/css/app.css">
        </head><body class="d-flex justify-content-center align-items-center" style="min-height:100vh;background:#f5f7fa;">
        <div class="text-center">
            <h1 class="display-1 text-primary fw-bold">404</h1>
            <p class="lead">Página no encontrada</p>
            <a href="' . BASE_URL . '" class="btn btn-primary">Ir al inicio</a>
        </div></body></html>';
        exit;
    }
}
