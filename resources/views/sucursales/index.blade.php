@extends('layouts.app', ['titulo' => 'Sucursales'])

@section('contenido')
    <x-page-header
        titulo="Sucursales"
        icono="fas fa-building"
        :breadcrumbs="['Sistema' => null, 'Sucursales' => null]"
    >
        <a class="btn btn-phoenix-secondary" href="{{ route('geografia.index') }}">
            <span class="fas fa-globe-americas me-2"></span>Geografía
        </a>
        <a class="btn btn-primary" href="{{ route('sucursales.create') }}">
            <span class="fas fa-plus me-2"></span>Nueva sucursal
        </a>
    </x-page-header>

    <p class="text-700 fw-semi-bold mb-4">Sedes de la empresa. Los países, departamentos y municipios se administran en <a href="{{ route('geografia.index') }}">Geografía</a>.</p>

    <x-phoenix-table>
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Nombre</th>
                    <th class="text-uppercase text-700">Ubicación</th>
                    <th class="text-uppercase text-700">Contacto</th>
                    <th class="text-end text-uppercase text-700">Usuarios</th>
                    <th class="text-uppercase text-700">Estado</th>
                    <th class="pe-0 text-end text-uppercase text-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($sucursales as $s)
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2">
                        <span class="fw-bold text-1000">{{ $s->nombre }}</span>
                        @if ($s->es_casa_matriz)<span class="badge badge-phoenix badge-phoenix-primary ms-1">Casa matriz</span>@endif
                        @if ($s->direccion)<p class="text-600 fs--2 mb-0 line-clamp-1">{{ $s->direccion }}</p>@endif
                    </td>
                    <td class="text-800">{{ collect([$s->municipio?->nombre, $s->division?->nombre, $s->pais?->nombre])->filter()->join(', ') ?: '—' }}</td>
                    <td class="text-800">
                        {{ $s->telefono ?: '' }}
                        @if ($s->email)<div class="text-600 fs--2">{{ $s->email }}</div>@endif
                        @if (! $s->telefono && ! $s->email)—@endif
                    </td>
                    <td class="text-end fw-bold text-900">{{ $s->usuarios_count }}</td>
                    <td>
                        @if ($s->activo)
                            <span class="badge badge-phoenix badge-phoenix-success">Activa</span>
                        @else
                            <span class="badge badge-phoenix badge-phoenix-danger">Inactiva</span>
                        @endif
                    </td>
                    <td class="pe-0 text-end text-nowrap">
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
                <tr><td colspan="6" class="text-center text-700 py-5">No hay sucursales.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
