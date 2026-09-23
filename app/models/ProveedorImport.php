<?php
/**
 * Modelo ProveedorImport
 * Registro histórico de cargas masivas de Proveedores desde Excel,
 * y los errores fila por fila de cada carga.
 */

class ProveedorImport extends Model {
    protected string $table = 'proveedor_imports';

    public function getAllWithUser(): array {
        return $this->db->fetchAll(
            "SELECT i.*, u.nombre_completo AS usuario_nombre
             FROM proveedor_imports i
             JOIN users u ON i.user_id = u.id
             ORDER BY i.id DESC"
        );
    }

    public function findByIdWithUser(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT i.*, u.nombre_completo AS usuario_nombre
             FROM proveedor_imports i
             JOIN users u ON i.user_id = u.id
             WHERE i.id = ?",
            [$id]
        );
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO proveedor_imports (nombre_archivo, total_filas, filas_creadas, filas_actualizadas, filas_error, user_id)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$data['nombre_archivo'], $data['total_filas'], $data['filas_creadas'], $data['filas_actualizadas'], $data['filas_error'], $data['user_id']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function updateStats(int $id, int $totalFilas, int $filasCreadas, int $filasActualizadas, int $filasError): void {
        $this->db->query(
            "UPDATE proveedor_imports SET total_filas = ?, filas_creadas = ?, filas_actualizadas = ?, filas_error = ? WHERE id = ?",
            [$totalFilas, $filasCreadas, $filasActualizadas, $filasError, $id]
        );
    }

    public function addError(int $importId, int $fila, string $mensaje, array $datosFila): void {
        $this->db->query(
            "INSERT INTO proveedor_import_errors (import_id, fila, mensaje, datos_fila) VALUES (?, ?, ?, ?)",
            [$importId, $fila, $mensaje, json_encode($datosFila, JSON_UNESCAPED_UNICODE)]
        );
    }

    public function getErrors(int $importId): array {
        return $this->db->fetchAll(
            "SELECT * FROM proveedor_import_errors WHERE import_id = ? ORDER BY fila",
            [$importId]
        );
    }
}
