<?php
/**
 * Modelo Pago - Pago individual a un proveedor dentro de un lote
 */

class Pago extends Model {
    protected string $table = 'pagos';

    /**
     * Crear un pago dentro de un lote, calculando su `orden` (siguiente
     * consecutivo dentro del lote) en una transacción explícita para
     * evitar condiciones de carrera si dos pagos se agregan casi a la vez.
     */
    public function create(array $data): int {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $row   = $this->db->fetchOne(
                "SELECT COALESCE(MAX(orden), 0) + 1 AS siguiente FROM pagos WHERE lote_pago_id = ?",
                [$data['lote_pago_id']]
            );
            $orden = (int)($row['siguiente'] ?? 1);

            $this->db->query(
                "INSERT INTO pagos
                    (lote_pago_id, proveedor_id, banco_id, tipo_producto_id, numero_producto,
                     fecha_pago, valor, comprobante_pdf, comprobante_pdf_original, orden, user_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['lote_pago_id'],
                    $data['proveedor_id'],
                    $data['banco_id'],
                    $data['tipo_producto_id'],
                    $data['numero_producto'],
                    $data['fecha_pago'],
                    $data['valor'],
                    $data['comprobante_pdf'] ?? null,
                    $data['comprobante_pdf_original'] ?? null,
                    $orden,
                    $data['user_id'],
                ]
            );
            $id = (int)$this->db->lastInsertId();
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Actualiza los datos editables de un pago (no toca lote/orden). */
    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE pagos SET proveedor_id = ?, banco_id = ?, tipo_producto_id = ?, numero_producto = ?,
                fecha_pago = ?, valor = ?
             WHERE id = ?",
            [
                $data['proveedor_id'],
                $data['banco_id'],
                $data['tipo_producto_id'],
                $data['numero_producto'],
                $data['fecha_pago'],
                $data['valor'],
                $id,
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public function getByLote(int $loteId): array {
        return $this->db->fetchAll(
            "SELECT pg.*, pv.nombre AS proveedor_nombre, pv.numero_identificacion,
                    b.nombre AS banco_nombre, tp.nombre AS tipo_producto_nombre
             FROM pagos pg
             JOIN proveedores pv ON pg.proveedor_id = pv.id
             JOIN bancos b ON pg.banco_id = b.id
             JOIN tipos_producto tp ON pg.tipo_producto_id = tp.id
             WHERE pg.lote_pago_id = ?
             ORDER BY pg.orden ASC",
            [$loteId]
        );
    }

    public function findById(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT pg.*, pv.nombre AS proveedor_nombre, pv.numero_identificacion,
                    b.nombre AS banco_nombre, tp.nombre AS tipo_producto_nombre
             FROM pagos pg
             JOIN proveedores pv ON pg.proveedor_id = pv.id
             JOIN bancos b ON pg.banco_id = b.id
             JOIN tipos_producto tp ON pg.tipo_producto_id = tp.id
             WHERE pg.id = ?",
            [$id]
        );
    }

    public function setArchivo(int $id, string $storedName, string $originalName): bool {
        $stmt = $this->db->query(
            "UPDATE pagos SET comprobante_pdf = ?, comprobante_pdf_original = ? WHERE id = ?",
            [$storedName, $originalName, $id]
        );
        return $stmt->rowCount() > 0;
    }
}
