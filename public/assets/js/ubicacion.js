/*
 * Selects en cascada país → departamento → municipio, filtrados en el navegador (sin API).
 * El formulario trae los catálogos en <script type="application/json" id="ubicacionDatos">
 * ({divisiones, municipios}) y los selects #id_pais, #id_division (data-valor) e #id_municipio (data-valor).
 */
(function () {
    'use strict';

    var datos = document.getElementById('ubicacionDatos');
    var pais = document.getElementById('id_pais');
    var division = document.getElementById('id_division');
    var municipio = document.getElementById('id_municipio');
    if (!datos || !pais || !division || !municipio) return;
    var geo = JSON.parse(datos.textContent);

    function llenar(select, opciones, llave, valor) {
        select.innerHTML = '';
        select.appendChild(new Option('—', ''));
        opciones.forEach(function (o) {
            var opcion = new Option(o.nombre, o[llave]);
            if (String(o[llave]) === String(valor)) opcion.selected = true;
            select.appendChild(opcion);
        });
        select.disabled = opciones.length === 0;
        // Con buscador (Choices.js) hay que reconstruirlo para que muestre las opciones nuevas.
        if (window.NexusSelect) window.NexusSelect.refrescar(select);
    }
    function cargarDivisiones(valor) {
        llenar(division, geo.divisiones.filter(function (d) { return String(d.id_pais) === pais.value; }), 'id_division', valor);
    }
    function cargarMunicipios(valor) {
        llenar(municipio, geo.municipios.filter(function (m) { return String(m.id_division) === division.value; }), 'id_municipio', valor);
    }

    pais.addEventListener('change', function () { cargarDivisiones(''); cargarMunicipios(''); });
    division.addEventListener('change', function () { cargarMunicipios(''); });
    cargarDivisiones(division.dataset.valor);
    cargarMunicipios(municipio.dataset.valor);
})();
