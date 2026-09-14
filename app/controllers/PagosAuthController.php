<?php
/**
 * PagosAuthController - Autenticación independiente del módulo de
 * Pagos a Proveedores. Usa la misma tabla `users`/`roles` que el login
 * principal, pero solo admite roles del módulo de pagos.
 */

class PagosAuthController extends Controller {

    private const ROLES_PAGOS = ['Admin Pagos', 'Operador Pagos'];

    private User $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }

    public function loginForm(): void {
        if (Session::isLoggedIn()) {
            $this->redirect('pagos');
            return;
        }
        $this->view('pagos/auth/login', [], 'auth');
    }

    public function login(): void {
        if (Session::isLoggedIn()) {
            $this->redirect('pagos');
            return;
        }

        $usuario  = $this->inputRaw('usuario', '');
        $password = $this->inputRaw('password', '');

        if (empty($usuario) || empty($password)) {
            $this->view('pagos/auth/login', [
                'error'   => 'Por favor ingrese usuario y contraseña.',
                'usuario' => $usuario,
            ], 'auth');
            return;
        }

        $user = $this->userModel->findByUsername($usuario);

        if (!$user || !in_array($user['rol_nombre'], self::ROLES_PAGOS, true) || !password_verify($password, $user['password'])) {
            // Introducir una pausa mínima para mitigar timing attacks
            usleep(random_int(100000, 200000));
            $this->view('pagos/auth/login', [
                'error'   => 'Usuario o contraseña incorrectos.',
                'usuario' => htmlspecialchars($usuario, ENT_QUOTES),
            ], 'auth');
            return;
        }

        session_regenerate_id(true);

        Session::set('user_id',     $user['id']);
        Session::set('user_nombre', $user['nombre_completo']);
        Session::set('user_rol',    $user['rol_nombre']);
        Session::set('user', [
            'id'              => $user['id'],
            'nombre_completo' => $user['nombre_completo'],
            'usuario'         => $user['usuario'],
            'rol'             => $user['rol_nombre'],
        ]);

        $this->redirect('pagos');
    }

    public function logout(): void {
        Session::destroy();
        header('Location: ' . BASE_URL . '/pagos/login');
        exit;
    }
}
