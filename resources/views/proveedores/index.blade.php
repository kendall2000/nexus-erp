@extends('layouts.app', ['titulo' => 'Proveedores'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar, $puedeExportar] = [$yo->puede('proveedores.crear'), $yo->puede('proveedores.editar'), $yo->puede('proveedores.eliminar'), $yo->puede('proveedores.exportar')];
    @endphp

    <x-page-header
        titulo="Proveedores"
        :subtitulo="$proveedores->total().' '.($proveedores->total() === 1 ? 'proveedor' : 'proveedores').(array_filter($filtros) ? ' con los filtros aplicados' : '').'.'"
        icono="fas fa-truck"
        :breadcrumbs="['Inventario y Compras' => null, 'Proveedores' => null]"
    >
        @if ($puedeExportar)
            <a class="btn btn-phoenix-secondary" href="{{ route('proveedores.exportar', request()->query()) }}">
                <span class="fas fa-file-excel me-2"></span>Exportar
            </a>
        @endif
        @if ($puedeCrear)
            <a class="btn btn-primary" href="{{ route('proveedores.create') }}">
                <span class="fas fa-plus me-2"></span>Nuevo proveedor
            </a>
        @endif
    </x-page-header>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Razón social, nombre comercial o NIT" maxlength="100" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="tipo">
                    <option value="">Todos los tipos</option>
                    @foreach ($tipos as $codigo => $nombre)
                        <option value="{{ $codigo }}" @selected(($filtros['tipo'] ?? '') === $codigo)>{{ $nombre }}</option>
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
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('proveedores.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$proveedores">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Proveedor</th>
                    <th class="text-uppercase text-700">NIT</th>
                    <th class="text-uppercase text-700">Tipo</th>
                    <th class="text-uppercase text-700">Contacto</th>
                    <th class="text-uppercase text-700">País</th>
                    <th class="text-end text-uppercase text-700">Crédito</th>
                    <th class="text-uppercase text-700">Estado</th>
                    <th class="pe-0 text-end text-uppercase text-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($proveedores as $p)
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2">
                        <span class="fw-bold text-1000">{{ $p->razon_social }}</span>
                        @if ($p->nombre_comercial)<p class="text-600 fs--2 mb-0">{{ $p->nombre_comercial }}</p>@endif
                    </td>
                    <td class="text-800">{{ $p->nit ?: '—' }}</td>
                    <td class="text-800">{{ $tipos[$p->tipo_proveedor] ?? $p->tipo_proveedor }}</td>
                    <td class="text-800">
                        {{ $p->contacto ?: '' }}
                        @if ($p->email)<div class="text-600 fs--2">{{ $p->email }}</div>@endif
                        @if ($p->telefono)<div class="text-600 fs--2">{{ $p->telefono }}</div>@endif
                    </td>
                    <td class="text-800">{{ $p->pais?->nombre ?? '—' }}</td>
                    <td class="text-end text-nowrap text-800">{{ $p->dias_credito }} días · {{ $p->moneda_pago }}</td>
                    <td>
                        @if ($p->activo)
                            <span class="badge badge-phoenix badge-phoenix-success">Activo</span>
                        @else
                            <span class="badge badge-phoenix badge-phoenix-danger">Inactivo</span>
                        @endif
                        @if ($p->ordenes_abiertas)<span class="badge badge-phoenix badge-phoenix-info ms-1" title="Órdenes de compra abiertas">{{ $p->ordenes_abiertas }} OC</span>@endif
                    </td>
                    <td class="pe-0 text-end text-nowrap">
                        @if ($puedeEditar)
                            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('proveedores.edit', $p->id_proveedor) }}">Editar</a>
                            <form method="POST" action="{{ route('proveedores.estado', $p->id_proveedor) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-phoenix-{{ $p->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $p->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        @endif
                        @if ($puedeEliminar && ! $p->ordenes_abiertas)
                            <form method="POST" action="{{ route('proveedores.destroy', $p->id_proveedor) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar al proveedor '.$p->razon_social.'?'))">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link text-danger btn-sm px-1" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-700 py-5">
                        <span class="fas fa-truck fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ningún proveedor coincide con los filtros.' : 'No hay proveedores.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
