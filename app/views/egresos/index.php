<?php
/** Listado de Egresos / Legalizaciones. Requiere: $documentos, $tipoFiltro, $sinLote, $pagina, $totalPaginas. */
$tipos = EgresoDocumento::TIPOS;
?>
<div class="page-actions">
    <a href="<?= BASE_URL ?>/pagos/egresos/nuevo" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Documento
    </a>
</div>

<?php if ($sinLote > 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Hay <strong><?= (int)$sinLote ?></strong> documento<?= $sinLote === 1 ? '' : 's' ?> sin lote asociado.
        Asócielos a un lote desde el botón de editar para que se incluyan en el PDF combinado.
    </div>
<?php endif; ?>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $tipoFiltro === null ? 'active' : '' ?>" href="<?= BASE_URL ?>/pagos/egresos">Todos</a></li>
    <?php foreach ($tipos as $k => $label): ?>
        <li class="nav-item"><a class="nav-link <?= $tipoFiltro === $k ? 'active' : '' ?>" href="<?= BASE_URL ?>/pagos/egresos?tipo=<?= $k ?>"><?= $label ?></a></li>
    <?php endforeach; ?>
</ul>

<form method="GET" action="<?= BASE_URL ?>/pagos/egresos" class="d-flex flex-wrap gap-2 mb-3" style="max-width:560px;">
    <?php if ($tipoFiltro): ?><input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoFiltro) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= htmlspecialchars($busqueda ?? '') ?>" class="form-control" style="flex:1;min-width:200px;"
           placeholder="Buscar por nombre de archivo...">
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
    <?php if (($busqueda ?? '') !== ''): ?>
        <a href="<?= BASE_URL ?>/pagos/egresos<?= $tipoFiltro ? '?tipo=' . $tipoFiltro : '' ?>" class="btn btn-light">Limpiar</a>
    <?php endif; ?>
