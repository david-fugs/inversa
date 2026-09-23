<?php
/**
 * Modelo Banco
 */

class Banco extends Model {
    protected string $table = 'bancos';

    public function getAll(string $orderBy = 'nombre'): array {
        return $this->db->fetchAll(
            "SELECT * FROM bancos ORDER BY {$orderBy}"
        );
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO bancos (codigo, nombre) VALUES (?, ?)",
            [$data['codigo'], $data['nombre']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE bancos SET codigo = ?, nombre = ? WHERE id = ?",
            [$data['codigo'], $data['nombre'], $id]
        );
        return $stmt->rowCount() > 0;
    }

    public function findByCodigo(string $codigo): array|false {
        return $this->db->fetchOne(
            "SELECT * FROM bancos WHERE codigo = ?",
            [$codigo]
        );
    }

    public function codigoExists(string $codigo, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM bancos WHERE codigo = ? AND id != ?",
            [$codigo, $excludeId]
        );
        return $row !== false;
    }

    public function nombreExists(string $nombre, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM bancos WHERE nombre = ? AND id != ?",
            [$nombre, $excludeId]
        );
        return $row !== false;
    }

    public function hasProveedores(int $id): bool {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM proveedores WHERE banco_id = ?",
            [$id]
        );
        return (int)($row['total'] ?? 0) > 0;
    }
}
