@extends('layouts.app', ['titulo' => 'Órdenes de compra'])

@section('contenido')
    @php
        $yo = auth()->user();
        $totalOrdenes = $ordenes->total();
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Inventario y Compras</li>
            <li class="breadcrumb-item active">Órdenes de Compra</li>
        </ol>
    </nav>

    <div class="mb-9">
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Órdenes de Compra</h2>
            </div>
        </div>

        {{-- Pestañas superiores de estado estilo Phoenix orders.html --}}
        <ul class="nav nav-links mb-3 mb-lg-2 mx-n3">
            <li class="nav-item">
                <a class="nav-link {{ empty($filtros['estado']) ? 'active' : '' }}" href="{{ route('ordenes-compra.index', array_merge(request()->query(), ['estado' => ''])) }}">
                    <span>Todas </span>
                    <span class="text-700 fw-semi-bold">({{ $totalOrdenes }})</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'BORRADOR' ? 'active' : '' }}" href="{{ route('ordenes-compra.index', array_merge(request()->query(), ['estado' => 'BORRADOR'])) }}">
                    <span>Borradores </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'APROBADA' ? 'active' : '' }}" href="{{ route('ordenes-compra.index', array_merge(request()->query(), ['estado' => 'APROBADA'])) }}">
                    <span>Aprobadas </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'RECIBIDA' ? 'active' : '' }}" href="{{ route('ordenes-compra.index', array_merge(request()->query(), ['estado' => 'RECIBIDA'])) }}">
                    <span>Recibidas </span>
                </a>
            </li>
        </ul>

        <div id="purchaseOrdersTable">
            {{-- Toolbar idéntico a apps/e-commerce/admin/orders.html --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('ordenes-compra.index') }}" class="row g-3">
                    <div class="col-auto">
                        <div class="search-box">
                            <div class="position-relative">
                                <input class="form-control search-input search" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Buscar por número de orden..." aria-label="Search" />
                                <span class="fas fa-search search-box-icon"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto scrollbar overflow-hidden-y flex-grow-1">
                        <div class="btn-group position-static" role="group">
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="proveedor" onchange="this.form.submit()">
                                    <option value="">Todos los proveedores</option>
                                    @foreach ($proveedores as $p)
                                        <option value="{{ $p->id_proveedor }}" @selected((int) ($filtros['proveedor'] ?? 0) === $p->id_proveedor)>{{ $p->razon_social }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="estado" onchange="this.form.submit()">
                                    <option value="">Todos los estados</option>
                                    @foreach ($estados as $codigo => [$nombre])
                                        <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if (array_filter($filtros))
                                <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('ordenes-compra.index') }}" title="Limpiar filtros">
                                    <span class="fas fa-times me-1"></span>Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        @if ($yo->puede('ordenes_compra.exportar'))
                            <a class="btn btn-link text-900 me-2 px-0" href="{{ route('ordenes-compra.exportar', request()->query()) }}">
                                <span class="fa-solid fa-file-export fs--1 me-2"></span>Exportar
                            </a>
                        @endif
                        @if ($yo->puede('ordenes_compra.crear'))
                            <a class="btn btn-primary" href="{{ route('ordenes-compra.create') }}">
                                <span class="fas fa-plus me-2"></span>Nueva orden
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Tabla con estructura visual exacta de Phoenix orders.html --}}
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="white-space-nowrap fs--1 align-middle ps-0" style="max-width:20px; width:18px;">
                                    <div class="form-check mb-0 fs-0">
                                        <input class="form-check-input" id="checkbox-bulk-orders-select" type="checkbox" />
                                    </div>
                                </th>
                                <th class="sort white-space-nowrap align-middle ps-3" scope="col" style="width:140px;">ORDEN</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:300px;">PROVEEDOR</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:160px;">BODEGA DESTINO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:130px;">EMISIÓN</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:130px;">ENTREGA</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:140px;">TOTAL</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:160px;">RECEPCIÓN</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:130px;">ESTADO</th>
                                <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody class="list" id="purchase-orders-table-body">
                            @forelse ($ordenes as $oc)
                                @php
                                    $avance = (float) $oc->pedido > 0 ? round((float) $oc->recibido / (float) $oc->pedido * 100) : 0;
                                    [$nombreEstado, $color] = $estados[$oc->estado] ?? [$oc->estado, 'secondary'];
                                    $atrasada = in_array($oc->estado, ['ENVIADA', 'PARCIAL'], true) && $oc->fecha_entrega_esperada?->isPast();
                                @endphp
                                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                                    <td class="fs--1 align-middle ps-0">
                                        <div class="form-check mb-0 fs-0">
                                            <input class="form-check-input" type="checkbox" />
                                        </div>
                                    </td>
                                    <td class="order align-middle white-space-nowrap ps-3 py-3">
                                        <a class="fw-bold text-primary" href="{{ route('ordenes-compra.show', $oc->id_oc) }}">
                                            #{{ $oc->numero_oc }}
                                        </a>
                                    </td>
                                    <td class="customer align-middle white-space-nowrap ps-4">
                                        <h6 class="mb-0 text-1000 fw-semi-bold">{{ $oc->proveedor?->razon_social ?? '—' }}</h6>
                                        @if ($oc->proveedor?->nit)
                                            <span class="fs--2 text-600">NIT: {{ $oc->proveedor->nit }}</span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 text-800">
                                        <span class="badge badge-phoenix badge-phoenix-secondary">{{ $oc->bodega?->nombre ?? '—' }}</span>
                                    </td>
                                    <td class="date align-middle white-space-nowrap text-700 ps-4">
                                        {{ $oc->fecha_emision?->format('d/m/Y') }}
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 {{ $atrasada ? 'text-danger fw-bold' : 'text-700' }}">
                                        {{ $oc->fecha_entrega_esperada?->format('d/m/Y') ?? '—' }}
                                        @if ($atrasada)
                                            <span class="badge badge-phoenix badge-phoenix-danger ms-1">Atrasada</span>
                                        @endif
                                    </td>
                                    <td class="total align-middle text-end white-space-nowrap fw-bold text-1000 ps-4">
                                        {{ $oc->moneda }} {{ number_format((float) $oc->total, 2) }}
                                    </td>
                                    <td class="align-middle ps-4" style="min-width: 140px;">
                                        @if (!in_array($oc->estado, ['BORRADOR', 'CANCELADA'], true))
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-2" style="height: 6px;">
                                                    <div class="progress-bar bg-success" style="width: {{ $avance }}%"></div>
                                                </div>
                                                <span class="fs--2 text-700 fw-semi-bold">{{ $avance }}%</span>
                                            </div>
                                        @else
                                            <span class="text-400">—</span>
                                        @endif
                                    </td>
                                    <td class="payment_status align-middle white-space-nowrap ps-4">
                                        <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                        <div class="font-sans-serif btn-reveal-trigger position-static">
                                            <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                                <span class="fas fa-ellipsis-h fs--2"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end py-2">
                                                <a class="dropdown-item" href="{{ route('ordenes-compra.show', $oc->id_oc) }}">
                                                    <span class="fas fa-eye me-2"></span>Ver detalle
                                                </a>
                                                @if ($oc->estado === 'BORRADOR' && $yo->puede('ordenes_compra.editar'))
                                                    <a class="dropdown-item" href="{{ route('ordenes-compra.edit', $oc->id_oc) }}">
                                                        <span class="fas fa-pen me-2"></span>Editar orden
                                                    </a>
                                                @endif
                                                @if ($yo->puede('ordenes_compra.imprimir'))
                                                    <a class="dropdown-item" href="{{ route('ordenes-compra.imprimir', $oc->id_oc) }}" target="_blank">
                                                        <span class="fas fa-print me-2"></span>Imprimir
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('ordenes-compra.pdf', $oc->id_oc) }}">
                                                        <span class="fas fa-file-pdf me-2"></span>Descargar PDF
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-700 py-5">
                                        <span class="fas fa-shopping-cart fs-2 text-300 d-block mb-2"></span>
                                        {{ array_filter($filtros) ? 'Ninguna orden coincide con los filtros aplicados.' : 'Aún no hay órdenes de compra registradas.' }}
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
                            Mostrando {{ $ordenes->firstItem() ?? 0 }} a {{ $ordenes->lastItem() ?? 0 }}
                            <span class="text-600">de</span> {{ $ordenes->total() }} órdenes
                        </p>
                    </div>
                    <div class="col-auto d-flex">
                        {{ $ordenes->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
