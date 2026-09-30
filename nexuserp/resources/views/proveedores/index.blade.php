@extends('layouts.app', ['titulo' => 'Proveedores'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar, $puedeExportar] = [$yo->puede('proveedores.crear'), $yo->puede('proveedores.editar'), $yo->puede('proveedores.eliminar'), $yo->puede('proveedores.exportar')];
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Proveedores</h2>
            <p class="text-700 fw-semi-bold mb-0">{{ $proveedores->total() }} {{ $proveedores->total() === 1 ? 'proveedor' : 'proveedores' }}{{ array_filter($filtros) ? ' con los filtros aplicados' : '' }}.</p>
        </div>
        <div class="d-flex gap-2">
            @if ($puedeExportar)
                <a class="btn btn-phoenix-secondary" href="{{ route('proveedores.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($puedeCrear)
                <a class="btn btn-primary" href="{{ route('proveedores.create') }}"><span class="fas fa-plus me-2"></span>Nuevo proveedor</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-5"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Razón social, nombre comercial o NIT" maxlength="100" /></div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="tipo">
                        <option value="">Todos los tipos</option>
                        @foreach ($tipos as $codigo => $nombre)<option value="{{ $codigo }}" @selected(($filtros['tipo'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
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
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('proveedores.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Proveedor</th><th>NIT</th><th>Tipo</th><th>Contacto</th><th>País</th><th class="text-end">Crédito</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse ($proveedores as $p)
                        <tr>
                            <td>
                                <span class="fw-semi-bold">{{ $p->razon_social }}</span>
                                @if ($p->nombre_comercial)<p class="text-600 fs--2 mb-0">{{ $p->nombre_comercial }}</p>@endif
                            </td>
                            <td>{{ $p->nit ?: '—' }}</td>
                            <td>{{ $tipos[$p->tipo_proveedor] ?? $p->tipo_proveedor }}</td>
                            <td>
                                {{ $p->contacto ?: '' }}
                                @if ($p->email)<div class="text-600 fs--2">{{ $p->email }}</div>@endif
                                @if ($p->telefono)<div class="text-600 fs--2">{{ $p->telefono }}</div>@endif
                            </td>
                            <td>{{ $p->pais?->nombre ?? '—' }}</td>
                            <td class="text-end text-nowrap">{{ $p->dias_credito }} días · {{ $p->moneda_pago }}</td>
                            <td>
                                @if ($p->activo)
                                    <span class="badge badge-phoenix badge-phoenix-success">Activo</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-danger">Inactivo</span>
                                @endif
                                @if ($p->ordenes_abiertas)<span class="badge badge-phoenix badge-phoenix-info ms-1" title="Órdenes de compra abiertas">{{ $p->ordenes_abiertas }} OC</span>@endif
                            </td>
                            <td class="text-end text-nowrap">
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
                        <tr><td colspan="8" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún proveedor coincide con los filtros.' : 'No hay proveedores.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $proveedores->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
