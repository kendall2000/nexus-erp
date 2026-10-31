@extends('layouts.app', ['titulo' => 'Pagos'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <x-page-header
        titulo="Pagos recibidos"
        subtitulo="Cobros de facturas. Un cobro no se borra: se revierte o se registra la devolución."
        icono="fas fa-hand-holding-usd"
        :breadcrumbs="['Finanzas' => null, 'Pagos' => null]"
    >
        @if ($yo->puede('pagos.exportar'))
            <a class="btn btn-phoenix-secondary" href="{{ route('pagos.exportar', request()->query()) }}">
                <span class="fas fa-file-excel me-2"></span>Exportar
            </a>
        @endif
        @if ($yo->puede('pagos.crear') || $yo->puede('facturas.cobrar'))
            <a class="btn btn-primary" href="{{ route('pagos.create') }}">
                <span class="fas fa-plus me-2"></span>Registrar cobro
            </a>
        @endif
    </x-page-header>

    <div class="row g-3 mb-4">
        @forelse ($cobrado as $c)
            <div class="col-sm-6 col-lg-3">
                <x-stat-card
                    :etiqueta="'Cobrado'.(array_filter($filtros) ? ' (filtrado)' : '')"
                    :valor="$c->moneda.' '.number_format((float) $c->total, 2)"
                    :subtexto="$c->cantidad.' '.($c->cantidad == 1 ? 'cobro aplicado' : 'cobros aplicados')"
                    icono="fas fa-check-circle"
                    color="success"
                />
            </div>
        @empty
            <div class="col-sm-6 col-lg-3">
                <x-stat-card etiqueta="Cobrado" valor="0.00" icono="fas fa-check-circle" color="success" />
            </div>
        @endforelse
        @if ($porAcreditar)
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body py-3 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="fs--1 text-700 fw-semi-bold mb-1">Por acreditar en banco</p>
                            <h3 class="mb-0 text-warning">{{ $porAcreditar }}</h3>
                            <a class="fs--2 fw-semi-bold" href="{{ route('pagos.index', ['sin_acreditar' => 1]) }}">Ver cuáles</a>
                        </div>
                        <div class="icon-wrapper-sm bg-warning-100 rounded-3 d-flex align-items-center justify-content-center" style="width:2.5rem;height:2.5rem">
                            <span class="fas fa-university text-warning fs-0"></span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Referencia, factura o cliente" maxlength="100" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="cliente">
                    <option value="">Todos los clientes</option>
                    @foreach ($clientes as $c)
                        <option value="{{ $c->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="forma">
                    <option value="">Toda forma de pago</option>
                    @foreach ($formas as $codigo => $nombre)
                        <option value="{{ $codigo }}" @selected(($filtros['forma'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <select class="form-select" name="estado" title="Estado">
                    <option value="">Todos</option>
                    @foreach ($estados as $codigo => [$nombre])
                        <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1"><input class="form-control" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Desde" /></div>
            <div class="col-md-1"><input class="form-control" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Hasta" /></div>
            <div class="col-md-2 d-flex gap-2">
                @if (! empty($filtros['sin_acreditar']))<input type="hidden" name="sin_acreditar" value="1" />@endif
                <button class="btn btn-phoenix-secondary w-100" type="submit">Filtrar</button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('pagos.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
        @if (! empty($filtros['sin_acreditar']))
            <p class="fs--1 text-700 mt-2 mb-0"><span class="fas fa-filter me-1"></span>Solo cobros aplicados que no son en efectivo y aún no tienen fecha de acreditación.</p>
        @endif
    </div>

    <x-phoenix-table :paginador="$pagos">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Recibo</th>
                    <th class="text-uppercase text-700">Fecha</th>
                    <th class="text-uppercase text-700">Cliente</th>
                    <th class="text-uppercase text-700">Factura</th>
                    <th class="text-uppercase text-700">Forma</th>
                    <th class="text-end text-uppercase text-700">Monto</th>
                    <th class="text-uppercase text-700">Acreditado</th>
                    <th class="pe-0 text-end text-uppercase text-700">Estado</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($pagos as $p)
                @php [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary']; @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static {{ $p->estado !== 'APLICADO' ? 'text-500' : '' }}">
                    <td class="ps-0 py-2">
                        <a class="fw-bold text-1100 text-decoration-none" href="{{ route('pagos.show', $p->id_pago) }}">#{{ $p->id_pago }}</a>
                    </td>
                    <td class="text-nowrap text-800">{{ $p->fecha_pago?->format('d/m/Y') }}</td>
                    <td class="fw-semi-bold text-900">{{ $p->cliente?->razon_social ?? '—' }}</td>
                    <td class="text-nowrap text-800">{{ $p->factura?->numero_completo ?? '—' }}</td>
                    <td class="text-800">
                        {{ $formas[$p->forma_pago] ?? $p->forma_pago }}
                        @if ($p->referencia)<span class="d-block fs--2 text-600">{{ $p->referencia }}</span>@endif
                    </td>
                    <td class="text-end text-nowrap fw-bold {{ $p->estado !== 'APLICADO' ? 'text-decoration-line-through text-500' : 'text-1000' }}">
                        {{ $p->moneda }} {{ number_format((float) $p->monto, 2) }}
                    </td>
                    <td class="text-nowrap text-800">{{ $p->fecha_acreditado?->format('d/m/Y') ?? ($p->forma_pago === 'EFECTIVO' ? '—' : 'Pendiente') }}</td>
                    <td class="pe-0 text-end">
                        <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-700 py-5">
                        <span class="fas fa-hand-holding-usd fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ningún cobro coincide con los filtros.' : 'Todavía no hay cobros.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