</form>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-receipt-cutoff"></i> Egresos / Legalizaciones</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th><th>Tipo</th><th>Fecha</th><th>Lotes</th><th>Pagos</th><th>Archivos</th>
                        <th>Registrado por</th><th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documentos)): ?>
                        <tr><td colspan="8" class="text-center text-muted"><?= ($busqueda ?? '') !== '' ? 'No hay documentos con archivos que coincidan con la búsqueda.' : 'Aún no hay documentos registrados.' ?></td></tr>
                    <?php endif; ?>
                    <?php
                    // Cada egreso va seguido de sus legalizaciones (nivel 1).
                    $filas = [];
                    foreach ($documentos as $doc) {
                        $filas[] = [$doc, 0];
                        foreach ($doc['hijas'] ?? [] as $h) $filas[] = [$h, 1];
                    }
                    ?>
                    <?php foreach ($filas as [$d, $nivel]): ?>
                        <tr <?= $nivel ? 'style="background:#f4f8fd;"' : 'style="border-top:2px solid #cfd8e3;"' ?>>
                            <td <?= $nivel ? 'style="border-left:4px solid #0d6efd;"' : '' ?>><?= (int)$d['id'] ?></td>
                            <td <?= $nivel ? 'style="padding-left:28px;"' : '' ?>>
                                <?php if ($nivel): ?><i class="bi bi-arrow-return-right text-primary"></i><?php endif; ?>
                                <span class="badge <?= $d['tipo'] === 'comprobante_egreso' ? 'bg-primary' : 'bg-info text-dark' ?>">
                                    <?= htmlspecialchars($tipos[$d['tipo']] ?? $d['tipo']) ?>
                                </span>
                                <?php if ($d['tipo'] === 'legalizacion_anticipo'): ?>
                                    <?php if ($d['egreso_id']): ?>
                                        <?php if (!$nivel): ?><small class="d-block text-muted">Egreso #<?= (int)$d['egreso_id'] ?></small><?php endif; ?>
                                    <?php else: ?>
                                        <small class="d-block text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Sin egreso asociado</small>
                                    <?php endif; ?>
                                <?php elseif (!empty($d['legalizaciones'])): ?>
                                    <small class="d-block text-muted"><?= count($d['legalizaciones']) ?> legalización(es): #<?= implode(', #', $d['legalizaciones']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($d['fecha'])) ?></td>
                            <td>
                                <?php if (empty($d['lotes'])): ?>
                                    <span class="badge bg-danger" title="Este documento no se incluirá en ningún PDF combinado">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Sin lote asociado
                                    </span>
                                <?php else: ?>
                                    <?php foreach ($d['lotes'] as $l): ?>
                                        <a href="<?= BASE_URL ?>/pagos/lotes/<?= (int)$l['id'] ?>" class="badge bg-success text-decoration-none">
                                            <?= htmlspecialchars($l['consecutivo']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td style="max-width:240px;">
                                <?php if (empty($d['pagos'])): ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                                <?php foreach ($d['pagos'] as $p): ?>
                                    <span class="badge bg-secondary" title="Lote <?= htmlspecialchars((string)$p['lote_consecutivo']) ?> · $<?= number_format((float)$p['valor'], 2) ?>">
                                        <?= htmlspecialchars($p['proveedor_nombre'] . ' · ' . $p['numero_producto']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php foreach ($d['archivos'] as $a): ?>
                                    <div>
                                        <a href="<?= BASE_URL ?>/pagos/egresos/archivos/<?= $a['id'] ?>/file" target="_blank" title="Ver <?= htmlspecialchars($a['archivo_original']) ?>">
                                            <i class="bi bi-file-earmark-pdf-fill"></i> <?= htmlspecialchars($a['archivo_original']) ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td><?= htmlspecialchars($d['registrado_por']) ?></td>
                            <td class="text-center" style="white-space:nowrap;">
                                <?php if ($d['tipo'] === 'comprobante_egreso'): ?>
                                    <button type="button" class="btn btn-icon btn-outline-secondary btn-sm" data-asociar-legalizaciones="<?= (int)$d['id'] ?>"
                                            title="Asociar legalizaciones de anticipo">
                                        <i class="bi bi-link-45deg"></i>
                                    </button>
                                    <a href="#" data-combinar="<?= BASE_URL ?>/pagos/egresos/<?= $d['id'] ?>/comprobantes" class="btn btn-icon btn-outline-primary btn-sm"
                                       title="Descargar PDF combinado (egreso, legalizaciones y comprobantes de sus pagos)">
                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>/pagos/egresos/<?= $d['id'] ?>/edit" class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/pagos/egresos/<?= $d['id'] ?>/delete" class="btn btn-icon btn-danger btn-sm" title="Eliminar"
                                   data-confirm="¿Eliminar este documento y sus archivos? Esta acción no se puede deshacer.">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $qTipo = ($tipoFiltro ? '&tipo=' . $tipoFiltro : '') . (($busqueda ?? '') !== '' ? '&q=' . urlencode($busqueda) : '');
        $paginaUrl = fn(int $n) => BASE_URL . '/pagos/egresos?page=' . $n . $qTipo;
        require APP_PATH . '/views/pagos/_paginacion.php';
        ?>
    </div>
</div>

<div class="modal fade" id="modalLegalizaciones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-link-45deg"></i> Legalizaciones del egreso #<span id="legEgresoId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size:13px;">
                    Marque las legalizaciones de anticipo que pertenecen a este comprobante de egreso. Solo aparecen las que aún no tienen egreso y las ya asociadas a este.
                    Las nuevas toman los lotes y pagos del egreso si no tienen propios.
                </p>
                <div id="legLista"><div class="text-center text-muted py-4">Cargando...</div></div>
                <small class="text-danger" id="legMsg"></small>

                <form id="legSubir" class="row g-2 align-items-end border-top pt-3 mt-3" novalidate>
                    <div class="col-12"><strong class="small">Subir una legalización nueva para este egreso</strong></div>
                    <div class="col-md-4">
                        <label class="form-label small" for="legFecha">Fecha</label>
                        <input type="date" class="form-control form-control-sm" id="legFecha" name="fecha" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small" for="legArchivos">Archivos PDF</label>
                        <input type="file" class="form-control form-control-sm" id="legArchivos" name="archivos[]" accept="application/pdf,.pdf" multiple>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload"></i> Subir legalización</button>
                        <small class="text-danger" id="legSubirMsg"></small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="legGuardar"><i class="bi bi-check-lg"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('modalLegalizaciones');
    var lista = document.getElementById('legLista');
    var msg = document.getElementById('legMsg');
    var guardar = document.getElementById('legGuardar');
    var egresoId = null, modal;
    var formSubir = document.getElementById('legSubir');
    var subirMsg = document.getElementById('legSubirMsg');
    var subido = false;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-asociar-legalizaciones]');
        if (!btn) return;
        egresoId = btn.dataset.asociarLegalizaciones;
        document.getElementById('legEgresoId').textContent = egresoId;
        msg.textContent = '';
        subirMsg.textContent = '';
        formSubir.reset();
        subido = false;
        guardar.disabled = false;
        modal = modal || new bootstrap.Modal(modalEl);
        modal.show();
        cargar();
    });

    // Si se subió algo sin pulsar "Guardar", recarga al cerrar para reflejarlo en el listado.
    modalEl.addEventListener('hidden.bs.modal', function () { if (subido) location.reload(); });

    formSubir.addEventListener('submit', function (e) {
        e.preventDefault();
        subirMsg.textContent = '';
        var b = formSubir.querySelector('button[type=submit]');
        b.disabled = true;
        // Conserva las marcas actuales para no perderlas al refrescar la lista.
        var marcadas = Array.prototype.map.call(lista.querySelectorAll('input[type=checkbox]:checked'), function (c) { return c.value; });
        fetch(BASE_URL + '/pagos/egresos/' + egresoId + '/legalizaciones/subir', { method: 'POST', body: new FormData(formSubir) })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                b.disabled = false;
                if (!d.ok) { subirMsg.textContent = d.error || 'No se pudo subir la legalización.'; return; }
                subido = true;
                formSubir.reset();
                cargar(marcadas);
            })
            .catch(function () { b.disabled = false; subirMsg.textContent = 'Error de conexión.'; });
    });

    function cargar(marcadas) {
        lista.innerHTML = '<div class="text-center text-muted py-4">Cargando...</div>';
        fetch(BASE_URL + '/pagos/egresos/' + egresoId + '/legalizaciones')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                lista.innerHTML = '';
                if (!d.ok) { lista.textContent = d.error || 'No se pudo cargar.'; return; }
                if (!d.items.length) {
                    var v = document.createElement('div');
                    v.className = 'alert alert-warning py-2';
                    v.textContent = 'No hay legalizaciones de anticipo disponibles. Suba una con el formulario de abajo.';
                    lista.appendChild(v);
                    return;
                }
                d.items.forEach(function (it) {
                    var lbl = document.createElement('label');
                    lbl.className = 'd-block border rounded p-2 mb-2 small';
                    var chk = document.createElement('input');
                    chk.type = 'checkbox'; chk.className = 'form-check-input me-2';
                    chk.value = it.id; chk.checked = it.asociada || (marcadas || []).indexOf(String(it.id)) !== -1;
                    lbl.appendChild(chk);
                    var t = document.createElement('strong');
                    t.textContent = '#' + it.id + ' · ' + it.fecha;
                    lbl.appendChild(t);
                    var n = document.createElement('span');
                    n.className = 'text-muted ms-2';
                    n.textContent = it.archivos.join(', ');
                    lbl.appendChild(n);
                    lista.appendChild(lbl);
                });
            })
            .catch(function () { lista.textContent = 'Error de conexión.'; });
    }

    guardar.addEventListener('click', function () {
        var fd = new FormData();
        lista.querySelectorAll('input[type=checkbox]:checked').forEach(function (c) { fd.append('ids[]', c.value); });
        guardar.disabled = true;
        fetch(BASE_URL + '/pagos/egresos/' + egresoId + '/legalizaciones', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { msg.textContent = d.error || 'No se pudo guardar.'; guardar.disabled = false; return; }
                location.reload();
            })
            .catch(function () { msg.textContent = 'Error de conexión.'; guardar.disabled = false; });
    });
})();
</script>
