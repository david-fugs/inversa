<?php
/**
 * Modelo TarifaGpu - tarifas GPU por aerolínea
 */

class TarifaGpu extends Model {
    protected string $table = 'tarifas_gpu';

    public function getAllWithAirline(): array {
        return $this->db->fetchAll(
            "SELECT t.*, a.nombre AS airline_nombre
             FROM tarifas_gpu t
             JOIN airlines a ON t.airline_id = a.id
             ORDER BY a.nombre"
        );
    }

    public function findByIdWithAirline(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT t.*, a.nombre AS airline_nombre
             FROM tarifas_gpu t
             JOIN airlines a ON t.airline_id = a.id
             WHERE t.id = ?",
            [$id]
        );
    }

    public function findByAirline(int $airlineId): array|false {
        return $this->db->fetchOne(
            "SELECT * FROM tarifas_gpu WHERE airline_id = ?",
            [$airlineId]
        );
    }

    public function airlineHasTarifa(int $airlineId, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM tarifas_gpu WHERE airline_id = ? AND id != ?",
            [$airlineId, $excludeId]
        );
        return $row !== false;
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO tarifas_gpu (airline_id, primeros_minutos, fraccion_minutos)
             VALUES (?, ?, ?)",
            [$data['airline_id'], $data['primeros_minutos'], $data['fraccion_minutos']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE tarifas_gpu SET airline_id = ?, primeros_minutos = ?, fraccion_minutos = ? WHERE id = ?",
            [$data['airline_id'], $data['primeros_minutos'], $data['fraccion_minutos'], $id]
        );
        return $stmt->rowCount() > 0;
    }
}
