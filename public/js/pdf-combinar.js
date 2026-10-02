/**
 * Une en el navegador los comprobantes PDF de un lote (pdf-lib) y descarga
 * el resultado. Se activa en cualquier <a data-combinar="URL_JSON">: la URL
 * devuelve { ok, archivo, comprobantes: [{url, nombre}] }.
 * pdf-lib lee PDFs con compresión moderna (object streams) que el parser
 * gratuito de FPDI del servidor no soporta.
 */
(function () {
    function setBusy(btn, busy) {
        if (busy) {
            btn.dataset.html = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uniendo PDF...';
            btn.classList.add('disabled');
        } else {
            btn.innerHTML = btn.dataset.html;
            btn.classList.remove('disabled');
        }
    }

    async function combinar(btn) {
        var resp = await fetch(btn.dataset.combinar);
        var info = await resp.json();
        if (!info.ok) throw new Error(info.error || 'No se pudo obtener el lote.');
        if (!info.comprobantes.length) throw new Error('No hay comprobantes para combinar.');

        var PDFDocument = PDFLib.PDFDocument;
        var salida = await PDFDocument.create();

        for (var i = 0; i < info.comprobantes.length; i++) {
            var c = info.comprobantes[i];
            var r = await fetch(c.url);
            if (!r.ok) throw new Error('No se pudo descargar "' + c.nombre + '".');
            var bytes = await r.arrayBuffer();
            try {
                var origen = await PDFDocument.load(bytes, { ignoreEncryption: true });
                var paginas = await salida.copyPages(origen, origen.getPageIndices());
                paginas.forEach(function (p) { salida.addPage(p); });
            } catch (e) {
                throw new Error('No se pudo leer el PDF "' + c.nombre + '".');
            }
        }

        var blob = new Blob([await salida.save()], { type: 'application/pdf' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = info.archivo;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(a.href); }, 10000);
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-combinar]');
        if (!btn) return;
        e.preventDefault();
        if (btn.classList.contains('disabled')) return;
        if (typeof PDFLib === 'undefined') {
            alert('No se pudo cargar la librería para unir PDF. Revise su conexión a internet.');
            return;
        }
        setBusy(btn, true);
        combinar(btn)
            .catch(function (err) { alert(err.message || 'Error al unir los PDF.'); })
            .finally(function () { setBusy(btn, false); });
    });
})();
