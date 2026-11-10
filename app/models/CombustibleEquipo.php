<?php
/**
 * Modelo CombustibleEquipo - tanqueos de combustible de los equipos.
 */

class CombustibleEquipo extends Model {
    protected string $table = 'combustibles_equipos';

    /** @param array{base_id?:int,desde?:string,hasta?:string} $f */
    public function listar(array $f = []): array {
        $where = [];
        $params = [];
        if (!empty($f['base_id'])) { $where[] = 'c.base_id = ?'; $params[] = (int)$f['base_id']; }
        if (!empty($f['desde']))   { $where[] = 'c.fecha >= ?';  $params[] = $f['desde']; }
        if (!empty($f['hasta']))   { $where[] = 'c.fecha <= ?';  $params[] = $f['hasta']; }
        return $this->db->fetchAll(
            "SELECT c.*, b.nombre AS base_nombre, e.nombre AS equipo_nombre, e.rpm, u.nombre_completo AS registrado_por
             FROM combustibles_equipos c
             JOIN bases b ON b.id = c.base_id
             JOIN equipos e ON e.id = c.equipo_id
             JOIN users u ON u.id = c.user_id
             " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
             ORDER BY c.fecha DESC, c.id DESC",
            $params
        );
    }

    public function create(array $d, int $userId): int {
        $this->db->query(
            "INSERT INTO combustibles_equipos (base_id, equipo_id, fecha, cantidad_galones, horometro, user_id) VALUES (?, ?, ?, ?, ?, ?)",
            [$d['base_id'], $d['equipo_id'], $d['fecha'], $d['cantidad_galones'], $d['horometro'], $userId]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void {
        $this->db->query(
            "UPDATE combustibles_equipos SET base_id = ?, equipo_id = ?, fecha = ?, cantidad_galones = ?, horometro = ? WHERE id = ?",
            [$d['base_id'], $d['equipo_id'], $d['fecha'], $d['cantidad_galones'], $d['horometro'], $id]
        );
    }
}
