@extends('layouts.app', ['titulo' => 'Facturas'])

@section('contenido')
    @php
        $yo = auth()->user();
        $tramos = \App\Http\Controllers\FacturaController::TRAMOS;
    @endphp

    <x-page-header
        titulo="Facturas"
        subtitulo="Borrador → emitida → enviada → pagada. Al emitir se ejecuta el presupuesto de ingresos."
        icono="fas fa-file-invoice-dollar"
        :breadcrumbs="['Finanzas' => null, 'Facturas' => null]"
    >
        @if ($yo->puede('facturas.exportar'))
            <a class="btn btn-phoenix-secondary" href="{{ route('facturas.exportar', request()->query()) }}">
                <span class="fas fa-file-excel me-2"></span>Exportar
            </a>
        @endif
        @if ($yo->puede('facturas.crear'))
            <a class="btn btn-primary" href="{{ route('facturas.create') }}">
                <span class="fas fa-plus me-2"></span>Nueva factura
            </a>
        @endif
    </x-page-header>

    @if ($cartera)
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 text-1100"><span class="fas fa-wallet text-primary me-2 fs-0"></span>Cartera por cobrar</h5>
                    @if (collect($cartera)->sum('vencido') > 0)
                        <a class="fs--1 fw-semi-bold text-danger" href="{{ route('facturas.index', ['estado' => 'vencidas']) }}">
                            <span class="fas fa-exclamation-circle me-1"></span>Ver las facturas vencidas
                        </a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-700">Moneda</th>
                                @foreach ($tramos as [$nombre])
                                    <th class="text-end text-uppercase text-700">{{ $nombre }}</th>
                                @endforeach
                                <th class="text-end text-uppercase text-700">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($cartera as $moneda => $c)
                            <tr>
                                <td class="fw-bold text-900">{{ $moneda }}</td>
                                @foreach ($c['tramos'] as $i => $monto)
                                    <td class="text-end {{ $i > 0 && $monto > 0 ? ($i >= 3 ? 'text-danger fw-bold' : 'text-warning fw-semi-bold') : 'text-800' }}">
                                        {{ $monto > 0 ? number_format($monto, 2) : '—' }}
                                    </td>
                                @endforeach
                                <td class="text-end fw-bold text-1100">{{ number_format($c['total'], 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número, cliente o NIT" maxlength="100" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="cliente">
                    <option value="">Todos los clientes</option>
                    @foreach ($clientes as $c)
                        <option value="{{ $c->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="estado">
                    <option value="">Todos los estados</option>
                    <option value="vencidas" @selected(($filtros['estado'] ?? '') === 'vencidas')>Vencidas con saldo</option>
                    @foreach ($estados as $codigo => [$nombre])
                        <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1"><input class="form-control" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Emitidas desde" /></div>
            <div class="col-md-1"><input class="form-control" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Emitidas hasta" /></div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-phoenix-secondary w-100" type="submit">Filtrar</button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('facturas.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$facturas">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Número</th>
                    <th class="text-uppercase text-700">Cliente</th>
                    <th class="text-uppercase text-700">Emisión</th>
                    <th class="text-uppercase text-700">Vence</th>
                    <th class="text-end text-uppercase text-700">Total</th>
                    <th class="text-end text-uppercase text-700">Saldo</th>
                    <th class="pe-0 text-end text-uppercase text-700">Estado</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($facturas as $f)
                @php
                    [$nombreEstado, $color] = $estados[$f->estado] ?? [$f->estado, 'secondary'];
                    $vencida = in_array($f->estado, \App\Models\Clientes\Cliente::ESTADOS_CON_SALDO, true) && (float) $f->saldo_pendiente > 0 && $f->fecha_vencimiento?->lt(today());
                @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static {{ $f->estado === 'ANULADA' ? 'text-500' : '' }}">
                    <td class="ps-0 py-2 text-nowrap">
                        <a class="fw-bold text-1100 text-decoration-none" href="{{ route('facturas.show', $f->id_factura) }}">{{ $f->numero_completo }}</a>
                        @if ($f->tipo !== 'FACTURA')
                            <span class="d-block fs--2 text-600">{{ $tipos[$f->tipo] ?? $f->tipo }}</span>
                        @endif
                    </td>
                    <td class="fw-semi-bold text-900">{{ $f->cliente?->razon_social ?? '—' }}</td>
                    <td class="text-nowrap text-800">{{ $f->fecha_emision?->format('d/m/Y') }}</td>
                    <td class="text-nowrap {{ $vencida ? 'text-danger fw-bold' : 'text-800' }}">{{ $f->fecha_vencimiento?->format('d/m/Y') }}</td>
                    <td class="text-end text-nowrap text-900">{{ $f->moneda }} {{ number_format((float) $f->total, 2) }}</td>
                    <td class="text-end text-nowrap fw-bold text-1000">
                        {{ (float) $f->saldo_pendiente > 0 && $f->estado !== 'BORRADOR' ? number_format((float) $f->saldo_pendiente, 2) : '—' }}
                    </td>
                    <td class="pe-0 text-end text-nowrap">
                        <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                        @if ($vencida)
                            <span class="badge badge-phoenix badge-phoenix-danger ms-1"><span class="fas fa-clock me-1"></span>Vencida</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-700 py-5">
                        <span class="fas fa-file-invoice-dollar fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ninguna factura coincide con los filtros.' : 'Todavía no hay facturas.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
