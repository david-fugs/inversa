<!-- Modal: documentos de egreso / legalización antes de unir el PDF -->
<div class="modal fade" id="modalDocumentosPdf" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt-cutoff"></i> Egresos / Legalizaciones del PDF combinado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size:13px;">
                    Los documentos asociados a cada lote van <strong>al inicio</strong> del PDF combinado, antes de los comprobantes de pago.
                    Si falta alguno, puede subirlo aquí. También puede definir el orden de los comprobantes de pago.
                </p>
                <div id="docsContenedor"><div class="text-center text-muted py-4">Cargando...</div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="docsConfirmar">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Confirmar y descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Antes de unir el PDF (botón "PDF Combinado (todos)" y el del modal "Ver lote"),
// muestra los documentos de egreso/legalización de los lotes y permite subir los
// que falten. Al confirmar se dispara la unión (public/js/pdf-combinar.js), que
// pone esos documentos primero.
(function () {
    var modalEl   = document.getElementById('modalDocumentosPdf');
    var cont      = document.getElementById('docsContenedor');
    var confirmar = document.getElementById('docsConfirmar');
    var urlJson   = null;      // URL de los comprobantes (la que usa pdf-combinar.js)
    var urlDocs   = null;      // URL de los documentos de egreso
    var modal;

    function el(tag, cls, texto) {
        var e = document.createElement(tag);
        if (cls) e.className = cls;
        if (texto !== undefined) e.textContent = texto;
        return e;
    }

    function formSubida(lote, tipos) {
        var form = el('form', 'row g-2 align-items-end');
        form.noValidate = true;

        var c1 = el('div', 'col-md-4');
        c1.appendChild(el('label', 'form-label small', 'Tipo'));
        var sel = el('select', 'form-select form-select-sm');
        sel.name = 'tipo';
        var op0 = el('option', '', '-- Seleccione --'); op0.value = ''; sel.appendChild(op0);
        Object.keys(tipos).forEach(function (k) { var o = el('option', '', tipos[k]); o.value = k; sel.appendChild(o); });
        c1.appendChild(sel);

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

        var cE = el('div', 'col-12');
        cE.style.display = 'none';
        cE.appendChild(el('label', 'form-label small', 'Comprobante de egreso asociado'));
        var selE = el('select', 'form-select form-select-sm');
        selE.name = 'egreso_id';
        var opE = el('option', '', '-- Seleccione --'); opE.value = ''; selE.appendChild(opE);
        (lote.egresos || []).forEach(function (e) { var o = el('option', '', e.label); o.value = e.id; selE.appendChild(o); });
        cE.appendChild(selE);
        if (!(lote.egresos || []).length) cE.appendChild(el('small', 'text-danger d-block', 'Este lote no tiene comprobante de egreso: suba primero el comprobante de egreso.'));
        sel.addEventListener('change', function () { cE.style.display = sel.value === 'legalizacion_anticipo' ? '' : 'none'; });

        var c5 = el('div', 'col-12');
        if (lote.pagos && lote.pagos.length) {
            c5.appendChild(el('label', 'form-label small mb-1', 'Pagos que cubre (opcional)'));
            lote.pagos.forEach(function (p) {
                var lbl = el('label', 'd-block small');
                var chk = el('input', 'form-check-input me-1');
                chk.type = 'checkbox'; chk.name = 'pagos[]'; chk.value = p.id;
                lbl.appendChild(chk);
                lbl.appendChild(document.createTextNode(p.label));
                c5.appendChild(lbl);
            });
        }

        var c4 = el('div', 'col-12 d-flex align-items-center gap-2');
        var btn = el('button', 'btn btn-sm btn-outline-primary', 'Subir documento');
        btn.type = 'submit';
        var msg = el('small', 'text-danger');
        c4.appendChild(btn); c4.appendChild(msg);

        [c1, c2, c3, cE, c5, c4].forEach(function (c) { form.appendChild(c); });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            msg.textContent = '';
            btn.disabled = true;
            fetch(BASE_URL + '/pagos/lotes/' + lote.id + '/documentos', { method: 'POST', body: new FormData(form) })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) { msg.textContent = d.error || 'No se pudo subir el documento.'; btn.disabled = false; return; }
                    huboCambios = true;
                    cargar();
                })
                .catch(function () { msg.textContent = 'Error de conexión.'; btn.disabled = false; });
        });
        return form;
    }

    // Orden de los comprobantes de pago dentro del PDF (arrastrar, flechas o invertir).
    var listas = {};   // loteId -> <ul> con los comprobantes en el orden elegido

    function seccionOrden(lote) {
        var wrap = el('div', 'mt-3 pt-2 border-top');
        var head = el('div', 'd-flex justify-content-between align-items-center mb-1');
        head.appendChild(el('strong', 'small', 'Orden de los comprobantes de pago'));
        var inv = el('button', 'btn btn-sm btn-outline-secondary');
        inv.type = 'button';
        inv.innerHTML = '<i class="bi bi-arrow-down-up"></i> Cambiar orden';
        inv.title = 'Invertir el orden (de abajo hacia arriba / de arriba hacia abajo)';
        head.appendChild(inv);
        wrap.appendChild(head);
        wrap.appendChild(el('small', 'text-muted d-block mb-1', 'Arrastre cada PDF o use las flechas. Se une de arriba hacia abajo.'));

        var ul = el('ul', 'list-group list-group-flush');
        ul.appendChild(el('li', 'list-group-item small text-muted', 'Cargando...'));
        wrap.appendChild(ul);
        listas[lote.id] = ul;

        inv.addEventListener('click', function () {
            Array.prototype.slice.call(ul.children).reverse().forEach(function (li) { ul.appendChild(li); });
        });

        var arrastrado = null;
        ul.addEventListener('dragstart', function (e) {
            arrastrado = e.target.closest('li[data-id]');
            if (arrastrado) e.dataTransfer.effectAllowed = 'move';
        });
        ul.addEventListener('dragover', function (e) {
            var li = e.target.closest('li[data-id]');
            if (!arrastrado || !li || li === arrastrado) return;
            e.preventDefault();
            var r = li.getBoundingClientRect();
            ul.insertBefore(arrastrado, e.clientY < r.top + r.height / 2 ? li : li.nextSibling);
        });
        ul.addEventListener('dragend', function () { arrastrado = null; });
        ul.addEventListener('click', function (e) {
            var b = e.target.closest('[data-mover]');
            if (!b) return;
            var li = b.closest('li');
            if (b.dataset.mover === 'up' && li.previousElementSibling) ul.insertBefore(li, li.previousElementSibling);
            if (b.dataset.mover === 'down' && li.nextElementSibling) ul.insertBefore(li.nextElementSibling, li);
        });

        fetch(BASE_URL + '/pagos/lotes/' + lote.id + '/orden-pdf')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                ul.innerHTML = '';
                if (!d.ok || !d.items.length) {
                    ul.appendChild(el('li', 'list-group-item small text-muted', 'Este lote no tiene comprobantes de pago.'));
                    delete listas[lote.id];
                    inv.style.display = 'none';
                    return;
                }
                d.items.forEach(function (it) {
                    var li = el('li', 'list-group-item d-flex align-items-center gap-2 py-1');
                    li.draggable = true;
                    li.dataset.id = it.id;
                    li.style.cursor = 'grab';
                    li.appendChild(el('i', 'bi bi-grip-vertical text-muted'));
                    var txt = el('span', 'flex-grow-1 small');
                    txt.appendChild(el('strong', '', it.proveedor + ' · ' + it.producto));
                    txt.appendChild(el('span', 'text-muted ms-2', it.nombre));
                    li.appendChild(txt);
                    var ver = el('a', 'btn btn-icon btn-outline-primary btn-sm');
                    ver.href = it.url; ver.target = '_blank'; ver.title = 'Ver ' + it.nombre;
                    ver.innerHTML = '<i class="bi bi-eye-fill"></i>';
                    li.appendChild(ver);
                    [['up', 'bi-chevron-up'], ['down', 'bi-chevron-down']].forEach(function (m) {
                        var b = el('button', 'btn btn-icon btn-light btn-sm');
                        b.type = 'button'; b.dataset.mover = m[0];
                        b.innerHTML = '<i class="bi ' + m[1] + '"></i>';
                        li.appendChild(b);
                    });
                    ul.appendChild(li);
                });
            })
            .catch(function () { ul.innerHTML = ''; ul.appendChild(el('li', 'list-group-item small text-danger', 'No se pudo cargar el orden.')); });
        return wrap;
    }

    function guardarOrdenes() {
        return Promise.all(Object.keys(listas).map(function (loteId) {
            var fd = new FormData();
            Array.prototype.forEach.call(listas[loteId].querySelectorAll('li[data-id]'), function (li) { fd.append('ids[]', li.dataset.id); });
            return fetch(BASE_URL + '/pagos/lotes/' + loteId + '/orden-pdf', { method: 'POST', body: fd });
        }));
    }

    function pintar(data) {
        listas = {};
        cont.innerHTML = '';
        data.lotes.forEach(function (lote) {
            var card = el('div', 'border rounded p-3 mb-3');
            var head = el('div', 'd-flex justify-content-between align-items-center mb-2');
            head.appendChild(el('strong', '', 'Lote ' + lote.consecutivo));
            head.appendChild(lote.documentos.length
                ? el('span', 'badge bg-success', lote.documentos.length + ' documento(s)')
                : el('span', 'badge bg-warning text-dark', 'Sin documentos'));
            card.appendChild(head);

            if (!lote.documentos.length) {
                card.appendChild(el('div', 'alert alert-warning py-2 mb-2',
                    'Este lote no tiene comprobante de egreso ni legalización de anticipo asociados.'));
            }
            lote.documentos.forEach(function (d) {
                var fila = el('div', 'd-flex flex-wrap align-items-center gap-2 mb-1');
                fila.appendChild(el('span', 'badge bg-primary', d.tipo_label));
                fila.appendChild(el('small', 'text-muted', d.fecha));
                if (d.egreso_id) fila.appendChild(el('small', 'text-muted', '(egreso #' + d.egreso_id + ')'));
                d.archivos.forEach(function (a) {
                    var link = el('a', 'btn btn-outline-primary btn-sm');
                    link.href = a.url; link.target = '_blank'; link.title = 'Ver ' + a.nombre;
                    link.innerHTML = '<i class="bi bi-file-earmark-pdf-fill"></i> ';
                    link.appendChild(document.createTextNode(a.nombre));
                    fila.appendChild(link);
                });
                (d.pagos || []).forEach(function (id) {
                    var p = (lote.pagos || []).filter(function (x) { return x.id === id; })[0];
                    if (p) fila.appendChild(el('span', 'badge bg-secondary', p.label));
                });
                card.appendChild(fila);
            });

            card.appendChild(seccionOrden(lote));

            var det = el('details', 'mt-2');
            if (!lote.documentos.length) det.open = true;
            det.appendChild(el('summary', 'small text-primary', lote.documentos.length ? 'Agregar otro documento' : 'Subir documento'));
            var wrap = el('div', 'pt-2');
            wrap.appendChild(formSubida(lote, data.tipos));
            det.appendChild(wrap);
            card.appendChild(det);
            cont.appendChild(card);
        });
        if (!data.lotes.length) cont.appendChild(el('div', 'text-center text-muted py-4', 'No hay lotes.'));
    }

    function cargar() {
        fetch(urlDocs)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { cont.textContent = d.error || 'No se pudieron cargar los documentos.'; return; }
                pintar(d);
            })
            .catch(function () { cont.textContent = 'Error de conexión.'; });
    }

    // Captura el clic ANTES que pdf-combinar.js y abre el modal en su lugar.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-combinar]');
        if (!btn || btn.dataset.confirmado) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        urlJson = btn.dataset.combinar;
        urlDocs = urlJson.replace(/\/comprobantes$/, '/documentos');
        cont.innerHTML = '<div class="text-center text-muted py-4">Cargando...</div>';
        modal = modal || new bootstrap.Modal(modalEl);
        // Cierra el modal "Ver lote" que esté abierto (el de /nuevo o el del detalle).
        var abierto = document.querySelector('.modal.show');
        var abiertoInst = abierto && abierto !== modalEl ? bootstrap.Modal.getInstance(abierto) : null;
        if (abiertoInst) abiertoInst.hide();
        modal.show();
        cargar();
    }, true);

    // Disparador sin descarga inmediata: <button data-docs-abrir="URL_COMPROBANTES">
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-docs-abrir]');
        if (!btn) return;
        e.preventDefault();
        urlJson = btn.dataset.docsAbrir;
        urlDocs = urlJson.replace(/\/comprobantes$/, '/documentos');
        cont.innerHTML = '<div class="text-center text-muted py-4">Cargando...</div>';
        modal = modal || new bootstrap.Modal(modalEl);
        modal.show();
        cargar();
    });

    var huboCambios = false;
    modalEl.addEventListener('hidden.bs.modal', function () {
        if (huboCambios && document.body.dataset.recargarDocs === '1') location.reload();
    });

    confirmar.addEventListener('click', function () {
        confirmar.disabled = true;
        guardarOrdenes()
            .catch(function () { alert('No se pudo guardar el orden; se usará el último guardado.'); })
            .then(function () { confirmar.disabled = false; unir(); });
    });

    function unir() {
        modal.hide();
        var a = document.createElement('a');
        a.href = '#';
        a.dataset.combinar = urlJson;
        a.dataset.confirmado = '1';
        a.className = 'btn btn-primary';
        a.style.cssText = 'position:fixed;left:-9999px;';
        a.textContent = 'Uniendo...';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { a.remove(); }, 60000);
    }
})();
</script>
