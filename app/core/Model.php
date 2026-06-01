<?php
/**
 * Clase Model base
 * Todos los modelos extienden esta clase
 */

abstract class Model {
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los registros activos
     */
    public function getAll(string $orderBy = ''): array {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->fetchAll($sql);
    }

    /**
     * Buscar por ID
     */
    public function findById(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    /**
     * Eliminar por ID
     */
    public function delete(int $id): bool {
        $stmt = $this->db->query(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Contar registros
     */
    public function count(): int {
        $row = $this->db->fetchOne("SELECT COUNT(*) as total FROM `{$this->table}`");
        return (int)($row['total'] ?? 0);
    }
}
