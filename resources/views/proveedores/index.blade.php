@extends('layouts.app', ['titulo' => 'Proveedores'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar, $puedeExportar] = [
            $yo->puede('proveedores.crear'),
            $yo->puede('proveedores.editar'),
            $yo->puede('proveedores.eliminar'),
            $yo->puede('proveedores.exportar')
        ];
        $totalProveedores = $proveedores->total();
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Inventario y Compras</li>
            <li class="breadcrumb-item active">Proveedores</li>
        </ol>
    </nav>

    <div class="mb-9">
        <div class="row g-2 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Proveedores</h2>
            </div>
        </div>

        {{-- Pestañas superiores de estado estilo Phoenix customers.html --}}
        <ul class="nav nav-links mb-3 mb-lg-2 mx-n3">
            <li class="nav-item">
                <a class="nav-link {{ empty($filtros['estado']) ? 'active' : '' }}" href="{{ route('proveedores.index', array_merge(request()->query(), ['estado' => ''])) }}">
                    <span>Todos </span>
                    <span class="text-700 fw-semi-bold">({{ $totalProveedores }})</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'activos' ? 'active' : '' }}" href="{{ route('proveedores.index', array_merge(request()->query(), ['estado' => 'activos'])) }}">
                    <span>Activos </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'inactivos' ? 'active' : '' }}" href="{{ route('proveedores.index', array_merge(request()->query(), ['estado' => 'inactivos'])) }}">
                    <span>Inactivos </span>
                </a>
            </li>
        </ul>

        <div id="suppliers">
            {{-- Toolbar idéntico a Phoenix customers.html --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('proveedores.index') }}" class="row g-3">
                    <div class="col-auto">
                        <div class="search-box">
                            <div class="position-relative">
                                <input class="form-control search-input search" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Buscar por razón social, nombre o NIT..." aria-label="Search" />
                                <span class="fas fa-search search-box-icon"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto scrollbar overflow-hidden-y flex-grow-1">
                        <div class="btn-group position-static" role="group">
                            <div class="btn-group position-static text-nowrap">
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="tipo" onchange="this.form.submit()">
                                    <option value="">Todos los tipos</option>
                                    @foreach ($tipos as $codigo => $nombre)
                                        <option value="{{ $codigo }}" @selected(($filtros['tipo'] ?? '') === $codigo)>{{ $nombre }}</option>
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
                                <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('proveedores.index') }}" title="Limpiar filtros">
                                    <span class="fas fa-times me-1"></span>Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        @if ($puedeExportar)
                            <a class="btn btn-link text-900 me-2 px-0" href="{{ route('proveedores.exportar', request()->query()) }}">
                                <span class="fa-solid fa-file-export fs--1 me-2"></span>Exportar
                            </a>
                        @endif
                        @if ($puedeCrear)
                            <a class="btn btn-primary" href="{{ route('proveedores.create') }}">
                                <span class="fas fa-plus me-2"></span>Agregar proveedor
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
                                        <input class="form-check-input" id="checkbox-bulk-suppliers-select" type="checkbox" />
                                    </div>
                                </th>
                                <th class="sort align-middle ps-3" scope="col" style="width:350px;">PROVEEDOR</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:140px;">NIT</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:180px;">TIPO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:240px;">CONTACTO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:140px;">PAÍS</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:160px;">COND. CRÉDITO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:140px;">ESTADO</th>
                                <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody class="list" id="suppliers-table-body">
                            @forelse ($proveedores as $p)
                                @php
                                    $iniciales = mb_strtoupper(mb_substr($p->razon_social, 0, 2));
                                @endphp
                                <tr class="hover-actions-trigger btn-reveal-trigger position-static {{ $p->activo ? '' : 'text-500' }}">
                                    <td class="fs--1 align-middle ps-0">
                                        <div class="form-check mb-0 fs-0">
                                            <input class="form-check-input" type="checkbox" />
                                        </div>
                                    </td>
                                    <td class="customer align-middle white-space-nowrap ps-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-m me-3">
                                                <div class="avatar-name rounded-circle {{ $p->activo ? 'bg-primary-100 text-primary-600' : 'bg-200 text-600' }}">
                                                    <span>{{ $iniciales }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-1100 fw-bold">{{ $p->razon_social }}</h6>
                                                @if ($p->nombre_comercial && $p->nombre_comercial !== $p->razon_social)
                                                    <p class="fs--2 mb-0 text-600">{{ $p->nombre_comercial }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        <span class="badge badge-phoenix fs--2 badge-phoenix-secondary">{{ $p->nit ?: '—' }}</span>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 text-800">
                                        <span class="badge badge-tag">{{ $tipos[$p->tipo_proveedor] ?? $p->tipo_proveedor }}</span>
                                    </td>
                                    <td class="email align-middle white-space-nowrap ps-4 fs--1">
                                        @if ($p->contacto)
                                            <span class="fw-semi-bold text-900 d-block">{{ $p->contacto }}</span>
                                        @endif
                                        @if ($p->email)
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="far fa-envelope text-600 me-2"></span>
                                                <a class="text-700" href="mailto:{{ $p->email }}">{{ $p->email }}</a>
                                            </div>
                                        @endif
                                        @if ($p->telefono)
                                            <div class="d-flex align-items-center">
                                                <span class="fas fa-phone-alt text-600 me-2"></span>
                                                <span class="text-700">{{ $p->telefono }}</span>
                                            </div>
                                        @endif
                                        @if (!$p->contacto && !$p->email && !$p->telefono)
                                            <span class="text-400">—</span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 text-800">
                                        {{ $p->pais?->nombre ?? '—' }}
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end ps-4 fs--1">
                                        <span class="fw-semi-bold text-900">{{ $p->dias_credito }} días</span>
                                        <p class="fs--2 mb-0 text-600">{{ $p->moneda_pago }}</p>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        @if ($p->activo)
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-success"><span class="badge-label">Activo</span><span class="ms-1 fas fa-check" style="height:10.8px;width:10.8px;"></span></span>
                                        @else
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-danger"><span class="badge-label">Inactivo</span><span class="ms-1 fas fa-times" style="height:10.8px;width:10.8px;"></span></span>
                                        @endif
                                        @if ($p->ordenes_abiertas)
                                            <div class="badge badge-phoenix badge-phoenix-info fs--2 mt-1 d-block">{{ $p->ordenes_abiertas }} OC abiertas</div>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                        <div class="font-sans-serif btn-reveal-trigger position-static">
                                            <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                                <span class="fas fa-ellipsis-h fs--2"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end py-2">
                                                @if ($puedeEditar)
                                                    <a class="dropdown-item" href="{{ route('proveedores.edit', $p->id_proveedor) }}">
                                                        <span class="fas fa-edit me-2"></span>Editar
                                                    </a>
                                                    <form method="POST" action="{{ route('proveedores.estado', $p->id_proveedor) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="dropdown-item" type="submit">
                                                            <span class="fas {{ $p->activo ? 'fa-ban text-warning' : 'fa-check text-success' }} me-2"></span>
                                                            {{ $p->activo ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($puedeEliminar && !$p->ordenes_abiertas)
                                                    <div class="dropdown-divider"></div>
                                                    <form method="POST" action="{{ route('proveedores.destroy', $p->id_proveedor) }}" onsubmit="return confirm(@js('¿Eliminar al proveedor '.$p->razon_social.'?'))">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit">
                                                            <span class="fas fa-trash me-2"></span>Eliminar
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-700 py-5">
                                        <span class="fas fa-truck fs-2 text-300 d-block mb-2"></span>
                                        {{ array_filter($filtros) ? 'Ningún proveedor coincide con los filtros aplicados.' : 'Aún no hay proveedores registrados.' }}
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
                            Mostrando {{ $proveedores->firstItem() ?? 0 }} a {{ $proveedores->lastItem() ?? 0 }}
                            <span class="text-600">de</span> {{ $proveedores->total() }} proveedores
                        </p>
                    </div>
                    <div class="col-auto d-flex">
                        {{ $proveedores->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
