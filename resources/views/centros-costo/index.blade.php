@extends('layouts.app', ['titulo' => 'Centros de costo'])

@section('contenido')
    @php
        $usuario = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$usuario->puede('centros_costo.crear'), $usuario->puede('centros_costo.editar'), $usuario->puede('centros_costo.eliminar')];
    @endphp

    <x-page-header
        titulo="Centros de costo"
        subtitulo="Áreas a las que se cargan los gastos y el presupuesto."
        icono="fas fa-Sitemap"
        :breadcrumbs="['Finanzas' => null, 'Centros de costo' => null]"
    >
        @if ($usuario->puede('centros_costo.exportar'))
            <a class="btn btn-phoenix-secondary" href="{{ route('centros-costo.exportar', request()->query()) }}">
                <span class="fas fa-file-excel me-2"></span>Exportar
            </a>
        @endif
        @if ($puedeCrear)
            <a class="btn btn-primary" href="{{ route('centros-costo.create') }}">
                <span class="fas fa-plus me-2"></span>Nuevo centro
            </a>
        @endif
    </x-page-header>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Código o nombre" maxlength="100" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="estado">
                    <option value="">Todos</option>
                    <option value="activos" @selected(($filtros['estado'] ?? '') === 'activos')>Activos</option>
                    <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-phoenix-secondary w-100" type="submit">Filtrar</button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('centros-costo.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table>
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Código</th>
                    <th class="text-uppercase text-700">Nombre</th>
                    <th class="text-end text-uppercase text-700">Presupuestos</th>
                    <th class="text-uppercase text-700">Estado</th>
                    <th class="pe-0 text-end text-uppercase text-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($centros as $c)
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2"><code>{{ $c->codigo }}</code></td>
                    <td>
                        <span class="fw-bold text-1000">{{ $c->nombre }}</span>
                        @if ($c->descripcion && $c->descripcion !== $c->nombre)<p class="text-600 fs--2 mb-0 line-clamp-1">{{ $c->descripcion }}</p>@endif
                    </td>
                    <td class="text-end text-900">{{ $c->presupuestos_count }}</td>
                    <td>
                        @if ($c->activo)
                            <span class="badge badge-phoenix badge-phoenix-success">Activo</span>
                        @else
                            <span class="badge badge-phoenix badge-phoenix-danger">Inactivo</span>
                        @endif
                    </td>
                    <td class="pe-0 text-end text-nowrap">
                        @if ($puedeEditar)
                            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('centros-costo.edit', $c->id_centro) }}">Editar</a>
                            <form method="POST" action="{{ route('centros-costo.estado', $c->id_centro) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-phoenix-{{ $c->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $c->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        @endif
                        @if ($puedeEliminar)
                            <form method="POST" action="{{ route('centros-costo.destroy', $c->id_centro) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar el centro de costo '.$c->etiqueta.'?'))">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link text-danger btn-sm px-1" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-700 py-5">{{ array_filter($filtros) ? 'Ningún centro coincide con los filtros.' : 'No hay centros de costo.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
