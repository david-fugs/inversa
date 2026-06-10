<?php
/**
 * Modelo User
 */

class User extends Model {
    protected string $table = 'users';

    /**
     * Buscar usuario por nombre de usuario
     */
    public function findByUsername(string $usuario): array|false {
        return $this->db->fetchOne(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM users u
             JOIN roles r ON u.rol_id = r.id
             WHERE u.usuario = ?",
            [$usuario]
        );
    }

    public function findById(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM users u
             JOIN roles r ON u.rol_id = r.id
             WHERE u.id = ?",
            [$id]
        );
    }

    /**
     * Obtener todos los usuarios con nombre de rol
     */
    public function getAllWithRol(): array {
        return $this->db->fetchAll(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM users u
             JOIN roles r ON u.rol_id = r.id
             ORDER BY u.nombre_completo"
        );
    }

    /**
     * Obtener colaboradores
     */
    public function getColaboradores(): array {
        return $this->db->fetchAll(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM users u
             JOIN roles r ON u.rol_id = r.id
             WHERE r.nombre = 'Colaborador'
             ORDER BY u.nombre_completo"
        );
    }

    /**
     * Obtener todos los roles
     */
    public function getRoles(): array {
        return $this->db->fetchAll("SELECT * FROM roles ORDER BY nombre");
    }

    /**
     * Crear usuario
     */
    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO users (nombre_completo, cedula, usuario, password, rol_id, base_asociada, puede_editar)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['nombre_completo'],
                $data['cedula'],
                $data['usuario'],
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                $data['rol_id'],
                $data['base_asociada'] ?: null,
                0,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualizar usuario (sin cambiar contraseña)
     */
    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE users SET nombre_completo = ?, cedula = ?, usuario = ?, rol_id = ?, base_asociada = ?
             WHERE id = ?",
            [
                $data['nombre_completo'],
                $data['cedula'],
                $data['usuario'],
                $data['rol_id'],
                $data['base_asociada'] ?: null,
                $id,
            ]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Activar/desactivar permiso de edición para colaborador
     */
    public function togglePuedeEditar(int $id): bool {
        $this->db->query(
            "UPDATE users SET puede_editar = IF(puede_editar = 1, 0, 1) WHERE id = ?",
            [$id]
        );
        return true;
    }

    /**
     * Cambiar contraseña
     */
    public function updatePassword(int $id, string $password): bool {
        $stmt = $this->db->query(
            "UPDATE users SET password = ? WHERE id = ?",
            [password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $id]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Verificar si la cédula ya existe (excluyendo ID)
     */
    public function cedulaExists(string $cedula, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM users WHERE cedula = ? AND id != ?",
            [$cedula, $excludeId]
        );
        return $row !== false;
    }

    /**
     * Verificar si el usuario ya existe (excluyendo ID)
     */
    public function usuarioExists(string $usuario, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM users WHERE usuario = ? AND id != ?",
            [$usuario, $excludeId]
        );
        return $row !== false;
    }
}
