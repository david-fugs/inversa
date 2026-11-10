<div class="page-actions">
    <a href="<?= BASE_URL ?>/combustibles-equipos/nuevo" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Tanqueo
    </a>
</div>

<form method="GET" action="<?= BASE_URL ?>/combustibles-equipos" class="card mb-3" id="filtrosForm">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small" for="f_base">Base</label>
                <select class="form-select form-select-sm" id="f_base" name="base_id">
                    <option value="">Todas</option>
                    <?php foreach ($bases as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= (int)$filtros['base_id'] === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small" for="f_desde">Desde</label>
                <input type="date" class="form-control form-control-sm" id="f_desde" name="desde" value="<?= htmlspecialchars($filtros['desde']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small" for="f_hasta">Hasta</label>
                <input type="date" class="form-control form-control-sm" id="f_hasta" name="hasta" value="<?= htmlspecialchars($filtros['hasta']) ?>">
            </div>
            <div class="col-md-5 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                <a href="<?= BASE_URL ?>/combustibles-equipos" class="btn btn-light btn-sm">Limpiar</a>
                <a href="<?= BASE_URL ?>/combustibles-equipos/export-excel?<?= htmlspecialchars(http_build_query(array_filter($filtros))) ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel-fill"></i> Exportar a Excel
                </a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-fuel-pump-fill"></i> Combustibles Equipos</h5>
        <span class="badge badge-primary"><?= count($registros) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th><th>Fecha</th><th>Base</th><th>Equipo</th><th>RPM</th><th>Galones</th><th>Horómetro</th>
                        <th>Registrado por</th><th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= (int)$r['id'] ?></td>
                            <td data-order="<?= htmlspecialchars($r['fecha']) ?>"><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                            <td><span class="badge bg-success"><?= htmlspecialchars($r['base_nombre']) ?></span></td>
                            <td><strong><?= htmlspecialchars($r['equipo_nombre']) ?></strong></td>
                            <td><?= number_format((int)$r['rpm'], 0, ',', '.') ?></td>
                            <td><?= number_format((float)$r['cantidad_galones'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($r['horometro']) ?></td>
                            <td><?= htmlspecialchars($r['registrado_por']) ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= BASE_URL ?>/combustibles-equipos/<?= (int)$r['id'] ?>/edit" class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/combustibles-equipos/<?= (int)$r['id'] ?>/delete" class="btn btn-icon btn-danger btn-sm" title="Eliminar"
                                       data-confirm="¿Eliminar este registro de combustible?">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
