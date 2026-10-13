<?php
/**
 * Modelo EgresoDocumento - Comprobantes de egreso y legalizaciones de
 * anticipo (uno o varios PDF), asociados opcionalmente a lotes de pago.
 */

class EgresoDocumento extends Model {
    protected string $table = 'egresos_documentos';

    public const TIPOS = [
        'comprobante_egreso'   => 'Comprobante de egreso',
        'legalizacion_anticipo' => 'Legalización de anticipo',
    ];

    public function create(string $tipo, string $fecha, int $userId, array $loteIds): int {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $this->db->query(
                "INSERT INTO egresos_documentos (tipo, fecha, user_id) VALUES (?, ?, ?)",
                [$tipo, $fecha, $userId]
            );
            $id = (int)$this->db->lastInsertId();
            $this->setLotes($id, $loteIds);
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateBasico(int $id, string $tipo, string $fecha, array $loteIds): void {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $this->db->query("UPDATE egresos_documentos SET tipo = ?, fecha = ? WHERE id = ?", [$tipo, $fecha, $id]);
            $this->setLotes($id, $loteIds);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Reemplaza el conjunto de lotes asociados. */
    private function setLotes(int $id, array $loteIds): void {
        $this->db->query("DELETE FROM egresos_lotes WHERE documento_id = ?", [$id]);
        foreach (array_unique(array_filter(array_map('intval', $loteIds))) as $loteId) {
            $this->db->query("INSERT INTO egresos_lotes (documento_id, lote_pago_id) VALUES (?, ?)", [$id, $loteId]);
        }
    }

    public function addArchivo(int $docId, string $archivo, string $original, int $orden): int {
        $this->db->query(
            "INSERT INTO egresos_archivos (documento_id, archivo, archivo_original, orden) VALUES (?, ?, ?, ?)",
            [$docId, $archivo, $original, $orden]
        );
        return (int)$this->db->lastInsertId();
    }

    public function siguienteOrdenArchivo(int $docId): int {
        $row = $this->db->fetchOne("SELECT COALESCE(MAX(orden), -1) + 1 AS n FROM egresos_archivos WHERE documento_id = ?", [$docId]);
        return (int)($row['n'] ?? 0);
    }

    public function count(?string $tipo = null): int {
        $row = $tipo
            ? $this->db->fetchOne("SELECT COUNT(*) AS n FROM egresos_documentos WHERE tipo = ?", [$tipo])
            : $this->db->fetchOne("SELECT COUNT(*) AS n FROM egresos_documentos");
        return (int)($row['n'] ?? 0);
    }

    public function countSinLote(): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS n FROM egresos_documentos d
             WHERE NOT EXISTS (SELECT 1 FROM egresos_lotes el WHERE el.documento_id = d.id)"
        );
        return (int)($row['n'] ?? 0);
    }

    /** Listado paginado con sus lotes y archivos. */
    public function paginate(int $limit, int $offset, ?string $tipo = null): array {
        $where  = $tipo ? 'WHERE d.tipo = ?' : '';
        $params = $tipo ? [$tipo] : [];
        $docs = $this->db->fetchAll(
            "SELECT d.*, u.nombre_completo AS registrado_por
             FROM egresos_documentos d
             JOIN users u ON u.id = d.user_id
             {$where}
             ORDER BY d.fecha DESC, d.id DESC
             LIMIT " . (int)$limit . " OFFSET " . (int)$offset,
            $params
        );
        return $this->adjuntar($docs);
    }

    public function findById(int $id): array|false {
        $docs = $this->db->fetchAll(
            "SELECT d.*, u.nombre_completo AS registrado_por
             FROM egresos_documentos d JOIN users u ON u.id = d.user_id WHERE d.id = ?",
            [$id]
        );
        return $docs ? $this->adjuntar($docs)[0] : false;
    }

    /** Documentos asociados a un lote (con sus archivos). */
    public function getByLote(int $loteId): array {
        $docs = $this->db->fetchAll(
            "SELECT d.*, u.nombre_completo AS registrado_por
             FROM egresos_documentos d
             JOIN egresos_lotes el ON el.documento_id = d.id
             JOIN users u ON u.id = d.user_id
             WHERE el.lote_pago_id = ?
             ORDER BY d.fecha ASC, d.id ASC",
            [$loteId]
        );
        return $this->adjuntar($docs);
    }

    /** Agrega a cada documento 'lotes' (id, consecutivo) y 'archivos'. */
    private function adjuntar(array $docs): array {
        if (!$docs) return [];
        $ids = array_column($docs, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));

        $lotes = [];
        foreach ($this->db->fetchAll(
            "SELECT el.documento_id, l.id, l.consecutivo FROM egresos_lotes el
             JOIN lotes_pago l ON l.id = el.lote_pago_id WHERE el.documento_id IN ($ph) ORDER BY l.consecutivo",
            $ids
        ) as $r) {
            $lotes[$r['documento_id']][] = ['id' => (int)$r['id'], 'consecutivo' => $r['consecutivo']];
        }
        $archivos = [];
        foreach ($this->db->fetchAll(
            "SELECT * FROM egresos_archivos WHERE documento_id IN ($ph) ORDER BY orden ASC, id ASC",
            $ids
        ) as $r) {
            $archivos[$r['documento_id']][] = $r;
        }
        foreach ($docs as &$d) {
            $d['lotes']    = $lotes[$d['id']] ?? [];
            $d['archivos'] = $archivos[$d['id']] ?? [];
        }
        unset($d);
        return $docs;
    }

    public function findArchivo(int $id): array|false {
        return $this->db->fetchOne("SELECT * FROM egresos_archivos WHERE id = ?", [$id]);
    }

    public function deleteArchivo(int $id): void {
        $this->db->query("DELETE FROM egresos_archivos WHERE id = ?", [$id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->query("DELETE FROM egresos_documentos WHERE id = ?", [$id]);
        return $stmt->rowCount() > 0;
    }
}
