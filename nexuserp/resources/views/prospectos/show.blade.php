@extends('layouts.app', ['titulo' => $p->nombre_empresa])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('prospectos.editar');
        [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary'];
        [$nombreTemp, $colorTemp] = $temperaturas[$p->temperatura] ?? [$p->temperatura, 'secondary'];
        $abierto = in_array($p->estado, ['NUEVO', 'EN_CONTACTO', 'CALIFICADO', 'NO_CALIFICADO'], true);
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('prospectos.index') }}">Prospectos</a></li>
            <li class="breadcrumb-item active">{{ $p->nombre_empresa }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">{{ $p->nombre_empresa }}
                <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1 align-middle">{{ $nombreEstado }}</span>
                <span class="badge badge-phoenix badge-phoenix-{{ $colorTemp }} fs--1 align-middle">{{ $nombreTemp }}</span></h2>
            <p class="text-700 mb-0">{{ $p->nombre_contacto }}{{ $p->cargo_contacto ? ', '.$p->cargo_contacto : '' }} · {{ $p->email_contacto }} {{ $p->telefono_contacto }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($abierto && $puedeEditar)
                <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#convertir"><span class="fas fa-user-check me-2"></span>Convertir en cliente</button>
                <a class="btn btn-phoenix-secondary" href="{{ route('prospectos.edit', $p->id_prospecto) }}"><span class="fas fa-pen me-2"></span>Editar</a>
                <button class="btn btn-phoenix-danger" type="button" data-bs-toggle="collapse" data-bs-target="#descartar">Descartar</button>
            @endif
            @if ($p->estado === 'CONVERTIDO' && $p->clienteGenerado && $yo->puede('clientes.ver'))
                <a class="btn btn-phoenix-primary" href="{{ route('clientes.show', $p->id_cliente_generado) }}">Ver cliente</a>
            @endif
            @if ($yo->puede('prospectos.eliminar') && $p->estado !== 'CONVERTIDO')
                <form method="POST" action="{{ route('prospectos.destroy', $p->id_prospecto) }}" onsubmit="return confirm(@js('¿Eliminar el prospecto '.$p->nombre_empresa.'?'))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link text-danger" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                </form>
            @endif
        </div>
    </div>

    @if ($abierto && $puedeEditar)
        <div class="collapse {{ $errors->has('id_etapa') || $errors->has('id_responsable') ? 'show' : '' }} mb-4" id="convertir">
            <div class="card border-success"><div class="card-body fs--1">
                <p class="mb-2">Se crea el cliente con los datos del prospecto y su contacto principal{{ $yo->puede('oportunidades.crear') ? '; también puedes abrir una oportunidad de venta' : '' }}.</p>
                <form method="POST" action="{{ route('prospectos.convertir', $p->id_prospecto) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <input type="hidden" name="crear_oportunidad" value="0" />
                        <div class="form-check mb-2"><input class="form-check-input" id="crear_oportunidad" type="checkbox" name="crear_oportunidad" value="1" @checked(old('crear_oportunidad')) /><label class="form-check-label" for="crear_oportunidad">Crear oportunidad</label></div>
                    </div>
                    <div class="col-md-3"><select class="form-select form-select-sm @error('id_etapa') is-invalid @enderror" name="id_etapa"><option value="">Etapa…</option>@foreach ($etapas as $e)<option value="{{ $e->id_etapa }}">{{ $e->nombre }} ({{ $e->probabilidad_cierre }} %)</option>@endforeach</select></div>
                    <div class="col-md-3"><select class="form-select form-select-sm @error('id_responsable') is-invalid @enderror" name="id_responsable"><option value="">Vendedor…</option>@foreach ($vendedores as $v)<option value="{{ $v->id_empleado }}" @selected($p->id_asignado_a === $v->id_empleado)>{{ $v->nombre_completo }}</option>@endforeach</select></div>
                    <div class="col-md-3"><button class="btn btn-success btn-sm w-100" type="submit">Convertir</button></div>
                    @if ($errors->has('id_etapa') || $errors->has('id_responsable'))<div class="col-12 text-danger">{{ $errors->first('id_etapa') ?: $errors->first('id_responsable') }}</div>@endif
                </form>
            </div></div>
        </div>
        <div class="collapse {{ $errors->has('motivo_descarte') ? 'show' : '' }} mb-4" id="descartar">
            <div class="card border-danger"><div class="card-body">
                <form method="POST" action="{{ route('prospectos.descartar', $p->id_prospecto) }}" class="row g-2">
                    @csrf
                    @method('PATCH')
                    <div class="col-md-9"><input class="form-control form-control-sm @error('motivo_descarte') is-invalid @enderror" name="motivo_descarte" value="{{ old('motivo_descarte') }}" maxlength="300" placeholder="Motivo del descarte" required /></div>
                    <div class="col-md-3"><button class="btn btn-danger btn-sm w-100" type="submit">Descartar</button></div>
                </form>
            </div></div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            @if ($abierto && $puedeEditar)
                <div class="card mb-4"><div class="card-body fs--1">
                    <h5 class="mb-3">Registrar seguimiento</h5>
                    @if ($errors->seguimiento->any())<div class="text-danger mb-2">{{ $errors->seguimiento->first() }}</div>@endif
                    <form method="POST" action="{{ route('prospectos.seguimiento', $p->id_prospecto) }}" class="row g-2">
                        @csrf
                        <div class="col-md-3"><select class="form-select form-select-sm" name="tipo">@foreach ($tipos as $k => $n)<option value="{{ $k }}" @selected(old('tipo') === $k)>{{ $n }}</option>@endforeach</select></div>
                        <div class="col-md-3"><select class="form-select form-select-sm" name="resultado">@foreach ($resultados as $k => $n)<option value="{{ $k }}" @selected(old('resultado') === $k)>{{ $n }}</option>@endforeach</select></div>
                        <div class="col-md-3"><input class="form-control form-control-sm" type="datetime-local" name="fecha_hora" value="{{ old('fecha_hora', now()->format('Y-m-d\TH:i')) }}" required /></div>
                        <div class="col-md-3"><select class="form-select form-select-sm" name="id_realizado_por" required><option value="">Vendedor…</option>@foreach ($vendedores as $v)<option value="{{ $v->id_empleado }}" @selected((int) old('id_realizado_por', $p->id_asignado_a) === $v->id_empleado)>{{ $v->nombre_completo }}</option>@endforeach</select></div>
                        <div class="col-12"><textarea class="form-control form-control-sm" name="resumen" rows="2" maxlength="5000" placeholder="¿Qué se habló?" required>{{ old('resumen') }}</textarea></div>
                        <div class="col-md-6"><input class="form-control form-control-sm" name="proxima_accion" value="{{ old('proxima_accion') }}" maxlength="300" placeholder="Próxima acción" /></div>
                        <div class="col-md-3"><input class="form-control form-control-sm" type="date" name="fecha_proxima_accion" value="{{ old('fecha_proxima_accion') }}" title="Fecha de la próxima acción" /></div>
                        <div class="col-md-3"><button class="btn btn-primary btn-sm w-100" type="submit">Guardar</button></div>
                    </form>
                </div></div>
            @endif
            <div class="card"><div class="card-body fs--1">
                <h5 class="mb-3">Historial</h5>
                @forelse ($seguimientos as $s)
                    <div class="border-bottom border-200 py-2">
                        <p class="mb-1"><span class="fw-semi-bold">{{ $tipos[$s->tipo] ?? $s->tipo }}</span> · {{ $resultados[$s->resultado] ?? $s->resultado }}
                            <span class="text-600">· {{ $s->fecha_hora?->format('d/m/Y H:i') }} · {{ $s->realizadoPor?->nombre_completo }}</span></p>
                        <p class="mb-0" style="white-space: pre-line">{{ $s->resumen }}</p>
                        @if ($s->proxima_accion)<p class="mb-0 text-primary"><span class="fas fa-arrow-right me-1"></span>{{ $s->proxima_accion }}{{ $s->fecha_proxima_accion ? ' · '.$s->fecha_proxima_accion->format('d/m/Y') : '' }}</p>@endif
                    </div>
                @empty
                    <p class="text-700 mb-0">Sin seguimientos todavía.</p>
                @endforelse
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4"><div class="card-body fs--1">
                <h5 class="mb-3">Detalles</h5>
                <dl class="row mb-0">
                    <dt class="col-5 text-700">Fuente</dt><dd class="col-7">{{ $p->fuente?->nombre ?? '—' }}</dd>
                    <dt class="col-5 text-700">Vendedor</dt><dd class="col-7">{{ $p->asignadoA?->nombre_completo ?? 'Sin asignar' }}</dd>
                    <dt class="col-5 text-700">Industria</dt><dd class="col-7">{{ $p->industria?->nombre ?? '—' }}</dd>
                    <dt class="col-5 text-700">Presupuesto</dt><dd class="col-7">{{ $p->presupuesto_estimado !== null ? $p->moneda.' '.number_format((float) $p->presupuesto_estimado, 2) : '—' }}</dd>
                    <dt class="col-5 text-700">Puntuación</dt><dd class="col-7">{{ $p->puntuacion_lead }} / 100</dd>
                    @if ($p->interes_servicio)<dt class="col-5 text-700">Interés</dt><dd class="col-7">{{ $p->interes_servicio }}</dd>@endif
                    @if ($p->motivo_descarte)<dt class="col-5 text-700">Descarte</dt><dd class="col-7">{{ $p->motivo_descarte }}</dd>@endif
                    @if ($p->notas)<dt class="col-5 text-700">Notas</dt><dd class="col-7">{{ $p->notas }}</dd>@endif
                </dl>
            </div></div>
            @if ($p->oportunidades->isNotEmpty())
                <div class="card"><div class="card-body fs--1">
                    <h5 class="mb-3">Oportunidades</h5>
                    @foreach ($p->oportunidades as $o)
                        <div class="py-1">@if ($yo->puede('oportunidades.ver'))<a href="{{ route('oportunidades.show', $o->id_oportunidad) }}">{{ $o->nombre }}</a>@else{{ $o->nombre }}@endif <span class="text-600">· {{ $o->etapa?->nombre }}</span></div>
                    @endforeach
                </div></div>
            @endif
        </div>
    </div>
@endsection
