<?php
/**
 * Modelo AircraftType
 */

class AircraftType extends Model {
    protected string $table = 'aircraft_types';

    public function getAllWithAirline(): array {
        return $this->db->fetchAll(
            "SELECT at.*, a.nombre AS airline_nombre
             FROM aircraft_types at
             JOIN airlines a ON at.airline_id = a.id
             ORDER BY a.nombre, at.tipo"
        );
    }

    public function getByAirline(int $airlineId): array {
        return $this->db->fetchAll(
            "SELECT * FROM aircraft_types WHERE airline_id = ? ORDER BY tipo",
            [$airlineId]
        );
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO aircraft_types (airline_id, tipo, tiempo_cumplimiento)
             VALUES (?, ?, ?)",
            [$data['airline_id'], $data['tipo'], $data['tiempo_cumplimiento']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE aircraft_types SET airline_id = ?, tipo = ?, tiempo_cumplimiento = ? WHERE id = ?",
            [$data['airline_id'], $data['tipo'], $data['tiempo_cumplimiento'], $id]
        );
        return $stmt->rowCount() > 0;
    }

    public function hasFlightServices(int $id): bool {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM flight_services WHERE aircraft_type_id = ?",
            [$id]
        );
        return (int)($row['total'] ?? 0) > 0;
    }

    public function findByIdWithAirline(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT at.*, a.nombre AS airline_nombre
             FROM aircraft_types at
             JOIN airlines a ON at.airline_id = a.id
             WHERE at.id = ?",
            [$id]
        );
    }
}
