@extends('layouts.app', ['titulo' => 'Seguridad y accesos'])

@section('contenido')
    @php
        $tiempo = fn (int $s) => $s >= 60 ? intdiv($s + 59, 60).' min' : $s.' s';
        $tarjetas = [
            ['Ingresos', $resumen->ingresos ?? 0, 'log-in', 'success', 'LOGIN_OK'],
            ['Intentos fallidos', $resumen->fallidos ?? 0, 'alert-triangle', 'warning', 'LOGIN_FAIL'],
            ['Intentos durante un bloqueo', $resumen->bloqueos ?? 0, 'lock', 'danger', 'BLOQUEO'],
            ['IPs con fallos', $resumen->ips ?? 0, 'globe', 'info', null],
        ];
    @endphp
    <div class="mb-4">
        <h2 class="mb-1 text-1100">Seguridad y accesos</h2>
        <p class="text-700 fw-semi-bold mb-0">Resumen de las últimas 24 horas.</p>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($tarjetas as [$titulo, $valor, $icono, $color, $evento])
            <div class="col-6 col-xl-3">
                <div class="card h-100"><div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="fs-2 text-{{ $color }}" data-feather="{{ $icono }}"></span>
                    <div>
                        <p class="fs--1 text-700 mb-1">{{ $titulo }}</p>
                        <h3 class="mb-0">
                            @if ($evento && $valor)<a class="text-1100" href="{{ route('seguridad.index', ['evento' => $evento, 'desde' => now()->subDay()->toDateString()]) }}#historial">{{ number_format($valor) }}</a>@else {{ number_format($valor) }} @endif
                        </h3>
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="mb-2">Bloqueos vigentes</h4>
                    <p class="text-700 fs--1">Usuario (o correo) + IP que agotaron los {{ $config['intentos'] }} intentos. El bloqueo vence solo a los {{ $config['bloqueo'] }} minutos; puedes quitarlo antes si confirmaste que es la persona.</p>
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th>Usuario</th><th>IP</th><th class="text-end">Intentos</th><th>Vence en</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($bloqueos as $b)
                                <tr>
                                    <td>
                                        {{ $b->login }}
                                        @if ($b->id_usuario === null)<span class="badge badge-phoenix badge-phoenix-secondary ms-1" title="Nadie tiene ese usuario o correo">No existe</span>@endif
                                    </td>
                                    <td>{{ $b->ip }}</td>
                                    <td class="text-end">{{ $b->fallidos }}</td>
                                    <td class="text-nowrap">{{ $tiempo($b->segundos) }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('seguridad.desbloquear') }}">
                                            @csrf
                                            <input type="hidden" name="login" value="{{ $b->login }}" />
                                            <input type="hidden" name="ip" value="{{ $b->ip }}" />
                                            <button class="btn btn-phoenix-primary btn-sm" type="submit"><span class="fas fa-unlock me-1"></span>Desbloquear</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-700 py-3">No hay bloqueos vigentes.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="mb-2">IPs con más intentos fallidos</h4>
                    <p class="text-700 fs--1">Últimos 7 días. Muchas cuentas distintas desde una misma IP suele ser un ataque.</p>
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0">
                            <thead><tr><th>IP</th><th class="text-end">Fallidos</th><th class="text-end">Cuentas</th><th>Último</th></tr></thead>
                            <tbody>
                            @forelse ($ipsSospechosas as $ip)
                                <tr>
                                    <td><a href="{{ route('seguridad.index', ['buscar' => $ip->ip_address]) }}#historial">{{ $ip->ip_address }}</a></td>
                                    <td class="text-end">{{ $ip->intentos }}</td>
                                    <td class="text-end {{ $ip->cuentas > 2 ? 'text-danger fw-bold' : '' }}">{{ $ip->cuentas }}</td>
                                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($ip->ultimo)->format('d/m H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-700 py-3">Sin intentos fallidos.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <div class="card" id="historial">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h4 class="mb-0">Historial de accesos</h4>
                <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('seguridad.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            </div>
            <form method="GET" action="{{ route('seguridad.index') }}#historial" class="row g-2 mb-3">
                <div class="col-md-3"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Usuario, correo o IP" maxlength="150" /></div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="usuario">
                        <option value="">Todos los usuarios</option>
                        @foreach ($usuarios as $u)<option value="{{ $u->id_usuario }}" @selected((int) ($filtros['usuario'] ?? 0) === $u->id_usuario)>{{ $u->nombre_completo }} ({{ $u->username }})</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="evento">
                        <option value="">Todos los eventos</option>
                        @foreach ($eventos as $clave => $texto)
                            <option value="{{ $clave }}" @selected(($filtros['evento'] ?? '') === $clave)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1"><input class="form-control form-control-sm" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Desde" /></div>
                <div class="col-md-1"><input class="form-control form-control-sm" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Hasta" /></div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('seguridad.index') }}#historial" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3">
                    <thead><tr><th>Fecha</th><th>Evento</th><th>Usuario</th><th>IP</th><th>Dispositivo</th><th>Detalle</th></tr></thead>
                    <tbody>
                    @forelse ($accesos as $a)
                        <tr>
                            <td class="text-nowrap">{{ $a->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $a->color() }}">{{ $eventos[$a->accion] ?? $a->accion }}</span></td>
                            <td>
                                @if ($a->usuario){{ $a->usuario->nombre_completo }}@else {{ $a->username_intento }} <span class="badge badge-phoenix badge-phoenix-secondary ms-1">No existe</span>@endif
                            </td>
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
