@extends('layouts.app', ['titulo' => $usuario->exists ? 'Editar usuario' : 'Nuevo usuario'])

@section('contenido')
    <h2 class="mb-4 text-1100">{{ $usuario->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h2>

    <div class="card" style="max-width: 44rem">
        <div class="card-body">
            <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario->id_usuario) : route('usuarios.store') }}">
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
                    <label class="form-label" for="password">{{ $usuario->exists ? 'Nueva contraseña (dejar vacío para no cambiarla)' : 'Contraseña' }}</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" @required(! $usuario->exists) />
                    <div class="form-text">
                        Mínimo 12 caracteres, con mayúsculas, minúsculas, números y símbolos.
                        @if ($usuario->exists && $usuario->id_usuario !== auth()->id())
                            Si la cambias, se cierran sus sesiones abiertas.
                        @endif
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('usuarios.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
