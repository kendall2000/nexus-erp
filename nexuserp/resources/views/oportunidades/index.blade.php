@extends('layouts.app', ['titulo' => 'Oportunidades'])

@section('contenido')
    @php
        $yo = auth()->user();
        $dinero = fn ($n) => number_format((float) $n, 2);
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Embudo de ventas</h2>
            <p class="text-700 fw-semi-bold mb-0">Oportunidades por etapa. El valor ponderado usa la probabilidad de cada una.</p>
        </div>
        <div class="d-flex gap-2">
            <form method="GET">
                <select class="form-select form-select-sm" name="responsable" onchange="this.form.submit()">
                    <option value="">Todos los vendedores</option>
                    @foreach ($vendedores as $v)<option value="{{ $v->id_empleado }}" @selected((int) ($filtros['responsable'] ?? 0) === $v->id_empleado)>{{ $v->nombre_completo }}</option>@endforeach
                </select>
            </form>
            @if ($yo->puede('oportunidades.crear'))
                <a class="btn btn-primary text-nowrap" href="{{ route('oportunidades.create') }}"><span class="fas fa-plus me-2"></span>Nueva oportunidad</a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-3"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Abiertas</p><h3 class="mb-0">{{ $resumen['abiertas'] }}</h3></div></div></div>
        <div class="col-sm-3"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Valor en juego</p><h3 class="mb-0">{{ $dinero($resumen['valor']) }}</h3></div></div></div>
        <div class="col-sm-3"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Valor ponderado</p><h3 class="mb-0">{{ $dinero($resumen['ponderado']) }}</h3></div></div></div>
        <div class="col-sm-3"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Ganado este mes</p><h3 class="mb-0 text-success">{{ $dinero($resumen['ganadasMes']) }}</h3></div></div></div>
    </div>

    <div class="d-flex gap-3 pb-3" style="overflow-x: auto">
        @foreach ($etapas as $etapa)
            @php $lista = $porEtapa->get($etapa->id_etapa, collect()); @endphp
            <div class="flex-shrink-0" style="width: 17rem">
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="fw-bold fs--1"><span class="d-inline-block rounded-circle me-1" style="width: .6rem; height: .6rem; background: {{ $etapa->color_hex ?? '#94A3B8' }}"></span>{{ $etapa->nombre }}</span>
                    <span class="fs--2 text-700">{{ $lista->count() }} · {{ $dinero($lista->sum('valor_estimado')) }}</span>
                </div>
                @forelse ($lista as $o)
                    <a class="card mb-2 text-decoration-none" href="{{ route('oportunidades.show', $o->id_oportunidad) }}">
                        <div class="card-body p-2 fs--1">
                            <p class="fw-semi-bold text-900 mb-1">{{ $o->nombre }}</p>
                            <p class="text-700 mb-1">{{ $o->cliente?->razon_social ?? $o->prospecto?->nombre_empresa }}</p>
                            <div class="d-flex justify-content-between text-700">
                                <span>{{ $o->moneda }} {{ $dinero($o->valor_estimado) }}</span>
                                <span>{{ $o->probabilidad }} %</span>
                            </div>
                            <div class="d-flex justify-content-between fs--2 text-600">
                                <span>{{ $o->responsable?->nombre_corto }}</span>
                                <span class="{{ $o->fecha_cierre_estimada?->isPast() && ! $etapa->es_ganada && ! $etapa->es_perdida ? 'text-danger' : '' }}">{{ $o->fecha_cierre_real?->format('d/m/Y') ?? $o->fecha_cierre_estimada?->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="fs--2 text-500 px-1">Sin oportunidades.</p>
                @endforelse
            </div>
        @endforeach
    </div>
@endsection
