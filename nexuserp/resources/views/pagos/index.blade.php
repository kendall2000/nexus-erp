@extends('layouts.app', ['titulo' => 'Pagos'])

@section('contenido')
    @php $yo = auth()->user(); @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Pagos recibidos</h2>
            <p class="text-700 fw-semi-bold mb-0">Cobros de facturas. Un cobro no se borra: se revierte o se registra la devolución.</p>
        </div>
        <div class="d-flex gap-2">
            @if ($yo->puede('pagos.exportar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('pagos.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($yo->puede('pagos.crear') || $yo->puede('facturas.cobrar'))
                <a class="btn btn-primary" href="{{ route('pagos.create') }}"><span class="fas fa-plus me-2"></span>Registrar cobro</a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        @forelse ($cobrado as $c)
            <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body py-3">
                <p class="fs--1 text-700 mb-1">Cobrado{{ array_filter($filtros) ? ' (filtrado)' : '' }}</p>
                <h3 class="mb-0">{{ $c->moneda }} {{ number_format((float) $c->total, 2) }}</h3>
                <p class="fs--2 text-600 mb-0">{{ $c->cantidad }} {{ $c->cantidad == 1 ? 'cobro aplicado' : 'cobros aplicados' }}</p>
            </div></div></div>
        @empty
            <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Cobrado</p><h3 class="mb-0">0.00</h3></div></div></div>
        @endforelse
        @if ($porAcreditar)
            <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body py-3">
                <p class="fs--1 text-700 mb-1">Por acreditar en banco</p>
                <h3 class="mb-0 text-warning">{{ $porAcreditar }}</h3>
                <a class="fs--2" href="{{ route('pagos.index', ['sin_acreditar' => 1]) }}">Ver cuáles</a>
            </div></div></div>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Referencia, factura o cliente" maxlength="100" /></div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="cliente">
                        <option value="">Todos los clientes</option>
                        @foreach ($clientes as $c)<option value="{{ $c->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="forma">
                        <option value="">Toda forma de pago</option>
                        @foreach ($formas as $codigo => $nombre)<option value="{{ $codigo }}" @selected(($filtros['forma'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <select class="form-select form-select-sm" name="estado" title="Estado">
                        <option value="">Todos</option>
                        @foreach ($estados as $codigo => [$nombre])<option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-1"><input class="form-control form-control-sm" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Desde" /></div>
                <div class="col-md-1"><input class="form-control form-control-sm" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Hasta" /></div>
                <div class="col-md-2 d-flex gap-2">
                    @if (! empty($filtros['sin_acreditar']))<input type="hidden" name="sin_acreditar" value="1" />@endif
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('pagos.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>
            @if (! empty($filtros['sin_acreditar']))<p class="fs--1 text-700"><span class="fas fa-filter me-1"></span>Solo cobros aplicados que no son en efectivo y aún no tienen fecha de acreditación.</p>@endif

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Recibo</th><th>Fecha</th><th>Cliente</th><th>Factura</th><th>Forma</th><th class="text-end">Monto</th><th>Acreditado</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($pagos as $p)
                        @php [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary']; @endphp
                        <tr class="{{ $p->estado !== 'APLICADO' ? 'text-500' : '' }}">
                            <td><a class="fw-semi-bold" href="{{ route('pagos.show', $p->id_pago) }}">#{{ $p->id_pago }}</a></td>
                            <td class="text-nowrap">{{ $p->fecha_pago?->format('d/m/Y') }}</td>
                            <td>{{ $p->cliente?->razon_social ?? '—' }}</td>
                            <td class="text-nowrap">{{ $p->factura?->numero_completo ?? '—' }}</td>
                            <td>{{ $formas[$p->forma_pago] ?? $p->forma_pago }}@if ($p->referencia)<span class="d-block fs--2 text-600">{{ $p->referencia }}</span>@endif</td>
                            <td class="text-end text-nowrap fw-semi-bold {{ $p->estado !== 'APLICADO' ? 'text-decoration-line-through' : '' }}">{{ $p->moneda }} {{ number_format((float) $p->monto, 2) }}</td>
                            <td class="text-nowrap">{{ $p->fecha_acreditado?->format('d/m/Y') ?? ($p->forma_pago === 'EFECTIVO' ? '—' : 'Pendiente') }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún cobro coincide con los filtros.' : 'Todavía no hay cobros.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $pagos->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
