/*
 * Sucursales y agencias: confirmación de los botones con data-confirmar (listado) y, en el
 * formulario, «Depende de» solo para agencias (una agencia no puede ser casa matriz).
 * La cascada de ubicación está en ubicacion.js. Sin JS en línea (CSP).
 */
(function () {
    'use strict';

    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirmar)) e.preventDefault();
        });
    });

    var tipo = document.getElementById('tipo');
    var bloquePadre = document.getElementById('bloquePadre');
    var padre = document.getElementById('id_sucursal_padre');
    var casaMatriz = document.getElementById('es_casa_matriz');
    if (!tipo || !bloquePadre || !padre) return;

    function aplicar() {
        var agencia = tipo.value === 'AGENCIA';
        bloquePadre.classList.toggle('d-none', !agencia);
        padre.required = agencia;
        padre.disabled = !agencia;
        if (casaMatriz) {
            if (agencia) casaMatriz.checked = false;
            casaMatriz.disabled = agencia;
        }
    }
    tipo.addEventListener('change', aplicar);
    aplicar();
})();
