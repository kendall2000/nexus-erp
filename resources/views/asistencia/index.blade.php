@extends('layouts.app', ['titulo' => 'Asistencia'])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('asistencia.editar');
        $puedeAprobar = $yo->puede('asistencia.aprobar');
        $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Asistencia</h2>
            <p class="text-700 fw-semi-bold mb-0">Registro diario (entrada a las {{ $horaEntrada }}) y solicitudes de ausencia. Nómina descuenta las ausencias y trae las horas extra.</p>
        </div>
        <form method="GET" class="d-flex gap-2">
            <a class="btn btn-phoenix-primary btn-sm text-nowrap" href="{{ route('rotativos.index') }}"><span class="fas fa-people-arrows me-1"></span>Personal rotativo</a>
            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('asistencia.index', ['fecha' => $fecha->copy()->subDay()->toDateString()]) }}" title="Día anterior"><span class="fas fa-chevron-left"></span></a>
            <input class="form-control form-control-sm" type="date" name="fecha" value="{{ $fecha->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()" />
            @if ($fecha->lt(today()))<a class="btn btn-phoenix-secondary btn-sm" href="{{ route('asistencia.index', ['fecha' => $fecha->copy()->addDay()->toDateString()]) }}" title="Día siguiente"><span class="fas fa-chevron-right"></span></a>@endif
        </form>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">{{ ucfirst(['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'][$fecha->dayOfWeek]) }} {{ $fecha->format('d/m/Y') }}
                @if ($fecha->isWeekend())<span class="badge badge-phoenix badge-phoenix-secondary fs--2 align-middle">Fin de semana</span>@endif</h5>
            @if ($empleados->isEmpty())
                <p class="text-700 fs--1 mb-0">No hay empleados activos en esta fecha.</p>
            @else
                <form method="POST" action="{{ route('asistencia.guardar') }}">
                    @csrf
                    <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}" />
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-3 align-middle">
                            <thead><tr><th>Empleado</th><th style="width: 9rem">Estado</th><th style="width: 7rem">Entrada</th><th style="width: 7rem">Salida</th><th style="width: 6rem">H. extra</th><th>Observaciones</th><th class="text-end">Mes: tardes / ausencias</th></tr></thead>
                            <tbody>
                            @foreach ($empleados as $e)
                                @php
                                    $r = $registros->get($e->id_empleado);
                                    $m = $resumen->get($e->id_empleado);
                                    $campo = fn ($c) => "asistencia[{$e->id_empleado}][{$c}]";
                                @endphp
                                <tr>
                                    <td><span class="fw-semi-bold">{{ $e->nombre_completo }}</span>@if ($r?->minutos_tarde)<span class="d-block fs--2 text-warning">{{ $r->minutos_tarde }} min tarde</span>@endif</td>
                                    <td>
                                        <select class="form-select form-select-sm" name="{{ $campo('estado') }}" @disabled(! $puedeEditar)>
                                            <option value="">Sin registrar</option>
                                            @foreach ($estados as $k => [$n])<option value="{{ $k }}" @selected(old("asistencia.{$e->id_empleado}.estado", $r?->estado) === $k)>{{ $n }}</option>@endforeach
                                        </select>
                                    </td>
                                    <td><input class="form-control form-control-sm" type="time" name="{{ $campo('entrada') }}" value="{{ old("asistencia.{$e->id_empleado}.entrada", $r?->hora_entrada?->format('H:i')) }}" @disabled(! $puedeEditar) /></td>
                                    <td><input class="form-control form-control-sm" type="time" name="{{ $campo('salida') }}" value="{{ old("asistencia.{$e->id_empleado}.salida", $r?->hora_salida?->format('H:i')) }}" @disabled(! $puedeEditar) /></td>
                                    <td><input class="form-control form-control-sm" type="number" step="0.5" min="0" max="12" name="{{ $campo('horas_extra') }}" value="{{ old("asistencia.{$e->id_empleado}.horas_extra", $r ? (float) $r->horas_extra : '') }}" @disabled(! $puedeEditar) /></td>
                                    <td><input class="form-control form-control-sm" name="{{ $campo('observaciones') }}" value="{{ old("asistencia.{$e->id_empleado}.observaciones", $r?->observaciones) }}" maxlength="300" @disabled(! $puedeEditar) /></td>
                                    <td class="text-end text-nowrap text-700">{{ (int) ($m->tardes ?? 0) }} / <span class="{{ ($m->ausencias ?? 0) > 0 ? 'text-danger' : '' }}">{{ (int) ($m->ausencias ?? 0) }}</span>{{ ($m->extras ?? 0) > 0 ? ' · '.$cantidad($m->extras).' h extra' : '' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($puedeEditar)
                        <div class="d-flex gap-2 align-items-center">
                            <button class="btn btn-primary" type="submit">Guardar asistencia del día</button>
                            <button class="btn btn-phoenix-secondary" type="button" id="todos-presentes">Marcar pendientes como presentes</button>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body fs--1">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Solicitudes de ausencia</h5>
                @if ($puedeEditar)<button class="btn btn-phoenix-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#nueva-solicitud">Nueva solicitud</button>@endif
            </div>
            @if ($puedeEditar)
                <div class="collapse {{ $errors->solicitud->any() ? 'show' : '' }} mb-3" id="nueva-solicitud">
                    @if ($errors->solicitud->any())<div class="text-danger mb-2">{{ $errors->solicitud->first() }}</div>@endif
                    <form method="POST" action="{{ route('asistencia.solicitudes.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-3"><select class="form-select form-select-sm" name="id_empleado" required><option value="">Empleado…</option>@foreach ($empleados as $e)<option value="{{ $e->id_empleado }}" @selected((int) old('id_empleado') === $e->id_empleado)>{{ $e->nombre_completo }}</option>@endforeach</select></div>
                        <div class="col-md-2"><select class="form-select form-select-sm" name="tipo">@foreach ($tipos as $k => $n)<option value="{{ $k }}" @selected(old('tipo') === $k)>{{ $n }}</option>@endforeach</select></div>
                        <div class="col-md-2"><input class="form-control form-control-sm" type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" required title="Desde" /></div>
                        <div class="col-md-2"><input class="form-control form-control-sm" type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" required title="Hasta" /></div>
                        <div class="col-md-3"><button class="btn btn-primary btn-sm w-100" type="submit">Registrar</button></div>
                        <div class="col-12"><input class="form-control form-control-sm" name="motivo" value="{{ old('motivo') }}" maxlength="500" placeholder="Motivo (opcional)" /></div>
                    </form>
                </div>
            @endif
            @error('solicitud')<div class="alert alert-soft-danger">{{ $message }}</div>@enderror
            @forelse ($solicitudes as $s)
                <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom border-200 py-2">
                    <div>
                        <span class="fw-semi-bold">{{ $s->empleado?->nombre_completo }}</span> · {{ $tipos[$s->tipo] ?? $s->tipo }}
                        <span class="d-block text-700">{{ $s->fecha_inicio?->format('d/m/Y') }} – {{ $s->fecha_fin?->format('d/m/Y') }} · {{ $s->dias_habiles }} días hábiles{{ $s->motivo ? ' · '.$s->motivo : '' }}</span>
                        @if ($s->estado !== 'PENDIENTE')<span class="d-block text-600">{{ ucfirst(strtolower($s->estado)) }} por {{ $s->aprobadoPor?->nombre_completo }}{{ $s->observaciones ? ': '.$s->observaciones : '' }}</span>@endif
                    </div>
                    @if ($s->estado === 'PENDIENTE' && $puedeAprobar)
                        <div class="d-flex gap-1 align-items-start">
                            <form method="POST" action="{{ route('asistencia.solicitudes.resolver', [$s->id_solicitud, 'aprobar']) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-success btn-sm" type="submit">Aprobar</button>
                            </form>
                            <form method="POST" action="{{ route('asistencia.solicitudes.resolver', [$s->id_solicitud, 'rechazar']) }}" class="d-flex gap-1">
                                @csrf
                                @method('PATCH')
                                <input class="form-control form-control-sm" name="observaciones" maxlength="500" placeholder="Motivo del rechazo" required />
                                <button class="btn btn-phoenix-danger btn-sm" type="submit">Rechazar</button>
                            </form>
                        </div>
                    @else
                        <span class="badge badge-phoenix badge-phoenix-{{ ['PENDIENTE' => 'warning', 'APROBADO' => 'success', 'RECHAZADO' => 'danger', 'CANCELADO' => 'secondary'][$s->estado] ?? 'secondary' }} align-self-start">{{ ucfirst(strtolower($s->estado)) }}</span>
                    @endif
                </div>
            @empty
                <p class="text-700 mb-0">No hay solicitudes recientes.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var boton = document.getElementById('todos-presentes');
            if (!boton) return;
            boton.addEventListener('click', function () {
                document.querySelectorAll('select[name$="[estado]"]').forEach(function (s) { if (!s.value) s.value = 'PRESENTE'; });
            });
        })();
    </script>
@endpush
