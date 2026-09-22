<?php
/**
 * Modelo PagoComprobante - Archivos PDF adjuntos a un pago (uno o
 * varios por pago).
 */

class PagoComprobante extends Model {
    protected string $table = 'pago_comprobantes';

    public function create(int $pagoId, string $archivo, string $archivoOriginal, int $orden): int {
        $this->db->query(
            "INSERT INTO pago_comprobantes (pago_id, archivo, archivo_original, orden) VALUES (?, ?, ?, ?)",
            [$pagoId, $archivo, $archivoOriginal, $orden]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getByPago(int $pagoId): array {
        return $this->db->fetchAll(
            "SELECT * FROM pago_comprobantes WHERE pago_id = ? ORDER BY orden ASC, id ASC",
            [$pagoId]
        );
    }

    public function getByLote(int $loteId): array {
        return $this->db->fetchAll(
            "SELECT pc.* FROM pago_comprobantes pc
             JOIN pagos pg ON pg.id = pc.pago_id
             WHERE pg.lote_pago_id = ?
             ORDER BY pg.orden ASC, pc.orden ASC, pc.id ASC",
            [$loteId]
        );
    }

    public function findById(int $id): array|false {
        return $this->db->fetchOne("SELECT * FROM pago_comprobantes WHERE id = ?", [$id]);
    }

    public function deleteByPago(int $pagoId): bool {
        $stmt = $this->db->query("DELETE FROM pago_comprobantes WHERE pago_id = ?", [$pagoId]);
        return $stmt->rowCount() > 0;
    }
}
