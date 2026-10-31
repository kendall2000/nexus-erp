@extends('layouts.app', ['titulo' => 'Recepciones'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <x-page-header
        titulo="Recepciones de mercadería"
        subtitulo="Lo recibido de las órdenes aprobadas entra al stock de la bodega y queda en el kardex."
        icono="fas fa-truck-loading"
        :breadcrumbs="['Inventario y Compras' => null, 'Recepciones' => null]"
    >
        @if ($yo->puede('recepciones.crear'))
            <a class="btn btn-primary" href="{{ route('recepciones.create') }}">
                <span class="fas fa-plus me-2"></span>Nueva recepción
            </a>
        @endif
    </x-page-header>

    @if ($pendientes && $yo->puede('recepciones.crear'))
        <div class="alert alert-soft-info py-2 fs--1 mb-4" role="alert">
            <span class="fas fa-truck-loading me-2"></span>{{ $pendientes === 1 ? 'Hay 1 orden aprobada' : "Hay {$pendientes} órdenes aprobadas" }} con mercadería por recibir.
        </div>
    @endif

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número de recepción u orden" maxlength="30" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="bodega">
                    <option value="">Todas las bodegas</option>
                    @foreach ($bodegas as $b)
                        <option value="{{ $b->id_bodega }}" @selected((int) ($filtros['bodega'] ?? 0) === $b->id_bodega)>{{ $b->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-phoenix-secondary w-100" type="submit">Filtrar</button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('recepciones.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$recepciones">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Número</th>
                    <th class="text-uppercase text-700">Fecha</th>
                    <th class="text-uppercase text-700">Orden</th>
                    <th class="text-uppercase text-700">Proveedor</th>
                    <th class="text-uppercase text-700">Bodega</th>
                    <th class="text-end text-uppercase text-700">Productos</th>
                    <th class="pe-0 text-end text-uppercase text-700">Valor</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($recepciones as $r)
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2 text-nowrap">
                        <a class="fw-bold text-1100 text-decoration-none" href="{{ route('recepciones.show', $r->id_recepcion) }}">{{ $r->numero_recepcion }}</a>
                    </td>
                    <td class="text-nowrap text-800">{{ $r->fecha_recepcion?->format('d/m/Y') }}</td>
                    <td class="text-nowrap">
                        @if ($r->ordenCompra && $yo->puede('ordenes_compra.ver'))
                            <a class="fw-semi-bold" href="{{ route('ordenes-compra.show', $r->id_oc) }}">{{ $r->ordenCompra->numero_oc }}</a>
                        @else
                            {{ $r->ordenCompra?->numero_oc ?? '—' }}
                        @endif
                    </td>
                    <td class="fw-semi-bold text-900">{{ $r->ordenCompra?->proveedor?->razon_social ?? '—' }}</td>
                    <td class="text-800">{{ $r->bodega?->nombre ?? '—' }}</td>
                    <td class="text-end text-800">{{ $r->detalles_count }}</td>
                    <td class="pe-0 text-end text-nowrap fw-bold text-1000">{{ $r->ordenCompra?->moneda }} {{ number_format((float) $r->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-700 py-5">
                        <span class="fas fa-truck-loading fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ninguna recepción coincide con los filtros.' : 'Todavía no hay recepciones.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
