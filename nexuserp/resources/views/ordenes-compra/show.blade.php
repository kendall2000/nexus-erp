@extends('layouts.app', ['titulo' => 'Orden '.$oc->numero_oc])

@section('contenido')
    @php
        $yo = auth()->user();
        [$nombreEstado, $color] = $estados[$oc->estado] ?? [$oc->estado, 'secondary'];
        $recibido = $oc->detalles->sum(fn ($d) => (float) $d->cantidad_recibida) > 0;
        $cancelable = in_array($oc->estado, ['BORRADOR', 'ENVIADA'], true) && ! $recibido;
        $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 4), '0'), '.');
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('ordenes-compra.index') }}">Órdenes de compra</a></li>
            <li class="breadcrumb-item active">{{ $oc->numero_oc }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Orden {{ $oc->numero_oc }} <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1 align-middle">{{ $nombreEstado }}</span></h2>
            <p class="text-700 mb-0">{{ $oc->proveedor?->razon_social }} · emitida el {{ $oc->fecha_emision?->format('d/m/Y') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($yo->puede('ordenes_compra.imprimir'))
                <a class="btn btn-phoenix-secondary" href="{{ route('ordenes-compra.imprimir', $oc->id_oc) }}" target="_blank" rel="noopener"><span class="fas fa-print me-2"></span>Imprimir</a>
            @endif
            @if ($oc->estado === 'BORRADOR' && $yo->puede('ordenes_compra.editar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('ordenes-compra.edit', $oc->id_oc) }}"><span class="fas fa-pen me-2"></span>Editar</a>
            @endif
            @if ($oc->estado === 'BORRADOR' && $yo->puede('ordenes_compra.aprobar'))
                <form method="POST" action="{{ route('ordenes-compra.aprobar', $oc->id_oc) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-success" type="submit"><span class="fas fa-check me-2"></span>Aprobar</button>
                </form>
            @endif
            @if ($cancelable && $yo->puede('ordenes_compra.cancelar'))
                <form method="POST" action="{{ route('ordenes-compra.cancelar', $oc->id_oc) }}" onsubmit="return confirm(@js('¿Cancelar la orden '.$oc->numero_oc.'?'.($oc->estado === 'ENVIADA' ? ' Se revertirá del presupuesto.' : '')))">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-phoenix-danger" type="submit">Cancelar orden</button>
                </form>
            @endif
            @if ($oc->estado === 'BORRADOR' && $yo->puede('ordenes_compra.editar'))
                <form method="POST" action="{{ route('ordenes-compra.destroy', $oc->id_oc) }}" onsubmit="return confirm(@js('¿Eliminar el borrador '.$oc->numero_oc.'?'))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link text-danger" type="submit" title="Eliminar borrador"><span class="fas fa-trash"></span></button>
                </form>
            @endif
        </div>
    </div>

    @if ($sobregiros)
        <div class="alert alert-soft-warning" role="alert">
            <h5 class="alert-heading mb-2"><span class="fas fa-exclamation-triangle me-2"></span>La orden excede el saldo del presupuesto</h5>
            <table class="table table-sm fs--1 mb-3">
                <thead><tr><th>Partida (centro / cuenta)</th><th class="text-end">Requerido</th><th class="text-end">Disponible</th><th class="text-end">Sobregiro</th></tr></thead>
                <tbody>
                @foreach ($sobregiros as $s)
                    <tr><td>{{ $s['partida'] }}</td><td class="text-end">{{ number_format($s['requerido'], 2) }}</td><td class="text-end">{{ number_format($s['disponible'], 2) }}</td><td class="text-end text-danger fw-bold">{{ number_format($s['sobregiro'], 2) }}</td></tr>
                @endforeach
                </tbody>
            </table>
            <form method="POST" action="{{ route('ordenes-compra.aprobar', $oc->id_oc) }}" class="d-flex gap-2 align-items-center">
                @csrf
                @method('PATCH')
                <input type="hidden" name="forzar" value="1" />
                <button class="btn btn-warning btn-sm" type="submit">Aprobar de todos modos</button>
                <span class="fs--1">o edita la orden para ajustarla.</span>
            </form>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th>Producto</th><th class="text-end">Pedido</th><th class="text-end">Recibido</th><th class="text-end">Precio</th><th class="text-end">Descuento</th><th class="text-end">Importe</th><th>Centro / cuenta</th></tr></thead>
                            <tbody>
                            @foreach ($oc->detalles as $d)
                                <tr>
                                    <td>
                                        <span class="fw-semi-bold">{{ $d->producto?->nombre ?? 'Producto #'.$d->id_producto }}</span>
                                        <span class="text-600 fs--2 d-block"><code>{{ $d->producto?->codigo }}</code> {{ $d->descripcion }}</span>
                                    </td>
                                    <td class="text-end">{{ $cantidad($d->cantidad_pedida) }} {{ $d->producto?->unidad_medida }}</td>
                                    <td class="text-end {{ (float) $d->cantidad_recibida >= (float) $d->cantidad_pedida ? 'text-success' : '' }}">{{ $cantidad($d->cantidad_recibida) }}</td>
                                    <td class="text-end">{{ number_format((float) $d->precio_unitario, 2) }}</td>
                                    <td class="text-end">{{ (float) $d->descuento ? number_format((float) $d->descuento, 2) : '—' }}</td>
                                    <td class="text-end fw-semi-bold">{{ number_format((float) $d->subtotal, 2) }}</td>
                                    <td class="fs--2 text-700">
                                        {{ $d->centroCosto?->nombre ?? ($d->producto?->centroDefault?->nombre ? $d->producto->centroDefault->nombre.' (producto)' : '—') }}<br>
                                        {{ $d->cuentaContable?->nombre ?? ($d->producto?->cuentaGasto?->nombre ? $d->producto->cuentaGasto->nombre.' (producto)' : '—') }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot class="fw-semi-bold">
                            <tr><td colspan="5" class="text-end">Subtotal</td><td class="text-end">{{ number_format((float) $oc->subtotal, 2) }}</td><td></td></tr>
                            <tr><td colspan="5" class="text-end">IVA</td><td class="text-end">{{ number_format((float) $oc->iva, 2) }}</td><td></td></tr>
                            <tr class="fs-0"><td colspan="5" class="text-end">Total</td><td class="text-end">{{ $oc->moneda }} {{ number_format((float) $oc->total, 2) }}</td><td></td></tr>
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
                        <dt class="col-5 text-700">Proveedor</dt><dd class="col-7">{{ $oc->proveedor?->razon_social ?? '—' }}<br><span class="text-600">{{ $oc->proveedor?->nit }}</span></dd>
                        <dt class="col-5 text-700">Bodega</dt><dd class="col-7">{{ $oc->bodega?->nombre ?? 'Sin definir' }}</dd>
                        <dt class="col-5 text-700">Entrega esperada</dt><dd class="col-7">{{ $oc->fecha_entrega_esperada?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-5 text-700">Creada por</dt><dd class="col-7">{{ $oc->creadoPor?->nombre_completo ?? '—' }}</dd>
                        <dt class="col-5 text-700">Aprobada por</dt><dd class="col-7">{{ $oc->aprobadoPor?->nombre_completo ?? '—' }}</dd>
                        @if ($oc->notas)<dt class="col-5 text-700">Notas</dt><dd class="col-7">{{ $oc->notas }}</dd>@endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
