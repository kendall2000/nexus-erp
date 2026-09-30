{{--
    Layout PUENTE para las pantallas que todavía usan Vue + API (/api/v1).
    Usa el mismo diseño que layouts/app (menú, barra superior, pie) y agrega
    las librerías que esas pantallas necesitan. Cada módulo pasa a layouts/app
    (Blade server-side) cuando se migra; al final este archivo se elimina.

    Las vistas que lo usan definen @section('breadcrumb') y @section('content').
--}}
@extends('layouts.app', ['titulo' => trim($__env->yieldContent('breadcrumb'))])

@push('estilos')
    <script>
        const server    = window.location.protocol + '//' + window.location.host;
        const basePath  = '';
        const servidor  = server + basePath;
        const apiUrl    = server + '/api/v1';
        // La sesión viaja en la cookie (ya no hay token en el navegador); se deja vacía
        // para las pantallas que aún la leen mientras se migran.
        const nexusToken = '';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vue-select@3.20.2/dist/vue-select.css">
    <link href="{{ asset('vendors/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/choices/choices.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/leaflet/leaflet.css') }}" rel="stylesheet">
    <link href="https://cdn.datatables.net/v/bs5/jq-3.7.0/jszip-3.10.1/dt-1.13.8/b-2.4.2/b-html5-2.4.2/sl-1.7.0/datatables.min.css" rel="stylesheet">
    <style>
        .table { width: 100% !important; }
        [v-cloak] { display: none; }
    </style>
    @stack('styles')
@endpush

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item active">@yield('breadcrumb', 'Dashboard')</li>
        </ol>
    </nav>

    @yield('content')
@endsection

{{-- @prepend: la vista del módulo se procesa antes; así estas librerías quedan antes de sus @push('scripts'). --}}
@prepend('scripts')
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
    <script src="{{ asset('vendors/choices/choices.min.js') }}"></script>
    <script src="{{ asset('vendors/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('vendors/echarts/echarts.min.js') }}"></script>

    {{-- Componentes Vue propios --}}
    <script src="{{ asset('js/componentes/vuecomponentes.js') }}?v={{ filemtime(public_path('js/componentes/vuecomponentes.js')) }}"></script>
    <script src="{{ asset('js/componentes/apis_service.js') }}?v={{ filemtime(public_path('js/componentes/apis_service.js')) }}"></script>
    <script src="{{ asset('js/componentes/api.js') }}?v={{ filemtime(public_path('js/componentes/api.js')) }}"></script>

    <script>
    (function() {
        // Interceptor global de fetch: la API usa la sesión del navegador (cookie).
        // A cada llamada a /api/v1 se le agrega el token CSRF y se le quita el
        // «Authorization: Bearer» que algunas pantallas todavía mandan.
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
            if (response.status === 401 || response.status === 419) {
                window.location.href = '/login';
            }

            return response;
        };

        // Al volver con «atrás» desde la caché del navegador, recargar para que el
        // servidor vuelva a validar la sesión.
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) window.location.reload();
        });
    })();
    </script>

    {{-- JS del módulo actual: resources/views/modulos/{modulo}/{archivo}.js --}}
    @php
        $segmentos = array_values(array_filter(explode('/', request()->path())));
        $jsUrl = null;
        if (count($segmentos) >= 2 && $segmentos[0] === 'sistema') {
            $modulo = strtolower($segmentos[1]);
            $archivo = isset($segmentos[2]) ? strtolower($segmentos[2]) : 'index';
            $ruta = resource_path("views/modulos/{$modulo}/{$archivo}.js");
            if (file_exists($ruta)) {
                $jsUrl = url("/modulos-js/{$modulo}/{$archivo}.js").'?v='.filemtime($ruta);
            }
        }
    @endphp
    @if ($jsUrl)
        <script src="{{ $jsUrl }}"></script>
    @endif
@endprepend
