<?php
/**
 * Modelo Airline
 */

class Airline extends Model {
    protected string $table = 'airlines';

    public function getAll(string $orderBy = 'nombre'): array {
        return $this->db->fetchAll(
            "SELECT * FROM airlines ORDER BY {$orderBy}"
        );
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO airlines (nombre) VALUES (?)",
            [$data['nombre']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE airlines SET nombre = ? WHERE id = ?",
            [$data['nombre'], $id]
        );
        return $stmt->rowCount() > 0;
    }

    public function nombreExists(string $nombre, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM airlines WHERE nombre = ? AND id != ?",
            [$nombre, $excludeId]
        );
        return $row !== false;
    }

    public function hasAircraftTypes(int $id): bool {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM aircraft_types WHERE airline_id = ?",
            [$id]
        );
        return (int)($row['total'] ?? 0) > 0;
    }
}
