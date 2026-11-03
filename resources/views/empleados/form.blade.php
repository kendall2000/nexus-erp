@extends('layouts.app', ['titulo' => $e->exists ? 'Editar empleado' : 'Nuevo empleado'])

@section('contenido')
    @php
        $v = fn (string $campo) => old($campo, $e->{$campo});
        $fecha = fn (string $campo) => old($campo, $e->{$campo}?->format('Y-m-d'));
        $invalido = fn (string $campo) => $errors->has($campo) ? 'is-invalid' : '';
        $migas = [['label' => 'Empleados', 'url' => route('empleados.index')]];
        if ($e->exists) {
            $migas[] = ['label' => $e->nombre_completo, 'url' => route('empleados.show', $e->id_empleado)];
        }
        $migas[] = ['label' => $e->exists ? 'Editar' : 'Nuevo'];
    @endphp

    <x-page-header
        :titulo="$e->exists ? 'Editar empleado' : 'Nuevo empleado'"
        icono="fas fa-user-tie"
        subtitulo="Registra los datos personales, contacto de emergencia y ubicación organizacional del colaborador."
        :migas="$migas"
    />

    <form method="POST" action="{{ $e->exists ? route('empleados.update', $e->id_empleado) : route('empleados.store') }}" class="mb-9">
        @csrf
        @if ($e->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            {{-- Columna Principal (8 cols) --}}
            <div class="col-12 col-xl-8">
                {{-- Tarjeta 1: Datos Personales --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-body-emphasis border-bottom border-200 py-3 px-4">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-id-card text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Datos personales</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label" for="primer_nombre">Primer nombre <span class="text-danger">*</span></label>
                                <input class="form-control {{ $invalido('primer_nombre') }}" id="primer_nombre" name="primer_nombre" value="{{ $v('primer_nombre') }}" required maxlength="80" />
                                @error('primer_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="segundo_nombre">Segundo nombre</label>
                                <input class="form-control" id="segundo_nombre" name="segundo_nombre" value="{{ $v('segundo_nombre') }}" maxlength="80" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="primer_apellido">Primer apellido <span class="text-danger">*</span></label>
                                <input class="form-control {{ $invalido('primer_apellido') }}" id="primer_apellido" name="primer_apellido" value="{{ $v('primer_apellido') }}" required maxlength="80" />
                                @error('primer_apellido')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="segundo_apellido">Segundo apellido</label>
                                <input class="form-control" id="segundo_apellido" name="segundo_apellido" value="{{ $v('segundo_apellido') }}" maxlength="80" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="apellido_casada">Apellido de casada</label>
                                <input class="form-control" id="apellido_casada" name="apellido_casada" value="{{ $v('apellido_casada') }}" maxlength="80" placeholder="de …" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="tipo_doc_id">Documento <span class="text-danger">*</span></label>
                                <select data-choices="data-choices" class="form-select" id="tipo_doc_id" name="tipo_doc_id">
                                    @foreach (['DPI', 'DUI', 'DNI', 'PASAPORTE', 'OTRO'] as $t)
                                        <option value="{{ $t }}" @selected($v('tipo_doc_id') === $t)>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="dpi_nit">Número de documento <span class="text-danger">*</span></label>
                                <input class="form-control {{ $invalido('dpi_nit') }}" id="dpi_nit" name="dpi_nit" value="{{ $v('dpi_nit') }}" required maxlength="25" />
                                @error('dpi_nit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="nit_personal">NIT</label>
                                <input class="form-control text-uppercase {{ $invalido('nit_personal') }}" id="nit_personal" name="nit_personal" value="{{ $v('nit_personal') }}" maxlength="20" placeholder="Ej.: 1234567-8" />
                                @error('nit_personal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="igss_afiliacion">Afiliación IGSS</label>
                                <input class="form-control" id="igss_afiliacion" name="igss_afiliacion" value="{{ $v('igss_afiliacion') }}" maxlength="20" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label>
                                <input class="form-control {{ $invalido('fecha_nacimiento') }}" id="fecha_nacimiento" name="fecha_nacimiento" type="date" value="{{ $fecha('fecha_nacimiento') }}" />
                                @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="genero">Género</label>
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="genero" name="genero">
                                    <option value="">—</option>
                                    @foreach (['M' => 'Masculino', 'F' => 'Femenino', 'OTRO' => 'Otro', 'NO_ESPECIFICA' => 'Prefiere no decir'] as $c => $n)
                                        <option value="{{ $c }}" @selected($v('genero') === $c)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="estado_civil">Estado civil</label>
                                <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="estado_civil" name="estado_civil">
                                    <option value="">—</option>
                                    @foreach (['SOLTERO' => 'Soltero(a)', 'CASADO' => 'Casado(a)', 'UNION_LIBRE' => 'Unión de hecho', 'DIVORCIADO' => 'Divorciado(a)', 'VIUDO' => 'Viudo(a)'] as $c => $n)
                                        <option value="{{ $c }}" @selected($v('estado_civil') === $c)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="nacionalidad">Nacionalidad</label>
                                <input class="form-control" id="nacionalidad" name="nacionalidad" value="{{ $v('nacionalidad') }}" maxlength="80" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tarjeta 2: Contacto y Residencia --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-body-emphasis border-bottom border-200 py-3 px-4">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-address-book text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Contacto y residencia</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="email_corporativo">Correo corporativo</label>
                                <input class="form-control {{ $invalido('email_corporativo') }}" id="email_corporativo" name="email_corporativo" type="email" value="{{ $v('email_corporativo') }}" maxlength="150" placeholder="nombre@empresa.com" />
                                @error('email_corporativo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="email_personal">Correo personal</label>
                                <input class="form-control {{ $invalido('email_personal') }}" id="email_personal" name="email_personal" type="email" value="{{ $v('email_personal') }}" maxlength="150" />
                                @error('email_personal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="telefono_personal">Teléfono personal</label>
                                <input class="form-control" id="telefono_personal" name="telefono_personal" value="{{ $v('telefono_personal') }}" maxlength="20" placeholder="+502 ..." />
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="contacto_emergencia">Contacto de emergencia</label>
                                <input class="form-control" id="contacto_emergencia" name="contacto_emergencia" value="{{ $v('contacto_emergencia') }}" maxlength="200" placeholder="Nombre completo y parentesco" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="telefono_emergencia">Teléfono de emergencia</label>
                                <input class="form-control" id="telefono_emergencia" name="telefono_emergencia" value="{{ $v('telefono_emergencia') }}" maxlength="20" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="id_municipio">Municipio / Ciudad</label>
                                <select class="form-select" id="id_municipio" name="id_municipio" data-choices='{"searchEnabled":true,"itemSelectText":""}'>
                                    <option value="">— Seleccionar —</option>
                                    @foreach ($divisiones as $d)
                                        <optgroup label="{{ $d->nombre }}">
                                            @foreach ($municipios->where('id_division', $d->id_division) as $m)
                                                <option value="{{ $m->id_municipio }}" @selected((int) $v('id_municipio') === $m->id_municipio)>{{ $m->nombre }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="direccion">Dirección de residencia</label>
                                <input class="form-control" id="direccion" name="direccion" value="{{ $v('direccion') }}" maxlength="300" placeholder="Calle, avenida, zona, número de casa/apartamento..." />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tarjeta 3: Ubicación y Asignación Laboral --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-body-emphasis border-bottom border-200 py-3 px-4">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-briefcase text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Ubicación y asignación laboral</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="id_depto_org">Departamento</label>
                                <select class="form-select" id="id_depto_org" name="id_depto_org" data-choices='{"searchEnabled":true,"itemSelectText":""}'>
                                    <option value="">— Ninguno —</option>
                                    @foreach ($departamentos as $d)
                                        <option value="{{ $d->id_depto_org }}" @selected((int) $v('id_depto_org') === $d->id_depto_org)>{{ $d->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="id_cargo">Cargo / Puesto</label>
                                <select class="form-select" id="id_cargo" name="id_cargo" data-choices='{"searchEnabled":true,"itemSelectText":""}'>
                                    <option value="">— Ninguno —</option>
                                    @foreach ($cargos as $c)
                                        <option value="{{ $c->id_cargo }}" @selected((int) $v('id_cargo') === $c->id_cargo)>{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="id_sucursal">Sucursal</label>
                                <select class="form-select" id="id_sucursal" name="id_sucursal" data-choices='{"searchEnabled":true,"itemSelectText":""}'>
                                    <option value="">— Ninguna —</option>
                                    @foreach ($sucursales as $s)
                                        <option value="{{ $s->id_sucursal }}" @selected((int) $v('id_sucursal') === $s->id_sucursal)>{{ $s->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="id_supervisor">Jefe inmediato / Supervisor</label>
                                <select class="form-select {{ $invalido('id_supervisor') }}" id="id_supervisor" name="id_supervisor" data-choices='{"searchEnabled":true,"itemSelectText":""}'>
                                    <option value="">— Sin supervisor directo —</option>
                                    @foreach ($supervisores as $s)
                                        <option value="{{ $s->id_empleado }}" @selected((int) $v('id_supervisor') === $s->id_empleado)>{{ $s->nombre_completo }}</option>
                                    @endforeach
                                </select>
                                @error('id_supervisor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Lateral Derecha: Datos de Contratación y Acciones (4 cols) --}}
            <div class="col-12 col-xl-4">
                {{-- Tarjeta: Parámetros de Contratación --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-body-emphasis border-bottom border-200 py-3 px-4">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-file-contract text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Contratación y régimen</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="codigo_empleado">Código de empleado</label>
                            <input class="form-control text-uppercase {{ $invalido('codigo_empleado') }}" id="codigo_empleado" name="codigo_empleado" value="{{ $v('codigo_empleado') }}" maxlength="30" placeholder="{{ $e->exists ? '' : 'Automático (Ej: EMP-0001)' }}" />
                            <div class="form-text fs--2">Se autogenera si se deja vacío.</div>
                            @error('codigo_empleado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="fecha_ingreso">Fecha de ingreso <span class="text-danger">*</span></label>
                            <input class="form-control" id="fecha_ingreso" name="fecha_ingreso" type="date" value="{{ $fecha('fecha_ingreso') }}" required />
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="tipo_contrato">Tipo de contrato</label>
                            <select data-choices="data-choices" class="form-select" id="tipo_contrato" name="tipo_contrato">
                                @foreach ($tipos as $c => $n)
                                    <option value="{{ $c }}" @selected($v('tipo_contrato') === $c)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="modalidad_trabajo">Modalidad de trabajo</label>
                            <select data-choices="data-choices" class="form-select" id="modalidad_trabajo" name="modalidad_trabajo">
                                @foreach (['PRESENCIAL' => 'Presencial', 'REMOTO' => 'Remoto', 'HIBRIDO' => 'Híbrido'] as $c => $n)
                                    <option value="{{ $c }}" @selected($v('modalidad_trabajo') === $c)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="border-top border-200 pt-3 mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" id="es_rotativo" name="es_rotativo" type="checkbox" value="1" @checked($v('es_rotativo')) />
                                <label class="form-check-label fw-semi-bold" for="es_rotativo">Personal rotativo</label>
                            </div>
                            <p class="fs--2 text-600 mb-2">Cubre turnos a otros colaboradores y cobra por día.</p>
                            
                            <label class="form-label fs--1" for="tarifa_dia">Tarifa por día</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Q</span>
                                <input class="form-control {{ $invalido('tarifa_dia') }}" id="tarifa_dia" name="tarifa_dia" type="number" step="0.01" min="0.01" value="{{ $v('tarifa_dia') !== null ? (float) $v('tarifa_dia') : '' }}" placeholder="0.00" />
                            </div>
                            @error('tarifa_dia')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        @if ($e->exists)
                            <div class="border-top border-200 pt-3">
                                <label class="form-label" for="estado">Estado del colaborador</label>
                                <select data-choices="data-choices" class="form-select" id="estado" name="estado">
                                    @foreach ($estados as $c => [$n])
                                        <option value="{{ $c }}" @selected($v('estado') === $c)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tarjeta: Acciones y Resumen --}}
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
                                <span class="fas fa-save me-2"></span>{{ $e->exists ? 'Guardar Cambios' : 'Registrar Empleado' }}
                            </button>
                            <a class="btn btn-phoenix-secondary" href="{{ $e->exists ? route('empleados.show', $e->id_empleado) : route('empleados.index') }}">
                                <span class="fas fa-times me-2"></span>Cancelar
                            </a>
                        </div>
                        @if ($e->exists)
                            <div class="border-top border-200 mt-4 pt-3 text-700 fs--2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Registrado el:</span>
                                    <span class="fw-semi-bold">{{ $e->created_at ? $e->created_at->format('d/m/Y H:i') : 'N/A' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Antigüedad:</span>
                                    <span class="fw-semi-bold">{{ $e->antiguedad_anios }} año(s)</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
