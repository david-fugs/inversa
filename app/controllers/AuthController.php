<?php
/**
 * AuthController - Gestión de autenticación
 */

class AuthController extends Controller {

    private User $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }

    /**
     * Raíz: redirigir según estado de sesión
     */
    public function index(): void {
        if (Session::isLoggedIn()) {
            $this->redirect('flight-services');
        } else {
            $this->redirect('auth/login');
        }
    }

    /**
     * Mostrar formulario de login
     */
    public function loginForm(): void {
        Session::requireGuest();
        $this->view('auth/login', [], 'auth');
    }

    /**
     * Procesar login
     */
    public function login(): void {
        Session::requireGuest();

        $usuario  = $this->inputRaw('usuario', '');
        $password = $this->inputRaw('password', '');

        // Validación básica
        if (empty($usuario) || empty($password)) {
            $this->view('auth/login', [
                'error'   => 'Por favor ingrese usuario y contraseña.',
                'usuario' => $usuario,
            ], 'auth');
            return;
        }

        // Buscar usuario
        $user = $this->userModel->findByUsername($usuario);

        if (!$user || !password_verify($password, $user['password'])) {
            // Introducir una pausa mínima para mitigar timing attacks
            usleep(random_int(100000, 200000));
            $this->view('auth/login', [
                'error'   => 'Usuario o contraseña incorrectos.',
                'usuario' => htmlspecialchars($usuario, ENT_QUOTES),
            ], 'auth');
            return;
        }

        // Regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);

        // Guardar datos en sesión
        Session::set('user_id',     $user['id']);
        Session::set('user_nombre', $user['nombre_completo']);
        Session::set('user_rol',    $user['rol_nombre']);
        Session::set('user',        [
            'id'             => $user['id'],
            'nombre_completo'=> $user['nombre_completo'],
            'usuario'        => $user['usuario'],
            'rol'            => $user['rol_nombre'],
        ]);

        $this->redirect('flight-services');
    }

    /**
     * Cerrar sesión
     */
    public function logout(): void {
        Session::destroy();
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }

    /**
     * Formulario cambio de contraseña
     */
    public function changePasswordForm(): void {
        Session::requireAuth();
        $this->view('auth/change_password', [
            'pageTitle'   => 'Cambiar Contraseña',
            'breadcrumbs' => ['Mi Cuenta' => null, 'Cambiar Contraseña' => null],
        ]);
    }

    /**
     * Procesar cambio de contraseña
     */
    public function changePassword(): void {
        Session::requireAuth();

        $actual    = $this->inputRaw('password_actual', '');
        $nueva     = $this->inputRaw('password_nueva', '');
        $confirmar = $this->inputRaw('password_confirmar', '');

        $errors = [];

        if (empty($actual)) {
            $errors['password_actual'] = 'Ingrese su contraseña actual.';
        }
        if (strlen($nueva) < 8) {
            $errors['password_nueva'] = 'La nueva contraseña debe tener al menos 8 caracteres.';
        }
        if ($nueva !== $confirmar) {
            $errors['password_confirmar'] = 'Las contraseñas no coinciden.';
        }

        if (empty($errors)) {
            $userId = (int) Session::get('user_id');
            $user   = $this->userModel->findById($userId);

            if (!$user || !password_verify($actual, $user['password'])) {
                $errors['password_actual'] = 'La contraseña actual es incorrecta.';
            }
        }

        if (!empty($errors)) {
            $this->view('auth/change_password', [
                'pageTitle'   => 'Cambiar Contraseña',
                'breadcrumbs' => ['Mi Cuenta' => null, 'Cambiar Contraseña' => null],
                'errors'      => $errors,
            ]);
            return;
        }

        $this->userModel->updatePassword((int) Session::get('user_id'), $nueva);
        $this->redirectWith('auth/change-password', 'success', 'Contraseña actualizada correctamente.');
    }
}
