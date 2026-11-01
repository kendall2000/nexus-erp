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
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Tickets</a></li>
            <li class="breadcrumb-item active">{{ $t->numero_ticket }}</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Encabezado idéntico a apps/chat.html / CRM Ticket Details --}}
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
            <div>
                <h2 class="mb-1 text-1100">Ticket #{{ $t->numero_ticket }}: {{ $t->asunto }}</h2>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1">{{ $nombreEstado }}</span>
                    <span class="badge badge-phoenix badge-phoenix-{{ $colorPrioridad }} fs--1">{{ $nombrePrioridad }}</span>
                    <span class="text-700 fs--1">Abierto el: <strong class="text-900">{{ $t->fecha_apertura?->format('d/m/Y H:i') }}</strong></span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($abierto && $yo->puede('tickets.cerrar'))
                    <button class="btn btn-phoenix-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#cerrar">
                        <span class="fas fa-check-circle me-2"></span>Cerrar ticket
                    </button>
                @endif
                @if (in_array($t->estado, ['RESUELTO', 'CERRADO'], true) && $yo->puede('tickets.reabrir'))
                    <button class="btn btn-phoenix-warning" type="button" data-bs-toggle="collapse" data-bs-target="#reabrir">
                        <span class="fas fa-redo me-2"></span>Reabrir
                    </button>
                @endif
            </div>
        </div>

        {{-- Collapse de Cierre / Reabrir --}}
        @if ($t->estado !== 'CERRADO' && $yo->puede('tickets.cerrar'))
            <div class="collapse mb-4" id="cerrar">
                <div class="card border border-200 shadow-none">
                    <div class="card-body">
                        <h5 class="mb-3 text-1100">Confirmar Cierre y Encuesta de Satisfacción</h5>
                        <form method="POST" action="{{ route('tickets.cerrar', $t->id_ticket) }}" class="row g-3 align-items-end">
                            @csrf
                            @method('PATCH')
                            <div class="col-md-3">
                                <label class="form-label" for="calificacion_cliente">Calificación</label>
                                <select class="form-select" id="calificacion_cliente" name="calificacion_cliente">
                                    <option value="">Sin calificar</option>
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}">{{ $i }} de 5 estrellas</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="comentario_calificacion">Comentarios del Cliente</label>
                                <input class="form-control" id="comentario_calificacion" name="comentario_calificacion" maxlength="500" placeholder="Comentario o retroalimentación (opcional)" />
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" type="submit">Finalizar Ticket</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tarjetas KPI de Tiempos de Respuesta y SLAs --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">SLA Primera Respuesta</h6>
                                <h3 class="mb-0 fw-bolder">{{ $slaRespuesta }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Límite: {{ $t->fecha_limite_respuesta ? $t->fecha_limite_respuesta->format('d/m H:i') : 'Sin SLA' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-{{ $colorRespuesta }}-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-reply text-{{ $colorRespuesta }} fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">SLA de Resolución</h6>
                                <h3 class="mb-0 fw-bolder">{{ $slaResolucion }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Límite: {{ $t->fecha_limite_resolucion ? $t->fecha_limite_resolucion->format('d/m H:i') : 'Sin SLA' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-{{ $colorResolucion }}-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-stopwatch text-{{ $colorResolucion }} fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Agente Responsable</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ $t->asignadoA?->nombre_completo ?? 'Sin asignar' }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Categoría: {{ $t->categoria?->nombre ?? 'General' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-user-check text-primary fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5">
            {{-- Columna Principal: Conversación y Mensajes del Ticket (8 columnas) --}}
            <div class="col-12 col-xl-8">
                {{-- Descripción Inicial del Caso --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-white border-bottom border-200 py-3 px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-1100 fw-bold">Descripción del Incidente</h5>
                            <span class="badge badge-tag">{{ $canales[$t->canal_origen] ?? $t->canal_origen }}</span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <p class="fs-0 text-900 mb-0" style="white-space: pre-line;">{{ $t->descripcion }}</p>
                    </div>
                </div>

                {{-- Hilo de Mensajes estilo Chat de Phoenix --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-white border-bottom border-200 py-3 px-4">
                        <h5 class="mb-0 text-1100 fw-bold">Historial de Respuestas & Seguimiento</h5>
                    </div>
                    <div class="card-body p-4">
                        @forelse ($t->mensajes as $m)
                            <div class="d-flex gap-3 mb-4 {{ $m->es_interno ? 'bg-soft-warning p-3 rounded-3' : '' }}">
                                <div class="avatar avatar-m">
                                    <div class="avatar-name rounded-circle {{ $m->es_interno ? 'bg-warning text-white' : 'bg-primary-100 text-primary-600' }} fs--1 fw-bold">
                                        <span>{{ mb_strtoupper(mb_substr($m->autor?->nombre_completo ?? 'U', 0, 1)) }}</span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 text-1100 fw-bold">
                                            {{ $m->autor?->nombre_completo ?? 'Sistema' }}
                                            @if ($m->es_interno)
                                                <span class="badge badge-phoenix badge-phoenix-warning fs--2 ms-2">Nota Interna</span>
                                            @endif
                                        </h6>
                                        <span class="fs--2 text-600">{{ $m->created_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="fs--1 text-800 mb-0" style="white-space: pre-line;">{{ $m->mensaje }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-600 fs--1 mb-0 py-2">No hay respuestas en este ticket todavía.</p>
                        @endforelse

                        {{-- Formulario para responder al ticket --}}
                        @if ($abierto && $yo->puede('tickets.responder'))
                            <div class="border-top border-200 pt-4 mt-3">
                                <form method="POST" action="{{ route('tickets.mensajes.store', $t->id_ticket) }}">
                                    @csrf
                                    <h6 class="mb-2 text-1000">Agregar Respuesta o Nota de Seguimiento</h6>
                                    <textarea class="form-control mb-3" name="mensaje" rows="4" placeholder="Escribe tu respuesta para el cliente o nota interna..." required></textarea>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="es_interno" name="es_interno" value="1" />
                                            <label class="form-check-label fs--1 text-700" for="es_interno">Marcar como nota interna (invisible para el cliente)</label>
                                        </div>
                                        <button class="btn btn-primary" type="submit">
                                            <span class="fas fa-paper-plane me-2"></span>Enviar respuesta
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Columna Lateral: Datos del Cliente y Asignación (4 columnas) --}}
            <div class="col-12 col-xl-4">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3 text-1100"><span class="fas fa-user-tie text-primary me-2"></span>Datos del Cliente</h5>
                        <h6 class="mb-1 text-1000 fw-bold">{{ $t->cliente?->razon_social ?? '—' }}</h6>
                        <p class="fs--2 text-600 mb-2">NIT: {{ $t->cliente?->nit ?? 'C/F' }}</p>
                        <div class="border-top border-200 pt-2">
                            <p class="fs--2 text-700 mb-1">Contacto: {{ $t->cliente?->email_principal ?? '—' }}</p>
                            <p class="fs--2 text-700 mb-0">Teléfono: {{ $t->cliente?->telefono_principal ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Reasignación rápida de agente --}}
                @if ($abierto && $yo->puede('tickets.asignar'))
                    <div class="card border border-200 shadow-none mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3 text-1100"><span class="fas fa-user-tag text-info me-2"></span>Reasignar Agente</h5>
                            <form method="POST" action="{{ route('tickets.asignar', $t->id_ticket) }}">
                                @csrf
                                @method('PATCH')
                                <div class="mb-3">
                                    <select class="form-select form-select-sm" name="id_asignado_a" required>
                                        <option value="">Selecciona un agente…</option>
                                        @foreach ($agentes as $a)
                                            <option value="{{ $a->id_empleado }}" @selected((int) old('id_asignado_a', $t->id_asignado_a) === $a->id_empleado)>{{ $a->nombre_completo }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-phoenix-primary btn-sm w-100" type="submit">Actualizar asignado</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
