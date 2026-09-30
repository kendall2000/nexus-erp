@extends('layouts.app', ['titulo' => $t->numero_ticket])

@section('contenido')
    @php
        $yo = auth()->user();
        [$nombreEstado, $color] = $estados[$t->estado] ?? [$t->estado, 'secondary'];
        [$nombrePrioridad, $colorPrioridad] = $prioridades[$t->prioridad] ?? [$t->prioridad, 'secondary'];
        $abierto = in_array($t->estado, \App\Http\Controllers\TicketController::ACTIVOS, true);
        [$slaRespuesta, $colorRespuesta] = \App\Support\Sla::semaforo($t->fecha_limite_respuesta, $t->fecha_primera_respuesta);
        [$slaResolucion, $colorResolucion] = \App\Support\Sla::semaforo($t->fecha_limite_resolucion, $t->fecha_resolucion);
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Tickets</a></li>
            <li class="breadcrumb-item active">{{ $t->numero_ticket }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">{{ $t->asunto }}</h2>
            <p class="text-700 mb-0">{{ $t->numero_ticket }} ·
                <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                <span class="badge badge-phoenix badge-phoenix-{{ $colorPrioridad }}">{{ $nombrePrioridad }}</span>
                · abierto el {{ $t->fecha_apertura?->format('d/m/Y H:i') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($abierto && $yo->puede('tickets.cerrar'))
                <button class="btn btn-phoenix-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#cerrar">Cerrar ticket</button>
            @endif
            @if (in_array($t->estado, ['RESUELTO'], true) && $yo->puede('tickets.cerrar'))
                <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#cerrar">Cerrar ticket</button>
            @endif
            @if (in_array($t->estado, ['RESUELTO', 'CERRADO'], true) && $yo->puede('tickets.reabrir'))
                <button class="btn btn-phoenix-warning" type="button" data-bs-toggle="collapse" data-bs-target="#reabrir">Reabrir</button>
            @endif
        </div>
    </div>

    @if ($t->estado !== 'CERRADO' && $yo->puede('tickets.cerrar'))
        <div class="collapse mb-4" id="cerrar">
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('tickets.cerrar', $t->id_ticket) }}" class="row g-2 align-items-end">
                    @csrf
                    @method('PATCH')
                    <div class="col-md-3">
                        <label class="form-label fs--1">Calificación del cliente</label>
                        <select class="form-select form-select-sm" name="calificacion_cliente">
                            <option value="">Sin calificar</option>
                            @for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ $i }} de 5</option>@endfor
                        </select>
                    </div>
                    <div class="col-md-6"><input class="form-control form-control-sm" name="comentario_calificacion" maxlength="500" placeholder="Comentario del cliente (opcional)" /></div>
                    <div class="col-md-3"><button class="btn btn-primary btn-sm w-100" type="submit">Confirmar cierre</button></div>
                </form>
            </div></div>
        </div>
    @endif
    @if (in_array($t->estado, ['RESUELTO', 'CERRADO'], true) && $yo->puede('tickets.reabrir'))
        <div class="collapse {{ $errors->has('motivo') ? 'show' : '' }} mb-4" id="reabrir">
            <div class="card border-warning"><div class="card-body">
                <form method="POST" action="{{ route('tickets.reabrir', $t->id_ticket) }}" class="row g-2">
                    @csrf
                    @method('PATCH')
                    <div class="col-md-9">
                        <input class="form-control form-control-sm @error('motivo') is-invalid @enderror" name="motivo" value="{{ old('motivo') }}" maxlength="2000" placeholder="¿Por qué se reabre?" required />
                        @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3"><button class="btn btn-warning btn-sm w-100" type="submit">Reabrir</button></div>
                </form>
            </div></div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <p class="fs--1 text-600 mb-1">Descripción · {{ $canales[$t->canal_origen] ?? $t->canal_origen }} · {{ $tipos[$t->tipo] ?? $t->tipo }}</p>
                    <p class="mb-0" style="white-space: pre-line">{{ $t->descripcion }}</p>
                </div>
            </div>

            @foreach ($comentarios as $c)
                <div class="card mb-3 {{ $c->es_nota_interna ? 'border-warning' : '' }}">
                    <div class="card-body py-3">
                        <p class="fs--1 text-600 mb-1">
                            <span class="fw-semi-bold text-900">{{ $c->usuario?->nombre_completo ?? $c->autor?->nombre_completo ?? 'Sistema' }}</span>
                            · {{ $c->created_at?->format('d/m/Y H:i') }}
                            @if ($c->es_nota_interna)<span class="badge badge-phoenix badge-phoenix-warning ms-1">Nota interna</span>@endif
                        </p>
                        <p class="mb-0 fs--1" style="white-space: pre-line">{{ $c->contenido }}</p>
                    </div>
                </div>
            @endforeach

            @if ($abierto && $yo->puede('tickets.editar'))
                <div class="card">
                    <div class="card-body">
                        @error('ticket')<div class="alert alert-soft-danger fs--1">{{ $message }}</div>@enderror
                        <form method="POST" action="{{ route('tickets.responder', $t->id_ticket) }}">
                            @csrf
                            <textarea class="form-control mb-2 @error('contenido') is-invalid @enderror" name="contenido" rows="4" maxlength="10000" placeholder="Escribe la respuesta o una nota interna…" required>{{ old('contenido') }}</textarea>
                            @error('contenido')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <input type="hidden" name="es_nota_interna" value="0" />
                                <div class="form-check mb-0">
                                    <input class="form-check-input" id="es_nota_interna" name="es_nota_interna" type="checkbox" value="1" @checked(old('es_nota_interna')) />
                                    <label class="form-check-label fs--1" for="es_nota_interna">Nota interna (no cuenta como respuesta al cliente)</label>
                                </div>
                                <select class="form-select form-select-sm w-auto" name="estado">
                                    <option value="">Mantener estado</option>
                                    <option value="EN_PROGRESO">En progreso</option>
                                    <option value="PENDIENTE_CLIENTE">Esperando al cliente</option>
                                    <option value="RESUELTO">Resuelto</option>
                                </select>
                                <button class="btn btn-primary btn-sm ms-auto" type="submit">Enviar</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Detalles</h5>
                    <dl class="row mb-0">
                        <dt class="col-5 text-700">Cliente</dt>
                        <dd class="col-7">@if ($yo->puede('clientes.ver'))<a href="{{ route('clientes.show', $t->id_cliente) }}">{{ $t->cliente?->razon_social }}</a>@else{{ $t->cliente?->razon_social }}@endif</dd>
                        <dt class="col-5 text-700">Contrato</dt>
                        <dd class="col-7">@if ($t->contrato && $yo->puede('contratos.ver'))<a href="{{ route('contratos.show', $t->id_contrato) }}">{{ $t->contrato->numero_contrato }}</a>@else{{ $t->contrato?->numero_contrato ?? '—' }}@endif</dd>
                        <dt class="col-5 text-700">Categoría</dt><dd class="col-7">{{ $t->categoria?->nombre ?? '—' }}</dd>
                        <dt class="col-5 text-700">Agente</dt><dd class="col-7">{{ $t->asignadoA?->nombre_completo ?? 'Sin asignar' }}</dd>
                        <dt class="col-5 text-700">SLA</dt><dd class="col-7">{{ $t->sla?->nombre ?? 'Sin SLA' }}</dd>
                        @if ($t->fecha_limite_respuesta)
                            <dt class="col-5 text-700">Respuesta</dt>
                            <dd class="col-7"><span class="badge badge-phoenix badge-phoenix-{{ $colorRespuesta }}">{{ $slaRespuesta }}</span><br>límite {{ $t->fecha_limite_respuesta->format('d/m H:i') }}</dd>
                            <dt class="col-5 text-700">Resolución</dt>
                            <dd class="col-7"><span class="badge badge-phoenix badge-phoenix-{{ $colorResolucion }}">{{ $slaResolucion }}</span><br>límite {{ $t->fecha_limite_resolucion?->format('d/m H:i') }}</dd>
                        @endif
                        @if ($t->calificacion_cliente)<dt class="col-5 text-700">Calificación</dt><dd class="col-7">{{ str_repeat('★', $t->calificacion_cliente) }}{{ str_repeat('☆', 5 - $t->calificacion_cliente) }} {{ $t->comentario_calificacion }}</dd>@endif
                    </dl>
                </div>
            </div>

            @if ($abierto && $yo->puede('tickets.asignar'))
                <div class="card mb-4">
                    <div class="card-body fs--1">
                        <h5 class="mb-3">{{ $t->id_asignado_a ? 'Reasignar' : 'Asignar' }}</h5>
                        <form method="POST" action="{{ route('tickets.asignar', $t->id_ticket) }}" class="d-grid gap-2">
                            @csrf
                            @method('PATCH')
                            <select class="form-select form-select-sm @error('id_asignado_a') is-invalid @enderror" name="id_asignado_a" required>
                                <option value="">Agente…</option>
                                @foreach ($agentes as $a)<option value="{{ $a->id_empleado }}" @selected((int) old('id_asignado_a') === $a->id_empleado)>{{ $a->nombre_completo }}</option>@endforeach
                            </select>
                            @error('id_asignado_a')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @if ($t->id_asignado_a)
                                <input class="form-control form-control-sm @error('motivo') is-invalid @enderror" name="motivo" value="{{ old('motivo') }}" maxlength="500" placeholder="Motivo de la reasignación" required />
                            @endif
                            <button class="btn btn-phoenix-primary btn-sm" type="submit">Guardar</button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($escalaciones->isNotEmpty())
                <div class="card">
                    <div class="card-body fs--1">
                        <h5 class="mb-3">Escalaciones</h5>
                        @foreach ($escalaciones as $e)
                            <div class="border-bottom border-200 py-1">
                                <span class="fw-semi-bold">Nivel {{ $e->nivel }} → {{ $e->escaladoA?->nombre_completo }}</span>
                                <span class="d-block text-700">{{ $e->motivo }}</span>
                                <span class="d-block text-600">{{ $e->usuario?->nombre_completo }} · {{ $e->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
