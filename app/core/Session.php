<?php
/**
 * Clase Session - Gestión de sesiones PHP
 */

class Session {
    /**
     * Iniciar sesión con configuración segura
     */
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict');
            session_name(SESSION_NAME);
            session_start();
        }
    }

    /**
     * Establecer un valor en la sesión
     */
    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    /**
     * Obtener un valor de la sesión
     */
    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verificar si existe una clave en la sesión
     */
    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    /**
     * Eliminar un valor de la sesión
     */
    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    /**
     * Destruir la sesión completamente
     */
    public static function destroy(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public static function isLoggedIn(): bool {
        return self::has('user_id') && self::has('user');
    }

    /**
     * Requerir autenticación o redirigir al login
     */
    public static function requireAuth(): void {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
    }

    /**
     * Requerir NO estar autenticado (para login)
     */
    public static function requireGuest(): void {
        if (self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/flight-services');
            exit;
        }
    }

    /**
     * Generar token CSRF
     */
    public static function generateCsrfToken(): string {
        if (!self::has('csrf_token')) {
            self::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('csrf_token');
    }

    /**
     * Verificar token CSRF
     */
    public static function verifyCsrfToken(string $token): bool {
        return hash_equals(self::get('csrf_token', ''), $token);
    }

    /**
     * Establecer mensaje flash (se borra al leerlo)
     */
    public static function setFlash(string $type, string $message): void {
        self::set('flash_' . $type, $message);
    }

    /**
     * Obtener y borrar mensaje flash
     */
    public static function getFlash(string $type): ?string {
        $message = self::get('flash_' . $type);
        self::remove('flash_' . $type);
        return $message;
    }
}
