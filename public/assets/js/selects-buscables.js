/**
 * Selects con buscador (Choices.js, el «Advance Select» de Phoenix).
 *
 * phoenix.js inicia Choices en cada <select data-choices> (opciones en data-options).
 * Este archivo se carga ANTES de phoenix.js y corrige tres cosas de Choices 9:
 *  1. Valores por defecto: textos en español, respeta el orden de las opciones
 *     (por defecto las ordena alfabéticamente) y muestra hasta 50 resultados (por defecto 4).
 *  2. Choices reconstruye las <option> del select original y pierde sus data-*.
 *     Los scripts de las vistas que leen selectedOptions[0].dataset siguen funcionando
 *     porque aquí se guardan antes de iniciar y se devuelven en cada cambio.
 *  3. El select original queda oculto: si es obligatorio y está vacío, el navegador
 *     bloquea el envío sin mostrar nada. Aquí se marca en rojo y se muestra el mensaje.
 *
 * Limitación: Choices no se entera si un script cambia select.value o reconstruye sus
 * <option>. Esos selects (p. ej. país → departamento → municipio) se dejan sin data-choices.
 */
(function () {
    if (!window.Choices || !window.Choices.defaults) return;

    Object.assign(window.Choices.defaults.options, {
        shouldSort: false,
        itemSelectText: '',
        searchResultLimit: 50,
        searchPlaceholderValue: 'Buscar…',
        noResultsText: 'Sin resultados',
        noChoicesText: 'No hay más opciones',
        loadingText: 'Cargando…',
        addItemText: function (valor) { return 'Presiona Enter para agregar «' + valor + '»'; },
        maxItemText: function (max) { return 'Solo se pueden elegir ' + max; }
    });

    var estilo = document.createElement('style');
    estilo.textContent = '.choices.is-invalid .choices__inner { border-color: var(--phoenix-danger, #ed2000) !important; }'
        // En columnas angostas el texto elegido no se parte en dos líneas: se corta con «…».
        + ' .choices__list--single { min-width: 0; padding-right: 1.5rem; }'
        + ' .choices__list--single .choices__item { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }'
        // Misma altura y letra que .form-control (el tema le da 1rem a Choices y 0.8rem a los inputs).
        + ' .choices .choices__inner { font-size: 0.8rem; line-height: 1.49 !important; min-height: 0 !important; padding: 0.5rem 3rem 0.5rem 1rem !important; }'
        + ' .choices[data-type*=select-one] .choices__inner { padding-bottom: 0.5rem !important; }'
        + ' .choices .choices__list--single { display: block; padding: 0 1.5rem 0 0; }'
        + ' .choices .choices__list--dropdown .choices__item, .choices .choices__input { font-size: 0.8rem; }'
        // Versión compacta para los select form-select-sm (como .form-control-sm).
        + ' .choices:has(select.form-select-sm) .choices__inner { padding: 0.375rem 2.5rem 0.375rem 1rem !important; }'
        // Filtros de los listados: no se aplastan en columnas angostas y la lista puede ser más ancha.
        + ' .choices.choices-filtro { margin-bottom: 0; }'
        + ' .choices.choices-filtro-libre { min-width: 9rem; }'
        // En un filtro «Todos…» es una opción real, no un texto de ayuda: se ve con el color normal.
        + ' .choices.choices-filtro .choices__list--single .choices__placeholder { opacity: 1; color: var(--phoenix-btn-color, inherit); }'
        + ' .choices.choices-filtro .choices__list--dropdown { min-width: 100%; width: max-content; max-width: 22rem; }'
        // Filtros con estilo de botón (btn-phoenix-*): usan las variables del botón del tema.
        + ' .choices.choices-boton .choices__inner { background-color: var(--phoenix-btn-bg) !important; border-color: var(--phoenix-btn-border-color) !important; color: var(--phoenix-btn-color); font-weight: 600; }'
        + ' .choices.choices-boton:hover .choices__inner { background-color: var(--phoenix-btn-hover-bg) !important; }';
    document.head.appendChild(estilo);

    // phoenix.js crea las instancias de Choices sin guardarlas. Esta subclase guarda la
    // instancia y su configuración en el select para poder reconstruirlo (NexusSelect).
    var ChoicesOriginal = window.Choices;
    window.Choices = class extends ChoicesOriginal {
        constructor(elemento, config) {
            super(elemento, config);
            var select = typeof elemento === 'string' ? document.querySelector(elemento) : elemento;
            if (select) {
                select._choices = this;
                select._choicesConfig = config || {};
            }
        }
    };

    function guardarDatos(select) {
        var datos = {};
        Array.prototype.forEach.call(select.options, function (opcion) {
            if (Object.keys(opcion.dataset).length) datos[opcion.value] = Object.assign({}, opcion.dataset);
        });
        select._datosOpciones = datos;
    }

    /**
     * Para los scripts de las vistas que reconstruyen las <option> de un select con buscador
     * (p. ej. país → departamento → municipio): después de cambiar las opciones, llamar
     * NexusSelect.refrescar(select). Si el select no tiene buscador, no hace nada.
     */
    window.NexusSelect = {
        refrescar: function (select) {
            var instancia = select && select._choices;
            if (!instancia) return;
            // destroy() devuelve al select sus opciones iniciales: se guardan las nuevas antes.
            var opciones = select.innerHTML;
            var deshabilitado = select.disabled;
            var config = select._choicesConfig;
            instancia.destroy();
            select.innerHTML = opciones;
            select.disabled = deshabilitado;
            guardarDatos(select);
            new window.Choices(select, config);
            var caja = select.closest('.choices');
            if (caja && select.classList.contains('is-invalid')) caja.classList.add('is-invalid');
        }
    };

    function devolverDatos(select) {
        if (!select._datosOpciones) return;
        Array.prototype.forEach.call(select.selectedOptions, function (opcion) {
            Object.assign(opcion.dataset, select._datosOpciones[opcion.value] || {});
        });
    }

    function limpiarError(select) {
        var caja = select.closest('.choices');
        if (!caja) return;
        caja.classList.remove('is-invalid');
        var aviso = caja.nextElementSibling;
        if (aviso && aviso.classList.contains('nexus-choices-error')) aviso.remove();
    }

    // Se registra antes que el de phoenix.js: guarda los data-* antes de que Choices los borre.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select[data-choices]').forEach(guardarDatos);

        // Ya iniciado Choices: datos de la opción elegida y errores de validación del servidor.
        setTimeout(function () {
            document.querySelectorAll('select[data-choices]').forEach(function (select) {
                devolverDatos(select);
                var caja = select.closest('.choices');
                if (!caja) return;
                if (select.classList.contains('is-invalid')) caja.classList.add('is-invalid');

                // Filtros de los listados (formularios GET): ancho mínimo y, si el select
                // tenía estilo de botón de Phoenix, la caja de Choices lo copia.
                var formulario = select.closest('form');
                if (formulario && (formulario.getAttribute('method') || 'get').toLowerCase() === 'get') {
                    caja.classList.add('choices-filtro');
                    // Fuera de una columna de la grilla (col-*) no hay ancho definido: se le da un mínimo.
                    if (!/(^|\s)col(-|\s|$)/.test(caja.parentElement.className)) caja.classList.add('choices-filtro-libre');
                    var boton = Array.prototype.find.call(select.classList, function (c) { return c.indexOf('btn-phoenix-') === 0; });
                    if (boton) caja.classList.add('choices-boton', boton);
                }
            });
        });
    });

    // Fase de captura: corre antes que los listeners de las vistas sobre el select.
    document.addEventListener('change', function (e) {
        var select = e.target;
        if (!select || select.tagName !== 'SELECT' || !select.closest('.choices')) return;
        devolverDatos(select);
        if (select.checkValidity()) limpiarError(select);
    }, true);

    // Un contenedor con scroll (.table-responsive, la barra de filtros con .scrollbar…)
    // recortaría la lista desplegada: mientras está abierta, sus ancestros con overflow
    // pasan a visible y al cerrarse vuelven a como estaban.
    document.addEventListener('showDropdown', function (e) {
        var select = e.target;
        if (!select || !select.closest) return;
        var recortados = [];
        for (var nodo = select.closest('.choices').parentElement; nodo && nodo !== document.body; nodo = nodo.parentElement) {
            var estiloNodo = getComputedStyle(nodo);
            if (estiloNodo.overflowX !== 'visible' || estiloNodo.overflowY !== 'visible') {
                recortados.push([nodo, nodo.style.overflow]);
                nodo.style.setProperty('overflow', 'visible', 'important');
            }
        }
        select._recortados = recortados;
    }, true);
    document.addEventListener('hideDropdown', function (e) {
        var select = e.target;
        (select && select._recortados || []).forEach(function (par) {
            par[0].style.removeProperty('overflow');
            if (par[1]) par[0].style.overflow = par[1];
        });
        if (select) select._recortados = [];
    }, true);

    var ultimoAviso = 0;
    document.addEventListener('invalid', function (e) {
        var select = e.target;
        if (!select || select.tagName !== 'SELECT') return;
        var caja = select.closest('.choices');
        if (!caja) return;

        caja.classList.add('is-invalid');
        var aviso = caja.nextElementSibling;
        if (!aviso || !aviso.classList.contains('nexus-choices-error')) {
            aviso = document.createElement('div');
            aviso.className = 'invalid-feedback d-block nexus-choices-error';
            caja.insertAdjacentElement('afterend', aviso);
        }
        aviso.textContent = select.validationMessage || 'Selecciona una opción.';

        // Solo el primer campo inválido de cada envío se lleva a la vista.
        if (Date.now() - ultimoAviso > 500) {
            ultimoAviso = Date.now();
            caja.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    }, true);
})();
