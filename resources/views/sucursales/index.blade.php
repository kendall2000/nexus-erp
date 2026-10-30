@extends('layouts.app', ['titulo' => 'Sucursales'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Sucursales</h2>
            <p class="text-700 fw-semi-bold mb-0">Sedes de la empresa. Los países, departamentos y municipios se administran en <a href="{{ route('geografia.index') }}">Geografía</a>.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('sucursales.create') }}"><span class="fas fa-plus me-2"></span>Nueva sucursal</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead><tr><th>Nombre</th><th>Ubicación</th><th>Contacto</th><th class="text-end">Usuarios</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse ($sucursales as $s)
                        <tr>
                            <td>
                                <span class="fw-semi-bold">{{ $s->nombre }}</span>
                                @if ($s->es_casa_matriz)<span class="badge badge-phoenix badge-phoenix-primary ms-1">Casa matriz</span>@endif
                                @if ($s->direccion)<p class="text-600 fs--2 mb-0 line-clamp-1">{{ $s->direccion }}</p>@endif
                            </td>
                            <td>{{ collect([$s->municipio?->nombre, $s->division?->nombre, $s->pais?->nombre])->filter()->join(', ') ?: '—' }}</td>
                            <td>
                                {{ $s->telefono ?: '' }}
                                @if ($s->email)<div class="text-600 fs--2">{{ $s->email }}</div>@endif
                                @if (! $s->telefono && ! $s->email)—@endif
                            </td>
                            <td class="text-end fw-bold text-700">{{ $s->usuarios_count }}</td>
                            <td>
                                @if ($s->activo)
                                    <span class="badge badge-phoenix badge-phoenix-success">Activa</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-danger">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('sucursales.edit', $s->id_sucursal) }}">Editar</a>
                                @unless ($s->es_casa_matriz)
                                    <form method="POST" action="{{ route('sucursales.estado', $s->id_sucursal) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-phoenix-{{ $s->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $s->activo ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('sucursales.destroy', $s->id_sucursal) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar la sucursal '.$s->nombre.'?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link text-danger btn-sm px-1" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-700 py-4">No hay sucursales.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
