{{-- Color primario de Configuración del sistema: reemplaza el azul de Phoenix (y su versión clara en modo oscuro). --}}
@php
    $p = \App\Support\Sistema::colorPrimario();
    $oscuro = \App\Support\Sistema::oscurecer($p, 0.15);
    $claro = \App\Support\Sistema::oscurecer($p, -0.45);
@endphp
@if (strtolower($p) !== '#3874ff')
    <style>
        :root { --phoenix-primary: {{ $p }}; --phoenix-primary-rgb: {{ \App\Support\Sistema::rgb($p) }}; --phoenix-link-color: {{ $p }}; --phoenix-link-hover-color: {{ $oscuro }}; --phoenix-navbar-vertical-link-active-color: {{ $p }}; }
        .dark { --phoenix-primary: {{ $claro }}; --phoenix-primary-rgb: {{ \App\Support\Sistema::rgb($claro) }}; --phoenix-link-color: {{ $claro }}; --phoenix-navbar-vertical-link-active-color: {{ $claro }}; }
        .btn-primary { --phoenix-btn-bg: {{ $p }}; --phoenix-btn-hover-bg: {{ $oscuro }}; --phoenix-btn-active-bg: {{ $oscuro }}; --phoenix-btn-disabled-bg: {{ $p }}; }
        .btn-phoenix-primary { --phoenix-btn-color: {{ $p }}; --phoenix-btn-hover-color: {{ $oscuro }}; }
        .dark .btn-phoenix-primary { --phoenix-btn-color: {{ $claro }}; --phoenix-btn-hover-color: {{ $claro }}; }
        .form-check-input:checked { background-color: {{ $p }}; border-color: {{ $p }}; }
        .text-primary { color: var(--phoenix-primary) !important; }
        .bg-primary { background-color: {{ $p }} !important; }
        .border-primary { border-color: {{ $p }} !important; }
        .nav-links .nav-link.active, .nav-links .nav-link:hover { color: var(--phoenix-primary); }
    </style>
@endif
