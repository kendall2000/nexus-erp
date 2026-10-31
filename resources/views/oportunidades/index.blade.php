@extends('layouts.app', ['titulo' => 'Oportunidades'])

@section('contenido')
    @php
        $yo = auth()->user();
        $dinero = fn ($n) => number_format((float) $n, 2);
    @endphp

    <x-page-header
        titulo="Embudo de ventas"
        subtitulo="Oportunidades por etapa. El valor ponderado usa la probabilidad de cada una."
        icono="fas fa-filter-circle-dollar"
        :breadcrumbs="['CRM' => null, 'Embudo de ventas' => null]"
    >
        <form method="GET">
            <select class="form-select" name="responsable" onchange="this.form.submit()">
                <option value="">Todos los vendedores</option>
                @foreach ($vendedores as $v)
                    <option value="{{ $v->id_empleado }}" @selected((int) ($filtros['responsable'] ?? 0) === $v->id_empleado)>{{ $v->nombre_completo }}</option>
                @endforeach
            </select>
        </form>
        @if ($yo->puede('oportunidades.crear'))
            <a class="btn btn-primary text-nowrap" href="{{ route('oportunidades.create') }}">
                <span class="fas fa-plus me-2"></span>Nueva oportunidad
            </a>
        @endif
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-sm-3">
            <x-stat-card etiqueta="Abiertas" :valor="$resumen['abiertas']" icono="fas fa-briefcase" color="primary" />
        </div>
        <div class="col-sm-3">
            <x-stat-card etiqueta="Valor en juego" :valor="$dinero($resumen['valor'])" icono="fas fa-coins" color="info" />
        </div>
        <div class="col-sm-3">
            <x-stat-card etiqueta="Valor ponderado" :valor="$dinero($resumen['ponderado'])" icono="fas fa-percentage" color="warning" />
        </div>
        <div class="col-sm-3">
            <x-stat-card etiqueta="Ganado este mes" :valor="$dinero($resumen['ganadasMes'])" icono="fas fa-trophy" color="success" />
        </div>
    </div>

    <div class="deals-container d-flex gap-3 pb-4 scrollbar" style="overflow-x: auto">
        @foreach ($etapas as $etapa)
            @php $lista = $porEtapa->get($etapa->id_etapa, collect()); @endphp
            <div class="deals-col flex-shrink-0" style="width: 19rem">
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="fw-bold fs--1 text-1000">
                        <span class="d-inline-block rounded-circle me-2" style="width: .65rem; height: .65rem; background: {{ $etapa->color_hex ?? '#94A3B8' }}"></span>{{ $etapa->nombre }}
                        <span class="badge badge-phoenix badge-phoenix-secondary ms-1">{{ $lista->count() }}</span>
                    </span>
                    <span class="fs--2 fw-semi-bold text-700">{{ $dinero($lista->sum('valor_estimado')) }}</span>
                </div>
                <div class="mb-3 rounded-pill" style="height: 4px; background: {{ $etapa->color_hex ?? '#94A3B8' }}"></div>

                @forelse ($lista as $o)
                    <a class="card mb-2 text-decoration-none border-200 shadow-sm" href="{{ route('oportunidades.show', $o->id_oportunidad) }}">
                        <div class="card-body p-3 fs--1">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <p class="fw-bold text-1000 mb-0">{{ $o->nombre }}</p>
                                <span class="badge badge-phoenix badge-phoenix-info fs--2">{{ $o->probabilidad }} %</span>
                            </div>
                            <p class="text-700 fs--2 mb-2">
                                <span class="far fa-building me-1 text-500"></span>{{ $o->cliente?->razon_social ?? $o->prospecto?->nombre_empresa }}
                            </p>
                            <div class="d-flex justify-content-between align-items-center border-top border-200 pt-2 mt-2">
                                <span class="fw-bold text-900">{{ $o->moneda }} {{ $dinero($o->valor_estimado) }}</span>
                                <span class="fs--2 {{ $o->fecha_cierre_estimada?->isPast() && ! $etapa->es_ganada && ! $etapa->es_perdida ? 'text-danger fw-semi-bold' : 'text-600' }}">
                                    <span class="far fa-calendar-alt me-1"></span>{{ $o->fecha_cierre_real?->format('d/m/Y') ?? $o->fecha_cierre_estimada?->format('d/m/Y') }}
                                </span>
                            </div>
                            @if ($o->responsable)
                                <div class="mt-2 fs--2 text-600">
                                    <span class="far fa-user me-1"></span>{{ $o->responsable->nombre_corto }}
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="card bg-100 border-dashed border-300">
                        <div class="card-body p-3 text-center">
                            <p class="fs--2 text-500 mb-0">Sin oportunidades.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        @endforeach
    </div>
@endsection
