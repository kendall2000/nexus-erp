{{-- Layout actual del sistema; pasa al layout nuevo estilo restaurante en el paso 3. --}}
@extends('layouts.app')

@section('breadcrumb', 'Seguridad y accesos')

@section('content')
    <h2 class="mb-4 text-1100">Seguridad y accesos</h2>

    @include('partials.alertas')

    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="mb-3">Reglas de inicio de sesión</h4>
                    <form method="POST" action="{{ route('seguridad.guardar') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="max_intentos">Intentos fallidos antes del bloqueo</label>
                            <input class="form-control" id="max_intentos" name="max_intentos" type="number" min="1" max="20" value="{{ old('max_intentos', $config['intentos']) }}" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="bloqueo_minutos">Minutos de bloqueo</label>
                            <input class="form-control" id="bloqueo_minutos" name="bloqueo_minutos" type="number" min="1" max="1440" value="{{ old('bloqueo_minutos', $config['bloqueo']) }}" required />
                            <div class="form-text">El bloqueo es por usuario (o correo) + IP.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="sesion_expira_min">Cerrar la sesión tras (minutos sin actividad)</label>
                            <input class="form-control" id="sesion_expira_min" name="sesion_expira_min" type="number" min="5" max="1440" value="{{ old('sesion_expira_min', $config['expira']) }}" required />
                        </div>
                        <button class="btn btn-primary" type="submit">Guardar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="mb-2">Verificación en dos pasos por rol</h4>
                    <p class="text-700 fs--1">Los usuarios de un rol que la exige no pueden usar el sistema hasta activarla.</p>
                    <form method="POST" action="{{ route('seguridad.roles') }}">
                        @csrf
                        @method('PUT')
                        <table class="table table-sm fs--1">
                            <thead><tr><th>Rol</th><th>Usuarios</th><th class="text-center">Exige 2 pasos</th></tr></thead>
                            <tbody>
                            @foreach ($roles as $rol)
                                <tr>
                                    <td>{{ $rol->nombre }} @unless ($rol->activo)<span class="badge badge-phoenix badge-phoenix-secondary ms-1">Inactivo</span>@endunless</td>
                                    <td>{{ $rol->usuarios_count }}</td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="requiere_2fa[]" value="{{ $rol->id_rol }}" @checked($rol->requiere_2fa) />
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <button class="btn btn-primary" type="submit">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h4 class="mb-0">Historial de accesos</h4>
                <form method="GET" class="d-flex gap-2">
                    <input class="form-control form-control-sm" name="buscar" value="{{ request('buscar') }}" placeholder="Usuario, correo o IP" maxlength="150" />
                    <select class="form-select form-select-sm" name="evento">
                        <option value="">Todos los eventos</option>
                        @foreach ($eventos as $clave => $texto)
                            <option value="{{ $clave }}" @selected(request('evento') === $clave)>{{ $texto }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-phoenix-secondary btn-sm" type="submit">Filtrar</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3">
                    <thead><tr><th>Fecha</th><th>Evento</th><th>Usuario</th><th>IP</th><th>Dispositivo</th><th>Detalle</th></tr></thead>
                    <tbody>
                    @forelse ($accesos as $a)
                        <tr>
                            <td class="text-nowrap">{{ $a->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $a->color() }}">{{ $eventos[$a->accion] ?? $a->accion }}</span></td>
                            <td>{{ $a->usuario?->nombre_completo ?? $a->username_intento }}</td>
                            <td>{{ $a->ip_address }}</td>
                            <td>{{ \App\Support\Seguridad::dispositivo($a->user_agent) }}</td>
                            <td>{{ $a->detalle }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-700">Sin registros.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $accesos->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
