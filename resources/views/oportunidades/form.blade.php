@extends('layouts.app', ['titulo' => $o->exists ? 'Editar oportunidad' : 'Nueva oportunidad'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $o->{$campo}); @endphp

    <x-page-header
        :titulo="$o->exists ? 'Editar oportunidad' : 'Nueva oportunidad'"
        icono="fas fa-funnel-dollar"
        subtitulo="Gestiona el embudo comercial, valor estimado y probabilidad de cierre."
        :migas="[
            ['label' => 'Oportunidades', 'url' => route('oportunidades.index')],
            ['label' => $o->exists ? $o->nombre : 'Nueva'],
        ]"
    />

    <form method="POST" action="{{ $o->exists ? route('oportunidades.update', $o->id_oportunidad) : route('oportunidades.store') }}" class="mb-9">
        @csrf
        @if ($o->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            {{-- Columna Principal: Datos de la Oportunidad y Proyección (8 cols) --}}
            <div class="col-12 col-xl-8">
                {{-- Tarjeta 1: Información Principal --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-body-emphasis border-bottom border-200 py-3 px-4">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-handshake text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Datos de la oportunidad</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="nombre">Nombre de la oportunidad <span class="text-danger">*</span></label>
                                <input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="200" placeholder="Ej.: Servicio de limpieza de oficinas corporativas" />
                                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="id_cliente">Cliente</label>
                                <select class="form-select @error('id_cliente') is-invalid @enderror" id="id_cliente" name="id_cliente" data-choices='{"searchEnabled":true,"itemSelectText":""}'>
                                    <option value="">— Ninguno —</option>
                                    @foreach ($clientes as $c)
                                        <option value="{{ $c->id_cliente }}" @selected((int) $v('id_cliente') === $c->id_cliente)>{{ $c->razon_social }}</option>
                                    @endforeach
                                </select>
                                @error('id_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="id_prospecto">o Prospecto</label>
                                <select class="form-select" id="id_prospecto" name="id_prospecto" data-choices='{"searchEnabled":true,"itemSelectText":""}'>
                                    <option value="">— Ninguno —</option>
                                    @foreach ($prospectos as $p)
                                        <option value="{{ $p->id_prospecto }}" @selected((int) $v('id_prospecto') === $p->id_prospecto)>{{ $p->nombre_empresa }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="id_responsable">Vendedor / Responsable <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_responsable') is-invalid @enderror" id="id_responsable" name="id_responsable" required data-choices='{"searchEnabled":true,"itemSelectText":""}'>
                                    <option value="">Selecciona…</option>
                                    @foreach ($vendedores as $ve)
                                        <option value="{{ $ve->id_empleado }}" @selected((int) $v('id_responsable') === $ve->id_empleado)>{{ $ve->nombre_completo }}</option>
                                    @endforeach
                                </select>
                                @error('id_responsable')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="id_linea">Línea de negocio</label>
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_linea" name="id_linea">
                                    <option value="">— General —</option>
                                    @foreach ($lineas as $l)
                                        <option value="{{ $l->id_linea }}" @selected((int) $v('id_linea') === $l->id_linea)>{{ $l->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="descripcion">Descripción y alcance comercial</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" maxlength="5000" placeholder="Detalles de requerimientos, necesidades del cliente...">{{ $v('descripcion') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="competidores">Competidores identificados</label>
                                <input class="form-control" id="competidores" name="competidores" value="{{ $v('competidores') }}" maxlength="300" placeholder="Empresas competidoras en el proceso" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Lateral: Valor, Cierre y Acciones (4 cols) --}}
            <div class="col-12 col-xl-4">
                {{-- Estimación y Probabilidad --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-body-emphasis border-bottom border-200 py-3 px-4">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-chart-line text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Estimación y Cierre</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        @unless ($o->exists)
                            <div class="mb-3">
                                <label class="form-label" for="id_etapa">Etapa inicial <span class="text-danger">*</span></label>
                                <select data-choices="data-choices" class="form-select @error('id_etapa') is-invalid @enderror" id="id_etapa" name="id_etapa" required>
                                    @foreach ($etapas as $e)
                                        <option value="{{ $e->id_etapa }}" data-probabilidad="{{ $e->probabilidad_cierre }}" @selected((int) old('id_etapa') === $e->id_etapa)>{{ $e->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_etapa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endunless

                        <div class="mb-3">
                            <label class="form-label" for="valor_estimado">Valor estimado <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input class="form-control" id="valor_estimado" name="valor_estimado" type="number" step="0.01" min="0" value="{{ $v('valor_estimado') !== null ? (float) $v('valor_estimado') : '' }}" required placeholder="0.00" />
                                <select data-choices="data-choices" class="form-select" id="moneda" name="moneda" style="max-width: 90px;">
                                    @foreach ($monedas as $m)
                                        <option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="probabilidad">Probabilidad estimada (%)</label>
                            <div class="input-group">
                                <input class="form-control" id="probabilidad" name="probabilidad" type="number" min="0" max="100" value="{{ $v('probabilidad') }}" placeholder="De la etapa" />
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="fecha_cierre_estimada">Fecha estimada de cierre</label>
                            <input class="form-control" id="fecha_cierre_estimada" name="fecha_cierre_estimada" type="date" value="{{ old('fecha_cierre_estimada', $o->fecha_cierre_estimada?->format('Y-m-d')) }}" />
                        </div>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="card border border-200 shadow-none">
                    <div class="card-header bg-body-emphasis border-bottom border-200 py-3 px-4">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-sliders-h text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Acciones</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" type="submit">
                                <span class="fas fa-save me-2"></span>Guardar
                            </button>
                            <a class="btn btn-phoenix-secondary" href="{{ $o->exists ? route('oportunidades.show', $o->id_oportunidad) : route('oportunidades.index') }}">
                                <span class="fas fa-times me-2"></span>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
