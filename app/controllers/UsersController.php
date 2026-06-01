<?php
/**
 * UsersController - CRUD de usuarios
 */

class UsersController extends Controller {

    private User $userModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->userModel = new User();
    }

    /** Listar usuarios */
    public function index(): void {
        $users = $this->userModel->getAllWithRol();
        $this->view('users/index', [
            'pageTitle'   => 'Gestión de Usuarios',
            'breadcrumbs' => ['Usuarios' => null],
            'users'       => $users,
        ]);
    }

    /** Formulario crear */
    public function createForm(): void {
        $roles = $this->userModel->getRoles();
        $this->view('users/create', [
            'pageTitle'   => 'Nuevo Usuario',
            'breadcrumbs' => ['Usuarios' => BASE_URL . '/users', 'Nuevo' => null],
            'roles'       => $roles,
            'errors'      => [],
            'old'         => [],
        ]);
    }

    /** Guardar nuevo usuario */
    public function store(): void {
        $data = [
            'nombre_completo' => $this->input('nombre_completo'),
            'cedula'          => $this->input('cedula'),
            'usuario'         => $this->input('usuario'),
            'password'        => $this->inputRaw('password'),
            'password_confirm'=> $this->inputRaw('password_confirm'),
            'rol_id'          => (int)$this->input('rol_id'),
        ];

        $errors = $this->validateUser($data);

        if (!empty($errors)) {
            $roles = $this->userModel->getRoles();
            $this->view('users/create', [
                'pageTitle'   => 'Nuevo Usuario',
                'breadcrumbs' => ['Usuarios' => BASE_URL . '/users', 'Nuevo' => null],
                'roles'       => $roles,
                'errors'      => $errors,
                'old'         => $data,
            ]);
            return;
        }

        $this->userModel->create($data);
        $this->redirectWith('users', 'success', 'Usuario creado correctamente.');
    }

    /** Formulario editar */
    public function editForm(string $id): void {
        $user = $this->userModel->findById((int)$id);
        if (!$user) {
            $this->redirectWith('users', 'error', 'Usuario no encontrado.');
            return;
        }
        $roles = $this->userModel->getRoles();
        $this->view('users/edit', [
            'pageTitle'   => 'Editar Usuario',
            'breadcrumbs' => ['Usuarios' => BASE_URL . '/users', 'Editar' => null],
            'user'        => $user,
            'roles'       => $roles,
            'errors'      => [],
        ]);
    }

    /** Actualizar usuario */
    public function update(string $id): void {
        $userId = (int)$id;
        $user   = $this->userModel->findById($userId);
        if (!$user) {
            $this->redirectWith('users', 'error', 'Usuario no encontrado.');
            return;
        }

        $data = [
            'nombre_completo'  => $this->input('nombre_completo'),
            'cedula'           => $this->input('cedula'),
            'usuario'          => $this->input('usuario'),
            'rol_id'           => (int)$this->input('rol_id'),
            'password'         => $this->inputRaw('password'),
            'password_confirm' => $this->inputRaw('password_confirm'),
        ];

        $errors = $this->validateUser($data, $userId);

        if (!empty($errors)) {
            $roles = $this->userModel->getRoles();
            $this->view('users/edit', [
                'pageTitle'   => 'Editar Usuario',
                'breadcrumbs' => ['Usuarios' => BASE_URL . '/users', 'Editar' => null],
                'user'        => array_merge($user, $data),
                'roles'       => $roles,
                'errors'      => $errors,
            ]);
            return;
        }

        $this->userModel->update($userId, $data);

        // Cambiar contraseña solo si se ingresó
        if (!empty($data['password'])) {
            $this->userModel->updatePassword($userId, $data['password']);
        }

        $this->redirectWith('users', 'success', 'Usuario actualizado correctamente.');
    }

    /** Eliminar usuario */
    public function delete(string $id): void {
        $userId = (int)$id;

        // No permitir eliminar al usuario logueado
        if ($userId === (int)Session::get('user_id')) {
            $this->redirectWith('users', 'error', 'No puede eliminar su propio usuario.');
            return;
        }

        if ($this->userModel->delete($userId)) {
            $this->redirectWith('users', 'success', 'Usuario eliminado correctamente.');
        } else {
            $this->redirectWith('users', 'error', 'No se pudo eliminar el usuario.');
        }
    }

    /** Validaciones de usuario */
    private function validateUser(array $data, int $excludeId = 0): array {
        $errors = [];

        if (empty($data['nombre_completo'])) {
            $errors['nombre_completo'] = 'El nombre completo es obligatorio.';
        }

        if (empty($data['cedula'])) {
            $errors['cedula'] = 'La cédula es obligatoria.';
        } elseif ($this->userModel->cedulaExists($data['cedula'], $excludeId)) {
            $errors['cedula'] = 'Esta cédula ya está registrada.';
        }

        if (empty($data['usuario'])) {
            $errors['usuario'] = 'El nombre de usuario es obligatorio.';
        } elseif ($this->userModel->usuarioExists($data['usuario'], $excludeId)) {
            $errors['usuario'] = 'Este nombre de usuario ya está en uso.';
        }

        if (empty($data['rol_id'])) {
            $errors['rol_id'] = 'Seleccione un rol.';
        }

        // Contraseña obligatoria solo en creación
        if ($excludeId === 0) {
            if (empty($data['password'])) {
                $errors['password'] = 'La contraseña es obligatoria.';
            } elseif (strlen($data['password']) < 8) {
                $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'] = 'Las contraseñas no coinciden.';
            }
        } elseif (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'] = 'Las contraseñas no coinciden.';
            }
        }

        return $errors;
    }
}
