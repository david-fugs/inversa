<?php
/**
 * Modelo Base
 */

class Base extends Model {
    protected string $table = 'bases';

    public function getAll(string $orderBy = 'nombre'): array {
        return $this->db->fetchAll("SELECT * FROM bases ORDER BY {$orderBy}");
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO bases (nombre) VALUES (?)",
            [$data['nombre']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $current = $this->findById($id);
        if (!$current) {
            return false;
        }

        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $this->db->query(
                "UPDATE bases SET nombre = ? WHERE id = ?",
                [$data['nombre'], $id]
            );
            $this->db->query(
                "UPDATE flight_services SET base = ? WHERE base = ?",
                [$data['nombre'], $current['nombre']]
            );
            $this->db->query(
                "UPDATE users SET base_asociada = ? WHERE base_asociada = ?",
                [$data['nombre'], $current['nombre']]
            );
            $pdo->commit();
            return $stmt->rowCount() > 0 || $current['nombre'] !== $data['nombre'];
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function nombreExists(string $nombre, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM bases WHERE nombre = ? AND id != ?",
            [$nombre, $excludeId]
        );
        return $row !== false;
    }

    public function valueExists(string $nombre): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM bases WHERE nombre = ?",
            [$nombre]
        );
        return $row !== false;
    }

    public function hasFlightServices(string $nombre): bool {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM flight_services WHERE base = ?",
            [$nombre]
        );
        return (int)($row['total'] ?? 0) > 0;
    }

    public function hasUsers(string $nombre): bool {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM users WHERE base_asociada = ?",
            [$nombre]
        );
        return (int)($row['total'] ?? 0) > 0;
    }
}
