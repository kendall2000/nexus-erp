@extends('layouts.app', ['titulo' => 'Usuarios'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeEditar, $puedeEliminar] = [$yo->puede('usuarios.editar'), $yo->puede('usuarios.eliminar')];
    @endphp

    <x-page-header
        titulo="Usuarios"
        subtitulo="Cuentas de acceso al sistema, roles asignados y sesiones activas."
        icono="fas fa-user-shield"
        :breadcrumbs="['Sistema' => null, 'Usuarios' => null]"
    >
        @if ($yo->puede('usuarios.crear'))
            <a class="btn btn-primary" href="{{ route('usuarios.create') }}">
                <span class="fas fa-plus me-2"></span>Nuevo usuario
            </a>
        @endif
    </x-page-header>

    <div class="mb-4">
        <form method="GET" class="d-flex gap-2" style="max-width: 30rem">
            <div class="search-box flex-grow-1">
                <input class="form-control search-input search" name="buscar" value="{{ $buscar }}" placeholder="Nombre, usuario o correo" maxlength="150" />
                <span class="fas fa-search search-box-icon"></span>
            </div>
            <button class="btn btn-phoenix-secondary" type="submit">Buscar</button>
            @if ($buscar !== '')
                <a class="btn btn-phoenix-danger px-3" href="{{ route('usuarios.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
            @endif
        </form>
    </div>

    <x-phoenix-table :paginador="$usuarios">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Nombre</th>
                    <th class="text-uppercase text-700">Usuario</th>
                    <th class="text-uppercase text-700">Correo</th>
                    <th class="text-uppercase text-700">Rol</th>
                    <th class="text-uppercase text-700">Sucursal</th>
                    <th class="text-uppercase text-700">2 pasos</th>
                    <th class="text-uppercase text-700">Último acceso</th>
                    <th class="text-uppercase text-700">Estado</th>
                    <th class="pe-0 text-end text-uppercase text-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($usuarios as $u)
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-m me-2">
                                @if ($u->avatar_url)
                                    <img class="rounded-circle" src="{{ $u->avatar_url }}" alt="" />
                                @else
                                    <div class="avatar-name rounded-circle"><span>{{ mb_strtoupper(mb_substr($u->nombre_completo ?: $u->username, 0, 1)) }}</span></div>
                                @endif
                            </div>
                            <span class="fw-bold text-1000">{{ $u->nombre_completo }}</span>
                        </div>
                    </td>
                    <td class="fw-semi-bold text-900">{{ $u->username }}</td>
                    <td class="text-800">{{ $u->email }}</td>
                    <td class="text-800">{{ $u->roles->pluck('nombre')->join(', ') ?: '—' }}</td>
                    <td class="text-800">{{ $u->sucursal?->nombre ?? '—' }}</td>
                    <td>
                        @if ($u->two_factor_confirmed_at)
                            <span class="badge badge-phoenix badge-phoenix-success">Activa</span>
                        @else
                            <span class="badge badge-phoenix badge-phoenix-secondary">No</span>
                        @endif
                    </td>
                    <td class="text-nowrap text-800">{{ $u->ultimo_login?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                    <td>
                        @if ($u->activo)
                            <span class="badge badge-phoenix badge-phoenix-success">Activo</span>
                        @else
                            <span class="badge badge-phoenix badge-phoenix-danger">Inactivo</span>
                        @endif
                    </td>
                    <td class="pe-0 text-end text-nowrap">
                        @if ($puedeEditar && $yo->puedeGestionar($u))
                            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('usuarios.edit', $u->id_usuario) }}">Editar</a>
                            @if ($u->id_usuario !== auth()->id())
                                <form method="POST" action="{{ route('usuarios.estado', $u->id_usuario) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-phoenix-{{ $u->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $u->activo ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                                <form method="POST" action="{{ route('usuarios.sesiones', $u->id_usuario) }}" class="d-inline" onsubmit="return confirm(@js('¿Cerrar todas las sesiones de '.$u->username.'?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-phoenix-warning btn-sm" type="submit">Cerrar sesiones</button>
                                </form>
                            @endif
                        @endif
                        @if ($puedeEliminar && $u->id_usuario !== auth()->id() && $yo->puedeGestionar($u))
                            <form method="POST" action="{{ route('usuarios.destroy', $u->id_usuario) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar al usuario '.$u->username.'? Ya no podrá entrar al sistema.'))">
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
                        <span class="fas fa-user-shield fs-2 text-300 d-block mb-2"></span>
                        {{ $buscar !== '' ? 'Ningún usuario coincide con la búsqueda.' : 'No hay usuarios.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
