<?php
/** Modal "Ver lote" para el detalle de un lote (equivalente al de /pagos/lotes/nuevo).
 *  Requiere $lote. Se abre con cualquier botón [data-ver-lote="ID"]. */
?>
<div class="modal fade" id="modalVerLoteDet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-stack"></i> Lote <span id="vldTitulo"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="vldResumen"></p>
                <div class="table-wrapper">
                    <table class="table" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th><th>Proveedor</th><th>Banco</th><th>Tipo Producto</th>
                                <th>Nro. Producto</th><th>Fecha</th><th>Valor</th><th>Comprobante</th>
                            </tr>
                        </thead>
                        <tbody id="vldBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-outline-primary" id="vldPdf"><i class="bi bi-file-earmark-pdf-fill"></i> Descargar PDF Combinado</a>
                <a href="#" class="btn btn-outline-success" id="vldExcel"><i class="bi bi-file-earmark-excel-fill"></i> Exportar a Excel</a>
                <a href="#" class="btn btn-success" id="vldCerrar" data-confirm="¿Cerrar este lote? Ya no podrá agregar más pagos."><i class="bi bi-lock-fill"></i> Cerrar Lote</a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal;
    function celda(tr, texto) {
        var td = document.createElement('td');
        td.textContent = texto;
        tr.appendChild(td);
        return td;
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-ver-lote]');
        if (!btn) return;
        e.preventDefault();
        fetch(BASE_URL + '/pagos/lotes/' + btn.dataset.verLote + '/modal')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) { alert(data.error || 'No se pudo cargar el lote.'); return; }
                document.getElementById('vldTitulo').textContent = data.lote.consecutivo;
                document.getElementById('vldResumen').innerHTML =
                    '<strong>Total pagos:</strong> ' + data.lote.total_pagos +
                    ' &nbsp;|&nbsp; <strong>Valor total:</strong> <span id="vldValor"></span>';
                document.getElementById('vldValor').textContent = data.lote.total_valor;

                var hayPagos = data.pagos.length > 0;
                var pdf = document.getElementById('vldPdf');
                pdf.href = data.combinado;
                pdf.dataset.combinar = data.comprobantes;
                pdf.style.display = hayPagos ? '' : 'none';
                var excel = document.getElementById('vldExcel');
                excel.href = data.exportar;
                excel.style.display = hayPagos ? '' : 'none';
                var cerrar = document.getElementById('vldCerrar');
                cerrar.href = BASE_URL + '/pagos/lotes/' + data.lote.id + '/cerrar';
                cerrar.style.display = hayPagos && data.lote.estado === 'abierto' ? '' : 'none';

                var body = document.getElementById('vldBody');
                body.innerHTML = '';
                if (!hayPagos) {
                    var vacio = document.createElement('tr');
                    var td = celda(vacio, 'Aún no hay pagos en este lote.');
                    td.colSpan = 8;
                    td.className = 'text-center text-muted';
                    body.appendChild(vacio);
                }
                data.pagos.forEach(function (p) {
                    var tr = document.createElement('tr');
                    [p.orden, p.proveedor, p.banco, p.tipo_producto, p.numero_producto, p.fecha, p.valor].forEach(function (v) { celda(tr, v); });
                    var tdc = document.createElement('td');
                    p.comprobantes.forEach(function (c) {
                        var a = document.createElement('a');
                        a.href = c.url;
                        a.target = '_blank';
                        a.className = 'btn btn-icon btn-outline-primary btn-sm';
                        a.title = 'Ver ' + c.nombre;
                        a.innerHTML = '<i class="bi bi-file-earmark-pdf-fill"></i>';
                        tdc.appendChild(a);
                    });
                    tr.appendChild(tdc);
                    body.appendChild(tr);
                });
                modal = modal || new bootstrap.Modal(document.getElementById('modalVerLoteDet'));
                modal.show();
            })
            .catch(function () { alert('Error de conexión al cargar el lote.'); });
    });
})();
</script>
