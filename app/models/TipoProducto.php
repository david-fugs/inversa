<?php
/**
 * Modelo TipoProducto
 */

class TipoProducto extends Model {
    protected string $table = 'tipos_producto';

    public function getAll(string $orderBy = 'nombre'): array {
        return $this->db->fetchAll(
            "SELECT * FROM tipos_producto ORDER BY {$orderBy}"
        );
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO tipos_producto (codigo, nombre) VALUES (?, ?)",
            [$data['codigo'], $data['nombre']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE tipos_producto SET codigo = ?, nombre = ? WHERE id = ?",
            [$data['codigo'], $data['nombre'], $id]
        );
        return $stmt->rowCount() > 0;
    }

    public function codigoExists(string $codigo, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM tipos_producto WHERE codigo = ? AND id != ?",
            [$codigo, $excludeId]
        );
        return $row !== false;
    }

    public function nombreExists(string $nombre, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM tipos_producto WHERE nombre = ? AND id != ?",
            [$nombre, $excludeId]
        );
        return $row !== false;
    }

    public function hasProveedores(int $id): bool {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM proveedores WHERE tipo_producto_id = ?",
            [$id]
        );
        return (int)($row['total'] ?? 0) > 0;
    }
}
