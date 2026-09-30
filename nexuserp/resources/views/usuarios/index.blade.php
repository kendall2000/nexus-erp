@extends('layouts.app', ['titulo' => 'Usuarios'])

@section('contenido')
    @php $puedeEditar = auth()->user()->puede('CONFIG.USUARIOS.EDITAR'); @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="mb-0 text-1100">Usuarios</h2>
        @if (auth()->user()->puede('CONFIG.USUARIOS.CREAR'))
            <a class="btn btn-primary" href="{{ route('usuarios.create') }}"><span class="fas fa-plus me-2"></span>Nuevo usuario</a>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2 mb-3" style="max-width: 28rem">
                <input class="form-control form-control-sm" name="buscar" value="{{ $buscar }}" placeholder="Nombre, usuario o correo" maxlength="150" />
                <button class="btn btn-phoenix-secondary btn-sm" type="submit">Buscar</button>
                @if ($buscar !== '')
                    <a class="btn btn-link btn-sm" href="{{ route('usuarios.index') }}">Limpiar</a>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Sucursal</th><th>2 pasos</th><th>Último acceso</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse ($usuarios as $u)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-m me-2">
                                        @if ($u->avatar_url)
                                            <img class="rounded-circle" src="{{ $u->avatar_url }}" alt="" />
                                        @else
                                            <div class="avatar-name rounded-circle"><span>{{ mb_strtoupper(mb_substr($u->nombre_completo ?: $u->username, 0, 1)) }}</span></div>
                                        @endif
                                    </div>
                                    {{ $u->nombre_completo }}
                                </div>
                            </td>
                            <td>{{ $u->username }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->roles->pluck('nombre')->join(', ') ?: '—' }}</td>
                            <td>{{ $u->sucursal?->nombre ?? '—' }}</td>
                            <td>
                                @if ($u->two_factor_confirmed_at)
                                    <span class="badge badge-phoenix badge-phoenix-success">Activa</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-secondary">No</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $u->ultimo_login?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                            <td>
                                @if ($u->activo)
                                    <span class="badge badge-phoenix badge-phoenix-success">Activo</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-danger">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($puedeEditar)
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
                                        <form method="POST" action="{{ route('usuarios.destroy', $u->id_usuario) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar al usuario '.$u->username.'? Ya no podrá entrar al sistema.'))">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-link text-danger btn-sm px-1" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-700 py-4">{{ $buscar !== '' ? 'Ningún usuario coincide con la búsqueda.' : 'No hay usuarios.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $usuarios->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
