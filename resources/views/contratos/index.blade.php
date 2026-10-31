@extends('layouts.app', ['titulo' => 'Contratos'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <x-page-header
        titulo="Contratos de servicio"
        subtitulo="Servicios contratados por cliente, sus sitios y el personal asignado."
        icono="fas fa-file-contract"
        :breadcrumbs="['Comercial' => null, 'Contratos' => null]"
    >
        @if ($yo->puede('contratos.crear'))
            <a class="btn btn-primary" href="{{ route('contratos.create') }}">
                <span class="fas fa-plus me-2"></span>Nuevo contrato
            </a>
        @endif
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <x-stat-card etiqueta="Contratos vigentes" :valor="$resumen['vigentes']" icono="fas fa-file-signature" color="primary" />
        </div>
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="fs--1 text-700 fw-semi-bold mb-1">Facturación mensual contratada</p>
                        @forelse ($resumen['mensual'] as $moneda => $total)
                            <h3 class="mb-0 text-1100">{{ $moneda }} {{ number_format((float) $total, 2) }}</h3>
                        @empty
                            <h3 class="mb-0 text-1100">0.00</h3>
                        @endforelse
                    </div>
                    <div class="icon-wrapper-sm bg-success-100 rounded-3 d-flex align-items-center justify-content-center" style="width:2.5rem;height:2.5rem">
                        <span class="fas fa-sync-alt text-success fs-0"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="fs--1 text-700 fw-semi-bold mb-1">Vencen en 30 días</p>
                        <h3 class="mb-0 {{ $resumen['porVencer'] ? 'text-warning' : 'text-1100' }}">{{ $resumen['porVencer'] }}</h3>
                        @if ($resumen['porVencer'])
                            <a class="fs--2 fw-semi-bold" href="{{ route('contratos.index', ['por_vencer' => 1]) }}">Ver cuáles</a>
                        @endif
                    </div>
                    <div class="icon-wrapper-sm bg-warning-100 rounded-3 d-flex align-items-center justify-content-center" style="width:2.5rem;height:2.5rem">
                        <span class="fas fa-hourglass-half text-warning fs-0"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número, proyecto o cliente" maxlength="100" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="cliente">
                    <option value="">Todos los clientes</option>
                    @foreach ($clientes as $cl)
                        <option value="{{ $cl->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $cl->id_cliente)>{{ $cl->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="estado">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $codigo => [$nombre])
                        <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                @if (! empty($filtros['por_vencer']))<input type="hidden" name="por_vencer" value="1" />@endif
                <button class="btn btn-phoenix-secondary w-100" type="submit">Filtrar</button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('contratos.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$contratos">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Contrato</th>
                    <th class="text-uppercase text-700">Cliente</th>
                    <th class="text-uppercase text-700">Vigencia</th>
                    <th class="text-end text-uppercase text-700">Valor mensual</th>
                    <th class="text-end text-uppercase text-700">Personal</th>
                    <th class="pe-0 text-end text-uppercase text-700">Estado</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($contratos as $c)
                @php
                    [$nombreEstado, $color] = $estados[$c->estado] ?? [$c->estado, 'secondary'];
                    $porVencer = $c->estado === 'VIGENTE' && $c->fecha_fin && $c->fecha_fin->between(today(), today()->addDays(30));
                @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2">
                        <a class="fw-bold text-1100 text-decoration-none" href="{{ route('contratos.show', $c->id_contrato) }}">{{ $c->numero_contrato }}</a>
                        @if ($c->nombre_proyecto)<span class="d-block fs--2 text-600">{{ $c->nombre_proyecto }}</span>@endif
                    </td>
                    <td class="fw-semi-bold text-900">{{ $c->cliente?->razon_social }}</td>
                    <td class="text-nowrap {{ $porVencer ? 'text-warning fw-semi-bold' : 'text-800' }}">
                        {{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? 'indefinido' }}
                    </td>
                    <td class="text-end text-nowrap fw-bold text-900">{{ $c->moneda }} {{ number_format((float) $c->valor_mensual, 2) }}</td>
                    <td class="text-end text-800">{{ $c->asignaciones_activas_count }}</td>
                    <td class="pe-0 text-end">
                        <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-700 py-5">
                        <span class="fas fa-file-contract fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ningún contrato coincide con los filtros.' : 'Todavía no hay contratos.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
