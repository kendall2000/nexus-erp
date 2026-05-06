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
<footer class="footer">
    <div class="row g-0 justify-content-between align-items-center">
        <div class="col-12 col-sm-auto text-center">
            <p class="mb-0 mt-2 mt-sm-0 text-900">
                NexusERP
                <span class="d-none d-sm-inline-block mx-1">|</span>
                {{ date('Y') }} &copy;
                <a class="mx-1" href="{{ url('/') }}">NexusERP</a>
            </p>
        </div>
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
<script src="{{ asset('Plantilla/public/vendors/popper/popper.min.js') }}"></script>
<script src="{{ asset('Plantilla/public/vendors/choices/choices.min.js') }}"></script>
<script src="{{ asset('Plantilla/public/vendors/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ asset('Plantilla/public/vendors/anchorjs/anchor.min.js') }}"></script>
<script src="{{ asset('Plantilla/public/vendors/is/is.min.js') }}"></script>
<script src="{{ asset('Plantilla/public/vendors/fontawesome/all.min.js') }}"></script>
<script src="{{ asset('Plantilla/public/vendors/lodash/lodash.min.js') }}"></script>
<script src="{{ asset('Plantilla/public/vendors/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('Plantilla/public/vendors/leaflet/leaflet.js') }}"></script>
<script src="{{ asset('Plantilla/public/vendors/echarts/echarts.min.js') }}"></script>

{{-- Phoenix core --}}
<script src="{{ asset('Plantilla/public/assets/js/phoenix.js') }}"></script>

{{-- Lógica del Loader --}}
@if(file_exists(public_path('Plantilla/public/componentes/loader.js')))
    <script src="{{ asset('Plantilla/public/componentes/loader.js') }}"></script>
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
@if(file_exists(public_path('Plantilla/public/componentes/vuecomponentes.js')))
    <script src="{{ url('/') }}/Plantilla/public/componentes/vuecomponentes.js?v={{ time() }}"></script>
@endif

@if(file_exists(public_path('Plantilla/public/componentes/apis_service.js')))
    <script src="{{ url('/') }}/Plantilla/public/componentes/apis_service.js?v={{ time() }}"></script>
    <script src="{{ url('/') }}/Plantilla/public/componentes/api.js?v={{ time() }}"></script>
@endif
{{-- ============================================================
     CONFIGURACIÓN GLOBAL — Auth + Phoenix
     ============================================================ --}}
<script>
(function() {
    'use strict';

    // ── 1. Interceptor global de fetch ──────────────────────────
    const originalFetch = window.fetch;
    window.fetch = async function(url, options) {
        options = options || {};
        options.headers = options.headers || {};

        const token = sessionStorage.getItem('nexus_token');
        if (token && typeof url === 'string' && url.indexOf('/api/v1') !== -1) {
            options.headers['Authorization'] = 'Bearer ' + token;
        }

        const response = await originalFetch(url, options);

        if (response.status === 401 && !window.location.pathname.includes('/login')) {
            sessionStorage.removeItem('nexus_token');
            sessionStorage.removeItem('nexus_usuario');
            window.location.href = '/login';
        }
        return response;
    };

    // ── 2. Validación de sesión SIN parpadeo ────────────────────
    function mostrarContenido() {
        document.documentElement.classList.remove('nexus-loading');
    }

    function validarSesion() {
        if (window.location.pathname.includes('/login')) {
            mostrarContenido();
            return;
        }

        const token = sessionStorage.getItem('nexus_token');
        if (!token) {
            window.location.href = '/login';
            return;
        }

        // ✅ Validamos en background; el body ya está oculto por el CSS anti-FOUC
        fetch(apiUrl + '/auth/me')
            .then(res => {
                if (!res.ok) {
                    sessionStorage.clear();
                    window.location.href = '/login';
                } else {
                    mostrarContenido(); // Solo mostramos si la sesión es válida
                }
            })
            .catch(() => {
                sessionStorage.clear();
                window.location.href = '/login';
            });
    }

    // ── 3. Eventos ──────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', validarSesion);
    } else {
        validarSesion();
    }

    window.addEventListener('pageshow', function(event) {
        if (event.persisted) validarSesion();
    });

    // ── 4. Configuración visual de Phoenix ──────────────────────
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