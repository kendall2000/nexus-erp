@extends('layouts.app', ['titulo' => 'Clientes'])

@section('contenido')
    @php
        $yo = auth()->user();
        $totalClientes = $clientes->total();
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Comercial</li>
            <li class="breadcrumb-item active">Clientes</li>
        </ol>
    </nav>

    <div class="mb-9">
        <div class="row g-2 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Clientes</h2>
            </div>
        </div>

        {{-- Pestañas superiores de estado estilo Phoenix customers.html --}}
        <ul class="nav nav-links mb-3 mb-lg-2 mx-n3">
            <li class="nav-item">
                <a class="nav-link {{ empty($filtros['estado']) ? 'active' : '' }}" href="{{ route('clientes.index', array_merge(request()->query(), ['estado' => ''])) }}">
                    <span>Todos </span>
                    <span class="text-700 fw-semi-bold">({{ $totalClientes }})</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'activos' ? 'active' : '' }}" href="{{ route('clientes.index', array_merge(request()->query(), ['estado' => 'activos'])) }}">
                    <span>Activos </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'inactivos' ? 'active' : '' }}" href="{{ route('clientes.index', array_merge(request()->query(), ['estado' => 'inactivos'])) }}">
                    <span>Inactivos </span>
                </a>
            </li>
        </ul>

        <div id="customers">
            {{-- Toolbar idéntico a apps/e-commerce/admin/customers.html --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('clientes.index') }}" class="row g-3">
                    <div class="col-auto">
                        <div class="search-box">
                            <div class="position-relative">
                                <input class="form-control search-input search" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Buscar por nombre, NIT o correo..." aria-label="Search" />
                                <span class="fas fa-search search-box-icon"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto scrollbar overflow-hidden-y flex-grow-1">
                        <div class="btn-group position-static" role="group">
                            <div class="btn-group position-static text-nowrap">
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="segmento" onchange="this.form.submit()">
                                    <option value="">Todos los segmentos</option>
                                    @foreach ($segmentos as $codigo => $nombre)
                                        <option value="{{ $codigo }}" @selected(($filtros['segmento'] ?? '') === $codigo)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="btn-group position-static text-nowrap">
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="categoria" onchange="this.form.submit()">
                                    <option value="">Todas las categorías</option>
                                    @foreach ($categorias as $cat)
                                        <option value="{{ $cat }}" @selected(($filtros['categoria'] ?? '') === $cat)>Categoría {{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="btn-group position-static text-nowrap">
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="estado" onchange="this.form.submit()">
                                    <option value="">Estado: Todos</option>
                                    <option value="activos" @selected(($filtros['estado'] ?? '') === 'activos')>Activos</option>
                                    <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                                </select>
                            </div>
                            @if (array_filter($filtros))
                                <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('clientes.index') }}" title="Limpiar filtros">
                                    <span class="fas fa-times me-1"></span>Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        @if ($yo->puede('clientes.exportar'))
                            <a class="btn btn-link text-900 me-2 px-0" href="{{ route('clientes.exportar', request()->query()) }}">
                                <span class="fa-solid fa-file-export fs--1 me-2"></span>Exportar
                            </a>
                        @endif
                        @if ($yo->puede('clientes.crear'))
                            <a class="btn btn-primary" href="{{ route('clientes.create') }}">
                                <span class="fas fa-plus me-2"></span>Agregar cliente
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Tabla con estructura visual exacta de Phoenix customers.html --}}
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar-overlay mx-n1 px-1">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="white-space-nowrap fs--1 align-middle ps-0" style="max-width:20px; width:18px;">
                                    <div class="form-check mb-0 fs-0">
                                        <input class="form-check-input" id="checkbox-bulk-customers-select" type="checkbox" />
                                    </div>
                                </th>
                                <th class="sort align-middle ps-3" scope="col" style="width:350px;">CLIENTE</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:140px;">NIT</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:220px;">CONTACTO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:160px;">SEGMENTO</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:160px;">COND. CRÉDITO</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:160px;">SALDO POR COBRAR</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:120px;">ESTADO</th>
                                <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody class="list" id="customers-table-body">
                            @forelse ($clientes as $c)
                                @php
                                    $saldo = (float) $c->saldo;
                                    $excede = $c->limite_credito !== null && $saldo > (float) $c->limite_credito;
                                    $iniciales = mb_strtoupper(mb_substr($c->razon_social, 0, 2));
                                @endphp
                                <tr class="hover-actions-trigger btn-reveal-trigger position-static {{ $c->activo ? '' : 'text-500' }}">
                                    <td class="fs--1 align-middle ps-0">
                                        <div class="form-check mb-0 fs-0">
                                            <input class="form-check-input" type="checkbox" />
                                        </div>
                                    </td>
                                    <td class="customer align-middle white-space-nowrap ps-3">
                                        <a class="d-flex align-items-center text-900 text-hover-1000" href="{{ route('clientes.show', $c->id_cliente) }}">
                                            <div class="avatar avatar-m me-3">
                                                <div class="avatar-name rounded-circle {{ $c->activo ? 'bg-primary-100 text-primary-600' : 'bg-200 text-600' }}">
                                                    <span>{{ $iniciales }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-1100 fw-bold">{{ $c->razon_social }}</h6>
                                                @if ($c->nombre_comercial && $c->nombre_comercial !== $c->razon_social)
                                                    <p class="fs--2 mb-0 text-600">{{ $c->nombre_comercial }}</p>
                                                @endif
                                            </div>
                                        </a>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        <span class="badge badge-phoenix fs--2 badge-phoenix-secondary">{{ $c->nit ?? 'C/F' }}</span>
                                    </td>
                                    <td class="email align-middle white-space-nowrap ps-4 fs--1">
                                        @if ($c->email_principal)
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="far fa-envelope text-600 me-2"></span>
                                                <a class="text-700" href="mailto:{{ $c->email_principal }}">{{ $c->email_principal }}</a>
                                            </div>
                                        @endif
                                        @if ($c->telefono_principal)
                                            <div class="d-flex align-items-center">
                                                <span class="fas fa-phone-alt text-600 me-2"></span>
                                                <span class="text-700">{{ $c->telefono_principal }}</span>
                                            </div>
                                        @endif
                                        @if (!$c->email_principal && !$c->telefono_principal)
                                            <span class="text-400">—</span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        <span class="fw-semi-bold text-800">{{ $segmentos[$c->segmento] ?? '—' }}</span>
                                        @if ($c->categoria)
                                            <span class="badge badge-phoenix badge-phoenix-info ms-1">Cat. {{ $c->categoria }}</span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end ps-4 fs--1">
                                        <span class="fw-semi-bold text-900">{{ $c->dias_credito }} días</span>
                                        <p class="fs--2 mb-0 text-600">{{ $c->limite_credito !== null ? $c->moneda_facturacion.' '.number_format((float) $c->limite_credito, 2) : 'Sin límite' }}</p>
                                    </td>
                                    <td class="total-spent align-middle white-space-nowrap fw-bold text-end ps-4 {{ $excede ? 'text-danger' : 'text-1100' }}">
                                        {{ $saldo > 0 ? $c->moneda_facturacion.' '.number_format($saldo, 2) : '—' }}
                                        @if ($excede)
                                            <span class="fas fa-exclamation-circle text-danger ms-1" title="Supera el límite de crédito"></span>
                                            <span class="d-none">Supera el límite de crédito</span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        @if ($c->activo)
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-success"><span class="badge-label">Activo</span><span class="ms-1 fas fa-check" style="height:10.8px;width:10.8px;"></span></span>
                                        @else
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-danger"><span class="badge-label">Inactivo</span><span class="ms-1 fas fa-times" style="height:10.8px;width:10.8px;"></span></span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                        <div class="font-sans-serif btn-reveal-trigger position-static">
                                            <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                                <span class="fas fa-ellipsis-h fs--2"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end py-2">
                                                <a class="dropdown-item" href="{{ route('clientes.show', $c->id_cliente) }}">
                                                    <span class="fas fa-eye me-2"></span>Detalles
                                                </a>
                                                @if ($yo->puede('clientes.editar'))
                                                    <a class="dropdown-item" href="{{ route('clientes.edit', $c->id_cliente) }}">
                                                        <span class="fas fa-edit me-2"></span>Editar
                                                    </a>
                                                @endif
                                                @if ($yo->puede('facturas.crear'))
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item" href="{{ route('facturas.create', ['id_cliente' => $c->id_cliente]) }}">
                                                        <span class="fas fa-file-invoice-dollar me-2"></span>Facturar
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-700 py-5">
                                        <span class="fas fa-users fs-2 text-300 d-block mb-2"></span>
                                        {{ array_filter($filtros) ? 'Ningún cliente coincide con los filtros aplicados.' : 'Aún no hay clientes registrados.' }}
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
                            Mostrando {{ $clientes->firstItem() ?? 0 }} a {{ $clientes->lastItem() ?? 0 }}
                            <span class="text-600">de</span> {{ $clientes->total() }} clientes
                        </p>
                    </div>
                    <div class="col-auto d-flex">
                        {{ $clientes->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
