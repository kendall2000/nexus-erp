@extends('layouts.app', ['titulo' => $usuario->exists ? 'Editar usuario' : 'Nuevo usuario'])

@section('contenido')
    <h2 class="mb-4 text-1100">{{ $usuario->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h2>

    <div class="card" style="max-width: 44rem">
        <div class="card-body">
            <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario->id_usuario) : route('usuarios.store') }}" enctype="multipart/form-data">
                @csrf
                @if ($usuario->exists)
                    @method('PUT')
                @endif
                <div class="mb-3">
                    <label class="form-label" for="nombre_completo">Nombre completo</label>
                    <input class="form-control" id="nombre_completo" name="nombre_completo" value="{{ old('nombre_completo', $usuario->nombre_completo) }}" required maxlength="200" />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-5">
                        <label class="form-label" for="username">Usuario</label>
                        <input class="form-control" id="username" name="username" value="{{ old('username', $usuario->username) }}" required maxlength="60" autocomplete="off" />
                        <div class="form-text">Sin espacios. Sirve para iniciar sesión.</div>
                    </div>
                    <div class="col-sm-7">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required maxlength="150" />
                        <div class="form-text">También sirve para iniciar sesión y recuperar la contraseña.</div>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="id_rol">Rol</label>
                        <select class="form-select" id="id_rol" name="id_rol" required>
                            <option value="">Selecciona…</option>
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->id_rol }}" @selected((int) old('id_rol', $idRol) === $rol->id_rol)>{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="id_sucursal">Sucursal</label>
                        <select class="form-select" id="id_sucursal" name="id_sucursal">
                            <option value="">Sin sucursal</option>
                            @foreach ($sucursales as $s)
                                <option value="{{ $s->id_sucursal }}" @selected((int) old('id_sucursal', $usuario->id_sucursal) === $s->id_sucursal)>{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="foto">Foto de perfil</label>
                    <div class="d-flex align-items-center gap-3">
                        @if ($usuario->avatar_url)
                            <div class="avatar avatar-3xl flex-shrink-0"><img class="rounded-circle" src="{{ $usuario->avatar_url }}" alt="" /></div>
                        @endif
                        <div class="flex-1">
                            <input class="form-control" id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp" />
                            <div class="form-text">JPG, PNG o WEBP de hasta 2 MB.</div>
                            @if ($usuario->avatar_url)
                                <div class="form-check mt-1 mb-0">
                                    <input class="form-check-input" id="quitar_foto" name="quitar_foto" type="checkbox" value="1" />
                                    <label class="form-check-label fs--1" for="quitar_foto">Quitar la foto actual</label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">{{ $usuario->exists ? 'Nueva contraseña (dejar vacío para no cambiarla)' : 'Contraseña' }}</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" @required(! $usuario->exists) />
                    <div class="form-text">
                        {{ \App\Support\Contrasenas::requisitos() }}
                        @if ($usuario->exists && $usuario->id_usuario !== auth()->id())
                            Si la cambias, se cierran sus sesiones abiertas.
                        @endif
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
                </div>
                @if ($usuario->id_usuario !== auth()->id())
                    <div class="form-check mb-4">
                        <input type="hidden" name="debe_cambiar_password" value="0" />
                        <input class="form-check-input" id="debe_cambiar_password" name="debe_cambiar_password" type="checkbox" value="1"
                            @checked(old('debe_cambiar_password', $usuario->exists ? $usuario->debe_cambiar_password : true)) />
                        <label class="form-check-label" for="debe_cambiar_password">Pedir que cambie la contraseña al entrar</label>
                        <div class="form-text mt-0">Así solo el usuario conoce su contraseña definitiva.</div>
                    </div>
                @else
                    <div class="mb-4"></div>
                @endif
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('usuarios.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    @if ($usuario->exists)
        {{-- Permisos extra: además de los de su rol (como en sistema-inventario). --}}
        <div class="card mt-4">
            <div class="card-body">
                <h4 class="mb-1">Permisos extra</h4>
                @if ($usuario->esAdministrador())
                    <p class="text-700 fs--1 mb-0">Tiene acceso total: ya tiene todos los permisos.</p>
                @else
                    <p class="text-700 fs--1 mb-3">
                        Se suman a los de su rol ({{ $usuario->roles->pluck('nombre')->join(', ') ?: 'sin rol' }}). Los que ya da el rol aparecen marcados y fijos.
                        @if ($propios !== null) Solo puedes dar los permisos que tú tienes. @endif
                    </p>
                    <form method="POST" action="{{ route('usuarios.permisos', $usuario->id_usuario) }}">
                        @csrf
                        @method('PUT')
                        @include('permisos.matriz', ['matriz' => $matriz, 'marcados' => $extras, 'propios' => $propios, 'heredados' => $heredados])
                        <button class="btn btn-primary" type="submit">Guardar permisos extra</button>
                    </form>
                @endif
            </div>
        </div>
    @endif
@endsection
