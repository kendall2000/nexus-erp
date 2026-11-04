/*
 * Empresas: confirmación al desactivar (listado) y, al crear, la moneda base que propone el país.
 * La cascada de ubicación está en ubicacion.js. Sin JS en línea (CSP).
 */
(function () {
    'use strict';

    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmar && !window.confirm(form.dataset.confirmar)) e.preventDefault();
        });
    });

    var formulario = document.getElementById('formEmpresa');
    var pais = document.getElementById('id_pais');
    var moneda = document.getElementById('moneda_base');
    if (!formulario || !pais || !moneda || moneda.tagName !== 'SELECT' || formulario.querySelector('input[name="_method"]')) return;

    pais.addEventListener('change', function () {
        var elegido = pais.options[pais.selectedIndex];
        var codigo = elegido ? elegido.dataset.moneda : '';
        if (codigo && Array.prototype.some.call(moneda.options, function (o) { return o.value === codigo; })) moneda.value = codigo;
    });
})();
