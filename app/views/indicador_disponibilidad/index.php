<?php
/** Calendario mensual de disponibilidad / confiabilidad.
 *  Requiere: $filtros, $bases, $dias (por día), $totalMes, $porEquipo, $hayEquipos. */
$meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
          7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
$mes = $filtros['mes']; $anio = $filtros['anio'];
$primerDia  = sprintf('%04d-%02d-01', $anio, $mes);
$diasMes    = (int)date('t', strtotime($primerDia));
$offset     = (int)date('N', strtotime($primerDia)) - 1;            // lunes = 0
$hoy        = date('Y-m-d');
$qBase      = $filtros['base_id'] ? '&base_id=' . (int)$filtros['base_id'] : '';
$qSolo      = $filtros['solo_registros'] ? '&solo_registros=1' : '';
$prev       = strtotime('-1 month', strtotime($primerDia));
$next       = strtotime('+1 month', strtotime($primerDia));
$urlMes     = fn(int $ts) => BASE_URL . '/indicador-disponibilidad?mes=' . date('n', $ts) . '&anio=' . date('Y', $ts) . $qBase . $qSolo;
$queryExport = http_build_query(array_filter([
    'mes' => $mes, 'anio' => $anio, 'base_id' => $filtros['base_id'], 'solo_registros' => $filtros['solo_registros'] ? 1 : 0,
]));
// Verde >= 95 %, amarillo >= 85 %, rojo por debajo.
$clase = function (?float $p): string {
    if ($p === null) return 'text-muted';
    return $p >= 95 ? 'text-success' : ($p >= 85 ? 'text-warning' : 'text-danger');
};
$fmt = fn(?float $p) => $p === null ? '—' : number_format($p, 1, ',', '.') . '%';
?>
<style>
.cal-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; }
.cal-head { text-align: center; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; padding: 4px 0; }
.cal-cell { min-height: 92px; border: 1px solid #dbe3ec; border-radius: 8px; background: #fff; padding: 6px 8px; text-align: left; width: 100%;
            display: flex; flex-direction: column; gap: 2px; cursor: pointer; transition: box-shadow .15s, border-color .15s; }
