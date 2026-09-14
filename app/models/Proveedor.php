<?php
/**
 * Modelo Proveedor
 */

class Proveedor extends Model {
    protected string $table = 'proveedores';

    public function getAll(string $orderBy = 'p.nombre'): array {
        return $this->db->fetchAll(
            "SELECT p.*, b.nombre AS banco_nombre, tp.nombre AS tipo_producto_nombre
             FROM proveedores p
             JOIN bancos b ON p.banco_id = b.id
             JOIN tipos_producto tp ON p.tipo_producto_id = tp.id
             ORDER BY {$orderBy}"
        );
    }

    public function findById(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT p.*, b.nombre AS banco_nombre, tp.nombre AS tipo_producto_nombre
             FROM proveedores p
             JOIN bancos b ON p.banco_id = b.id
             JOIN tipos_producto tp ON p.tipo_producto_id = tp.id
             WHERE p.id = ?",
            [$id]
        );
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO proveedores (numero_identificacion, nombre, banco_id, tipo_producto_id, numero_producto)
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['numero_identificacion'],
                $data['nombre'],
                $data['banco_id'],
                $data['tipo_producto_id'],
                $data['numero_producto'],
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE proveedores SET numero_identificacion = ?, nombre = ?, banco_id = ?, tipo_producto_id = ?, numero_producto = ?
             WHERE id = ?",
            [
                $data['numero_identificacion'],
                $data['nombre'],
                $data['banco_id'],
                $data['tipo_producto_id'],
                $data['numero_producto'],
                $id,
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public function numeroIdentificacionExists(string $numero, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM proveedores WHERE numero_identificacion = ? AND id != ?",
            [$numero, $excludeId]
        );
        return $row !== false;
    }

    public function hasPagos(int $id): bool {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM pagos WHERE proveedor_id = ?",
            [$id]
        );
        return (int)($row['total'] ?? 0) > 0;
    }
}
