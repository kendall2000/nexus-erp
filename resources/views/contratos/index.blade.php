@extends('layouts.app', ['titulo' => 'Contratos'])

@section('contenido')
    @php $yo = auth()->user(); @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Contratos de servicio</h2>
            <p class="text-700 fw-semi-bold mb-0">Servicios contratados por cliente, sus sitios y el personal asignado.</p>
        </div>
        @if ($yo->puede('contratos.crear'))
            <a class="btn btn-primary" href="{{ route('contratos.create') }}"><span class="fas fa-plus me-2"></span>Nuevo contrato</a>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Contratos vigentes</p><h3 class="mb-0">{{ $resumen['vigentes'] }}</h3>
        </div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Facturación mensual contratada</p>
            @forelse ($resumen['mensual'] as $moneda => $total)<h3 class="mb-0">{{ $moneda }} {{ number_format((float) $total, 2) }}</h3>@empty<h3 class="mb-0">0.00</h3>@endforelse
        </div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Vencen en 30 días</p>
            <h3 class="mb-0 {{ $resumen['porVencer'] ? 'text-warning' : '' }}">{{ $resumen['porVencer'] }}</h3>
            @if ($resumen['porVencer'])<a class="fs--2" href="{{ route('contratos.index', ['por_vencer' => 1]) }}">Ver cuáles</a>@endif
        </div></div></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número, proyecto o cliente" maxlength="100" /></div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="cliente">
                        <option value="">Todos los clientes</option>
                        @foreach ($clientes as $cl)<option value="{{ $cl->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $cl->id_cliente)>{{ $cl->razon_social }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $codigo => [$nombre])<option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    @if (! empty($filtros['por_vencer']))<input type="hidden" name="por_vencer" value="1" />@endif
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('contratos.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Contrato</th><th>Cliente</th><th>Vigencia</th><th class="text-end">Valor mensual</th><th class="text-end">Personal</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($contratos as $c)
                        @php
                            [$nombreEstado, $color] = $estados[$c->estado] ?? [$c->estado, 'secondary'];
                            $porVencer = $c->estado === 'VIGENTE' && $c->fecha_fin && $c->fecha_fin->between(today(), today()->addDays(30));
                        @endphp
                        <tr>
                            <td>
                                <a class="fw-semi-bold" href="{{ route('contratos.show', $c->id_contrato) }}">{{ $c->numero_contrato }}</a>
                                @if ($c->nombre_proyecto)<span class="d-block fs--2 text-600">{{ $c->nombre_proyecto }}</span>@endif
                            </td>
                            <td>{{ $c->cliente?->razon_social }}</td>
                            <td class="text-nowrap {{ $porVencer ? 'text-warning fw-semi-bold' : '' }}">{{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? 'indefinido' }}</td>
                            <td class="text-end text-nowrap">{{ $c->moneda }} {{ number_format((float) $c->valor_mensual, 2) }}</td>
                            <td class="text-end">{{ $c->asignaciones_activas_count }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún contrato coincide con los filtros.' : 'Todavía no hay contratos.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $contratos->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
