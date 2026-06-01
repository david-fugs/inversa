<?php
/**
 * Clase Controller base
 * Todos los controladores extienden esta clase
 */

abstract class Controller {
    protected Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Renderizar una vista dentro del layout principal
     */
    protected function view(string $viewPath, array $data = [], string $layout = 'main'): void {
        // Extraer variables para la vista
        extract($data);

        // Capturar el contenido de la vista
        ob_start();
        $viewFile = APP_PATH . '/views/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            die('Vista no encontrada: ' . htmlspecialchars($viewFile));
        }
        require $viewFile;
        $content = ob_get_clean();

        // Cargar el layout
        $layoutFile = APP_PATH . '/views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            die('Layout no encontrado: ' . htmlspecialchars($layoutFile));
        }
        require $layoutFile;
    }

    /**
     * Redirigir a una URL
     */
    protected function redirect(string $path): void {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
        exit;
    }

    /**
     * Redirigir con mensaje flash
     */
    protected function redirectWith(string $path, string $flashType, string $message): void {
        Session::setFlash($flashType, $message);
        $this->redirect($path);
    }

    /**
     * Responder JSON (para peticiones AJAX)
     */
    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Verificar CSRF en peticiones POST
     */
    protected function verifyCsrf(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::verifyCsrfToken($token)) {
            Session::setFlash('error', 'Token de seguridad inválido. Por favor intente de nuevo.');
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL);
            exit;
        }
    }

    /**
     * Obtener datos POST sanitizados
     */
    protected function input(string $key, mixed $default = null): mixed {
        if (!isset($_POST[$key])) return $default;
        $value = $_POST[$key];
        if (is_string($value)) {
            return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }
        return $value;
    }

    /**
     * Obtener datos POST sin escapar (para contraseñas, etc.)
     */
    protected function inputRaw(string $key, mixed $default = null): mixed {
        return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
    }

    /**
     * Verificar método HTTP
     */
    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
