@extends('layouts.app', ['titulo' => 'Recepción '.$r->numero_recepcion])

@section('contenido')
    @php
        $yo = auth()->user();
        $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 4), '0'), '.');
        $moneda = $r->ordenCompra?->moneda;
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('recepciones.index') }}">Recepciones</a></li>
            <li class="breadcrumb-item active">{{ $r->numero_recepcion }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Recepción {{ $r->numero_recepcion }}</h2>
            <p class="text-700 mb-0">{{ $r->ordenCompra?->proveedor?->razon_social }} · recibida el {{ $r->fecha_recepcion?->format('d/m/Y') }} en {{ $r->bodega?->nombre }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($yo->puede('recepciones.imprimir'))
                <a class="btn btn-phoenix-secondary" href="{{ route('recepciones.imprimir', $r->id_recepcion) }}" target="_blank" rel="noopener"><span class="fas fa-print me-2"></span>Imprimir</a>
            @endif
            @if ($r->ordenCompra && in_array($r->ordenCompra->estado, \App\Http\Controllers\RecepcionController::RECIBIBLES, true) && $yo->puede('recepciones.crear'))
                <a class="btn btn-primary" href="{{ route('recepciones.create', ['oc' => $r->id_oc]) }}"><span class="fas fa-truck-loading me-2"></span>Recibir lo pendiente</a>
            @endif
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th>Producto</th><th class="text-end">Recibido</th><th class="text-end">Costo unitario</th><th class="text-end">Importe</th></tr></thead>
                            <tbody>
                            @foreach ($r->detalles as $d)
                                <tr>
                                    <td>
                                        <span class="fw-semi-bold">{{ $d->producto?->nombre ?? 'Producto #'.$d->id_producto }}</span>
                                        <span class="text-600 fs--2 d-block"><code>{{ $d->producto?->codigo }}</code> {{ $d->lineaOC?->descripcion }}</span>
                                    </td>
                                    <td class="text-end">{{ $cantidad($d->cantidad_recibida) }} {{ $d->producto?->unidad_medida }}</td>
                                    <td class="text-end">{{ number_format((float) $d->costo_unitario, 2) }}</td>
                                    <td class="text-end fw-semi-bold">{{ number_format((float) $d->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot class="fw-semi-bold">
                            <tr class="fs-0"><td colspan="3" class="text-end">Valor recibido (sin IVA)</td><td class="text-end text-nowrap">{{ $moneda }} {{ number_format((float) $r->detalles->sum('subtotal'), 2) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Detalles</h5>
                    <dl class="row mb-0">
                        <dt class="col-5 text-700">Orden</dt>
                        <dd class="col-7">
                            @if ($r->ordenCompra && $yo->puede('ordenes_compra.ver'))
                                <a href="{{ route('ordenes-compra.show', $r->id_oc) }}">{{ $r->ordenCompra->numero_oc }}</a>
                            @else
                                {{ $r->ordenCompra?->numero_oc ?? '—' }}
                            @endif
                            <br><span class="text-600">{{ \App\Http\Controllers\OrdenCompraController::ESTADOS[$r->ordenCompra?->estado][0] ?? '' }}</span>
                        </dd>
                        <dt class="col-5 text-700">Proveedor</dt><dd class="col-7">{{ $r->ordenCompra?->proveedor?->razon_social ?? '—' }}</dd>
                        <dt class="col-5 text-700">Bodega</dt><dd class="col-7">{{ $r->bodega?->nombre ?? '—' }}</dd>
                        <dt class="col-5 text-700">Registrada por</dt><dd class="col-7">{{ $r->creadoPor?->nombre_completo ?? '—' }}<br><span class="text-600">{{ $r->created_at?->format('d/m/Y H:i') }}</span></dd>
                        @if ($r->notas)<dt class="col-5 text-700">Notas</dt><dd class="col-7">{{ $r->notas }}</dd>@endif
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3"><span class="fas fa-exchange-alt me-2 text-600"></span>Movimientos de inventario (kardex)</h5>
            @if ($movimientos->isEmpty())
                <p class="text-700 fs--1 mb-0">Esta recepción no tiene movimientos en el kardex.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead><tr><th>#</th><th>Fecha</th><th>Tipo</th><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Costo total</th></tr></thead>
                        <tbody>
                        @foreach ($movimientos as $m)
                            <tr>
                                <td>{{ $m->id_movimiento }}</td>
                                <td class="text-nowrap">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                                <td><span class="badge badge-phoenix badge-phoenix-success">{{ $m->tipo_movimiento }}</span></td>
                                <td>{{ $m->producto?->nombre }}</td>
                                <td class="text-end">+{{ $cantidad($m->cantidad) }}</td>
                                <td class="text-end">{{ $m->moneda }} {{ number_format((float) $m->costo_total, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
