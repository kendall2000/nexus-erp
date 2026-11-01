@extends('layouts.app', ['titulo' => 'Productos'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar, $puedeExportar] = [
            $yo->puede('productos.crear'),
            $yo->puede('productos.editar'),
            $yo->puede('productos.eliminar'),
            $yo->puede('productos.exportar')
        ];

        // Preparar métricas estilo Phoenix
        $totalProductos = $productos->total();
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Inventario y Compras</li>
            <li class="breadcrumb-item active">Productos</li>
        </ol>
    </nav>

    <div class="mb-9">
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Productos</h2>
            </div>
        </div>

        {{-- Pestañas superiores de estado estilo Phoenix --}}
        <ul class="nav nav-links mb-3 mb-lg-2 mx-n3">
            <li class="nav-item">
                <a class="nav-link {{ empty($filtros['estado']) ? 'active' : '' }}" href="{{ route('productos.index', array_merge(request()->query(), ['estado' => ''])) }}">
                    <span>Todos </span>
                    <span class="text-700 fw-semi-bold">({{ $totalProductos }})</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'activos' ? 'active' : '' }}" href="{{ route('productos.index', array_merge(request()->query(), ['estado' => 'activos'])) }}">
                    <span>Activos </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($filtros['estado'] ?? '') === 'inactivos' ? 'active' : '' }}" href="{{ route('productos.index', array_merge(request()->query(), ['estado' => 'inactivos'])) }}">
                    <span>Inactivos </span>
                </a>
            </li>
        </ul>

        <div id="products">
            {{-- Toolbar idéntico a apps/e-commerce/admin/products.html --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('productos.index') }}" class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="search-box">
                        <div class="position-relative">
                            <input class="form-control search-input search" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Buscar por código o nombre..." aria-label="Search" />
                            <span class="fas fa-search search-box-icon"></span>
                        </div>
                    </div>

                    <div class="scrollbar overflow-hidden-y">
                        <div class="btn-group position-static" role="group">
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="categoria" onchange="this.form.submit()">
                                    <option value="">Todas las categorías</option>
                                    @foreach ($categorias as $c)
                                        <option value="{{ $c->id_categoria }}" @selected((int) ($filtros['categoria'] ?? 0) === $c->id_categoria)>{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="estado" onchange="this.form.submit()">
                                    <option value="">Estado: Todos</option>
                                    <option value="activos" @selected(($filtros['estado'] ?? '') === 'activos')>Activos</option>
                                    <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                                </select>
                            </div>
                            @if (array_filter($filtros))
                                <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('productos.index') }}" title="Limpiar filtros">
                                    <span class="fas fa-times me-1"></span>Limpiar
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="ms-xxl-auto d-flex gap-2">
                        @if ($puedeExportar)
                            <a class="btn btn-link text-900 me-2 px-0" href="{{ route('productos.exportar', request()->query()) }}">
                                <span class="fa-solid fa-file-export fs--1 me-2"></span>Exportar
                            </a>
                        @endif
                        @if ($puedeCrear)
                            <a class="btn btn-primary" id="addBtn" href="{{ route('productos.create') }}">
                                <span class="fas fa-plus me-2"></span>Agregar producto
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Tabla con estructura exacta de Phoenix apps/e-commerce/admin/products.html --}}
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="white-space-nowrap fs--1 align-middle ps-0" style="max-width:20px; width:18px;">
                                    <div class="form-check mb-0 fs-0">
                                        <input class="form-check-input" id="checkbox-bulk-products-select" type="checkbox" />
                                    </div>
                                </th>
                                <th class="sort white-space-nowrap align-middle ps-2" scope="col" style="width:70px;">IMAGEN</th>
                                <th class="sort white-space-nowrap align-middle ps-4" scope="col" style="min-width:280px;">NOMBRE DEL PRODUCTO</th>
                                <th class="sort white-space-nowrap align-middle ps-4" scope="col" style="width:120px;">CÓDIGO</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:140px;">PRECIO COMPRA</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:140px;">PRECIO VENTA</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:180px;">CATEGORÍA</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:130px;">EXISTENCIA</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:120px;">ESTADO</th>
                                <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:80px;">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody class="list" id="products-table-body">
                            @forelse ($productos as $p)
                                @php
                                    $existencia = (float) $p->existencia;
                                    $bajo = $existencia <= (float) $p->stock_minimo;
                                @endphp
                                <tr class="position-static">
                                    <td class="fs--1 align-middle ps-0">
                                        <div class="form-check mb-0 fs-0">
                                            <input class="form-check-input" type="checkbox" />
                                        </div>
                                    </td>
                                    <td class="align-middle white-space-nowrap py-0 ps-2">
                                        <div class="d-block border rounded-2 p-1 text-center bg-light" style="width:53px; height:53px;">
                                            <span class="fas fa-box text-400 fs-1 mt-2"></span>
                                        </div>
                                    </td>
                                    <td class="product align-middle ps-4">
                                        <a class="fw-semi-bold line-clamp-2 mb-0 text-1100" href="{{ $puedeEditar ? route('productos.edit', $p->id_producto) : '#!' }}">
                                            {{ $p->nombre }}
                                        </a>
                                        @if ($p->descripcion)
                                            <p class="fs--2 text-600 mb-0 line-clamp-1">{{ $p->descripcion }}</p>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        <span class="badge badge-phoenix fs--2 badge-phoenix-secondary">{{ $p->codigo }}</span>
                                    </td>
                                    <td class="price align-middle white-space-nowrap text-end text-700 ps-4">
                                        {{ $p->precio_compra !== null ? $moneda.' '.number_format((float) $p->precio_compra, 2) : '—' }}
                                    </td>
                                    <td class="price align-middle white-space-nowrap text-end fw-bold text-1000 ps-4">
                                        {{ $p->precio_venta !== null ? $moneda.' '.number_format((float) $p->precio_venta, 2) : '—' }}
                                    </td>
                                    <td class="category align-middle white-space-nowrap text-600 fs--1 ps-4 fw-semi-bold">
                                        <span class="badge badge-tag">{{ $p->categoria?->nombre ?? 'Sin categoría' }}</span>
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end ps-4">
                                        <span class="{{ $bajo ? 'text-danger fw-bold' : 'text-900 fw-semi-bold' }}">
                                            {{ rtrim(rtrim(number_format($existencia, 2), '0'), '.') }} {{ $p->unidad_medida }}
                                        </span>
                                        @if ($bajo)
                                            <div class="badge badge-phoenix badge-phoenix-warning fs--2 d-block mt-1">Reponer</div>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        @if ($p->activo)
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
                                                @if ($puedeEditar)
                                                    <a class="dropdown-item" href="{{ route('productos.edit', $p->id_producto) }}">
                                                        <span class="fas fa-edit me-2"></span>Editar
                                                    </a>
                                                    <form method="POST" action="{{ route('productos.estado', $p->id_producto) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="dropdown-item" type="submit">
                                                            <span class="fas {{ $p->activo ? 'fa-ban text-warning' : 'fa-check text-success' }} me-2"></span>
                                                            {{ $p->activo ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($puedeEliminar)
                                                    <div class="dropdown-divider"></div>
                                                    <form method="POST" action="{{ route('productos.destroy', $p->id_producto) }}" onsubmit="return confirm(@js('¿Eliminar el producto '.$p->nombre.'?'))">
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
                                    <td colspan="10" class="text-center text-700 py-5">
                                        <span class="fas fa-boxes-stacked fs-2 text-300 d-block mb-2"></span>
                                        {{ array_filter($filtros) ? 'Ningún producto coincide con los filtros aplicados.' : 'Aún no hay productos registrados.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer de paginación idéntico al de Phoenix --}}
                <div class="row align-items-center justify-content-between py-2 pe-0 fs--1 border-top border-200">
                    <div class="col-auto d-flex">
                        <p class="mb-0 d-none d-sm-block me-3 fw-semi-bold text-900">
                            Mostrando {{ $productos->firstItem() ?? 0 }} a {{ $productos->lastItem() ?? 0 }}
                            <span class="text-600">de</span> {{ $productos->total() }} productos
                        </p>
                    </div>
                    <div class="col-auto d-flex">
                        {{ $productos->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
