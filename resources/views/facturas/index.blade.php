@extends('layouts.app', ['titulo' => 'Facturas'])

@section('contenido')
    @php
        $yo = auth()->user();
        $tramos = \App\Http\Controllers\FacturaController::TRAMOS;
        $totalFacturas = $facturas->total();
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Finanzas</li>
            <li class="breadcrumb-item active">Facturas</li>
        </ol>
    </nav>

    <div class="mb-9">
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Facturación y Ventas</h2>
            </div>
        </div>

        {{-- Pestañas superiores de estado estilo Phoenix orders.html --}}
        <ul class="nav nav-links mb-3 mb-lg-2 mx-n3">
            <li class="nav-item">
                <a class="nav-link {{ empty($filtros['estado']) ? 'active' : '' }}" href="{{ route('facturas.index', array_merge(request()->query(), ['estado' => ''])) }}">
                    <span>Todas </span>
                    <span class="text-700 fw-semi-bold">({{ $totalFacturas }})</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'EMITIDA' ? 'active' : '' }}" href="{{ route('facturas.index', array_merge(request()->query(), ['estado' => 'EMITIDA'])) }}">
                    <span>Emitidas </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'PAGADA' ? 'active' : '' }}" href="{{ route('facturas.index', array_merge(request()->query(), ['estado' => 'PAGADA'])) }}">
                    <span>Pagadas </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'vencidas' ? 'active text-danger fw-bold' : 'text-danger' }}" href="{{ route('facturas.index', array_merge(request()->query(), ['estado' => 'vencidas'])) }}">
                    <span>Vencidas </span>
                </a>
            </li>
        </ul>

        @if ($cartera)
            {{-- Tarjeta de resumen de cartera envejecida estilo Phoenix --}}
            <div class="card mb-4 border border-200 shadow-none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 text-1100"><span class="fas fa-wallet text-primary me-2 fs-0"></span>Antigüedad de Saldos / Cartera por cobrar</h5>
                        @if (collect($cartera)->sum('vencido') > 0)
                            <a class="fs--1 fw-semi-bold text-danger" href="{{ route('facturas.index', ['estado' => 'vencidas']) }}">
                                <span class="fas fa-exclamation-circle me-1"></span>Ver facturas vencidas
                            </a>
                        @endif
                    </div>
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead class="bg-100">
                                <tr>
                                    <th class="ps-3 text-uppercase text-700">Moneda</th>
                                    @foreach ($tramos as [$nombre])
                                        <th class="text-end text-uppercase text-700">{{ $nombre }}</th>
                                    @endforeach
                                    <th class="pe-3 text-end text-uppercase text-700">Total en Cartera</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($cartera as $moneda => $c)
                                <tr>
                                    <td class="ps-3 fw-bold text-900">{{ $moneda }}</td>
                                    @foreach ($c['tramos'] as $i => $monto)
                                        <td class="text-end {{ $i > 0 && $monto > 0 ? ($i >= 3 ? 'text-danger fw-bold' : 'text-warning fw-semi-bold') : 'text-800' }}">
                                            {{ $monto > 0 ? number_format($monto, 2) : '—' }}
                                        </td>
                                    @endforeach
                                    <td class="pe-3 text-end fw-bold text-1100 fs-0">{{ number_format($c['total'], 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div id="orderTable">
            {{-- Toolbar idéntico a apps/e-commerce/admin/orders.html --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('facturas.index') }}" class="row g-3">
                    <div class="col-auto">
                        <div class="search-box">
                            <div class="position-relative">
                                <input class="form-control search-input search" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Buscar por número, cliente o NIT..." aria-label="Search" />
                                <span class="fas fa-search search-box-icon"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto scrollbar overflow-hidden-y flex-grow-1">
                        <div class="btn-group position-static" role="group">
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="cliente" onchange="this.form.submit()">
                                    <option value="">Todos los clientes</option>
                                    @foreach ($clientes as $c)
                                        <option value="{{ $c->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="estado" onchange="this.form.submit()">
                                    <option value="">Todos los estados</option>
                                    <option value="vencidas" @selected(($filtros['estado'] ?? '') === 'vencidas')>Vencidas con saldo</option>
                                    @foreach ($estados as $codigo => [$nombre])
                                        <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input class="form-control btn-phoenix-secondary px-3 flex-shrink-0" style="width: 140px;" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Emitidas desde" onchange="this.form.submit()" />
                            <input class="form-control btn-phoenix-secondary px-3 flex-shrink-0" style="width: 140px;" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Emitidas hasta" onchange="this.form.submit()" />
                            @if (array_filter($filtros))
                                <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('facturas.index') }}" title="Limpiar filtros">
                                    <span class="fas fa-times me-1"></span>Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        @if ($yo->puede('facturas.exportar'))
                            <a class="btn btn-link text-900 me-2 px-0" href="{{ route('facturas.exportar', request()->query()) }}">
                                <span class="fa-solid fa-file-export fs--1 me-2"></span>Exportar
                            </a>
                        @endif
                        @if ($yo->puede('facturas.crear'))
                            <a class="btn btn-primary" href="{{ route('facturas.create') }}">
                                <span class="fas fa-plus me-2"></span>Nueva factura
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
                                <th class="sort white-space-nowrap align-middle ps-3" scope="col" style="width:140px;">FACTURA</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:300px;">CLIENTE</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:130px;">EMISIÓN</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:130px;">VENCIMIENTO</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:140px;">TOTAL</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:140px;">SALDO PENDIENTE</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:150px;">ESTADO</th>
                                <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody class="list" id="order-table-body">
                            @forelse ($facturas as $f)
                                @php
                                    [$nombreEstado, $color] = $estados[$f->estado] ?? [$f->estado, 'secondary'];
                                    $vencida = in_array($f->estado, \App\Models\Clientes\Cliente::ESTADOS_CON_SALDO, true) && (float) $f->saldo_pendiente > 0 && $f->fecha_vencimiento?->lt(today());
                                @endphp
                                <tr class="hover-actions-trigger btn-reveal-trigger position-static {{ $f->estado === 'ANULADA' ? 'text-500' : '' }}">
                                    <td class="fs--1 align-middle ps-0">
                                        <div class="form-check mb-0 fs-0">
                                            <input class="form-check-input" type="checkbox" />
                                        </div>
                                    </td>
                                    <td class="order align-middle white-space-nowrap ps-3 py-3">
                                        <a class="fw-bold text-primary" href="{{ route('facturas.show', $f->id_factura) }}">
                                            #{{ $f->numero_completo }}
                                        </a>
                                        @if ($f->tipo !== 'FACTURA')
                                            <span class="d-block fs--2 text-600">{{ $tipos[$f->tipo] ?? $f->tipo }}</span>
                                        @endif
                                    </td>
                                    <td class="customer align-middle white-space-nowrap ps-4">
                                        <h6 class="mb-0 text-1000 fw-semi-bold">{{ $f->cliente?->razon_social ?? '—' }}</h6>
                                        @if ($f->cliente?->nit)
                                            <span class="fs--2 text-600">NIT: {{ $f->cliente->nit }}</span>
                                        @endif
                                    </td>
                                    <td class="date align-middle white-space-nowrap text-700 ps-4">
                                        {{ $f->fecha_emision?->format('d/m/Y') }}
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 {{ $vencida ? 'text-danger fw-bold' : 'text-700' }}">
                                        {{ $f->fecha_vencimiento?->format('d/m/Y') }}
                                        @if ($vencida)
                                            <span class="fas fa-clock ms-1 text-danger" title="Vencida"></span>
                                        @endif
                                    </td>
                                    <td class="total align-middle text-end white-space-nowrap fw-bold text-1000 ps-4">
                                        {{ $f->moneda }} {{ number_format((float) $f->total, 2) }}
                                    </td>
                                    <td class="align-middle text-end white-space-nowrap fw-bold ps-4 {{ (float) $f->saldo_pendiente > 0 ? 'text-danger' : 'text-700' }}">
                                        {{ (float) $f->saldo_pendiente > 0 && $f->estado !== 'BORRADOR' ? $f->moneda.' '.number_format((float) $f->saldo_pendiente, 2) : '—' }}
                                    </td>
                                    <td class="payment_status align-middle white-space-nowrap ps-4">
                                        <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                                        @if ($vencida)
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-danger ms-1">Vencida</span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                        <div class="font-sans-serif btn-reveal-trigger position-static">
                                            <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                                <span class="fas fa-ellipsis-h fs--2"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end py-2">
                                                <a class="dropdown-item" href="{{ route('facturas.show', $f->id_factura) }}">
                                                    <span class="fas fa-eye me-2"></span>Ver detalle
                                                </a>
                                                @if ($yo->puede('facturas.imprimir'))
                                                    <a class="dropdown-item" href="{{ route('facturas.pdf', $f->id_factura) }}" target="_blank">
                                                        <span class="fas fa-file-pdf me-2"></span>Imprimir PDF
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-700 py-5">
                                        <span class="fas fa-file-invoice-dollar fs-2 text-300 d-block mb-2"></span>
                                        {{ array_filter($filtros) ? 'Ninguna factura coincide con los filtros aplicados.' : 'Aún no hay facturas registradas.' }}
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
                            Mostrando {{ $facturas->firstItem() ?? 0 }} a {{ $facturas->lastItem() ?? 0 }}
                            <span class="text-600">de</span> {{ $facturas->total() }} facturas
                        </p>
                    </div>
                    <div class="col-auto d-flex">
                        {{ $facturas->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
