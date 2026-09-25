<?php
/**
 * Modelo LotePago - Agrupador de pagos bajo un consecutivo escrito por el usuario
 */

class LotePago extends Model {
    protected string $table = 'lotes_pago';

    public function getAll(string $orderBy = 'l.created_at DESC', string $where = '1=1'): array {
        return $this->db->fetchAll(
            "SELECT l.*, COUNT(p.id) AS total_pagos, COALESCE(SUM(p.valor), 0) AS total_valor
             FROM lotes_pago l
             LEFT JOIN pagos p ON p.lote_pago_id = l.id
             WHERE {$where}
             GROUP BY l.id
             ORDER BY {$orderBy}"
        );
    }

    public function getPorIds(array $ids): array {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }
        return $this->getAll('l.created_at DESC', "l.id IN (" . implode(',', $ids) . ")");
    }

    /** Lote con sus totales (para refrescar las tarjetas tras asignar un pago). */
    public function findConTotales(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT l.*, COUNT(p.id) AS total_pagos, COALESCE(SUM(p.valor), 0) AS total_valor
             FROM lotes_pago l
             LEFT JOIN pagos p ON p.lote_pago_id = l.id
             WHERE l.id = ?
             GROUP BY l.id",
            [$id]
        );
    }

    public function findByConsecutivo(string $consecutivo): array|false {
        return $this->db->fetchOne(
            "SELECT * FROM lotes_pago WHERE consecutivo = ?",
            [$consecutivo]
        );
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO lotes_pago (consecutivo, user_id, estado) VALUES (?, ?, 'abierto')",
            [$data['consecutivo'], $data['user_id']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function cerrar(int $id): bool {
        $stmt = $this->db->query(
            "UPDATE lotes_pago SET estado = 'cerrado', cerrado_at = NOW() WHERE id = ?",
            [$id]
        );
        return $stmt->rowCount() > 0;
    }
}
