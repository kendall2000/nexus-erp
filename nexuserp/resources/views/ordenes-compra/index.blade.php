@extends('layouts.app', ['titulo' => 'Órdenes de compra'])

@section('contenido')
    @php $yo = auth()->user(); @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Órdenes de compra</h2>
            <p class="text-700 fw-semi-bold mb-0">Borrador → aprobada → recibida. Al aprobar se ejecuta el presupuesto.</p>
        </div>
        <div class="d-flex gap-2">
            @if ($yo->puede('ordenes_compra.exportar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('ordenes-compra.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($yo->puede('ordenes_compra.crear'))
                <a class="btn btn-primary" href="{{ route('ordenes-compra.create') }}"><span class="fas fa-plus me-2"></span>Nueva orden</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número de orden" maxlength="30" /></div>
                <div class="col-md-4">
                    <select class="form-select form-select-sm" name="proveedor">
                        <option value="">Todos los proveedores</option>
                        @foreach ($proveedores as $p)<option value="{{ $p->id_proveedor }}" @selected((int) ($filtros['proveedor'] ?? 0) === $p->id_proveedor)>{{ $p->razon_social }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $codigo => [$nombre])<option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('ordenes-compra.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Número</th><th>Proveedor</th><th>Bodega</th><th>Emisión</th><th>Entrega</th><th class="text-end">Total</th><th>Recepción</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($ordenes as $oc)
                        @php
                            $avance = (float) $oc->pedido > 0 ? round((float) $oc->recibido / (float) $oc->pedido * 100) : 0;
                            [$nombreEstado, $color] = $estados[$oc->estado] ?? [$oc->estado, 'secondary'];
                            $atrasada = in_array($oc->estado, ['ENVIADA', 'PARCIAL'], true) && $oc->fecha_entrega_esperada?->isPast();
                        @endphp
                        <tr>
                            <td class="text-nowrap"><a class="fw-semi-bold" href="{{ route('ordenes-compra.show', $oc->id_oc) }}">{{ $oc->numero_oc }}</a></td>
                            <td>{{ $oc->proveedor?->razon_social ?? '—' }}</td>
                            <td>{{ $oc->bodega?->nombre ?? '—' }}</td>
                            <td class="text-nowrap">{{ $oc->fecha_emision?->format('d/m/Y') }}</td>
                            <td class="text-nowrap {{ $atrasada ? 'text-danger fw-semi-bold' : '' }}">{{ $oc->fecha_entrega_esperada?->format('d/m/Y') ?? '—' }}@if ($atrasada) <span class="fas fa-exclamation-circle" title="Atrasada"></span>@endif</td>
                            <td class="text-end text-nowrap fw-semi-bold">{{ $oc->moneda }} {{ number_format((float) $oc->total, 2) }}</td>
                            <td style="min-width: 7rem">
                                @if (! in_array($oc->estado, ['BORRADOR', 'CANCELADA'], true))
                                    <div class="progress" style="height: 6px" title="{{ $avance }} %"><div class="progress-bar bg-success" style="width: {{ $avance }}%"></div></div>
                                    <span class="fs--2 text-600">{{ $avance }} %</span>
                                @else
                                    <span class="text-400">—</span>
                                @endif
                            </td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ninguna orden coincide con los filtros.' : 'No hay órdenes de compra.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $ordenes->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
