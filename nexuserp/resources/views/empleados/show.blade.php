@extends('layouts.app', ['titulo' => $e->nombre_completo])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('empleados.editar');
        [$nombreEstado, $color] = $estados[$e->estado] ?? [$e->estado, 'secondary'];
        $cv = $e->contratoVigente;
        $dinero = fn ($n) => number_format((float) $n, 2);
        $deBaja = $e->estado === 'BAJA';
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('empleados.index') }}">Empleados</a></li>
            <li class="breadcrumb-item active">{{ $e->nombre_completo }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">{{ $e->nombre_completo }} <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1 align-middle">{{ $nombreEstado }}</span></h2>
            <p class="text-700 mb-0">{{ $e->codigo_empleado }} · {{ $e->cargo?->nombre ?? 'Sin cargo' }}{{ $e->departamento ? ' · '.$e->departamento->nombre : '' }}
                · ingresó el {{ $e->fecha_ingreso?->format('d/m/Y') }} ({{ $e->antiguedad_anios }} años)</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($puedeEditar && ! $deBaja)
                <a class="btn btn-phoenix-secondary" href="{{ route('empleados.edit', $e->id_empleado) }}"><span class="fas fa-pen me-2"></span>Editar</a>
                <button class="btn btn-phoenix-danger" type="button" data-bs-toggle="collapse" data-bs-target="#baja">Dar de baja</button>
            @endif
            @if ($puedeEditar && $deBaja)
                <form method="POST" action="{{ route('empleados.reactivar', $e->id_empleado) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-phoenix-success" type="submit">Reactivar</button>
                </form>
            @endif
            @if ($yo->puede('empleados.eliminar'))
                <form method="POST" action="{{ route('empleados.destroy', $e->id_empleado) }}" onsubmit="return confirm(@js('¿Eliminar a '.$e->nombre_completo.'? Solo se permite si nunca estuvo en nómina ni asistencia.'))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link text-danger" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                </form>
            @endif
        </div>
    </div>

    @if ($puedeEditar && ! $deBaja)
        <div class="collapse {{ $errors->has('fecha_baja') || $errors->has('motivo_baja') ? 'show' : '' }} mb-4" id="baja">
            <div class="card border-danger"><div class="card-body">
                <p class="fs--1 mb-2">La baja cierra el contrato vigente. El registro se conserva para nóminas e historial.</p>
                <form method="POST" action="{{ route('empleados.baja', $e->id_empleado) }}" class="row g-2 align-items-start">
                    @csrf
                    @method('PATCH')
                    <div class="col-md-3">
                        <input class="form-control @error('fecha_baja') is-invalid @enderror" type="date" name="fecha_baja" value="{{ old('fecha_baja', now()->format('Y-m-d')) }}" required />
                        @error('fecha_baja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <input class="form-control @error('motivo_baja') is-invalid @enderror" name="motivo_baja" value="{{ old('motivo_baja') }}" maxlength="300" placeholder="Motivo (renuncia, despido, fin de contrato…)" required />
                        @error('motivo_baja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3"><button class="btn btn-danger w-100" type="submit">Confirmar baja</button></div>
                </form>
            </div></div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Datos</h5>
                    <dl class="row mb-0">
                        <dt class="col-5 text-700">Documento</dt><dd class="col-7">{{ $e->tipo_doc_id }} {{ $e->dpi_nit }}</dd>
                        <dt class="col-5 text-700">NIT / IGSS</dt><dd class="col-7">{{ $e->nit_personal ?? '—' }} / {{ $e->igss_afiliacion ?? '—' }}</dd>
                        <dt class="col-5 text-700">Nacimiento</dt><dd class="col-7">{{ $e->fecha_nacimiento ? $e->fecha_nacimiento->format('d/m/Y').' ('.$e->edad.' años)' : '—' }}</dd>
                        <dt class="col-5 text-700">Correo</dt><dd class="col-7 text-break">{{ $e->email_corporativo ?? $e->email_personal ?? '—' }}</dd>
                        <dt class="col-5 text-700">Teléfono</dt><dd class="col-7">{{ $e->telefono_personal ?? '—' }}</dd>
                        <dt class="col-5 text-700">Emergencia</dt><dd class="col-7">{{ $e->contacto_emergencia ?? '—' }} {{ $e->telefono_emergencia }}</dd>
                        <dt class="col-5 text-700">Dirección</dt><dd class="col-7">{{ collect([$e->direccion, $e->municipio?->nombre, $e->municipio?->division?->nombre])->filter()->implode(', ') ?: '—' }}</dd>
                        <dt class="col-5 text-700">Sucursal</dt><dd class="col-7">{{ $e->sucursal?->nombre ?? '—' }}</dd>
                        <dt class="col-5 text-700">Jefe inmediato</dt>
                        <dd class="col-7">@if ($e->supervisor)<a href="{{ route('empleados.show', $e->id_supervisor) }}">{{ $e->supervisor->nombre_completo }}</a>@else — @endif</dd>
                        @if ($e->subordinados->isNotEmpty())
                            <dt class="col-5 text-700">A su cargo</dt>
                            <dd class="col-7">@foreach ($e->subordinados as $s)<a class="d-block" href="{{ route('empleados.show', $s->id_empleado) }}">{{ $s->nombre_completo }}</a>@endforeach</dd>
                        @endif
                        @if ($deBaja)
                            <dt class="col-5 text-700">Baja</dt><dd class="col-7">{{ $e->fecha_baja?->format('d/m/Y') }} · {{ $e->motivo_baja }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="mb-0">Contrato y salario</h5>
                        @if ($puedeEditar && ! $deBaja)
                            <div class="d-flex gap-2">
                                @if ($cv)<button class="btn btn-phoenix-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#salario">Cambiar salario</button>@endif
                                <button class="btn btn-phoenix-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#contrato">{{ $cv ? 'Nuevo contrato' : 'Registrar contrato' }}</button>
                            </div>
                        @endif
                    </div>

                    @if ($cv)
                        <dl class="row mb-3">
                            <dt class="col-5 text-700">Salario base</dt><dd class="col-7 fs-0 fw-bold">{{ $cv->moneda }} {{ $dinero($cv->salario_base) }}</dd>
                            <dt class="col-5 text-700">Contrato</dt><dd class="col-7">{{ $cv->numero_contrato }} · {{ $tipos[$cv->tipo] ?? $cv->tipo }}</dd>
                            <dt class="col-5 text-700">Vigencia</dt><dd class="col-7">{{ $cv->fecha_inicio?->format('d/m/Y') }} – {{ $cv->fecha_fin?->format('d/m/Y') ?? 'indefinido' }}</dd>
                            <dt class="col-5 text-700">Jornada</dt><dd class="col-7">{{ $jornadas[$cv->jornada] ?? $cv->jornada }} · {{ $cv->horas_semana }} h/semana</dd>
                        </dl>
                    @else
                        <p class="text-warning"><span class="fas fa-exclamation-triangle me-1"></span>Sin contrato vigente: no entrará en la nómina.</p>
                    @endif

                    @if ($puedeEditar && ! $deBaja)
                        <div class="collapse {{ $errors->salario->any() ? 'show' : '' }}" id="salario">
                            <form method="POST" action="{{ route('empleados.salario', $e->id_empleado) }}" class="row g-2 border rounded p-2 mb-3">
                                @csrf
                                @if ($errors->salario->any())<div class="col-12 text-danger">{{ $errors->salario->first() }}</div>@endif
                                <div class="col-md-4"><select class="form-select form-select-sm" name="tipo_cambio">@foreach ($cambios as $c => $n)<option value="{{ $c }}" @selected(old('tipo_cambio') === $c)>{{ $n }}</option>@endforeach</select></div>
                                <div class="col-md-4"><input class="form-control form-control-sm" name="salario_nuevo" type="number" step="0.01" min="0.01" value="{{ old('salario_nuevo') }}" placeholder="Salario nuevo" required /></div>
                                <div class="col-md-4"><input class="form-control form-control-sm" name="fecha_efectiva" type="date" value="{{ old('fecha_efectiva', now()->format('Y-m-d')) }}" required /></div>
                                <div class="col-md-8"><input class="form-control form-control-sm" name="motivo" value="{{ old('motivo') }}" maxlength="300" placeholder="Motivo" required /></div>
                                <div class="col-md-4"><button class="btn btn-primary btn-sm w-100" type="submit">Guardar</button></div>
                            </form>
                        </div>
                        <div class="collapse {{ $errors->contrato->any() ? 'show' : '' }}" id="contrato">
                            <form method="POST" action="{{ route('empleados.contrato', $e->id_empleado) }}" class="row g-2 border rounded p-2 mb-3">
                                @csrf
                                @if ($errors->contrato->any())<div class="col-12 text-danger">{{ $errors->contrato->first() }}</div>@endif
                                @if ($cv)<div class="col-12 text-700">El contrato actual quedará como renovado.</div>@endif
                                <div class="col-md-4"><input class="form-control form-control-sm text-uppercase" name="numero_contrato" value="{{ old('numero_contrato') }}" maxlength="50" placeholder="{{ $siguienteContrato }}" title="Número (vacío = automático)" /></div>
                                <div class="col-md-4"><select class="form-select form-select-sm" name="tipo">@foreach ($tipos as $c => $n)<option value="{{ $c }}" @selected(old('tipo', $e->tipo_contrato) === $c)>{{ $n }}</option>@endforeach</select></div>
                                <div class="col-md-4"><select class="form-select form-select-sm" name="jornada">@foreach ($jornadas as $c => $n)<option value="{{ $c }}" @selected(old('jornada') === $c)>{{ $n }}</option>@endforeach</select></div>
                                <div class="col-md-4"><label class="fs--2 text-600">Inicio</label><input class="form-control form-control-sm" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', ($cv ? now() : $e->fecha_ingreso)?->format('Y-m-d')) }}" required /></div>
                                <div class="col-md-4"><label class="fs--2 text-600">Fin (si aplica)</label><input class="form-control form-control-sm" name="fecha_fin" type="date" value="{{ old('fecha_fin') }}" /></div>
                                <div class="col-md-4"><label class="fs--2 text-600">Horas por semana</label><input class="form-control form-control-sm" name="horas_semana" type="number" min="1" max="72" value="{{ old('horas_semana', 44) }}" required /></div>
                                <div class="col-md-5"><input class="form-control form-control-sm" name="salario_base" type="number" step="0.01" min="0.01" value="{{ old('salario_base', $cv?->salario_base ? (float) $cv->salario_base : '') }}" placeholder="Salario base mensual" required /></div>
                                <div class="col-md-3"><select class="form-select form-select-sm" name="moneda">@foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected(old('moneda', $cv->moneda ?? 'GTQ') === $m->codigo)>{{ $m->codigo }}</option>@endforeach</select></div>
                                <div class="col-md-4"><button class="btn btn-primary btn-sm w-100" type="submit">Guardar contrato</button></div>
                            </form>
                        </div>
                    @endif

                    <h6 class="mt-2">Historial salarial</h6>
                    @forelse ($historial as $h)
                        <div class="d-flex justify-content-between border-bottom border-200 py-1">
                            <span>{{ $h->fecha_efectiva?->format('d/m/Y') }} · {{ \App\Http\Controllers\EmpleadoController::CAMBIOS_SALARIO[$h->tipo_cambio] ?? ucfirst(strtolower($h->tipo_cambio)) }}
                                <span class="text-600">{{ $h->motivo }}{{ $h->cargo ? ' · '.$h->cargo->nombre : '' }}</span></span>
                            <span class="text-nowrap">{{ $h->salario_anterior !== null ? $dinero($h->salario_anterior).' → ' : '' }}<strong>{{ $h->moneda }} {{ $dinero($h->salario_nuevo) }}</strong></span>
                        </div>
                    @empty
                        <p class="text-700 mb-0">Sin movimientos.</p>
                    @endforelse

                    @if ($contratos->count() > 1 || ($contratos->count() === 1 && ! $cv))
                        <h6 class="mt-3">Contratos anteriores</h6>
                        @foreach ($contratos->where('estado', '!=', 'VIGENTE') as $c)
                            <div class="d-flex justify-content-between border-bottom border-200 py-1 text-700">
                                <span>{{ $c->numero_contrato }} · {{ $tipos[$c->tipo] ?? $c->tipo }} · {{ ucfirst(strtolower($c->estado)) }}</span>
                                <span>{{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? '…' }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
