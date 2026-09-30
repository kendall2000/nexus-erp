@extends('layouts.app', ['titulo' => 'Facturas'])

@section('contenido')
    @php
        $yo = auth()->user();
        $tramos = \App\Http\Controllers\FacturaController::TRAMOS;
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Facturas</h2>
            <p class="text-700 fw-semi-bold mb-0">Borrador → emitida → enviada → pagada. Al emitir se ejecuta el presupuesto de ingresos.</p>
        </div>
        <div class="d-flex gap-2">
            @if ($yo->puede('facturas.exportar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('facturas.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($yo->puede('facturas.crear'))
                <a class="btn btn-primary" href="{{ route('facturas.create') }}"><span class="fas fa-plus me-2"></span>Nueva factura</a>
            @endif
        </div>
    </div>

    @if ($cartera)
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Cartera por cobrar</h5>
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead><tr><th>Moneda</th>@foreach ($tramos as [$nombre])<th class="text-end">{{ $nombre }}</th>@endforeach<th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @foreach ($cartera as $moneda => $c)
                            <tr>
                                <td class="fw-semi-bold">{{ $moneda }}</td>
                                @foreach ($c['tramos'] as $i => $monto)
                                    <td class="text-end {{ $i > 0 && $monto > 0 ? ($i >= 3 ? 'text-danger fw-semi-bold' : 'text-warning') : '' }}">{{ $monto > 0 ? number_format($monto, 2) : '—' }}</td>
                                @endforeach
                                <td class="text-end fw-bold">{{ number_format($c['total'], 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if (collect($cartera)->sum('vencido') > 0)
                    <a class="fs--1" href="{{ route('facturas.index', ['estado' => 'vencidas']) }}"><span class="fas fa-exclamation-circle me-1"></span>Ver las facturas vencidas</a>
                @endif
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número, cliente o NIT" maxlength="100" /></div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="cliente">
                        <option value="">Todos los clientes</option>
                        @foreach ($clientes as $c)<option value="{{ $c->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Todos los estados</option>
                        <option value="vencidas" @selected(($filtros['estado'] ?? '') === 'vencidas')>Vencidas con saldo</option>
                        @foreach ($estados as $codigo => [$nombre])<option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-1"><input class="form-control form-control-sm" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Emitidas desde" /></div>
                <div class="col-md-1"><input class="form-control form-control-sm" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Emitidas hasta" /></div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('facturas.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Número</th><th>Cliente</th><th>Emisión</th><th>Vence</th><th class="text-end">Total</th><th class="text-end">Saldo</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($facturas as $f)
                        @php
                            [$nombreEstado, $color] = $estados[$f->estado] ?? [$f->estado, 'secondary'];
                            $vencida = in_array($f->estado, \App\Models\Clientes\Cliente::ESTADOS_CON_SALDO, true) && (float) $f->saldo_pendiente > 0 && $f->fecha_vencimiento?->lt(today());
                        @endphp
                        <tr class="{{ $f->estado === 'ANULADA' ? 'text-500' : '' }}">
                            <td class="text-nowrap">
                                <a class="fw-semi-bold" href="{{ route('facturas.show', $f->id_factura) }}">{{ $f->numero_completo }}</a>
                                @if ($f->tipo !== 'FACTURA')<span class="d-block fs--2 text-600">{{ $tipos[$f->tipo] ?? $f->tipo }}</span>@endif
                            </td>
                            <td>{{ $f->cliente?->razon_social ?? '—' }}</td>
                            <td class="text-nowrap">{{ $f->fecha_emision?->format('d/m/Y') }}</td>
                            <td class="text-nowrap {{ $vencida ? 'text-danger fw-semi-bold' : '' }}">{{ $f->fecha_vencimiento?->format('d/m/Y') }}</td>
                            <td class="text-end text-nowrap">{{ $f->moneda }} {{ number_format((float) $f->total, 2) }}</td>
                            <td class="text-end text-nowrap fw-semi-bold">{{ (float) $f->saldo_pendiente > 0 && $f->estado !== 'BORRADOR' ? number_format((float) $f->saldo_pendiente, 2) : '—' }}</td>
                            <td class="text-nowrap">
                                <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                                @if ($vencida)<span class="badge badge-phoenix badge-phoenix-danger"><span class="fas fa-clock me-1"></span>Vencida</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ninguna factura coincide con los filtros.' : 'Todavía no hay facturas.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $facturas->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
