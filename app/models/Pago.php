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
            $loteId = $data['lote_pago_id'] ?? null;
            $orden  = $loteId ? $this->siguienteOrden((int)$loteId) : 0;

            $this->db->query(
                "INSERT INTO pagos
                    (lote_pago_id, proveedor_id, tipo_identificacion, banco_id, tipo_producto_id, numero_producto,
                     fecha_pago, valor, comprobante_pdf, comprobante_pdf_original, orden, user_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $loteId,
                    $data['proveedor_id'],
                    $data['tipo_identificacion'],
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

    private function siguienteOrden(int $loteId): int {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(MAX(orden), 0) + 1 AS siguiente FROM pagos WHERE lote_pago_id = ?",
            [$loteId]
        );
        return (int)($row['siguiente'] ?? 1);
    }

    /** Asigna un pago sin lote a un lote, dejándolo al final del orden. */
    public function asignarALote(int $pagoId, int $loteId): bool {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $this->db->query(
                "UPDATE pagos SET lote_pago_id = ?, orden = ? WHERE id = ? AND lote_pago_id IS NULL",
                [$loteId, $this->siguienteOrden($loteId), $pagoId]
            );
            $pdo->commit();
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Pagos registrados por un usuario que aún no tienen lote. */
    public function getSinLote(int $userId): array {
        $pagos = $this->db->fetchAll(
            "SELECT pg.*, pv.nombre AS proveedor_nombre, pv.numero_identificacion,
                    b.nombre AS banco_nombre, tp.nombre AS tipo_producto_nombre
             FROM pagos pg
             JOIN proveedores pv ON pg.proveedor_id = pv.id
             JOIN bancos b ON pg.banco_id = b.id
             JOIN tipos_producto tp ON pg.tipo_producto_id = tp.id
             WHERE pg.lote_pago_id IS NULL AND pg.user_id = ?
             ORDER BY pg.id ASC",
            [$userId]
        );

        $comprobante = new PagoComprobante();
        foreach ($pagos as &$p) {
            $p['comprobantes'] = $comprobante->getByPago((int)$p['id']);
        }
        unset($p);

        return $pagos;
    }

    /** Actualiza los datos editables de un pago (no toca lote/orden). */
    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE pagos SET proveedor_id = ?, tipo_identificacion = ?, banco_id = ?, tipo_producto_id = ?, numero_producto = ?,
                fecha_pago = ?, valor = ?
             WHERE id = ?",
            [
                $data['proveedor_id'],
                $data['tipo_identificacion'],
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
        $pagos = $this->db->fetchAll(
            "SELECT pg.*, pv.nombre AS proveedor_nombre, pv.numero_identificacion,
                    b.nombre AS banco_nombre, b.codigo AS banco_codigo,
                    tp.nombre AS tipo_producto_nombre, tp.codigo AS tipo_producto_codigo
             FROM pagos pg
             JOIN proveedores pv ON pg.proveedor_id = pv.id
             JOIN bancos b ON pg.banco_id = b.id
             JOIN tipos_producto tp ON pg.tipo_producto_id = tp.id
             WHERE pg.lote_pago_id = ?
             ORDER BY pg.orden ASC",
            [$loteId]
        );

        if (empty($pagos)) {
            return $pagos;
        }

        $comprobantes = (new PagoComprobante())->getByLote($loteId);
        $porPago = [];
        foreach ($comprobantes as $c) {
            $porPago[(int)$c['pago_id']][] = $c;
        }
        foreach ($pagos as &$p) {
            $p['comprobantes'] = $porPago[(int)$p['id']] ?? [];
        }
        unset($p);

        return $pagos;
    }

    public function findById(int $id): array|false {
        $pago = $this->db->fetchOne(
            "SELECT pg.*, pv.nombre AS proveedor_nombre, pv.numero_identificacion,
                    b.nombre AS banco_nombre, tp.nombre AS tipo_producto_nombre
             FROM pagos pg
             JOIN proveedores pv ON pg.proveedor_id = pv.id
             JOIN bancos b ON pg.banco_id = b.id
             JOIN tipos_producto tp ON pg.tipo_producto_id = tp.id
             WHERE pg.id = ?",
            [$id]
        );

        if ($pago) {
            $pago['comprobantes'] = (new PagoComprobante())->getByPago($id);
        }

        return $pago;
    }
}
