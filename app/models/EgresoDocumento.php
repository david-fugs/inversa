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

    public function create(string $tipo, string $fecha, int $userId, array $loteIds, array $pagoIds = [], ?int $egresoId = null): int {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $egresoId = $tipo === 'legalizacion_anticipo' ? $egresoId : null;
            [$loteIds, $pagoIds] = $this->heredar($egresoId, $loteIds, $pagoIds);
            $this->db->query(
                "INSERT INTO egresos_documentos (tipo, egreso_id, fecha, user_id) VALUES (?, ?, ?, ?)",
                [$tipo, $egresoId, $fecha, $userId]
            );
            $id = (int)$this->db->lastInsertId();
            $this->setLotes($id, $loteIds);
            $this->setPagos($id, $pagoIds);
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateBasico(int $id, string $tipo, string $fecha, array $loteIds, array $pagoIds = [], ?int $egresoId = null): void {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $egresoId = $tipo === 'legalizacion_anticipo' ? $egresoId : null;
            [$loteIds, $pagoIds] = $this->heredar($egresoId, $loteIds, $pagoIds);
            $this->db->query("UPDATE egresos_documentos SET tipo = ?, egreso_id = ?, fecha = ? WHERE id = ?", [$tipo, $egresoId, $fecha, $id]);
            $this->setLotes($id, $loteIds);
            $this->setPagos($id, $pagoIds);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Una legalización sin lotes/pagos propios hereda los de su comprobante de egreso. */
    private function heredar(?int $egresoId, array $loteIds, array $pagoIds): array {
        if ($egresoId) {
            if (!array_filter(array_map('intval', $loteIds))) {
                $loteIds = array_column($this->db->fetchAll("SELECT lote_pago_id AS id FROM egresos_lotes WHERE documento_id = ?", [$egresoId]), 'id');
            }
            if (!array_filter(array_map('intval', $pagoIds))) {
                $pagoIds = array_column($this->db->fetchAll("SELECT pago_id AS id FROM egresos_pagos WHERE documento_id = ?", [$egresoId]), 'id');
            }
        }
        return [$loteIds, $pagoIds];
    }

    /** Comprobantes de egreso (para asociarles legalizaciones), con el nombre de su primer archivo. */
    public function egresosDisponibles(?int $excluirId = null): array {
        return $this->db->fetchAll(
            "SELECT d.id, d.fecha,
                    (SELECT a.archivo_original FROM egresos_archivos a WHERE a.documento_id = d.id ORDER BY a.orden, a.id LIMIT 1) AS archivo
             FROM egresos_documentos d
             WHERE d.tipo = 'comprobante_egreso' AND d.id <> ?
             ORDER BY d.fecha DESC, d.id DESC",
            [(int)$excluirId]
        );
    }

    /** Legalizaciones que se pueden asociar a un egreso: las libres y las ya asociadas a él. */
    public function legalizacionesParaEgreso(int $egresoId): array {
        $docs = $this->db->fetchAll(
            "SELECT d.* FROM egresos_documentos d
             WHERE d.tipo = 'legalizacion_anticipo' AND (d.egreso_id IS NULL OR d.egreso_id = ?)
             ORDER BY d.fecha DESC, d.id DESC",
            [$egresoId]
        );
        return $this->adjuntar($docs);
    }

    /** Deja como legalizaciones del egreso exactamente $ids (las libres o ya suyas); las nuevas heredan sus lotes/pagos si no tienen. */
    public function setLegalizaciones(int $egresoId, array $ids): void {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            if ($ids) {
                $ph = implode(',', array_fill(0, count($ids), '?'));
                $this->db->query("UPDATE egresos_documentos SET egreso_id = NULL WHERE egreso_id = ? AND id NOT IN ($ph)", array_merge([$egresoId], $ids));
            } else {
                $this->db->query("UPDATE egresos_documentos SET egreso_id = NULL WHERE egreso_id = ?", [$egresoId]);
            }
            [$loteIds, $pagoIds] = $this->heredar($egresoId, [], []);
            foreach ($ids as $id) {
                $stmt = $this->db->query(
                    "UPDATE egresos_documentos SET egreso_id = ? WHERE id = ? AND tipo = 'legalizacion_anticipo' AND (egreso_id IS NULL OR egreso_id = ?)",
                    [$egresoId, $id, $egresoId]
                );
                if (!$stmt->rowCount()) continue;
                if (!$this->db->fetchOne("SELECT 1 AS x FROM egresos_lotes WHERE documento_id = ? LIMIT 1", [$id])) {
                    foreach ($loteIds as $l) $this->db->query("INSERT IGNORE INTO egresos_lotes (documento_id, lote_pago_id) VALUES (?, ?)", [$id, (int)$l]);
                }
                if (!$this->db->fetchOne("SELECT 1 AS x FROM egresos_pagos WHERE documento_id = ? LIMIT 1", [$id])) {
                    foreach ($pagoIds as $p) $this->db->query("INSERT IGNORE INTO egresos_pagos (documento_id, pago_id) VALUES (?, ?)", [$id, (int)$p]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** ¿Existe un comprobante de egreso con ese id? */
    public function esComprobanteEgreso(int $id): bool {
        return (bool)$this->db->fetchOne("SELECT id FROM egresos_documentos WHERE id = ? AND tipo = 'comprobante_egreso'", [$id]);
    }

    /** Ids de pago de un comprobante de egreso y de sus legalizaciones. */
    public function pagoIdsDeEgreso(int $egresoId): array {
        return array_map('intval', array_column($this->db->fetchAll(
            "SELECT DISTINCT ep.pago_id FROM egresos_pagos ep
             JOIN egresos_documentos d ON d.id = ep.documento_id
             WHERE d.id = ? OR d.egreso_id = ?
             ORDER BY ep.pago_id",
            [$egresoId, $egresoId]
        ), 'pago_id'));
    }

    /** Archivos del comprobante de egreso y, a continuación, los de sus legalizaciones. */
    public function getArchivosEgreso(int $egresoId): array {
        return $this->archivosDeDocumentos([$egresoId], true);
    }

    /** Archivos de los documentos con ids dados (más sus legalizaciones/egreso si $conRelacionados), un egreso seguido de sus legalizaciones. */
    private function archivosDeDocumentos(array $docIds, bool $conRelacionados): array {
        $docIds = array_values(array_unique(array_filter(array_map('intval', $docIds))));
        if (!$docIds) return [];
        $ph = implode(',', array_fill(0, count($docIds), '?'));
        if ($conRelacionados) {
            $extra = $this->db->fetchAll(
                "SELECT id FROM egresos_documentos WHERE egreso_id IN ($ph) OR id IN (SELECT egreso_id FROM egresos_documentos WHERE id IN ($ph) AND egreso_id IS NOT NULL)",
                array_merge($docIds, $docIds)
            );
            $docIds = array_values(array_unique(array_merge($docIds, array_map('intval', array_column($extra, 'id')))));
            $ph = implode(',', array_fill(0, count($docIds), '?'));
        }
        return $this->db->fetchAll(
            "SELECT a.* FROM egresos_archivos a
             JOIN egresos_documentos d ON d.id = a.documento_id
             WHERE a.documento_id IN ($ph)
             ORDER BY COALESCE(d.egreso_id, d.id) ASC, (d.egreso_id IS NOT NULL) ASC, d.fecha ASC, d.id ASC, a.orden ASC, a.id ASC",
            $docIds
        );
    }

    /** Reemplaza el conjunto de lotes asociados. */
    private function setLotes(int $id, array $loteIds): void {
        $this->db->query("DELETE FROM egresos_lotes WHERE documento_id = ?", [$id]);
        foreach (array_unique(array_filter(array_map('intval', $loteIds))) as $loteId) {
            $this->db->query("INSERT INTO egresos_lotes (documento_id, lote_pago_id) VALUES (?, ?)", [$id, $loteId]);
        }
    }

    /** Reemplaza el conjunto de pagos asociados; el lote de cada pago también queda asociado. */
    private function setPagos(int $id, array $pagoIds): void {
        $this->db->query("DELETE FROM egresos_pagos WHERE documento_id = ?", [$id]);
        $pagoIds = array_values(array_unique(array_filter(array_map('intval', $pagoIds))));
        if (!$pagoIds) return;
        $ph = implode(',', array_fill(0, count($pagoIds), '?'));
        foreach ($this->db->fetchAll(
            "SELECT id, lote_pago_id FROM pagos WHERE id IN ($ph) AND lote_pago_id IS NOT NULL",
            $pagoIds
        ) as $p) {
            $this->db->query("INSERT INTO egresos_pagos (documento_id, pago_id) VALUES (?, ?)", [$id, (int)$p['id']]);
            $this->db->query("INSERT IGNORE INTO egresos_lotes (documento_id, lote_pago_id) VALUES (?, ?)", [$id, (int)$p['lote_pago_id']]);
        }
    }

    /** Pagos con lote, para el selector del formulario. */
    public function pagosAsociables(): array {
        return $this->db->fetchAll(
            "SELECT pg.id, pg.lote_pago_id, pg.numero_producto, pg.valor, l.consecutivo AS lote_consecutivo, pv.nombre AS proveedor_nombre
             FROM pagos pg
             JOIN lotes_pago l ON l.id = pg.lote_pago_id
             JOIN proveedores pv ON pv.id = pg.proveedor_id
             ORDER BY pg.id DESC"
        );
    }

    /** Archivos de los documentos asociados a alguno de los pagos dados, con las legalizaciones de sus egresos (y los egresos de sus legalizaciones). */
    public function getArchivosPorPagos(array $pagoIds): array {
        $pagoIds = array_values(array_unique(array_filter(array_map('intval', $pagoIds))));
        if (!$pagoIds) return [];
        $ph = implode(',', array_fill(0, count($pagoIds), '?'));
        $docs = $this->db->fetchAll("SELECT DISTINCT documento_id FROM egresos_pagos WHERE pago_id IN ($ph)", $pagoIds);
        return $this->archivosDeDocumentos(array_column($docs, 'documento_id'), true);
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

    /** Filtro del listado: "Todos" muestra los documentos sin egreso padre (las legalizaciones van anidadas bajo su egreso). */
    private function filtroListado(?string $tipo, string $q = ''): array {
        [$where, $params] = $tipo ? ['WHERE d.tipo = ?', [$tipo]] : ['WHERE d.egreso_id IS NULL', []];
        if ($q !== '') {
            // Por nombre de archivo: del propio documento o de sus legalizaciones (un egreso aparece si alguna legalización coincide).
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where .= " AND (EXISTS (SELECT 1 FROM egresos_archivos a WHERE a.documento_id = d.id AND a.archivo_original LIKE ?)
                        OR EXISTS (SELECT 1 FROM egresos_documentos h JOIN egresos_archivos ha ON ha.documento_id = h.id
                                   WHERE h.egreso_id = d.id AND ha.archivo_original LIKE ?))";
            array_push($params, $like, $like);
        }
        return [$where, $params];
    }

    /** Cantidad de filas principales del listado (sin contar las legalizaciones anidadas). */
    public function count(?string $tipo = null, string $q = ''): int {
        [$where, $params] = $this->filtroListado($tipo, $q);
        $row = $this->db->fetchOne("SELECT COUNT(*) AS n FROM egresos_documentos d {$where}", $params);
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
    public function paginate(int $limit, int $offset, ?string $tipo = null, string $q = ''): array {
        [$where, $params] = $this->filtroListado($tipo, $q);
        $docs = $this->db->fetchAll(
            "SELECT d.*, u.nombre_completo AS registrado_por
             FROM egresos_documentos d
             JOIN users u ON u.id = d.user_id
             {$where}
             ORDER BY d.fecha DESC, d.id DESC
             LIMIT " . (int)$limit . " OFFSET " . (int)$offset,
            $params
        );
        $docs = $this->adjuntar($docs);

        // Legalizaciones de cada egreso listado, para mostrarlas debajo de él.
        $egresoIds = array_column(array_filter($docs, fn($d) => $d['tipo'] === 'comprobante_egreso'), 'id');
        $hijas = [];
        if ($egresoIds) {
            $ph = implode(',', array_fill(0, count($egresoIds), '?'));
            $rows = $this->db->fetchAll(
                "SELECT d.*, u.nombre_completo AS registrado_por
                 FROM egresos_documentos d JOIN users u ON u.id = d.user_id
                 WHERE d.egreso_id IN ($ph) ORDER BY d.fecha ASC, d.id ASC",
                $egresoIds
            );
            foreach ($this->adjuntar($rows) as $h) {
                $hijas[(int)$h['egreso_id']][] = $h;
            }
        }
        foreach ($docs as &$d) {
            $d['hijas'] = $hijas[(int)$d['id']] ?? [];
        }
        unset($d);
        return $docs;
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
             ORDER BY COALESCE(d.egreso_id, d.id) ASC, (d.egreso_id IS NOT NULL) ASC, d.fecha ASC, d.id ASC",
            [$loteId]
        );
        return $this->adjuntar($docs);
    }

    /** Agrega a cada documento 'lotes' (id, consecutivo), 'pagos' y 'archivos'. */
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
        $pagos = [];
        foreach ($this->db->fetchAll(
            "SELECT ep.documento_id, pg.id, pg.numero_producto, pg.valor, pv.nombre AS proveedor_nombre, l.consecutivo AS lote_consecutivo
             FROM egresos_pagos ep
             JOIN pagos pg ON pg.id = ep.pago_id
             JOIN proveedores pv ON pv.id = pg.proveedor_id
             LEFT JOIN lotes_pago l ON l.id = pg.lote_pago_id
             WHERE ep.documento_id IN ($ph) ORDER BY pg.id",
            $ids
        ) as $r) {
            $pagos[$r['documento_id']][] = $r;
        }
        $legalizaciones = [];
        foreach ($this->db->fetchAll(
            "SELECT id, egreso_id FROM egresos_documentos WHERE egreso_id IN ($ph) ORDER BY id",
            $ids
        ) as $r) {
            $legalizaciones[$r['egreso_id']][] = (int)$r['id'];
        }
        foreach ($docs as &$d) {
            $d['legalizaciones'] = $legalizaciones[$d['id']] ?? [];
            $d['pagos']    = $pagos[$d['id']] ?? [];
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
