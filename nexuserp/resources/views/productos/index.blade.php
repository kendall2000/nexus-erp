@extends('layouts.app', ['titulo' => 'Productos'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar, $puedeExportar] = [$yo->puede('productos.crear'), $yo->puede('productos.editar'), $yo->puede('productos.eliminar'), $yo->puede('productos.exportar')];
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Productos</h2>
            <p class="text-700 fw-semi-bold mb-0">{{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}{{ array_filter($filtros) ? ' con los filtros aplicados' : '' }}.</p>
        </div>
        <div class="d-flex gap-2">
            @if ($puedeExportar)
                <a class="btn btn-phoenix-secondary" href="{{ route('productos.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($puedeCrear)
                <a class="btn btn-primary" href="{{ route('productos.create') }}"><span class="fas fa-plus me-2"></span>Nuevo producto</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-5"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Código o nombre" maxlength="100" /></div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="categoria">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $c)<option value="{{ $c->id_categoria }}" @selected((int) ($filtros['categoria'] ?? 0) === $c->id_categoria)>{{ $c->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Todos</option>
                        <option value="activos" @selected(($filtros['estado'] ?? '') === 'activos')>Activos</option>
                        <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('productos.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Unidad</th><th class="text-end">Precio compra</th><th class="text-end">Precio venta</th><th class="text-end">Existencia</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse ($productos as $p)
                        @php $existencia = (float) $p->existencia; $bajo = $existencia <= (float) $p->stock_minimo; @endphp
                        <tr>
                            <td class="text-nowrap"><code>{{ $p->codigo }}</code></td>
                            <td class="fw-semi-bold">{{ $p->nombre }}</td>
                            <td>{{ $p->categoria?->nombre ?? '—' }}</td>
                            <td>{{ $p->unidad_medida }}</td>
                            <td class="text-end">{{ $p->precio_compra !== null ? number_format((float) $p->precio_compra, 2) : '—' }}</td>
                            <td class="text-end">{{ $p->precio_venta !== null ? number_format((float) $p->precio_venta, 2) : '—' }}</td>
                            <td class="text-end">
                                <span class="{{ $bajo ? 'text-danger fw-bold' : '' }}" title="Mínimo: {{ (float) $p->stock_minimo }}">{{ rtrim(rtrim(number_format($existencia, 2), '0'), '.') }}</span>
                                @if ($bajo)<span class="badge badge-phoenix badge-phoenix-warning ms-1">Reponer</span>@endif
                            </td>
                            <td>
                                @if ($p->activo)
                                    <span class="badge badge-phoenix badge-phoenix-success">Activo</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-danger">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
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
                        <tr><td colspan="9" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún producto coincide con los filtros.' : 'No hay productos.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $productos->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
