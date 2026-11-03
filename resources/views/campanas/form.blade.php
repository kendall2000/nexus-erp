@extends('layouts.app', ['titulo' => $c->exists ? 'Editar campaña' : 'Nueva campaña'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $c->{$campo}); @endphp

    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('campanas.index') }}">Campañas</a></li>
            <li class="breadcrumb-item active">{{ $c->exists ? 'Editar: '.$c->nombre : 'Nueva Campaña' }}</li>
        </ol>
    </nav>

    <div class="mb-4">
        <h2 class="mb-1 text-bold text-1100">
            <span class="fas fa-bullhorn text-primary me-2"></span>{{ $c->exists ? 'Editar Campaña de Marketing' : 'Configurar Nueva Campaña' }}
        </h2>
        <p class="text-700 mb-0">Planificación de iniciativas promocionales, presupuestos, metas de captación y canales.</p>
    </div>

    <form method="POST" action="{{ $c->exists ? route('campanas.update', $c->id_campana) : route('campanas.store') }}" class="mb-9">
        @csrf
        @if ($c->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <!-- Columna Principal -->
            <div class="col-12 col-xl-8">
                <!-- Tarjeta 1: Parámetros Básicos -->
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-body-emphasis p-3 border-bottom">
                        <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-bullhorn text-primary me-2"></i>Estrategia e Identificación</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label" for="nombre">Nombre de la Campaña <span class="text-danger">*</span></label>
                                <input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="200" placeholder="Ej.: Black Friday 2026 - Sector Industrial" />
                                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="id_linea">Línea de Negocio / Producto</label>
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_linea" name="id_linea">
                                    <option value="">Línea General</option>
                                    @foreach ($lineas as $l)
                                        <option value="{{ $l->id_linea }}" @selected((int) $v('id_linea') === $l->id_linea)>{{ $l->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="tipo">Tipo / Canal Publicitario</label>
                                <select data-choices="data-choices" class="form-select" id="tipo" name="tipo">
                                    @foreach ($tipos as $k => $n)
                                        <option value="{{ $k }}" @selected($v('tipo') === $k)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="objetivo">Objetivo Primario</label>
                                <select data-choices="data-choices" class="form-select" id="objetivo" name="objetivo">
                                    @foreach ($objetivos as $k => $n)
                                        <option value="{{ $k }}" @selected($v('objetivo') === $k)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="fecha_inicio">Fecha de Inicio <span class="text-danger">*</span></label>
                                <input class="form-control" id="fecha_inicio" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', $c->fecha_inicio?->format('Y-m-d')) }}" required />
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="fecha_fin">Fecha de Finalización</label>
                                <input class="form-control @error('fecha_fin') is-invalid @enderror" id="fecha_fin" name="fecha_fin" type="date" value="{{ old('fecha_fin', $c->fecha_fin?->format('Y-m-d')) }}" />
                                @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="descripcion">Descripción y Alcance de la Campaña</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" maxlength="5000" placeholder="Público objetivo, segmentación geográfica, mensaje clave o enlaces de las piezas publicitarias...">{{ $v('descripcion') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Lateral: Presupuesto y Metas -->
            <div class="col-12 col-xl-4">
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-body-emphasis p-3 border-bottom">
                        <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-coins text-primary me-2"></i>Presupuesto y Metas</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label" for="moneda">Moneda Operativa</label>
                            <select data-choices="data-choices" class="form-select" id="moneda" name="moneda">
                                @foreach ($monedas as $m)
                                    <option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }} ({{ $m->nombre }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="presupuesto">Presupuesto Asignado</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input class="form-control" id="presupuesto" name="presupuesto" type="number" step="0.01" min="0" value="{{ $v('presupuesto') !== null ? (float) $v('presupuesto') : '' }}" placeholder="0.00" />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="meta_leads">Meta de Prospectos (Leads)</label>
                            <div class="input-group">
                                <input class="form-control" id="meta_leads" name="meta_leads" type="number" min="0" value="{{ $v('meta_leads') }}" placeholder="Ej.: 100" />
                                <span class="input-group-text"><i class="fas fa-users"></i></span>
                            </div>
                        </div>

                        @if ($c->exists)
                            <div class="border-top pt-3 mb-3">
                                <label class="form-label fw-bold" for="estado">Estado Actual de Campaña</label>
                                <select data-choices="data-choices" class="form-select" id="estado" name="estado">
                                    @foreach ($estados as $k => [$n])
                                        <option value="{{ $k }}" @selected($v('estado') === $k)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="gasto_real">Gasto Real Ejecutado</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input class="form-control" id="gasto_real" name="gasto_real" type="number" step="0.01" min="0" value="{{ (float) $v('gasto_real') }}" />
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="leads_generados">Prospectos Concretados</label>
                                <input class="form-control" id="leads_generados" name="leads_generados" type="number" min="0" value="{{ $v('leads_generados') }}" />
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary py-2" type="submit">
                        <i class="fas fa-save me-2"></i>{{ $c->exists ? 'Actualizar Campaña' : 'Crear Campaña' }}
                    </button>
                    <a class="btn btn-phoenix-secondary" href="{{ $c->exists ? route('campanas.show', $c->id_campana) : route('campanas.index') }}">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
@endsection
