@extends('layouts.app', ['titulo' => 'Centros de costo'])

@section('contenido')
    @php
        $usuario = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$usuario->puede('centros_costo.crear'), $usuario->puede('centros_costo.editar'), $usuario->puede('centros_costo.eliminar')];
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Centros de costo</h2>
            <p class="text-700 fw-semi-bold mb-0">Áreas a las que se cargan los gastos y el presupuesto.</p>
        </div>
        <div class="d-flex gap-2">
            @if ($usuario->puede('centros_costo.exportar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('centros-costo.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($puedeCrear)
                <a class="btn btn-primary" href="{{ route('centros-costo.create') }}"><span class="fas fa-plus me-2"></span>Nuevo centro</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-6"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Código o nombre" maxlength="100" /></div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Todos</option>
                        <option value="activos" @selected(($filtros['estado'] ?? '') === 'activos')>Activos</option>
                        <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('centros-costo.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead><tr><th>Código</th><th>Nombre</th><th class="text-end">Presupuestos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse ($centros as $c)
                        <tr>
                            <td><code>{{ $c->codigo }}</code></td>
                            <td>
                                <span class="fw-semi-bold">{{ $c->nombre }}</span>
                                @if ($c->descripcion && $c->descripcion !== $c->nombre)<p class="text-600 fs--2 mb-0 line-clamp-1">{{ $c->descripcion }}</p>@endif
                            </td>
                            <td class="text-end">{{ $c->presupuestos_count }}</td>
                            <td>
                                @if ($c->activo)
                                    <span class="badge badge-phoenix badge-phoenix-success">Activo</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-danger">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
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
                        <tr><td colspan="5" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún centro coincide con los filtros.' : 'No hay centros de costo.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
