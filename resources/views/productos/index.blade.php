@extends('layouts.app', ['titulo' => 'Productos'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar, $puedeExportar] = [$yo->puede('productos.crear'), $yo->puede('productos.editar'), $yo->puede('productos.eliminar'), $yo->puede('productos.exportar')];
    @endphp

    <x-page-header
        titulo="Productos"
        :subtitulo="$productos->total().' '.($productos->total() === 1 ? 'producto' : 'productos').(array_filter($filtros) ? ' con los filtros aplicados' : '').'.'"
        icono="fas fa-boxes-stacked"
        :breadcrumbs="['Inventario y Compras' => null, 'Productos' => null]"
    >
        @if ($puedeExportar)
            <a class="btn btn-phoenix-secondary" href="{{ route('productos.exportar', request()->query()) }}">
                <span class="fas fa-file-excel me-2"></span>Exportar
            </a>
        @endif
        @if ($puedeCrear)
            <a class="btn btn-primary" href="{{ route('productos.create') }}">
                <span class="fas fa-plus me-2"></span>Nuevo producto
            </a>
        @endif
    </x-page-header>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Código o nombre" maxlength="100" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="categoria">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $c)
                        <option value="{{ $c->id_categoria }}" @selected((int) ($filtros['categoria'] ?? 0) === $c->id_categoria)>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="estado">
                    <option value="">Todos</option>
                    <option value="activos" @selected(($filtros['estado'] ?? '') === 'activos')>Activos</option>
                    <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-phoenix-secondary w-100" type="submit">Filtrar</button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('productos.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$productos">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Código</th>
                    <th class="text-uppercase text-700">Producto</th>
                    <th class="text-uppercase text-700">Categoría</th>
                    <th class="text-uppercase text-700">Unidad</th>
                    <th class="text-end text-uppercase text-700">Precio compra</th>
                    <th class="text-end text-uppercase text-700">Precio venta</th>
                    <th class="text-end text-uppercase text-700">Existencia</th>
                    <th class="text-uppercase text-700">Estado</th>
                    <th class="pe-0 text-end text-uppercase text-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($productos as $p)
                @php $existencia = (float) $p->existencia; $bajo = $existencia <= (float) $p->stock_minimo; @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2 text-nowrap"><code>{{ $p->codigo }}</code></td>
                    <td class="fw-bold text-1000">{{ $p->nombre }}</td>
                    <td class="text-800">{{ $p->categoria?->nombre ?? '—' }}</td>
                    <td class="text-800">{{ $p->unidad_medida }}</td>
                    <td class="text-end text-900">{{ $p->precio_compra !== null ? number_format((float) $p->precio_compra, 2) : '—' }}</td>
                    <td class="text-end text-900">{{ $p->precio_venta !== null ? number_format((float) $p->precio_venta, 2) : '—' }}</td>
                    <td class="text-end">
                        <span class="{{ $bajo ? 'text-danger fw-bold' : 'text-900 fw-semi-bold' }}" title="Mínimo: {{ (float) $p->stock_minimo }}">{{ rtrim(rtrim(number_format($existencia, 2), '0'), '.') }}</span>
                        @if ($bajo)<span class="badge badge-phoenix badge-phoenix-warning ms-1">Reponer</span>@endif
                    </td>
                    <td>
                        @if ($p->activo)
                            <span class="badge badge-phoenix badge-phoenix-success">Activo</span>
                        @else
                            <span class="badge badge-phoenix badge-phoenix-danger">Inactivo</span>
                        @endif
                    </td>
                    <td class="pe-0 text-end text-nowrap">
                        @if ($puedeEditar)
                            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('productos.edit', $p->id_producto) }}">Editar</a>
                            <form method="POST" action="{{ route('productos.estado', $p->id_producto) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-phoenix-{{ $p->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $p->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        @endif
                        @if ($puedeEliminar)
                            <form method="POST" action="{{ route('productos.destroy', $p->id_producto) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar el producto '.$p->nombre.'?'))">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link text-danger btn-sm px-1" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-700 py-5">
                        <span class="fas fa-boxes-stacked fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ningún producto coincide con los filtros.' : 'No hay productos.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
