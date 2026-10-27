<?php
/**
 * UsersController - CRUD de usuarios
 */

class UsersController extends Controller {

    private User $userModel;
    private Base $baseModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->userModel = new User();
        $this->baseModel = new Base();
    }

    /** Roles del módulo de Pagos: los únicos que puede gestionar el Admin Pagos. */
    private const ROLES_PAGOS = ['Admin Pagos', 'Operador Pagos', 'Creador Pagos', 'Revisor Pagos'];

    private function esAdminPagos(): bool {
        return Session::get('user_rol') === 'Admin Pagos';
    }

    /** Administrador gestiona todos los usuarios; Admin Pagos solo los de Pagos. */
    private function requireAdmin(): void {
        $rol = Session::get('user_rol');
        if ($rol !== 'Administrador' && $rol !== 'Admin Pagos') {
            $this->redirectWith('flight-services', 'error', 'Acceso denegado.');
            exit;
        }
    }

    /** Solo el Administrador (acciones propias de Ground Handling). */
    private function requireSuperAdmin(): void {
        if (Session::get('user_rol') !== 'Administrador') {
            $this->redirectWith($this->esAdminPagos() ? 'users' : 'flight-services', 'error', 'Acceso denegado.');
            exit;
        }
    }

    /** Layout según el módulo del usuario en sesión. */
    private function layout(): string {
        return $this->esAdminPagos() ? 'pagos' : 'main';
    }

    /** Roles que el usuario en sesión puede asignar. */
    private function rolesPermitidos(): array {
        $roles = $this->userModel->getRoles();
        if (!$this->esAdminPagos()) return $roles;
        return array_values(array_filter($roles, fn($r) => in_array($r['nombre'], self::ROLES_PAGOS, true)));
    }

    /** ¿Puede el usuario en sesión gestionar a este usuario? */
    private function puedeGestionar(array $user): bool {
        return !$this->esAdminPagos() || in_array($user['rol_nombre'], self::ROLES_PAGOS, true);
    }

    private function rolIdPermitido(int $rolId): bool {
        foreach ($this->rolesPermitidos() as $r) {
            if ((int)$r['id'] === $rolId) return true;
        }
        return false;
    }

    /** Listar usuarios */
    public function index(): void {
        $this->requireAdmin();
        $users = $this->userModel->getAllWithRol();
        if ($this->esAdminPagos()) {
            $users = array_values(array_filter($users, fn($u) => in_array($u['rol_nombre'], self::ROLES_PAGOS, true)));
        }
        $this->view('users/index', [
            'pageTitle'   => 'Gestión de Usuarios',
            'breadcrumbs' => ['Usuarios' => null],
            'users'       => $users,
        ], $this->layout());
    }

    /** Formulario crear */
    public function createForm(): void {
        $this->requireAdmin();
        $roles = $this->rolesPermitidos();
        $this->view('users/create', [
            'pageTitle'   => 'Nuevo Usuario',
            'breadcrumbs' => ['Usuarios' => BASE_URL . '/users', 'Nuevo' => null],
            'roles'       => $roles,
            'bases'       => $this->baseModel->getAll(),
            'errors'      => [],
            'old'         => [],
        ], $this->layout());
    }

    /** Guardar nuevo usuario */
    public function store(): void {
        $this->requireAdmin();
        $data = [
            'nombre_completo' => $this->input('nombre_completo'),
            'cedula'          => $this->input('cedula'),
            'usuario'         => $this->input('usuario'),
            'password'        => $this->inputRaw('password'),
            'password_confirm'=> $this->inputRaw('password_confirm'),
            'rol_id'          => (int)$this->input('rol_id'),
            'base_asociada'   => $this->input('base_asociada', ''),
            'puede_subir_pdf' => $this->input('puede_subir_pdf') ? 1 : 0,
        ];

        $errors = $this->validateUser($data);

        if (!empty($errors)) {
            $roles = $this->rolesPermitidos();
            $this->view('users/create', [
                'pageTitle'   => 'Nuevo Usuario',
                'breadcrumbs' => ['Usuarios' => BASE_URL . '/users', 'Nuevo' => null],
                'roles'       => $roles,
                'bases'       => $this->baseModel->getAll(),
                'errors'      => $errors,
                'old'         => $data,
            ], $this->layout());
            return;
        }

        if (!$this->roleRequiresBaseAsociada((int)$data['rol_id'])) {
            $data['base_asociada'] = '';
        }

        $this->userModel->create($data);
        $this->redirectWith('users', 'success', 'Usuario creado correctamente.');
    }

    /** Formulario editar */
    public function editForm(string $id): void {
        $this->requireAdmin();
        $user = $this->userModel->findById((int)$id);
        if (!$user || !$this->puedeGestionar($user)) {
            $this->redirectWith('users', 'error', 'Usuario no encontrado.');
            return;
        }
        $roles = $this->rolesPermitidos();
        $this->view('users/edit', [
            'pageTitle'   => 'Editar Usuario',
            'breadcrumbs' => ['Usuarios' => BASE_URL . '/users', 'Editar' => null],
            'user'        => $user,
            'roles'       => $roles,
            'bases'       => $this->baseModel->getAll(),
            'errors'      => [],
        ], $this->layout());
    }

    /** Actualizar usuario */
    public function update(string $id): void {
        $this->requireAdmin();
        $userId = (int)$id;
        $user   = $this->userModel->findById($userId);
        if (!$user || !$this->puedeGestionar($user)) {
            $this->redirectWith('users', 'error', 'Usuario no encontrado.');
            return;
        }

        $data = [
            'nombre_completo'  => $this->input('nombre_completo'),
            'cedula'           => $this->input('cedula'),
            'usuario'          => $this->input('usuario'),
            'rol_id'           => (int)$this->input('rol_id'),
            'base_asociada'    => $this->input('base_asociada', ''),
            'puede_subir_pdf'  => $this->input('puede_subir_pdf') ? 1 : 0,
            'password'         => $this->inputRaw('password'),
            'password_confirm' => $this->inputRaw('password_confirm'),
        ];

        $errors = $this->validateUser($data, $userId);

        if (!empty($errors)) {
            $roles = $this->rolesPermitidos();
            $this->view('users/edit', [
                'pageTitle'   => 'Editar Usuario',
                'breadcrumbs' => ['Usuarios' => BASE_URL . '/users', 'Editar' => null],
                'user'        => array_merge($user, $data),
                'roles'       => $roles,
                'bases'       => $this->baseModel->getAll(),
                'errors'      => $errors,
            ], $this->layout());
            return;
        }

        if (!$this->roleRequiresBaseAsociada((int)$data['rol_id'])) {
            $data['base_asociada'] = '';
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
        $this->requireAdmin();
        $userId = (int)$id;

        // No permitir eliminar al usuario logueado
        if ($userId === (int)Session::get('user_id')) {
            $this->redirectWith('users', 'error', 'No puede eliminar su propio usuario.');
            return;
        }
        $objetivo = $this->userModel->findById($userId);
        if (!$objetivo || !$this->puedeGestionar($objetivo)) {
            $this->redirectWith('users', 'error', 'Usuario no encontrado.');
            return;
        }

        if ($this->userModel->delete($userId)) {
            $this->redirectWith('users', 'success', 'Usuario eliminado correctamente.');
        } else {
            $this->redirectWith('users', 'error', 'No se pudo eliminar el usuario.');
        }
    }

    /** Conceder/quitar permiso de edición a un colaborador. Con "servicio_id"
     *  (opcional) el permiso se limita a ese servicio de vuelo. */
    public function permisoEdicion(string $id): void {
        $this->requireSuperAdmin();
        $userId = (int)$id;

        if (($_POST['accion'] ?? '') === 'quitar') {
            $this->userModel->quitarPermisoEdicion($userId);
            $this->redirectWith('users', 'success', 'Permiso de edición retirado.');
            return;
        }

        // Hasta 4 IDs de vuelo separados por comas (vacío = todos los vuelos).
        $texto = trim((string)($_POST['servicio_id'] ?? ''));
        $ids = [];
        if ($texto !== '') {
            foreach (explode(',', $texto) as $parte) {
                $parte = trim($parte);
                if ($parte === '') continue;
                if (!ctype_digit($parte) || (int)$parte < 1) {
                    $this->redirectWith('users', 'error', 'Los IDs de vuelo deben ser números separados por comas.');
                    return;
                }
                $ids[(int)$parte] = (int)$parte;
            }
        }
        if (count($ids) > 4) {
            $this->redirectWith('users', 'error', 'Máximo 4 IDs de vuelo por usuario.');
            return;
        }
        $this->userModel->concederPermisoEdicion($userId, $ids ? implode(',', $ids) : null);
        $this->redirectWith('users', 'success', 'Permiso de edición actualizado.');
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
        } elseif (!$this->rolIdPermitido((int)$data['rol_id'])) {
            $errors['rol_id'] = 'No tiene permiso para asignar este rol.';
        }

        if ($this->roleRequiresBaseAsociada((int)$data['rol_id'])) {
            if (empty($data['base_asociada'])) {
                $errors['base_asociada'] = 'Seleccione una base asociada.';
            } elseif (!$this->baseModel->valueExists($data['base_asociada'])) {
                $errors['base_asociada'] = 'Seleccione una base asociada válida.';
            }
        }

        // Contraseña obligatoria solo en creación
        if ($excludeId === 0) {
            if (empty($data['password'])) {
                $errors['password'] = 'La contraseña es obligatoria.';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
            } elseif ($data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'] = 'Las contraseñas no coinciden.';
            }
        } elseif (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
            } elseif ($data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'] = 'Las contraseñas no coinciden.';
            }
        }

        return $errors;
    }

    /** Roles cuyos usuarios quedan restringidos a una sola base y por
     *  eso necesitan "Base Asociada" (Colaborador, Líder SVC). */
    private const ROLES_CON_BASE_ASOCIADA = ['Colaborador', 'Líder SVC'];

    private function roleRequiresBaseAsociada(int $rolId): bool {
        foreach ($this->userModel->getRoles() as $rol) {
            if ((int)$rol['id'] === $rolId) {
                return in_array($rol['nombre'], self::ROLES_CON_BASE_ASOCIADA, true);
            }
        }
        return false;
    }
}
