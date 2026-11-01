@extends('layouts.app', ['titulo' => 'Oportunidades'])

@section('contenido')
    @php
        $yo = auth()->user();
        $dinero = fn ($n) => number_format((float) $n, 2);
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">CRM & Ventas</li>
            <li class="breadcrumb-item active">Embudo de Negocios (Deals)</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Encabezado idéntico a apps/crm/deals.html --}}
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-12 col-md-auto">
                <h2 class="mb-1 text-1100">Embudo de Oportunidades & Ventas</h2>
                <h5 class="text-700 fw-semi-bold mb-0">Gestión visual del pipeline comercial por etapas</h5>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                @if ($yo->puede('oportunidades.crear'))
                    <a class="btn btn-primary" href="{{ route('oportunidades.create') }}">
                        <span class="fas fa-plus me-2"></span>Nueva oportunidad
                    </a>
                @endif
            </div>
        </div>

        {{-- Métricas de Resumen estilo Phoenix --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Oportunidades Abiertas</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ number_format($resumen['abiertas']) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-briefcase text-primary fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Valor Total en Juego</h6>
                                <h3 class="text-info mb-0 fw-bolder">Q {{ $dinero($resumen['valor']) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-coins text-info fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Valor Ponderado (Forecast)</h6>
                                <h3 class="text-warning mb-0 fw-bolder">Q {{ $dinero($resumen['ponderado']) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-warning-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-percentage text-warning fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Ganado este Mes</h6>
                                <h3 class="text-success mb-0 fw-bolder">Q {{ $dinero($resumen['ganadasMes']) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-success-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-trophy text-success fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros estilo apps/crm/deals.html --}}
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-body py-3 px-4">
                <form method="GET" action="{{ route('oportunidades.index') }}" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <span class="text-800 fw-semi-bold fs--1"><span class="fas fa-filter text-primary me-2"></span>Filtrar por Vendedor:</span>
                    </div>
                    <div class="col-auto">
                        <select class="form-select btn-phoenix-secondary px-4" name="responsable" onchange="this.form.submit()">
                            <option value="">Todos los ejecutivos de cuenta</option>
                            @foreach ($vendedores as $v)
                                <option value="{{ $v->id_empleado }}" @selected((int) ($filtros['responsable'] ?? 0) === $v->id_empleado)>{{ $v->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (array_filter($filtros))
                        <div class="col-auto">
                            <a class="btn btn-phoenix-danger px-3" href="{{ route('oportunidades.index') }}">
                                <span class="fas fa-times me-1"></span>Quitar filtro
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        {{-- Tablero Kanban Deals idéntico a Phoenix deals.html --}}
        <div class="kanban-deals-wrapper scrollbar mx-n4 px-4 mx-lg-n6 px-lg-6">
            <div class="deals d-flex gap-4 pb-4">
                @foreach ($etapas as $etapa)
                    @php
                        $lista = $porEtapa->get($etapa->id_etapa, collect());
                        $sumaValor = $lista->sum('valor_estimado');
                    @endphp
                    <div class="deals-col flex-shrink-0" style="width: 22rem;">
                        {{-- Cabecera de Columna de Etapa --}}
                        <div class="p-3 bg-white rounded-top-3 border-top border-start border-end border-200 position-sticky top-0 z-index-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 10px; height: 10px; background: {{ $etapa->color_hex ?? '#3874ff' }}"></span>
                                    <h5 class="mb-0 text-1100 fw-bold">{{ $etapa->nombre }}</h5>
                                    <span class="badge badge-phoenix badge-phoenix-secondary ms-2 fs--2">{{ $lista->count() }}</span>
                                </div>
                            </div>
                            <p class="fs--2 text-600 mb-1">Monto en etapa:</p>
                            <h4 class="mb-0 text-primary fw-bolder">Q {{ $dinero($sumaValor) }}</h4>
                            <div class="mt-2 rounded-pill" style="height: 3px; background: {{ $etapa->color_hex ?? '#3874ff' }}"></div>
                        </div>

                        {{-- Contenedor de Tarjetas Sortables --}}
                        <div class="p-3 bg-100 rounded-bottom-3 border-bottom border-start border-end border-200" style="min-height: 480px;">
                            @forelse ($lista as $o)
                                <div class="card mb-3 border border-200 shadow-sm position-relative">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center text-700 fs--2">
                                                <span class="far fa-clock me-1 text-500"></span>
                                                <span class="{{ $o->fecha_cierre_estimada?->isPast() && !$etapa->es_ganada && !$etapa->es_perdida ? 'text-danger fw-bold' : '' }}">
                                                    {{ $o->fecha_cierre_real?->format('d/m/Y') ?? $o->fecha_cierre_estimada?->format('d/m/Y') ?? 'Sin fecha' }}
                                                </span>
                                            </div>
                                            <span class="badge badge-phoenix badge-phoenix-info fs--2">{{ $o->probabilidad }}%</span>
                                        </div>

                                        <h6 class="mb-1">
                                            <a class="text-1100 text-hover-primary fw-bold fs-0" href="{{ route('oportunidades.show', $o->id_oportunidad) }}">
                                                {{ $o->nombre }}
                                            </a>
                                        </h6>

                                        <div class="d-flex align-items-center text-600 fs--2 mb-3">
                                            <span class="far fa-building me-1 text-primary"></span>
                                            <span class="text-truncate" style="max-width: 15rem;">{{ $o->cliente?->razon_social ?? $o->prospecto?->nombre_empresa ?? 'Sin cliente' }}</span>
                                        </div>

                                        {{-- Barra de Probabilidad estilo Phoenix --}}
                                        <div class="progress mb-3" style="height: 5px;">
                                            <div class="progress-bar rounded-pill bg-{{ $o->probabilidad >= 70 ? 'success' : ($o->probabilidad >= 40 ? 'primary' : 'warning') }}" style="width: {{ $o->probabilidad }}%"></div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-200">
                                            <div>
                                                <span class="fs--2 text-600 d-block">Valor</span>
                                                <span class="fw-bold text-1100 fs--1">{{ $o->moneda }} {{ $dinero($o->valor_estimado) }}</span>
                                            </div>
                                            @if ($o->responsable)
                                                <div class="d-flex align-items-center" title="Ejecutivo: {{ $o->responsable->nombre_completo }}">
                                                    <div class="avatar avatar-s">
                                                        <div class="avatar-name rounded-circle bg-soft-primary text-primary fs--2">
                                                            <span>{{ mb_strtoupper(mb_substr($o->responsable->nombres ?: $o->responsable->nombre_completo, 0, 1)) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="card bg-white border-dashed border-300">
                                    <div class="card-body p-4 text-center">
                                        <span class="fas fa-handshake text-300 fs-1 d-block mb-2"></span>
                                        <p class="fs--2 text-500 mb-0">Sin oportunidades en esta etapa.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('estilos')
    <style>
        .kanban-deals-wrapper { overflow-x: auto; }
        .deals-col { min-width: 22rem; }
    </style>
@endpush
