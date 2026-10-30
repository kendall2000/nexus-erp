@extends('layouts.app', ['titulo' => 'Líneas de negocio'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$yo->puede('lineas_negocio.crear'), $yo->puede('lineas_negocio.editar'), $yo->puede('lineas_negocio.eliminar')];
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Líneas de negocio</h2>
            <p class="text-700 fw-semi-bold mb-0">Agrupan los tipos de servicio que se venden en facturas y contratos.</p>
        </div>
        @if ($puedeCrear)
            <a class="btn btn-primary" href="{{ route('lineas-negocio.create') }}"><span class="fas fa-plus me-2"></span>Nueva línea</a>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead><tr><th>Línea</th><th class="text-end">Servicios</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse ($lineas as $l)
                        <tr class="{{ $l->activo ? '' : 'text-500' }}">
                            <td>
                                <span class="fw-semi-bold">{{ $l->nombre }}</span>
                                @if ($l->descripcion)<p class="text-600 fs--2 mb-0 line-clamp-1">{{ $l->descripcion }}</p>@endif
                            </td>
                            <td class="text-end">
                                @if ($yo->puede('tipos_servicio.ver'))<a href="{{ route('tipos-servicio.index', ['linea' => $l->id_linea]) }}">{{ $l->tipos_servicio_count }}</a>@else{{ $l->tipos_servicio_count }}@endif
                            </td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $l->activo ? 'success' : 'danger' }}">{{ $l->activo ? 'Activa' : 'Inactiva' }}</span></td>
                            <td class="text-end text-nowrap">
                                @if ($puedeEditar)
                                    <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('lineas-negocio.edit', $l->id_linea) }}">Editar</a>
                                    <form method="POST" action="{{ route('lineas-negocio.estado', $l->id_linea) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-phoenix-{{ $l->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $l->activo ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                @endif
                                @if ($puedeEliminar && ! $l->tipos_servicio_count)
                                    <form method="POST" action="{{ route('lineas-negocio.destroy', $l->id_linea) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar la línea '.$l->nombre.'?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link text-danger btn-sm px-1" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-700 py-4">Todavía no hay líneas de negocio.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
