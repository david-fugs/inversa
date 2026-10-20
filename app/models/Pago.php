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
                     fecha_pago, valor, comprobante_pdf, comprobante_pdf_original, orden, user_id, estado_revision)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
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
                    $data['estado_revision'] ?? 'pendiente',
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
                "UPDATE pagos SET lote_pago_id = ?, orden = ? WHERE id = ? AND lote_pago_id IS NULL AND estado_revision = 'aprobado'",
                [$loteId, $this->siguienteOrden($loteId), $pagoId]
            );
            $pdo->commit();
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Devuelve un pago de un lote a "sin lote" para poder asignarlo de nuevo. */
    public function quitarDeLote(int $pagoId): bool {
        $stmt = $this->db->query(
            "UPDATE pagos SET lote_pago_id = NULL, orden = 0 WHERE id = ? AND lote_pago_id IS NOT NULL",
            [$pagoId]
        );
        return $stmt->rowCount() > 0;
    }

    /** Pagos sin lote visibles para quien arma lotes: los propios y los
     *  registrados por usuarios "Creador Pagos" (con su estado de revisión). */
    public function getSinLote(int $userId): array {
        $pagos = $this->db->fetchAll(
            "SELECT pg.*, pv.nombre AS proveedor_nombre, pv.numero_identificacion,
                    b.nombre AS banco_nombre, tp.nombre AS tipo_producto_nombre,
                    u.nombre_completo AS creador_nombre
             FROM pagos pg
             JOIN proveedores pv ON pg.proveedor_id = pv.id
             JOIN bancos b ON pg.banco_id = b.id
             JOIN tipos_producto tp ON pg.tipo_producto_id = tp.id
             JOIN users u ON pg.user_id = u.id
             JOIN roles r ON u.rol_id = r.id
             WHERE pg.lote_pago_id IS NULL AND (pg.user_id = ? OR r.nombre = 'Creador Pagos')
             ORDER BY pg.id ASC",
            [$userId]
        );
        return $this->conComprobantes($pagos);
    }

    /** Condición SQL (y parámetros) de la bandeja del revisor. $estado: pendiente|aprobado|rechazado|null (todos). */
    private function filtroRevision(?string $estado, bool $archivados): array {
        $where  = "r.nombre = 'Creador Pagos' AND pg.archivado_revisor = ?";
        $params = [$archivados ? 1 : 0];
        if ($estado !== null) {
            $where .= ' AND pg.estado_revision = ?';
            $params[] = $estado;
        }
        return [$where, $params];
    }

    /** Pagos registrados por los Creador Pagos, para el revisor (paginado). */
    public function getParaRevision(?string $estado, bool $archivados, int $limit, int $offset): array {
        [$where, $params] = $this->filtroRevision($estado, $archivados);
        $pagos = $this->db->fetchAll(
            "SELECT pg.*, pv.nombre AS proveedor_nombre, pv.numero_identificacion,
                    b.nombre AS banco_nombre, tp.nombre AS tipo_producto_nombre,
                    u.nombre_completo AS creador_nombre, l.consecutivo AS lote_consecutivo
             FROM pagos pg
             JOIN proveedores pv ON pg.proveedor_id = pv.id
             JOIN bancos b ON pg.banco_id = b.id
             JOIN tipos_producto tp ON pg.tipo_producto_id = tp.id
             JOIN users u ON pg.user_id = u.id
             JOIN roles r ON u.rol_id = r.id
             LEFT JOIN lotes_pago l ON pg.lote_pago_id = l.id
             WHERE {$where}
             ORDER BY pg.id DESC
             LIMIT " . (int)$limit . " OFFSET " . (int)$offset,
            $params
        );
        return $this->conComprobantes($pagos);
    }

    public function contarParaRevision(?string $estado, bool $archivados): int {
        [$where, $params] = $this->filtroRevision($estado, $archivados);
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS n FROM pagos pg JOIN users u ON pg.user_id = u.id JOIN roles r ON u.rol_id = r.id WHERE {$where}",
            $params
        );
        return (int)($row['n'] ?? 0);
    }

    /** Totales de las pestañas del revisor: pendiente/aprobado/rechazado/todos (sin archivar) y archivados. */
    public function resumenRevision(): array {
        $row = $this->db->fetchOne(
            "SELECT
                COALESCE(SUM(pg.archivado_revisor = 0 AND pg.estado_revision = 'pendiente'), 0) AS pendiente,
                COALESCE(SUM(pg.archivado_revisor = 0 AND pg.estado_revision = 'aprobado'), 0)  AS aprobado,
                COALESCE(SUM(pg.archivado_revisor = 0 AND pg.estado_revision = 'rechazado'), 0) AS rechazado,
                COALESCE(SUM(pg.archivado_revisor = 0), 0) AS todos,
                COALESCE(SUM(pg.archivado_revisor = 1), 0) AS archivados
             FROM pagos pg JOIN users u ON pg.user_id = u.id JOIN roles r ON u.rol_id = r.id
             WHERE r.nombre = 'Creador Pagos'"
        );
        return array_map('intval', $row ?: ['pendiente' => 0, 'aprobado' => 0, 'rechazado' => 0, 'todos' => 0, 'archivados' => 0]);
    }

    /** Pagos registrados por un usuario (para su propia pantalla), paginado. */
    public function getByCreador(int $userId, bool $archivados, int $limit, int $offset): array {
        $pagos = $this->db->fetchAll(
            "SELECT pg.*, pv.nombre AS proveedor_nombre, pv.numero_identificacion,
                    b.nombre AS banco_nombre, tp.nombre AS tipo_producto_nombre,
                    l.consecutivo AS lote_consecutivo
             FROM pagos pg
             JOIN proveedores pv ON pg.proveedor_id = pv.id
             JOIN bancos b ON pg.banco_id = b.id
             JOIN tipos_producto tp ON pg.tipo_producto_id = tp.id
             LEFT JOIN lotes_pago l ON pg.lote_pago_id = l.id
             WHERE pg.user_id = ? AND pg.archivado_creador = ?
             ORDER BY pg.id DESC
             LIMIT " . (int)$limit . " OFFSET " . (int)$offset,
            [$userId, $archivados ? 1 : 0]
        );
        return $this->conComprobantes($pagos);
    }

    /** Totales de las pestañas del creador: activos y archivados. */
    public function resumenCreador(int $userId): array {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(SUM(archivado_creador = 0), 0) AS activos, COALESCE(SUM(archivado_creador = 1), 0) AS archivados
             FROM pagos WHERE user_id = ?",
            [$userId]
        );
        return array_map('intval', $row ?: ['activos' => 0, 'archivados' => 0]);
    }

    /** Archiva/desarchiva un pago en la bandeja del rol indicado ('creador' o 'revisor'). */
    public function setArchivado(int $id, string $rol, bool $valor): void {
        $col = $rol === 'revisor' ? 'archivado_revisor' : 'archivado_creador';
        $this->db->query("UPDATE pagos SET {$col} = ? WHERE id = ?", [$valor ? 1 : 0, $id]);
    }

    private function conComprobantes(array $pagos): array {
        $comprobante = new PagoComprobante();
        foreach ($pagos as &$p) {
            $p['comprobantes'] = $comprobante->getByPago((int)$p['id']);
        }
        unset($p);
        return $pagos;
    }

    /** Registra la decisión del revisor sobre un pago que aún no tiene lote. */
    public function setRevision(int $id, string $estado, int $revisorId, ?string $motivo): bool {
        $stmt = $this->db->query(
            "UPDATE pagos SET estado_revision = ?, revisado_por = ?, revisado_at = NOW(), motivo_rechazo = ?,
                    archivado_creador = IF(? = 'rechazado', 0, archivado_creador)
             WHERE id = ? AND lote_pago_id IS NULL",
            [$estado, $revisorId, $estado === 'rechazado' ? $motivo : null, $estado, $id]
        );
        return $stmt->rowCount() > 0;
    }

    /** Devuelve un pago a revisión (tras ser editado por su creador). */
    public function resetRevision(int $id): void {
        $this->db->query(
            "UPDATE pagos SET estado_revision = 'pendiente', revisado_por = NULL, revisado_at = NULL, motivo_rechazo = NULL, archivado_revisor = 0
             WHERE id = ? AND lote_pago_id IS NULL",
            [$id]
        );
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
