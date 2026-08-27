<?php
/**
 * Modelo TarifaGpu - tarifas GPU / ACU por aerolínea y base
 *
 * base_id = NULL significa "todas las bases" (aplica a la aerolínea
 * sin importar la base). Si existe una tarifa específica para una
 * base, esa tiene prioridad sobre la de "todas las bases".
 *
 * tipo_cobro distingue si la tarifa es de planta eléctrica ('gpu')
 * o de aire acondicionado ('acu'). Una misma aerolínea + base puede
 * tener hasta dos tarifas: una de cada tipo.
 */

class TarifaGpu extends Model {
    protected string $table = 'tarifas_gpu';

    public const TIPO_GPU = 'gpu';
    public const TIPO_ACU = 'acu';
    public const TIPOS_COBRO = [self::TIPO_GPU, self::TIPO_ACU];

    public function getAllWithAirline(): array {
        return $this->db->fetchAll(
            "SELECT t.*, a.nombre AS airline_nombre, b.nombre AS base_nombre
             FROM tarifas_gpu t
             JOIN airlines a ON t.airline_id = a.id
             LEFT JOIN bases b ON t.base_id = b.id
             ORDER BY a.nombre, t.tipo_cobro, b.nombre"
        );
    }

    public function findByIdWithAirline(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT t.*, a.nombre AS airline_nombre, b.nombre AS base_nombre
             FROM tarifas_gpu t
             JOIN airlines a ON t.airline_id = a.id
             LEFT JOIN bases b ON t.base_id = b.id
             WHERE t.id = ?",
            [$id]
        );
    }

    /**
     * Buscar la tarifa aplicable para una aerolínea, un tipo de cobro
     * (gpu/acu) y una base: primero la específica de esa base, si no
     * existe, la de "todas las bases" (base_id IS NULL) configurada
     * para la aerolínea y el tipo de cobro.
     */
    public function findByAirlineBaseTipo(int $airlineId, ?int $baseId, string $tipoCobro): array|false {
        if ($baseId !== null) {
            $row = $this->db->fetchOne(
                "SELECT * FROM tarifas_gpu WHERE airline_id = ? AND base_id = ? AND tipo_cobro = ?",
                [$airlineId, $baseId, $tipoCobro]
            );
            if ($row !== false) {
                return $row;
            }
        }

        return $this->db->fetchOne(
            "SELECT * FROM tarifas_gpu WHERE airline_id = ? AND base_id IS NULL AND tipo_cobro = ?",
            [$airlineId, $tipoCobro]
        );
    }

    /**
     * ¿Ya existe una tarifa configurada para esta combinación
     * aerolínea + base (o aerolínea + "todas las bases") + tipo de cobro?
     */
    public function airlineBaseTipoHasTarifa(int $airlineId, ?int $baseId, string $tipoCobro, int $excludeId = 0): bool {
        if ($baseId === null) {
            $row = $this->db->fetchOne(
                "SELECT id FROM tarifas_gpu WHERE airline_id = ? AND base_id IS NULL AND tipo_cobro = ? AND id != ?",
                [$airlineId, $tipoCobro, $excludeId]
            );
        } else {
            $row = $this->db->fetchOne(
                "SELECT id FROM tarifas_gpu WHERE airline_id = ? AND base_id = ? AND tipo_cobro = ? AND id != ?",
                [$airlineId, $baseId, $tipoCobro, $excludeId]
            );
        }
        return $row !== false;
    }

    public function create(array $data): int {
        $this->db->query(
            "INSERT INTO tarifas_gpu (airline_id, base_id, tipo_cobro, primeros_minutos, fraccion_minutos)
             VALUES (?, ?, ?, ?, ?)",
            [$data['airline_id'], $data['base_id'], $data['tipo_cobro'], $data['primeros_minutos'], $data['fraccion_minutos']]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->query(
            "UPDATE tarifas_gpu SET airline_id = ?, base_id = ?, tipo_cobro = ?, primeros_minutos = ?, fraccion_minutos = ? WHERE id = ?",
            [$data['airline_id'], $data['base_id'], $data['tipo_cobro'], $data['primeros_minutos'], $data['fraccion_minutos'], $id]
        );
        return $stmt->rowCount() > 0;
    }
}
