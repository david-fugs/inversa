<?php
/** Modal "Descargar con pagos específicos": se eligen los pagos de un lote, se ven/suben los
 *  egresos y legalizaciones asociados a ellos y se descarga el PDF combinado solo con esos pagos.
 *  Se abre con cualquier elemento [data-pagos-especificos="ID_LOTE"]. */
?>
<div class="modal fade" id="modalPagosEsp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-ui-checks"></i> PDF con pagos específicos · Lote <span id="peTitulo"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size:13px;">
                    Elija los pagos. El PDF llevará primero los egresos / legalizaciones asociados a esos pagos y luego sus comprobantes de pago.
                    Los documentos que suba aquí quedan registrados y asociados a los pagos elegidos.
                </p>
                <div id="peContenedor"><div class="text-center text-muted py-4">Cargando...</div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="peDescargar" disabled>
                    <i class="bi bi-file-earmark-pdf-fill"></i> Descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('modalPagosEsp');
    var cont    = document.getElementById('peContenedor');
    var btnDesc = document.getElementById('peDescargar');
    var loteId  = null;
    var lote    = null;
    var tipos   = {};
    var elegidos = {};   // pagoId -> true
    var modal;

    function el(tag, cls, texto) {
        var e = document.createElement(tag);
        if (cls) e.className = cls;
        if (texto !== undefined) e.textContent = texto;
        return e;
    }

    function ids() {
        return lote.pagos.map(function (p) { return p.id; }).filter(function (id) { return elegidos[id]; });
    }

    function etiqueta(id) {
        var p = lote.pagos.filter(function (x) { return x.id === id; })[0];
        return p ? p.label : '#' + id;
    }

    function pintar() {
        cont.innerHTML = '';
        var sel = ids();
        btnDesc.disabled = sel.length === 0;

        var head = el('div', 'd-flex justify-content-between align-items-center mb-1');
        head.appendChild(el('strong', '', 'Pagos del lote'));
        var todos = el('button', 'btn btn-sm btn-outline-secondary', sel.length === lote.pagos.length ? 'Quitar todos' : 'Seleccionar todos');
        todos.type = 'button';
        todos.addEventListener('click', function () {
            var marcar = sel.length !== lote.pagos.length;
            lote.pagos.forEach(function (p) { elegidos[p.id] = marcar; });
            pintar();
        });
        head.appendChild(todos);
        cont.appendChild(head);

        var lista = el('div', 'border rounded p-2 mb-3');
        lote.pagos.forEach(function (p) {
            var lbl = el('label', 'd-block small py-1');
            var chk = el('input', 'form-check-input me-2');
            chk.type = 'checkbox'; chk.checked = !!elegidos[p.id];
            chk.addEventListener('change', function () { elegidos[p.id] = chk.checked; pintar(); });
            lbl.appendChild(chk);
            lbl.appendChild(document.createTextNode(p.label));
            lista.appendChild(lbl);
        });
        cont.appendChild(lista);

        if (!sel.length) {
            cont.appendChild(el('div', 'text-muted small', 'Seleccione al menos un pago para ver o subir sus egresos y legalizaciones.'));
            return;
        }

        cont.appendChild(el('strong', 'd-block mb-1', 'Egresos / Legalizaciones de los pagos elegidos'));
        var docs = lote.documentos.filter(function (d) {
            return d.pagos.some(function (id) { return elegidos[id]; });
        });
        if (!docs.length) {
            cont.appendChild(el('div', 'alert alert-warning py-2', 'Los pagos elegidos no tienen egreso ni legalización asociados.'));
        }
        docs.forEach(function (d) {
            var fila = el('div', 'd-flex flex-wrap align-items-center gap-2 mb-2');
            fila.appendChild(el('span', 'badge bg-primary', d.tipo_label));
            fila.appendChild(el('small', 'text-muted', d.fecha));
            d.archivos.forEach(function (a) {
                var link = el('a', 'btn btn-outline-primary btn-sm');
                link.href = a.url; link.target = '_blank';
                link.innerHTML = '<i class="bi bi-file-earmark-pdf-fill"></i> ';
                link.appendChild(document.createTextNode(a.nombre));
                fila.appendChild(link);
            });
            d.pagos.forEach(function (id) { fila.appendChild(el('span', 'badge bg-secondary', etiqueta(id))); });
            cont.appendChild(fila);
        });

        var det = el('details', 'mt-2');
        det.open = !docs.length;
        det.appendChild(el('summary', 'small text-primary', 'Subir egreso / legalización para los ' + sel.length + ' pago(s) elegidos'));
        det.appendChild(formSubida(sel));
        cont.appendChild(det);
    }

    function formSubida(sel) {
        var form = el('form', 'row g-2 align-items-end pt-2');
        form.noValidate = true;

        var c1 = el('div', 'col-md-4');
        c1.appendChild(el('label', 'form-label small', 'Tipo'));
        var tipo = el('select', 'form-select form-select-sm');
        tipo.name = 'tipo';
        var op0 = el('option', '', '-- Seleccione --'); op0.value = ''; tipo.appendChild(op0);
        Object.keys(tipos).forEach(function (k) { var o = el('option', '', tipos[k]); o.value = k; tipo.appendChild(o); });
        c1.appendChild(tipo);

        var c2 = el('div', 'col-md-3');
        c2.appendChild(el('label', 'form-label small', 'Fecha'));
        var fecha = el('input', 'form-control form-control-sm');
        fecha.type = 'date'; fecha.name = 'fecha'; fecha.value = new Date().toISOString().slice(0, 10);
        c2.appendChild(fecha);

        var c3 = el('div', 'col-md-5');
        c3.appendChild(el('label', 'form-label small', 'Archivos PDF'));
        var file = el('input', 'form-control form-control-sm');
        file.type = 'file'; file.name = 'archivos[]'; file.multiple = true; file.accept = 'application/pdf,.pdf';
        c3.appendChild(file);

        var c4 = el('div', 'col-12 d-flex align-items-center gap-2');
        var btn = el('button', 'btn btn-sm btn-outline-primary', 'Subir documento');
        btn.type = 'submit';
        var msg = el('small', 'text-danger');
        c4.appendChild(btn); c4.appendChild(msg);

        [c1, c2, c3, c4].forEach(function (c) { form.appendChild(c); });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            msg.textContent = '';
            btn.disabled = true;
            var fd = new FormData(form);
            sel.forEach(function (id) { fd.append('pagos[]', id); });
            fetch(BASE_URL + '/pagos/lotes/' + loteId + '/documentos', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) { msg.textContent = d.error || 'No se pudo subir el documento.'; btn.disabled = false; return; }
                    subido = true;
                    cargar();
                })
                .catch(function () { msg.textContent = 'Error de conexión.'; btn.disabled = false; });
        });
        return form;
    }

    function cargar() {
        fetch(BASE_URL + '/pagos/lotes/' + loteId + '/documentos')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok || !d.lotes.length) { cont.textContent = d.error || 'No se pudo cargar el lote.'; return; }
                lote = d.lotes[0];
                tipos = d.tipos;
                document.getElementById('peTitulo').textContent = lote.consecutivo;
                pintar();
            })
            .catch(function () { cont.textContent = 'Error de conexión.'; });
    }

    var subido = false;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-pagos-especificos]');
        if (!btn) return;
        e.preventDefault();
        loteId = btn.dataset.pagosEspecificos;
        elegidos = {};
        subido = false;
        btnDesc.disabled = true;
        cont.innerHTML = '<div class="text-center text-muted py-4">Cargando...</div>';
        modal = modal || new bootstrap.Modal(modalEl);
        // Cierra el modal "Ver lote" que esté abierto.
        var abierto = document.querySelector('.modal.show');
        var abiertoInst = abierto && abierto !== modalEl ? bootstrap.Modal.getInstance(abierto) : null;
        if (abiertoInst) abiertoInst.hide();
        modal.show();
        cargar();
    });

    // En el detalle del lote, recarga al cerrar si se subió algún documento (la tarjeta de egresos es del servidor).
    modalEl.addEventListener('hidden.bs.modal', function () {
        if (subido && document.body.dataset.recargarDocs === '1') location.reload();
    });

    btnDesc.addEventListener('click', function () {
        var sel = ids();
        if (!sel.length) return;
        // Reutiliza la unión de PDF del navegador (pdf-combinar.js) con la lista filtrada por pagos.
        var a = document.createElement('a');
        a.href = '#';
        a.dataset.combinar = BASE_URL + '/pagos/lotes/' + loteId + '/comprobantes?pagos=' + sel.join(',');
        a.dataset.confirmado = '1';
        a.className = 'btn btn-primary';
        a.style.cssText = 'position:fixed;left:-9999px;';
        a.textContent = 'Uniendo...';
        document.body.appendChild(a);
        subido = false;   // evita recargar al cerrar: la descarga es lo que sigue
        modal.hide();
        a.click();
        setTimeout(function () { a.remove(); }, 60000);
    });
})();
</script>
