<script>
// El selector de archivos del navegador entrega los PDF ordenados por nombre, no en el orden
// en que se eligieron. Esta lista acumula las selecciones (en orden de llegada), permite
// reordenarlas o quitarlas y deja el input en ese orden, que es el que guarda el servidor.
(function () {
    var input = document.getElementById('comprobante_pdf');
    if (!input || !window.DataTransfer) return;

    var archivos = [];
    var lista = document.createElement('ol');
    lista.className = 'list-group list-group-numbered mt-2';
    input.parentNode.insertBefore(lista, input.nextSibling);

    function sincronizar() {
        var dt = new DataTransfer();
        archivos.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
        pintar();
    }

    function boton(icono, titulo, fn) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-icon btn-light btn-sm';
        b.title = titulo;
        b.innerHTML = '<i class="bi ' + icono + '"></i>';
        b.addEventListener('click', fn);
        return b;
    }

    function mover(i, d) {
        var j = i + d;
        if (j < 0 || j >= archivos.length) return;
        var t = archivos[i]; archivos[i] = archivos[j]; archivos[j] = t;
        sincronizar();
    }

    function pintar() {
        lista.innerHTML = '';
        lista.style.display = archivos.length ? '' : 'none';
        archivos.forEach(function (f, i) {
            var li = document.createElement('li');
            li.className = 'list-group-item d-flex align-items-center gap-2 py-1';
            var nombre = document.createElement('span');
            nombre.className = 'flex-grow-1 small';
            nombre.textContent = f.name;
            li.appendChild(nombre);
            li.appendChild(boton('bi-chevron-up', 'Subir', function () { mover(i, -1); }));
            li.appendChild(boton('bi-chevron-down', 'Bajar', function () { mover(i, 1); }));
            li.appendChild(boton('bi-x-lg', 'Quitar', function () { archivos.splice(i, 1); sincronizar(); }));
            lista.appendChild(li);
        });
    }

    input.addEventListener('change', function () {
        Array.prototype.forEach.call(input.files, function (f) {
            var repetido = archivos.some(function (a) {
                return a.name === f.name && a.size === f.size && a.lastModified === f.lastModified;
            });
            if (!repetido) archivos.push(f);
        });
        sincronizar();
    });
    pintar();
})();
</script>
