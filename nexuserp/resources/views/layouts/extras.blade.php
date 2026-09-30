{{-- ============================================================
     EXTRAS — NexusERP
     Footer + JavaScripts globales de Phoenix
     ============================================================ --}}

{{-- Loader global --}}
<div id="global-loader" class="loader-overlay">
    <div class="loader-container">
        <div class="loader-track">
            <div class="loader-shape"></div>
        </div>
        <!-- <div class="loading-text">Cargando, por favor espere...</div> -->
    </div>
</div>

{{-- Footer --}}
<footer class="footer position-absolute">
    <div class="row g-0 justify-content-between align-items-center h-100">
        {{-- Columna 1: Izquierda --}}
        <div class="col-12 col-sm-auto text-center">
            <p class="mb-0 mt-2 mt-sm-0 text-900">
                NexusERP
                <span class="d-none d-sm-inline-block mx-1">|</span>
                {{ date('Y') }} &copy;
                <a class="mx-1" href="{{ url('/') }}">NexusERP</a>
            </p>
        </div>
        
        {{-- Columna 2: Derecha (Estaba anidada por error, ahora es un hermano) --}}
        <div class="col-12 col-sm-auto text-center">
            <p class="mb-0 text-600">v1.0.0</p>
        </div>
    </div>
</footer>

</div> {{-- ¡CRÍTICO! Cierra div.content abierto en wrapper.blade.php --}}
</main> {{-- ¡CRÍTICO! Cierra main abierto en header.blade.php --}}

{{-- ============================================================
     JavaScripts — Orden importante:
     1. CDN externos
     2. Phoenix vendors (via base href)
     3. Phoenix core
     4. Scripts del módulo actual
     ============================================================ --}}

{{-- CDN externos --}}
<script src="https://cdn.jsdelivr.net/npm/jspdf/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable"></script>
<script src="https://cdn.jsdelivr.net/npm/vue-select@3.20.2"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/jszip-3.10.1/dt-1.13.8/b-2.4.2/b-html5-2.4.2/sl-1.7.0/datatables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/1.5.0/list.min.js"></script>

{{-- Phoenix vendors --}}
<script src="{{ asset('vendors/popper/popper.min.js') }}"></script>
<script src="{{ asset('vendors/choices/choices.min.js') }}"></script>
<script src="{{ asset('vendors/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ asset('vendors/anchorjs/anchor.min.js') }}"></script>
<script src="{{ asset('vendors/is/is.min.js') }}"></script>
<script src="{{ asset('vendors/fontawesome/all.min.js') }}"></script>
<script src="{{ asset('vendors/lodash/lodash.min.js') }}"></script>
<script src="{{ asset('vendors/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('vendors/leaflet/leaflet.js') }}"></script>
<script src="{{ asset('vendors/echarts/echarts.min.js') }}"></script>

{{-- Phoenix core --}}
<script src="{{ asset('assets/js/phoenix.js') }}"></script>

{{-- Lógica del Loader --}}
@if(file_exists(public_path('js/componentes/loader.js')))
    <script src="{{ asset('js/componentes/loader.js') }}"></script>
@else
    {{-- Si el archivo loader.js no existe, ocultamos el loader automáticamente con este pequeño script --}}
    <script>
        window.addEventListener('load', function() {
            var loader = document.getElementById('global-loader');
            if(loader) loader.style.display = 'none';
        });
    </script>
@endif

{{-- Componentes Vue propios --}}
@if(file_exists(public_path('js/componentes/vuecomponentes.js')))
    <script src="{{ asset('js/componentes/vuecomponentes.js') }}?v={{ filemtime(public_path('js/componentes/vuecomponentes.js')) }}"></script>
@endif

@if(file_exists(public_path('js/componentes/apis_service.js')))
    <script src="{{ asset('js/componentes/apis_service.js') }}?v={{ filemtime(public_path('js/componentes/apis_service.js')) }}"></script>
    <script src="{{ asset('js/componentes/api.js') }}?v={{ filemtime(public_path('js/componentes/api.js')) }}"></script>
@endif
{{-- ============================================================
     CONFIGURACIÓN GLOBAL — Auth + Phoenix
     ============================================================ --}}
<script>
(function() {
    // ── 1. Interceptor global de fetch ──────────────────────────
    // La API usa la sesión del navegador (cookie). A cada llamada a /api/v1 se le
    // agrega el token CSRF y se le quita el «Authorization: Bearer» que algunas
    // pantallas todavía mandan (ya no hay token en el navegador).
    const originalFetch = window.fetch;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    window.fetch = async function(url, options) {
        options = options || {};

        if (typeof url === 'string' && url.indexOf('/api/v1') !== -1) {
            const headers = new Headers(options.headers || {});
            headers.delete('Authorization');
            headers.set('X-CSRF-TOKEN', csrf);
            headers.set('X-Requested-With', 'XMLHttpRequest');
            if (!headers.has('Accept')) headers.set('Accept', 'application/json');
            options.headers = headers;
            options.credentials = 'same-origin';
        }

        const response = await originalFetch(url, options);

        // 401: la sesión expiró o el usuario fue desactivado. 419: token CSRF vencido.
        if ((response.status === 401 || response.status === 419) && !window.location.pathname.includes('/login')) {
            window.location.href = '/login';
        }

        return response;
    };

    // ── 2. Al volver con «atrás» desde la caché del navegador, recargar
    //       para que el servidor vuelva a validar la sesión ───────
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    // ── 3. Configuración visual de Phoenix ──────────────────────
    const navbarTopStyle = window.config?.config?.phoenixNavbarTopStyle;
    const navbarTop = document.querySelector('.navbar-top');
    if (navbarTopStyle === 'darker' && navbarTop) {
        navbarTop.classList.add('navbar-darker');
    }

    const navbarVerticalStyle = window.config?.config?.phoenixNavbarVerticalStyle;
    const navbarVertical = document.querySelector('.navbar-vertical');
    if (navbarVertical && navbarVerticalStyle === 'darker') {
        navbarVertical.classList.add('navbar-darker');
    }
})();
</script>
{{-- ── JS automático del módulo actual ───────────────────────── --}}
@php
    $segmentos = array_values(array_filter(explode('/', request()->path())));
    $jsUrl     = null;

    if (count($segmentos) >= 2 && $segmentos[0] === 'sistema') {
        $modulo  = strtolower($segmentos[1]);
        $archivo = isset($segmentos[2]) ? strtolower($segmentos[2]) : 'index';
        $ruta    = resource_path("views/modulos/{$modulo}/{$archivo}.js");

        if (file_exists($ruta)) {
            $jsUrl = url("/modulos-js/{$modulo}/{$archivo}.js") . '?v=' . filemtime($ruta);
        }
    }
@endphp

@if($jsUrl)
    <script src="{{ $jsUrl }}"></script>
@endif

@stack('scripts')
</body>
</html>