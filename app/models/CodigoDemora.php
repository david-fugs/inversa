<?php
/**
 * Modelo CodigoDemora - catálogo de códigos de demora
 */

class CodigoDemora extends Model {
    protected string $table = 'codigo_demoras';

    public function getAll(string $orderBy = 'codigo'): array {
        return $this->db->fetchAll(
            "SELECT cd.*, a.nombre AS airline_nombre
             FROM codigo_demoras cd
             LEFT JOIN airlines a ON a.id = cd.airline_id
             ORDER BY cd.{$orderBy}"
        );
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO codigo_demoras (codigo, descripcion, airline_id) VALUES (?, ?, ?)",
            [$data['codigo'], $data['descripcion'], $data['airline_id']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE codigo_demoras SET codigo = ?, descripcion = ?, airline_id = ? WHERE id = ?",
            [$data['codigo'], $data['descripcion'], $data['airline_id'], $id]
        );
        return $stmt->rowCount() > 0;
    }

    /** Aerolíneas que manejan código demora (se muestran como columnas) */
    public function getAirlines(): array {
        return $this->db->fetchAll(
            "SELECT id, nombre FROM airlines
             WHERE UPPER(nombre) IN ('AVIANCA', 'CLIC', 'SATENA')
             ORDER BY FIELD(UPPER(nombre), 'AVIANCA', 'CLIC', 'SATENA')"
        );
    }

    public function codigoExists(string $codigo, int $airlineId, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM codigo_demoras WHERE codigo = ? AND airline_id = ? AND id != ?",
            [$codigo, $airlineId, $excludeId]
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
