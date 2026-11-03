@extends('layouts.app', ['titulo' => $p->exists ? 'Editar prospecto' : 'Nuevo prospecto'])

@section('contenido')
    @php
        $v = fn (string $campo) => old($campo, $p->{$campo});
        $inv = fn (string $campo) => $errors->has($campo) ? 'is-invalid' : '';
    @endphp

    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('prospectos.index') }}">Prospectos</a></li>
            <li class="breadcrumb-item active">{{ $p->exists ? 'Editar: '.$p->nombre_empresa : 'Nuevo Prospecto' }}</li>
        </ol>
    </nav>

    <div class="mb-4">
        <h2 class="mb-1 text-bold text-1100">
            <span class="fas fa-user-plus text-primary me-2"></span>{{ $p->exists ? 'Editar Prospecto Comercial' : 'Registrar Nuevo Prospecto (Lead)' }}
        </h2>
        <p class="text-700 mb-0">Captura de información comercial, perfil de contacto, calificación de temperatura y asignación.</p>
    </div>

    <form method="POST" action="{{ $p->exists ? route('prospectos.update', $p->id_prospecto) : route('prospectos.store') }}" class="mb-9">
        @csrf
        @if ($p->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <!-- Columna Principal -->
            <div class="col-12 col-xl-8">
                <!-- Tarjeta 1: Datos de la Empresa y Contacto -->
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-body-emphasis p-3 border-bottom">
                        <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-building text-primary me-2"></i>Organización y Contacto Directo</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="nombre_empresa">Nombre Comercial de la Empresa <span class="text-danger">*</span></label>
                                <input class="form-control {{ $inv('nombre_empresa') }}" id="nombre_empresa" name="nombre_empresa" value="{{ $v('nombre_empresa') }}" required maxlength="250" placeholder="Ej.: Acero & Construcciones S.A." />
                                @error('nombre_empresa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label" for="id_industria">Sector / Industria</label>
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_industria" name="id_industria">
                                    <option value="">Seleccione...</option>
                                    @foreach ($industrias as $i)
                                        <option value="{{ $i->id_industria }}" @selected((int) $v('id_industria') === $i->id_industria)>{{ $i->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label" for="id_pais">País</label>
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_pais" name="id_pais">
                                    <option value="">Seleccione...</option>
                                    @foreach ($paises as $pa)
                                        <option value="{{ $pa->id_pais }}" @selected((int) $v('id_pais') === $pa->id_pais)>{{ $pa->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="nombre_contacto">Nombre del Contacto <span class="text-danger">*</span></label>
                                <input class="form-control {{ $inv('nombre_contacto') }}" id="nombre_contacto" name="nombre_contacto" value="{{ $v('nombre_contacto') }}" required maxlength="200" placeholder="Ej.: Lic. Carlos Mendizábal" />
                                @error('nombre_contacto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="cargo_contacto">Cargo o Puesto</label>
                                <input class="form-control" id="cargo_contacto" name="cargo_contacto" value="{{ $v('cargo_contacto') }}" maxlength="150" placeholder="Ej.: Director de Compras" />
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="email_contacto">Correo Electrónico <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="far fa-envelope"></i></span>
                                    <input class="form-control {{ $inv('email_contacto') }}" id="email_contacto" name="email_contacto" type="email" value="{{ $v('email_contacto') }}" required maxlength="150" placeholder="contacto@empresa.com" />
                                </div>
                                @error('email_contacto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label" for="telefono_contacto">Teléfono Fijo / PBX</label>
                                <input class="form-control" id="telefono_contacto" name="telefono_contacto" value="{{ $v('telefono_contacto') }}" maxlength="20" placeholder="+502 2222-0000" />
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label" for="whatsapp_contacto">WhatsApp Directo</label>
                                <input class="form-control" id="whatsapp_contacto" name="whatsapp_contacto" value="{{ $v('whatsapp_contacto') }}" maxlength="20" placeholder="+502 5555-0000" />
                            </div>

                            <div class="col-12 col-md-8">
                                <label class="form-label" for="sitio_web">Sitio Web</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                    <input class="form-control {{ $inv('sitio_web') }}" id="sitio_web" name="sitio_web" value="{{ $v('sitio_web') }}" maxlength="200" placeholder="https://www.empresa.com" />
                                </div>
                                @error('sitio_web')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="empleados_estimados">Nº Empleados Estimados</label>
                                <input class="form-control" id="empleados_estimados" name="empleados_estimados" value="{{ $v('empleados_estimados') }}" maxlength="20" placeholder="Ej.: 50 - 150" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 2: Necesidad y Notas Internas -->
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-body-emphasis p-3 border-bottom">
                        <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-clipboard-list text-primary me-2"></i>Interés Comercial y Notas</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="interes_servicio">Interés Específico / Productos o Servicios Buscados</label>
                                <textarea class="form-control" id="interes_servicio" name="interes_servicio" rows="2" maxlength="500" placeholder="Describe brevemente qué solución o producto está solicitando el prospecto...">{{ $v('interes_servicio') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notas">Notas Internas de Calificación</label>
                                <textarea class="form-control" id="notas" name="notas" rows="3" maxlength="2000" placeholder="Comentarios adicionales para el equipo comercial...">{{ $v('notas') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Lateral: Estado, Calificación y Asignación -->
            <div class="col-12 col-xl-4">
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-body-emphasis p-3 border-bottom">
                        <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-sliders-h text-primary me-2"></i>Cualificación & Pipeline</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label" for="id_fuente">Canal / Fuente de Captación</label>
                            <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_fuente" name="id_fuente">
                                <option value="">Sin especificar...</option>
                                @foreach ($fuentes as $f)
                                    <option value="{{ $f->id_fuente }}" @selected((int) $v('id_fuente') === $f->id_fuente)>{{ $f->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="id_asignado_a">Vendedor Asignado</label>
                            <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_asignado_a" name="id_asignado_a">
                                <option value="">Sin asignar (Bolsa general)</option>
                                @foreach ($vendedores as $ve)
                                    <option value="{{ $ve->id_empleado }}" @selected((int) $v('id_asignado_a') === $ve->id_empleado)>{{ $ve->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="temperatura">Temperatura del Lead</label>
                            <select data-choices="data-choices" class="form-select" id="temperatura" name="temperatura">
                                @foreach ($temperaturas as $k => [$n])
                                    <option value="{{ $k }}" @selected($v('temperatura') === $k)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="puntuacion_lead">Puntuación Estimada (0 - 100)</label>
                            <div class="input-group">
                                <input class="form-control" id="puntuacion_lead" name="puntuacion_lead" type="number" min="0" max="100" value="{{ $v('puntuacion_lead') ?? 50 }}" />
                                <span class="input-group-text">pts</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="presupuesto_estimado">Presupuesto Estimado</label>
                            <div class="input-group">
                                <select data-choices="data-choices" class="form-select" name="moneda" style="max-width: 90px;">
                                    <option value="GTQ" @selected($v('moneda') === 'GTQ')>GTQ</option>
                                    <option value="USD" @selected($v('moneda') === 'USD')>USD</option>
                                </select>
                                <input class="form-control" id="presupuesto_estimado" name="presupuesto_estimado" type="number" step="0.01" min="0" value="{{ $v('presupuesto_estimado') }}" placeholder="0.00" />
                            </div>
                        </div>

                        @if ($p->exists)
                            <div class="mb-3">
                                <label class="form-label" for="estado">Estado del Prospecto</label>
                                <select data-choices="data-choices" class="form-select" id="estado" name="estado">
                                    @foreach ($estados as $k => [$n])
                                        <option value="{{ $k }}" @selected($v('estado') === $k)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary py-2" type="submit">
                        <i class="fas fa-save me-2"></i>{{ $p->exists ? 'Actualizar Prospecto' : 'Guardar y Cualificar Lead' }}
                    </button>
                    <a class="btn btn-phoenix-secondary" href="{{ $p->exists ? route('prospectos.show', $p->id_prospecto) : route('prospectos.index') }}">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
@endsection
