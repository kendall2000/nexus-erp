@extends('layouts.app', ['titulo' => 'Recepciones de Mercadería'])

@section('contenido')
    @php
        $yo = auth()->user();
        $totalRecepciones = $recepciones->total();
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Inventario y Compras</li>
            <li class="breadcrumb-item active">Recepciones</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Encabezado idéntico a Phoenix --}}
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-12 col-md-auto">
                <h2 class="mb-1 text-1100">Recepciones de Mercadería en Bodega</h2>
                <h5 class="text-700 fw-semi-bold mb-0">Ingreso físico de productos desde órdenes de compra al stock valorado</h5>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                @if ($yo->puede('recepciones.crear'))
                    <a class="btn btn-primary" href="{{ route('recepciones.create') }}">
                        <span class="fas fa-plus me-2"></span>Registrar nueva recepción
                    </a>
                @endif
            </div>
        </div>

        @if ($pendientes && $yo->puede('recepciones.crear'))
            <div class="alert alert-subtle-info border-0 py-3 mb-4 d-flex align-items-center justify-content-between" role="alert">
                <div class="d-flex align-items-center">
                    <span class="fas fa-truck-loading fs-2 text-info me-3"></span>
                    <div>
                        <h6 class="mb-0 text-1100 fw-bold">{{ $pendientes === 1 ? '1 orden de compra aprobada' : "{$pendientes} órdenes de compra aprobadas" }} con mercadería por recibir</h6>
                        <span class="fs--2 text-700">Puedes generar la entrada física de inventario para alimentar el Kardex.</span>
                    </div>
                </div>
                <a class="btn btn-phoenix-primary btn-sm" href="{{ route('ordenes-compra.index', ['estado' => 'APROBADA']) }}">
                    Ver órdenes aprobadas
                </a>
            </div>
        @endif

        <div id="recepcionesTable">
            {{-- Toolbar idéntico a Phoenix --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('recepciones.index') }}" class="row g-3">
                    <div class="col-auto">
                        <div class="search-box">
                            <div class="position-relative">
                                <input class="form-control search-input search" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número de recepción u orden..." aria-label="Search" />
                                <span class="fas fa-search search-box-icon"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto scrollbar overflow-hidden-y flex-grow-1">
                        <div class="btn-group position-static" role="group">
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="bodega" onchange="this.form.submit()">
                                    <option value="">Todas las bodegas de entrega</option>
                                    @foreach ($bodegas as $b)
                                        <option value="{{ $b->id_bodega }}" @selected((int) ($filtros['bodega'] ?? 0) === $b->id_bodega)>{{ $b->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if (array_filter($filtros))
                                <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('recepciones.index') }}" title="Limpiar filtros">
                                    <span class="fas fa-times me-1"></span>Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabla con estructura visual exacta de Phoenix --}}
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="white-space-nowrap fs--1 align-middle ps-0" style="max-width:20px; width:18px;">
                                    <div class="form-check mb-0 fs-0">
                                        <input class="form-check-input" id="checkbox-bulk-recepciones-select" type="checkbox" />
                                    </div>
                                </th>
                                <th class="sort white-space-nowrap align-middle ps-3" scope="col" style="width:140px;">RECEPCIÓN</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:130px;">FECHA INGRESO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:140px;">ORDEN COMPRA</th>
                                <th class="sort align-middle ps-4" scope="col" style="min-width:260px;">PROVEEDOR</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:180px;">BODEGA DESTINO</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:120px;">LÍNEAS</th>
                                <th class="sort align-middle text-end ps-4 pe-4" scope="col" style="width:160px;">VALOR INGRESADO</th>
                            </tr>
                        </thead>
                        <tbody class="list" id="recepciones-table-body">
                            @forelse ($recepciones as $r)
                                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                                    <td class="fs--1 align-middle ps-0">
                                        <div class="form-check mb-0 fs-0">
                                            <input class="form-check-input" type="checkbox" />
                                        </div>
                                    </td>
                                    <td class="order align-middle white-space-nowrap ps-3 py-3">
                                        <a class="fw-bold text-primary" href="{{ route('recepciones.show', $r->id_recepcion) }}">
                                            #{{ $r->numero_recepcion }}
                                        </a>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 text-700">
                                        {{ $r->fecha_recepcion?->format('d/m/Y') }}
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        @if ($r->ordenCompra && $yo->puede('ordenes_compra.ver'))
                                            <a class="badge badge-phoenix badge-phoenix-primary" href="{{ route('ordenes-compra.show', $r->id_oc) }}">
                                                #{{ $r->ordenCompra->numero_oc }}
                                            </a>
                                        @else
                                            <span class="badge badge-phoenix badge-phoenix-secondary">{{ $r->ordenCompra?->numero_oc ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="customer align-middle white-space-nowrap ps-4">
                                        <h6 class="mb-0 text-1000 fw-semi-bold">{{ $r->ordenCompra?->proveedor?->razon_social ?? '—' }}</h6>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 text-800">
                                        <span class="badge badge-tag">{{ $r->bodega?->nombre ?? '—' }}</span>
                                    </td>
                                    <td class="align-middle text-end ps-4 text-900 fw-semi-bold">
                                        {{ $r->detalles_count }} productos
                                    </td>
                                    <td class="total align-middle text-end ps-4 pe-4 fw-bold text-1000">
                                        {{ $r->ordenCompra?->moneda }} {{ number_format((float) $r->total, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-700 py-5">
                                        <span class="fas fa-truck-loading fs-2 text-300 d-block mb-2"></span>
                                        {{ array_filter($filtros) ? 'Ninguna recepción coincide con los filtros aplicados.' : 'Aún no hay recepciones registradas.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer de paginación --}}
                <div class="row align-items-center justify-content-between py-2 pe-0 fs--1 border-top border-200">
                    <div class="col-auto d-flex">
                        <p class="mb-0 d-none d-sm-block me-3 fw-semi-bold text-900">
                            Mostrando {{ $recepciones->firstItem() ?? 0 }} a {{ $recepciones->lastItem() ?? 0 }}
                            <span class="text-600">de</span> {{ $recepciones->total() }} recepciones
                        </p>
                    </div>
                    <div class="col-auto d-flex">
                        {{ $recepciones->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
