<?php
/**
 * Modelo Equipo - equipos por base (nombre y RPM).
 */

class Equipo extends Model {
    protected string $table = 'equipos';

    public function getAll(string $orderBy = 'b.nombre, e.nombre'): array {
        return $this->db->fetchAll(
            "SELECT e.*, b.nombre AS base_nombre
             FROM equipos e JOIN bases b ON b.id = e.base_id
             ORDER BY {$orderBy}"
        );
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO equipos (base_id, nombre, rpm, cantidad) VALUES (?, ?, ?, ?)",
            [$data['base_id'], $data['nombre'], $data['rpm'], $data['cantidad']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $this->db->query(
            "UPDATE equipos SET base_id = ?, nombre = ?, rpm = ?, cantidad = ? WHERE id = ?",
            [$data['base_id'], $data['nombre'], $data['rpm'], $data['cantidad'], $id]
        );
    }

    public function nombreExists(int $baseId, string $nombre, int $excludeId = 0): bool {
        return (bool)$this->db->fetchOne(
            "SELECT id FROM equipos WHERE base_id = ? AND nombre = ? AND id <> ?",
            [$baseId, $nombre, $excludeId]
        );
    }

    public function enUso(int $id): bool {
        $row = $this->db->fetchOne(
            "SELECT (SELECT COUNT(*) FROM control_aceites WHERE equipo_id = ?)
                  + (SELECT COUNT(*) FROM combustibles_equipos WHERE equipo_id = ?)
                  + (SELECT COUNT(*) FROM disponibilidad_registros WHERE equipo_id = ?) AS n",
            [$id, $id, $id]
        );
        return (int)($row['n'] ?? 0) > 0;
    }
}
