<?php
/**
 * Modelo DisponibilidadRegistro - novedades diarias de equipos (inoperativo,
 * correctivo, preventivo) y cálculo de disponibilidad / confiabilidad.
 *
 *   Disponibilidad = (cantidad - inoperativo) / cantidad
 *   Confiabilidad  = (cantidad - correctivo)  / cantidad
 */

class DisponibilidadRegistro extends Model {
    protected string $table = 'disponibilidad_registros';

    /** Registros de un mes (opcionalmente de una base), con nombre de base y equipo. */
    public function delMes(int $anio, int $mes, int $baseId = 0): array {
        $ini = sprintf('%04d-%02d-01', $anio, $mes);
        $fin = date('Y-m-t', strtotime($ini));
        $sql = "SELECT d.*, b.nombre AS base_nombre, e.nombre AS equipo_nombre
                FROM disponibilidad_registros d
                JOIN bases b ON b.id = d.base_id
                JOIN equipos e ON e.id = d.equipo_id
                WHERE d.fecha BETWEEN ? AND ?";
        $params = [$ini, $fin];
        if ($baseId) { $sql .= " AND d.base_id = ?"; $params[] = $baseId; }
        return $this->db->fetchAll($sql . " ORDER BY d.fecha, b.nombre, e.nombre", $params);
    }

    public function existe(int $equipoId, string $fecha, int $excluirId = 0): bool {
        return (bool)$this->db->fetchOne(
            "SELECT id FROM disponibilidad_registros WHERE equipo_id = ? AND fecha = ? AND id <> ?",
            [$equipoId, $fecha, $excluirId]
        );
    }

    public function create(array $d, int $userId): int {
        $this->db->query(
            "INSERT INTO disponibilidad_registros (base_id, equipo_id, fecha, cantidad, inoperativo, correctivo, preventivo, user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$d['base_id'], $d['equipo_id'], $d['fecha'], $d['cantidad'], $d['inoperativo'], $d['correctivo'], $d['preventivo'], $userId]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void {
        $this->db->query(
            "UPDATE disponibilidad_registros
             SET base_id = ?, equipo_id = ?, fecha = ?, cantidad = ?, inoperativo = ?, correctivo = ?, preventivo = ?
             WHERE id = ?",
            [$d['base_id'], $d['equipo_id'], $d['fecha'], $d['cantidad'], $d['inoperativo'], $d['correctivo'], $d['preventivo'], $id]
        );
    }

    /** Porcentaje (0-100, 1 decimal) o null si no hay cantidad. */
    public static function pct(float $cantidad, float $resta): ?float {
        return $cantidad > 0 ? round(($cantidad - $resta) / $cantidad * 100, 1) : null;
    }

    /**
     * Una fila por equipo y día del mes: los registros guardados y, si
     * $contarSinNovedad, los días ya transcurridos sin registro como 100 % operativos.
     *
     * @param array $equipos  Equipos a considerar (de Equipo::getAll, con 'cantidad' y 'base_nombre').
     * @param array $registros Registros del mes (delMes).
     * @return array<int,array> cada fila: fecha, dia, equipo_id, base_nombre, equipo_nombre, cantidad,
     *                          inoperativo, correctivo, preventivo, disponibilidad, confiabilidad, registro_id
     */
    public function filasDelMes(int $anio, int $mes, array $equipos, array $registros, bool $contarSinNovedad): array {
        $diasMes = (int)date('t', strtotime(sprintf('%04d-%02d-01', $anio, $mes)));
        $hoy = date('Y-m-d');
        $porEquipoDia = [];
        foreach ($registros as $r) {
            $porEquipoDia[(int)$r['equipo_id']][(int)date('j', strtotime($r['fecha']))] = $r;
        }
        $filas = [];
        foreach ($equipos as $e) {
            for ($dia = 1; $dia <= $diasMes; $dia++) {
                $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                $r = $porEquipoDia[(int)$e['id']][$dia] ?? null;
                if ($r) {
                    $cant = (int)$r['cantidad']; $ino = (int)$r['inoperativo']; $cor = (int)$r['correctivo']; $pre = (int)$r['preventivo'];
                } elseif ($contarSinNovedad && $fecha <= $hoy) {
                    $cant = (int)$e['cantidad']; $ino = $cor = $pre = 0;
                } else {
                    continue;
                }
                $filas[] = [
                    'fecha' => $fecha, 'dia' => $dia, 'equipo_id' => (int)$e['id'],
                    'base_nombre' => $e['base_nombre'], 'equipo_nombre' => $e['nombre'],
                    'cantidad' => $cant, 'inoperativo' => $ino, 'correctivo' => $cor, 'preventivo' => $pre,
                    'disponibilidad' => self::pct($cant, $ino), 'confiabilidad' => self::pct($cant, $cor),
                    'registro_id' => $r ? (int)$r['id'] : null,
                ];
            }
        }
        usort($filas, fn($a, $b) => [$a['fecha'], $a['base_nombre'], $a['equipo_nombre']] <=> [$b['fecha'], $b['base_nombre'], $b['equipo_nombre']]);
        return $filas;
    }

    /** Suma cantidad/inoperativo/correctivo/preventivo de $filas y calcula los porcentajes. */
    public static function totales(array $filas): array {
        $t = ['cantidad' => 0, 'inoperativo' => 0, 'correctivo' => 0, 'preventivo' => 0, 'registros' => 0];
        foreach ($filas as $f) {
            $t['cantidad'] += $f['cantidad']; $t['inoperativo'] += $f['inoperativo'];
            $t['correctivo'] += $f['correctivo']; $t['preventivo'] += $f['preventivo'];
            if ($f['registro_id']) $t['registros']++;
        }
        $t['disponibilidad'] = self::pct($t['cantidad'], $t['inoperativo']);
        $t['confiabilidad']  = self::pct($t['cantidad'], $t['correctivo']);
        return $t;
    }
}