.cal-cell:hover { border-color: #1b4f8a; box-shadow: 0 2px 8px rgba(27,79,138,.15); }
.cal-cell.vacia { background: transparent; border: 0; cursor: default; min-height: 0; }
.cal-cell.futuro { background: #f8fafc; color: #94a3b8; }
.cal-cell.hoy { border: 2px solid #1b4f8a; }
.cal-dia { font-weight: 700; font-size: 14px; }
.cal-kpi { font-size: 12px; line-height: 1.25; white-space: nowrap; }
.cal-kpi b { font-weight: 700; }
.cal-nov { font-size: 11px; color: #64748b; }
@media (max-width: 767px) { .cal-cell { min-height: 70px; padding: 4px; } .cal-kpi { font-size: 10px; } .cal-nov { display: none; } }
</style>

<div class="page-actions">
    <a href="<?= BASE_URL ?>/indicador-disponibilidad/nuevo?fecha=<?= date('Y-m-d') ?><?= $filtros['base_id'] ? '&base_id=' . (int)$filtros['base_id'] : '' ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Registrar Novedad
    </a>
</div>

<?php if (!$hayEquipos): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill"></i>
        No hay equipos<?= $filtros['base_id'] ? ' en esta base' : '' ?>. Créelos (con su cantidad) en el menú <a href="<?= BASE_URL ?>/equipos">Equipos</a>.
    </div>
<?php endif; ?>

<form method="GET" action="<?= BASE_URL ?>/indicador-disponibilidad" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small" for="f_mes">Mes</label>
                <select class="form-select form-select-sm" id="f_mes" name="mes">
                    <?php foreach ($meses as $n => $nombre): ?>
                        <option value="<?= $n ?>" <?= $n === $mes ? 'selected' : '' ?>><?= $nombre ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small" for="f_anio">Año</label>
                <select class="form-select form-select-sm" id="f_anio" name="anio">
                    <?php for ($y = (int)date('Y') + 1; $y >= 2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $y === $anio ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small" for="f_base">Base</label>
                <select class="form-select form-select-sm" id="f_base" name="base_id">
                    <option value="">Todas</option>
                    <?php foreach ($bases as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= (int)$filtros['base_id'] === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-5 d-flex flex-wrap gap-2 align-items-center">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                <a href="<?= BASE_URL ?>/indicador-disponibilidad" class="btn btn-light btn-sm">Mes actual</a>
                <a href="<?= BASE_URL ?>/indicador-disponibilidad/export-excel?<?= htmlspecialchars($queryExport) ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel-fill"></i> Exportar a Excel
                </a>
            </div>
            <div class="col-12">
                <label class="small text-muted d-flex align-items-center gap-2 mb-0">
                    <input type="checkbox" class="form-check-input mt-0" name="solo_registros" value="1" <?= $filtros['solo_registros'] ? 'checked' : '' ?>>
                    Calcular solo con los días que tienen novedad registrada
                    <span>(por defecto, un día sin novedad cuenta como 100 % operativo)</span>
                </label>
            </div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Disponibilidad del mes</div>
            <div class="fs-3 fw-bold <?= $clase($totalMes['disponibilidad']) ?>"><?= $fmt($totalMes['disponibilidad']) ?></div>
            <div class="small text-muted"><?= (int)$totalMes['inoperativo'] ?> inoperativo(s)-día</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Confiabilidad del mes</div>
            <div class="fs-3 fw-bold <?= $clase($totalMes['confiabilidad']) ?>"><?= $fmt($totalMes['confiabilidad']) ?></div>
            <div class="small text-muted"><?= (int)$totalMes['correctivo'] ?> correctivo(s)-día</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Preventivos</div>
            <div class="fs-3 fw-bold"><?= (int)$totalMes['preventivo'] ?></div>
            <div class="small text-muted">unidad(es)-día en el mes</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Novedades registradas</div>
            <div class="fs-3 fw-bold"><?= (int)$totalMes['registros'] ?></div>
            <div class="small text-muted">en <?= $meses[$mes] ?> <?= $anio ?></div>
        </div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-calendar3"></i> <?= $meses[$mes] ?> <?= $anio ?></h5>
        <div class="d-flex gap-1">
            <a href="<?= $urlMes($prev) ?>" class="btn btn-light btn-sm" title="Mes anterior"><i class="bi bi-chevron-left"></i></a>
            <a href="<?= $urlMes($next) ?>" class="btn btn-light btn-sm" title="Mes siguiente"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
    <div class="card-body">
        <div class="cal-grid mb-1">
            <?php foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d): ?><div class="cal-head"><?= $d ?></div><?php endforeach; ?>
        </div>
        <div class="cal-grid">
            <?php for ($i = 0; $i < $offset; $i++): ?><div class="cal-cell vacia"></div><?php endfor; ?>
            <?php for ($dia = 1; $dia <= $diasMes; $dia++):
                $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                $t = $dias[$dia] ?? null;
                $clases = 'cal-cell' . ($fecha === $hoy ? ' hoy' : '') . ($fecha > $hoy ? ' futuro' : '');
            ?>
                <button type="button" class="<?= $clases ?>" data-dia="<?= $dia ?>" data-fecha="<?= $fecha ?>">
                    <span class="cal-dia"><?= $dia ?></span>
                    <?php if ($t): ?>
                        <span class="cal-kpi">Disp. <b class="<?= $clase($t['disponibilidad']) ?>"><?= $fmt($t['disponibilidad']) ?></b></span>
                        <span class="cal-kpi">Conf. <b class="<?= $clase($t['confiabilidad']) ?>"><?= $fmt($t['confiabilidad']) ?></b></span>
                        <?php if ($t['registros']): ?>
                            <span class="cal-nov"><i class="bi bi-exclamation-circle"></i> <?= (int)$t['registros'] ?> novedad(es)</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </button>
            <?php endfor; ?>
        </div>
        <div class="small text-muted mt-2">
            <span class="text-success fw-bold">Verde</span> ≥ 95 % ·
            <span class="text-warning fw-bold">Amarillo</span> ≥ 85 % ·
            <span class="text-danger fw-bold">Rojo</span> &lt; 85 %. Haga clic en un día para ver el detalle o registrar una novedad.
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="bi bi-table"></i> Resumen por equipo</h5></div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>Base</th><th>Equipo</th><th>Cantidad</th><th>Días</th><th>Inoperativo</th><th>Correctivo</th><th>Preventivo</th>
                        <th>Disponibilidad</th><th>Confiabilidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$porEquipo): ?>
                        <tr><td colspan="9" class="text-center text-muted">Sin equipos.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($porEquipo as $p): ?>
                        <tr>
                            <td><span class="badge bg-success"><?= htmlspecialchars($p['equipo']['base_nombre']) ?></span></td>
                            <td><strong><?= htmlspecialchars($p['equipo']['nombre']) ?></strong></td>
                            <td><?= (int)$p['equipo']['cantidad'] ?></td>
                            <td><?= (int)$p['dias'] ?></td>
                            <td><?= (int)$p['inoperativo'] ?></td>
                            <td><?= (int)$p['correctivo'] ?></td>
                            <td><?= (int)$p['preventivo'] ?></td>
                            <td class="fw-bold <?= $clase($p['disponibilidad']) ?>"><?= $fmt($p['disponibilidad']) ?></td>
                            <td class="fw-bold <?= $clase($p['confiabilidad']) ?>"><?= $fmt($p['confiabilidad']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detalle de un día -->
<div class="modal fade" id="modalDia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-day"></i> <span id="diaTitulo"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="diaResumen" class="mb-3 small"></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="diaTabla">
                        <thead>
                            <tr><th>Base</th><th>Equipo</th><th>Cant.</th><th>Inop.</th><th>Corr.</th><th>Prev.</th><th>Disp.</th><th>Conf.</th><th></th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" id="diaNueva"><i class="bi bi-plus-lg"></i> Registrar novedad de este día</a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    var dias = <?= json_encode($dias, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var baseId = <?= (int)$filtros['base_id'] ?>;
    var meses = <?= json_encode(array_values($meses), JSON_UNESCAPED_UNICODE) ?>;
    var mes = <?= $mes ?>, anio = <?= $anio ?>;
    var modalEl = document.getElementById('modalDia');
    var modal = new bootstrap.Modal(modalEl);
    var tbody = modalEl.querySelector('tbody');

    function pct(p) { return p === null || p === undefined ? '—' : p.toFixed(1).replace('.', ',') + '%'; }
    function celda(tr, texto, cls) { var td = document.createElement('td'); td.textContent = texto; if (cls) td.className = cls; tr.appendChild(td); return td; }
    function color(p) { return p === null || p === undefined ? '' : (p >= 95 ? 'text-success fw-bold' : (p >= 85 ? 'text-warning fw-bold' : 'text-danger fw-bold')); }

    document.querySelectorAll('.cal-cell[data-dia]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var dia = btn.dataset.dia, t = dias[dia];
            document.getElementById('diaTitulo').textContent = dia + ' de ' + meses[mes - 1] + ' de ' + anio;
            document.getElementById('diaNueva').href = BASE_URL + '/indicador-disponibilidad/nuevo?fecha=' + btn.dataset.fecha + (baseId ? '&base_id=' + baseId : '');
            var resumen = document.getElementById('diaResumen');
            resumen.textContent = t
                ? 'Disponibilidad del día: ' + pct(t.disponibilidad) + ' · Confiabilidad del día: ' + pct(t.confiabilidad)
                : 'Este día no tiene datos todavía.';
            tbody.innerHTML = '';
            ((t && t.registros_dia) || []).forEach(function (r) {
                var tr = document.createElement('tr');
                celda(tr, r.base); celda(tr, r.equipo); celda(tr, r.cantidad); celda(tr, r.inoperativo); celda(tr, r.correctivo); celda(tr, r.preventivo);
                celda(tr, pct(r.disponibilidad), color(r.disponibilidad)); celda(tr, pct(r.confiabilidad), color(r.confiabilidad));
                var acc = document.createElement('td');
                acc.className = 'text-end text-nowrap';
                if (r.id) {
                    var ed = document.createElement('a');
                    ed.className = 'btn btn-icon btn-outline-primary btn-sm me-1'; ed.title = 'Editar';
                    ed.href = BASE_URL + '/indicador-disponibilidad/' + r.id + '/edit';
                    ed.innerHTML = '<i class="bi bi-pencil-fill"></i>';
                    var del = document.createElement('a');
                    del.className = 'btn btn-icon btn-danger btn-sm'; del.title = 'Eliminar';
                    del.href = BASE_URL + '/indicador-disponibilidad/' + r.id + '/delete';
                    del.addEventListener('click', function (e) { if (!confirm('¿Eliminar esta novedad?')) e.preventDefault(); });
                    del.innerHTML = '<i class="bi bi-trash-fill"></i>';
                    acc.appendChild(ed); acc.appendChild(del);
                } else {
                    acc.className += ' text-muted small';
                    acc.textContent = 'sin novedad';
                }
                tr.appendChild(acc);
                tbody.appendChild(tr);
            });
            modal.show();
        });
    });
});
</script>
