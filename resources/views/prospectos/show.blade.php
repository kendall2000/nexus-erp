@extends('layouts.app', ['titulo' => 'Detalle del Prospecto: '.$p->nombre_empresa])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('prospectos.editar');
        [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary'];
        [$nombreTemp, $colorTemp] = $temperaturas[$p->temperatura] ?? [$p->temperatura, 'secondary'];
        $abierto = in_array($p->estado, ['NUEVO', 'EN_CONTACTO', 'CALIFICADO', 'NO_CALIFICADO'], true);
        $iniciales = mb_strtoupper(mb_substr($p->nombre_empresa, 0, 2));
    @endphp

    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('prospectos.index') }}">Prospectos</a></li>
            <li class="breadcrumb-item active">{{ $p->nombre_empresa }}</li>
        </ol>
    </nav>

    <!-- Encabezado Lead / Prospecto Phoenix -->
    <div class="card shadow-none border mb-4">
        <div class="card-body p-4">
            <div class="row g-3 justify-content-between align-items-center">
                <div class="col-12 col-md d-flex align-items-center">
                    <div class="avatar avatar-4xl me-3 flex-shrink-0">
                        <div class="avatar-name rounded-circle bg-{{ $colorTemp }}-subtle text-{{ $colorTemp }} fw-bold fs-2">
                            <span>{{ $iniciales }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h2 class="mb-0 text-bold text-1100">{{ $p->nombre_empresa }}</h2>
                            <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--2 px-2 py-1">{{ $nombreEstado }}</span>
                            <span class="badge badge-phoenix badge-phoenix-{{ $colorTemp }} fs--2 px-2 py-1">
                                <i class="fas fa-fire me-1"></i>{{ $nombreTemp }}
                            </span>
                        </div>
                        <p class="text-700 fs--1 mb-0">
                            <i class="fas fa-user-circle me-1 text-primary"></i><strong>{{ $p->nombre_contacto }}</strong>
                            @if ($p->cargo_contacto) <span class="text-muted">({{ $p->cargo_contacto }})</span> @endif
                            @if ($p->email_contacto)
                                <span class="mx-2">·</span><a href="mailto:{{ $p->email_contacto }}" class="text-decoration-none text-700"><i class="far fa-envelope me-1"></i>{{ $p->email_contacto }}</a>
                            @endif
                            @if ($p->telefono_contacto)
                                <span class="mx-2">·</span><a href="tel:{{ $p->telefono_contacto }}" class="text-decoration-none text-700"><i class="fas fa-phone me-1"></i>{{ $p->telefono_contacto }}</a>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                    @if ($abierto && $puedeEditar)
                        <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#convertir">
                            <span class="fas fa-user-check me-2"></span>Convertir a Cliente
                        </button>
                        <a class="btn btn-phoenix-secondary" href="{{ route('prospectos.edit', $p->id_prospecto) }}">
                            <span class="fas fa-pen me-2"></span>Editar
                        </a>
                        <button class="btn btn-phoenix-danger" type="button" data-bs-toggle="collapse" data-bs-target="#descartar">
                            <span class="fas fa-times me-2"></span>Descartar
                        </button>
                    @endif
                    @if ($p->estado === 'CONVERTIDO' && $p->id_cliente_generado && $yo->puede('clientes.ver'))
                        <a class="btn btn-phoenix-primary" href="{{ route('clientes.show', $p->id_cliente_generado) }}">
                            <i class="fas fa-external-link-alt me-2"></i>Ver Expediente de Cliente
                        </a>
                    @endif
                    @if ($yo->puede('prospectos.eliminar') && $p->estado !== 'CONVERTIDO')
                        <form method="POST" action="{{ route('prospectos.destroy', $p->id_prospecto) }}" onsubmit="return confirm(@js('¿Eliminar prospecto '.$p->nombre_empresa.'?'))">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-phoenix-danger px-3" type="submit" title="Eliminar definitivamente">
                                <span class="fas fa-trash"></span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Colapsables: Conversión y Descarte -->
    @if ($abierto && $puedeEditar)
        <div class="collapse {{ $errors->has('id_etapa') || $errors->has('id_responsable') ? 'show' : '' }} mb-4" id="convertir">
            <div class="card border border-success shadow-none bg-success-subtle">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="fas fa-user-check text-success fs-1 me-2"></span>
                        <div>
                            <h5 class="text-success mb-0">Conversión a Cuenta de Cliente Activo</h5>
                            <p class="fs--1 text-800 mb-0">Se creará un registro de cliente permanente en base a los datos de este contacto{{ $yo->puede('oportunidades.crear') ? ', con opción a abrir simultáneamente una oportunidad comercial' : '' }}.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('prospectos.convertir', $p->id_prospecto) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" id="crear_oportunidad" type="checkbox" name="crear_oportunidad" value="1" @checked(old('crear_oportunidad')) />
                                <label class="form-check-label fw-bold fs--1" for="crear_oportunidad">Generar Oportunidad</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs--2 mb-1">Etapa de la oportunidad</label>
                            <select class="form-select form-select-sm @error('id_etapa') is-invalid @enderror" name="id_etapa">
                                <option value="">Selecciona etapa...</option>
                                @foreach ($etapas as $e)
                                    <option value="{{ $e->id_etapa }}">{{ $e->nombre }} ({{ $e->probabilidad_cierre }} %)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs--2 mb-1">Ejecutivo responsable</label>
                            <select class="form-select form-select-sm @error('id_responsable') is-invalid @enderror" name="id_responsable">
                                <option value="">Selecciona vendedor...</option>
                                @foreach ($vendedores as $v)
                                    <option value="{{ $v->id_empleado }}" @selected($p->id_asignado_a === $v->id_empleado)>{{ $v->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-success btn-sm w-100" type="submit">
                                <i class="fas fa-check me-1"></i>Confirmar Conversión
                            </button>
                        </div>
                        @if ($errors->has('id_etapa') || $errors->has('id_responsable'))
                            <div class="col-12 text-danger fs--2 mt-1">{{ $errors->first('id_etapa') ?: $errors->first('id_responsable') }}</div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="collapse {{ $errors->has('motivo_descarte') ? 'show' : '' }} mb-4" id="descartar">
            <div class="card border border-danger shadow-none bg-danger-subtle">
                <div class="card-body">
                    <h6 class="text-danger mb-2"><i class="fas fa-exclamation-triangle me-1"></i>Descartar Prospecto Comercial</h6>
                    <form method="POST" action="{{ route('prospectos.descartar', $p->id_prospecto) }}" class="row g-2">
                        @csrf
                        @method('PATCH')
                        <div class="col-md-9">
                            <input class="form-control form-control-sm @error('motivo_descarte') is-invalid @enderror" name="motivo_descarte" value="{{ old('motivo_descarte') }}" maxlength="300" placeholder="Describe brevemente la razón de descarte (ej. Fuera de presupuesto, sin respuesta, no califica)..." required />
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-danger btn-sm w-100" type="submit">Confirmar Descarte</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Métricas del Lead -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100 border-start border-4 border-primary shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Presupuesto Estimado</h6>
                            <h3 class="fw-bolder mb-1 text-1000">{{ $p->presupuesto_estimado !== null ? $p->moneda.' '.number_format((float) $p->presupuesto_estimado, 2) : 'Sin estimación' }}</h3>
                            <span class="fs--2 text-muted"><i class="fas fa-bullhorn me-1"></i>Fuente: {{ $p->fuente?->nombre ?? 'Directo / Orgánico' }}</span>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-primary-subtle text-primary fs-1">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100 border-start border-4 border-warning shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Calificación de Lead (Score)</h6>
                            <h3 class="fw-bolder mb-1 text-warning">{{ $p->puntuacion_lead }} <span class="fs-0 text-700">/ 100</span></h3>
                            <span class="badge badge-phoenix badge-phoenix-{{ $colorTemp }} fs--2">
                                <i class="fas fa-thermometer-half me-1"></i>Temperatura {{ $nombreTemp }}
                            </span>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-warning-subtle text-warning fs-1">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-12 col-xl-4">
            <div class="card h-100 border-start border-4 border-info shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Actividad Registrada</h6>
                            <h3 class="fw-bolder mb-1 text-info">{{ count($seguimientos) }} <span class="fs-0 text-700">interacciones</span></h3>
                            <span class="fs--2 text-muted"><i class="fas fa-user-tie me-1"></i>Vendedor: {{ $p->asignadoA?->nombre_completo ?? 'Sin asignar' }}</span>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-info-subtle text-info fs-1">
                                <i class="fas fa-comments"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal: Timeline y Panel Lateral -->
    <div class="row g-4">
        <!-- Columna de Timeline e Interacciones -->
        <div class="col-12 col-lg-8">
            @if ($abierto && $puedeEditar)
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-body-emphasis p-3 border-bottom">
                        <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-plus-circle text-primary me-2"></i>Registrar Seguimiento Comercial</h5>
                    </div>
                    <div class="card-body p-3">
                        @if ($errors->seguimiento->any())
                            <div class="alert alert-subtle-danger py-2 fs--2 mb-3">{{ $errors->seguimiento->first() }}</div>
                        @endif
                        <form method="POST" action="{{ route('prospectos.seguimiento', $p->id_prospecto) }}" class="row g-2">
                            @csrf
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label fs--2 mb-1">Canal</label>
                                <select class="form-select form-select-sm" name="tipo">
                                    @foreach ($tipos as $k => $n)
                                        <option value="{{ $k }}" @selected(old('tipo') === $k)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label fs--2 mb-1">Resultado</label>
                                <select class="form-select form-select-sm" name="resultado">
                                    @foreach ($resultados as $k => $n)
                                        <option value="{{ $k }}" @selected(old('resultado') === $k)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label fs--2 mb-1">Fecha y Hora</label>
                                <input class="form-control form-control-sm" type="datetime-local" name="fecha_hora" value="{{ old('fecha_hora', now()->format('Y-m-d\TH:i')) }}" required />
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label fs--2 mb-1">Ejecutivo</label>
                                <select class="form-select form-select-sm" name="id_realizado_por" required>
                                    <option value="">Selecciona...</option>
                                    @foreach ($vendedores as $v)
                                        <option value="{{ $v->id_empleado }}" @selected((int) old('id_realizado_por', $p->id_asignado_a) === $v->id_empleado)>{{ $v->nombre_completo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fs--2 mb-1">Resumen del Contacto</label>
                                <textarea class="form-control form-control-sm" name="resumen" rows="2" maxlength="5000" placeholder="¿Qué se acordó durante la llamada o reunión?" required>{{ old('resumen') }}</textarea>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fs--2 mb-1">Próxima Acción Acordada</label>
                                <input class="form-control form-control-sm" name="proxima_accion" value="{{ old('proxima_accion') }}" maxlength="300" placeholder="Ej.: Enviar cotización actualizada" />
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fs--2 mb-1">Fecha Próx. Acción</label>
                                <input class="form-control form-control-sm" type="date" name="fecha_proxima_accion" value="{{ old('fecha_proxima_accion') }}" />
                            </div>
                            <div class="col-6 col-md-3 d-flex align-items-end">
                                <button class="btn btn-primary btn-sm w-100" type="submit">
                                    <i class="fas fa-save me-1"></i>Guardar Nota
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Historial estilo Timeline Phoenix -->
            <div class="card shadow-none border">
                <div class="card-header bg-body-emphasis p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-history text-primary me-2"></i>Historial de Seguimientos</h5>
                    <span class="badge badge-phoenix badge-phoenix-secondary fs--2">{{ count($seguimientos) }} registros</span>
                </div>
                <div class="card-body p-4">
                    <div class="timeline-vertical timeline-with-details">
                        @forelse ($seguimientos as $s)
                            <div class="timeline-item position-relative mb-4">
                                <div class="row g-md-3">
                                    <div class="col-12 col-md-auto d-flex align-items-start">
                                        <div class="timeline-item-date text-end me-3 text-700 fs--2" style="min-width: 80px;">
                                            <span class="d-block fw-bold text-900">{{ $s->fecha_hora?->format('d M') }}</span>
                                            <span>{{ $s->fecha_hora?->format('H:i') }}</span>
                                        </div>
                                        <div class="timeline-item-bar position-relative me-3">
                                            <div class="icon-item icon-item-sm rounded-circle bg-primary-subtle text-primary border border-2 border-white">
                                                <i class="fas fa-comment"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="timeline-item-content card border bg-body-tertiary shadow-none p-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="fw-bold text-1000 fs--1">
                                                    {{ $tipos[$s->tipo] ?? $s->tipo }}
                                                    <span class="badge badge-phoenix badge-phoenix-info ms-2 fs--2">{{ $resultados[$s->resultado] ?? $s->resultado }}</span>
                                                </div>
                                                <span class="fs--2 text-muted"><i class="fas fa-user-circle me-1"></i>{{ $s->realizadoPor?->nombre_completo }}</span>
                                            </div>
                                            <p class="fs--1 text-800 mb-2" style="white-space: pre-line">{{ $s->resumen }}</p>
                                            @if ($s->proxima_accion)
                                                <div class="border-top pt-2 mt-2 fs--2 text-primary fw-semi-bold">
                                                    <i class="fas fa-arrow-circle-right me-1"></i>Próximo compromiso: {{ $s->proxima_accion }}
                                                    @if ($s->fecha_proxima_accion)
                                                        <span class="badge badge-phoenix badge-phoenix-warning ms-1">{{ $s->fecha_proxima_accion->format('d/m/Y') }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-comments fs-2 text-300 d-block mb-2"></i>
                                <span class="fs--1">Aún no se han registrado bitácoras de llamada o reunión con este prospecto.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna de Información Detallada del Prospecto -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-none border mb-4">
                <div class="card-header bg-body-emphasis p-3 border-bottom">
                    <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-info-circle text-primary me-2"></i>Ficha del Contacto</h5>
                </div>
                <div class="card-body p-3 fs--1">
                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-700 py-1" style="width: 40%;"><i class="fas fa-bullhorn text-400 me-2"></i>Fuente:</td>
                                <td class="text-900 fw-semi-bold py-1">{{ $p->fuente?->nombre ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-700 py-1"><i class="fas fa-industry text-400 me-2"></i>Industria:</td>
                                <td class="text-900 fw-semi-bold py-1">{{ $p->industria?->nombre ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-700 py-1"><i class="fas fa-globe text-400 me-2"></i>País:</td>
                                <td class="text-900 fw-semi-bold py-1">{{ $p->pais?->nombre ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-700 py-1"><i class="fas fa-users text-400 me-2"></i>Empleados:</td>
                                <td class="text-900 fw-semi-bold py-1">{{ $p->empleados_estimados ?? '—' }}</td>
                            </tr>
                            @if ($p->sitio_web)
                                <tr>
                                    <td class="text-700 py-1"><i class="fas fa-link text-400 me-2"></i>Sitio Web:</td>
                                    <td class="py-1"><a href="{{ Str::startsWith($p->sitio_web, 'http') ? $p->sitio_web : 'https://'.$p->sitio_web }}" target="_blank" class="text-primary text-decoration-none">{{ $p->sitio_web }}</a></td>
                                </tr>
                            @endif
                            @if ($p->whatsapp_contacto)
                                <tr>
                                    <td class="text-700 py-1"><i class="fab fa-whatsapp text-success me-2"></i>WhatsApp:</td>
                                    <td class="py-1"><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $p->whatsapp_contacto) }}" target="_blank" class="text-success text-decoration-none fw-semi-bold">{{ $p->whatsapp_contacto }}</a></td>
                                </tr>
                            @endif
                            @if ($p->interes_servicio)
                                <tr>
                                    <td class="text-700 py-1 align-top"><i class="fas fa-tag text-400 me-2"></i>Interés:</td>
                                    <td class="text-900 py-1">{{ $p->interes_servicio }}</td>
                                </tr>
                            @endif
                            @if ($p->motivo_descarte)
                                <tr>
                                    <td class="text-danger py-1 align-top"><i class="fas fa-times-circle text-danger me-2"></i>Descarte:</td>
                                    <td class="text-danger py-1">{{ $p->motivo_descarte }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    @if ($p->notas)
                        <div class="mt-3 pt-3 border-top">
                            <h6 class="fs--2 text-700 text-uppercase fw-bold mb-1">Notas Internas:</h6>
                            <p class="fs--2 text-800 mb-0 bg-body-tertiary p-2 rounded">{{ $p->notas }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Oportunidades vinculadas -->
            @if ($p->oportunidades->isNotEmpty())
                <div class="card shadow-none border">
                    <div class="card-header bg-body-emphasis p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-funnel-dollar text-success me-2"></i>Pipeline Asociado</h5>
                        <span class="badge badge-phoenix badge-phoenix-success fs--2">{{ $p->oportunidades->count() }}</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="list-group list-group-flush">
                            @foreach ($p->oportunidades as $o)
                                <div class="list-group-item px-0 py-2 border-dashed">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            @if ($yo->puede('oportunidades.ver'))
                                                <a class="fw-bold text-primary text-decoration-none fs--1" href="{{ route('oportunidades.show', $o->id_oportunidad) }}">{{ $o->nombre }}</a>
                                            @else
                                                <span class="fw-bold fs--1 text-900">{{ $o->nombre }}</span>
                                            @endif
                                            <div class="fs--2 text-muted">{{ $o->etapa?->nombre }} ({{ $o->probabilidad_cierre }}%)</div>
                                        </div>
                                        <div class="text-end">
                                            <span class="fw-bold text-success fs--1">${{ number_format((float) $o->valor_estimado, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
