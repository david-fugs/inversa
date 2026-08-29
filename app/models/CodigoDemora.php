<?php
/**
 * Modelo CodigoDemora - catálogo de códigos de demora
 */

class CodigoDemora extends Model {
    protected string $table = 'codigo_demoras';

    public function getAll(string $orderBy = 'codigo'): array {
        return $this->db->fetchAll("SELECT * FROM codigo_demoras ORDER BY {$orderBy}");
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO codigo_demoras (codigo, descripcion) VALUES (?, ?)",
            [$data['codigo'], $data['descripcion']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE codigo_demoras SET codigo = ?, descripcion = ? WHERE id = ?",
            [$data['codigo'], $data['descripcion'], $id]
        );
        return $stmt->rowCount() > 0;
    }

    public function codigoExists(string $codigo, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM codigo_demoras WHERE codigo = ? AND id != ?",
            [$codigo, $excludeId]
        );
        return $row !== false;
    }

    public function hasFlightServices(int $id): bool {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM flight_services WHERE codigo_demora_id = ?",
            [$id]
        );
        return (int)($row['total'] ?? 0) > 0;
    }
}
