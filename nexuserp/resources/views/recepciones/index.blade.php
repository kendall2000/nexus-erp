@extends('layouts.app', ['titulo' => 'Recepciones'])

@section('contenido')
    @php $yo = auth()->user(); @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Recepciones de mercadería</h2>
            <p class="text-700 fw-semi-bold mb-0">Lo recibido de las órdenes aprobadas entra al stock de la bodega y queda en el kardex.</p>
        </div>
        @if ($yo->puede('recepciones.crear'))
            <a class="btn btn-primary" href="{{ route('recepciones.create') }}"><span class="fas fa-plus me-2"></span>Nueva recepción</a>
        @endif
    </div>

    @if ($pendientes && $yo->puede('recepciones.crear'))
        <div class="alert alert-soft-info py-2 fs--1" role="alert">
            <span class="fas fa-truck-loading me-2"></span>{{ $pendientes === 1 ? 'Hay 1 orden aprobada' : "Hay {$pendientes} órdenes aprobadas" }} con mercadería por recibir.
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-5"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número de recepción u orden" maxlength="30" /></div>
                <div class="col-md-4">
                    <select class="form-select form-select-sm" name="bodega">
                        <option value="">Todas las bodegas</option>
                        @foreach ($bodegas as $b)<option value="{{ $b->id_bodega }}" @selected((int) ($filtros['bodega'] ?? 0) === $b->id_bodega)>{{ $b->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('recepciones.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Número</th><th>Fecha</th><th>Orden</th><th>Proveedor</th><th>Bodega</th><th class="text-end">Productos</th><th class="text-end">Valor</th></tr></thead>
                    <tbody>
                    @forelse ($recepciones as $r)
                        <tr>
                            <td class="text-nowrap"><a class="fw-semi-bold" href="{{ route('recepciones.show', $r->id_recepcion) }}">{{ $r->numero_recepcion }}</a></td>
                            <td class="text-nowrap">{{ $r->fecha_recepcion?->format('d/m/Y') }}</td>
                            <td class="text-nowrap">
                                @if ($r->ordenCompra && $yo->puede('ordenes_compra.ver'))
                                    <a href="{{ route('ordenes-compra.show', $r->id_oc) }}">{{ $r->ordenCompra->numero_oc }}</a>
                                @else
                                    {{ $r->ordenCompra?->numero_oc ?? '—' }}
                                @endif
                            </td>
                            <td>{{ $r->ordenCompra?->proveedor?->razon_social ?? '—' }}</td>
                            <td>{{ $r->bodega?->nombre ?? '—' }}</td>
                            <td class="text-end">{{ $r->detalles_count }}</td>
                            <td class="text-end text-nowrap fw-semi-bold">{{ $r->ordenCompra?->moneda }} {{ number_format((float) $r->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ninguna recepción coincide con los filtros.' : 'Todavía no hay recepciones.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $recepciones->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
