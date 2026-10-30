@extends('layouts.app', ['titulo' => 'Inicio'])

{{-- Estructura: index.html (dashboard e-commerce de Phoenix: saludo y cifras arriba). --}}
@section('contenido')
    <div class="row g-3 flex-between-end mb-4">
        <div class="col-auto">
            <h2 class="mb-1 text-1100">Hola, {{ $usuario->nombre_completo ?: $usuario->username }}</h2>
            <h5 class="text-700 fw-semi-bold mb-0">
                {{ $empresa?->nombre_comercial ? $empresa->nombre_comercial.' · ' : '' }}{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM, H:mm')) }}
            </h5>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach ($tarjetas as $t)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start mb-3">
                            <span class="d-flex flex-center rounded-circle bg-soft-{{ $t['color'] }} text-{{ $t['color'] }} me-3 flex-shrink-0" style="width: 2.5rem; height: 2.5rem;">
                                <span data-feather="{{ $t['icono'] }}" style="width: 18px; height: 18px;"></span>
                            </span>
                            <div class="flex-1 min-w-0">
                                <h5 class="mb-0 text-1000">{{ $t['titulo'] }}</h5>
                            </div>
                            @if ($t['enlace'])
                                <a class="btn btn-link p-0 fs--1 ms-2 white-space-nowrap" href="{{ $t['enlace'] }}">Ver<span class="fas fa-chevron-right ms-1 fs--2"></span></a>
                            @endif
                        </div>
                        <h3 class="mb-0 text-1100">{{ number_format($t['valor']) }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
