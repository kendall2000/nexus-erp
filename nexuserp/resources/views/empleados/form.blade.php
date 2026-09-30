@extends('layouts.app', ['titulo' => $e->exists ? 'Editar empleado' : 'Nuevo empleado'])

@section('contenido')
    @php
        $v = fn (string $campo) => old($campo, $e->{$campo});
        $fecha = fn (string $campo) => old($campo, $e->{$campo}?->format('Y-m-d'));
        $invalido = fn (string $campo) => $errors->has($campo) ? 'is-invalid' : '';
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('empleados.index') }}">Empleados</a></li>
            @if ($e->exists)<li class="breadcrumb-item"><a href="{{ route('empleados.show', $e->id_empleado) }}">{{ $e->nombre_completo }}</a></li>@endif
            <li class="breadcrumb-item active">{{ $e->exists ? 'Editar' : 'Nuevo' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $e->exists ? 'Editar empleado' : 'Nuevo empleado' }}</h2>

    <form method="POST" action="{{ $e->exists ? route('empleados.update', $e->id_empleado) : route('empleados.store') }}" class="mb-9" style="max-width: 64rem">
        @csrf
        @if ($e->exists)
            @method('PUT')
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Datos personales</h5>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label" for="primer_nombre">Primer nombre</label><input class="form-control {{ $invalido('primer_nombre') }}" id="primer_nombre" name="primer_nombre" value="{{ $v('primer_nombre') }}" required maxlength="80" /></div>
                    <div class="col-md-3"><label class="form-label" for="segundo_nombre">Segundo nombre</label><input class="form-control" id="segundo_nombre" name="segundo_nombre" value="{{ $v('segundo_nombre') }}" maxlength="80" /></div>
                    <div class="col-md-3"><label class="form-label" for="primer_apellido">Primer apellido</label><input class="form-control {{ $invalido('primer_apellido') }}" id="primer_apellido" name="primer_apellido" value="{{ $v('primer_apellido') }}" required maxlength="80" /></div>
                    <div class="col-md-3"><label class="form-label" for="segundo_apellido">Segundo apellido</label><input class="form-control" id="segundo_apellido" name="segundo_apellido" value="{{ $v('segundo_apellido') }}" maxlength="80" /></div>
                    <div class="col-md-3"><label class="form-label" for="apellido_casada">Apellido de casada</label><input class="form-control" id="apellido_casada" name="apellido_casada" value="{{ $v('apellido_casada') }}" maxlength="80" placeholder="de …" /></div>
                    <div class="col-md-2">
                        <label class="form-label" for="tipo_doc_id">Documento</label>
                        <select class="form-select" id="tipo_doc_id" name="tipo_doc_id">
                            @foreach (['DPI', 'DUI', 'DNI', 'PASAPORTE', 'OTRO'] as $t)<option value="{{ $t }}" @selected($v('tipo_doc_id') === $t)>{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="dpi_nit">Número</label>
                        <input class="form-control {{ $invalido('dpi_nit') }}" id="dpi_nit" name="dpi_nit" value="{{ $v('dpi_nit') }}" required maxlength="25" />
                        @error('dpi_nit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="nit_personal">NIT</label>
                        <input class="form-control text-uppercase {{ $invalido('nit_personal') }}" id="nit_personal" name="nit_personal" value="{{ $v('nit_personal') }}" maxlength="20" />
                        @error('nit_personal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2"><label class="form-label" for="igss_afiliacion">Afiliación IGSS</label><input class="form-control" id="igss_afiliacion" name="igss_afiliacion" value="{{ $v('igss_afiliacion') }}" maxlength="20" /></div>
                    <div class="col-md-3">
                        <label class="form-label" for="fecha_nacimiento">Nacimiento</label>
                        <input class="form-control {{ $invalido('fecha_nacimiento') }}" id="fecha_nacimiento" name="fecha_nacimiento" type="date" value="{{ $fecha('fecha_nacimiento') }}" />
                        @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="genero">Género</label>
                        <select class="form-select" id="genero" name="genero">
                            <option value="">—</option>
                            @foreach (['M' => 'Masculino', 'F' => 'Femenino', 'OTRO' => 'Otro', 'NO_ESPECIFICA' => 'Prefiere no decir'] as $c => $n)<option value="{{ $c }}" @selected($v('genero') === $c)>{{ $n }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="estado_civil">Estado civil</label>
                        <select class="form-select" id="estado_civil" name="estado_civil">
                            <option value="">—</option>
                            @foreach (['SOLTERO' => 'Soltero(a)', 'CASADO' => 'Casado(a)', 'UNION_LIBRE' => 'Unión de hecho', 'DIVORCIADO' => 'Divorciado(a)', 'VIUDO' => 'Viudo(a)'] as $c => $n)<option value="{{ $c }}" @selected($v('estado_civil') === $c)>{{ $n }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label" for="nacionalidad">Nacionalidad</label><input class="form-control" id="nacionalidad" name="nacionalidad" value="{{ $v('nacionalidad') }}" maxlength="80" /></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Contacto</h5>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="email_corporativo">Correo corporativo</label><input class="form-control {{ $invalido('email_corporativo') }}" id="email_corporativo" name="email_corporativo" type="email" value="{{ $v('email_corporativo') }}" maxlength="150" /></div>
                    <div class="col-md-4"><label class="form-label" for="email_personal">Correo personal</label><input class="form-control {{ $invalido('email_personal') }}" id="email_personal" name="email_personal" type="email" value="{{ $v('email_personal') }}" maxlength="150" /></div>
                    <div class="col-md-4"><label class="form-label" for="telefono_personal">Teléfono</label><input class="form-control" id="telefono_personal" name="telefono_personal" value="{{ $v('telefono_personal') }}" maxlength="20" /></div>
                    <div class="col-md-5"><label class="form-label" for="contacto_emergencia">Contacto de emergencia</label><input class="form-control" id="contacto_emergencia" name="contacto_emergencia" value="{{ $v('contacto_emergencia') }}" maxlength="200" placeholder="Nombre y parentesco" /></div>
                    <div class="col-md-3"><label class="form-label" for="telefono_emergencia">Teléfono de emergencia</label><input class="form-control" id="telefono_emergencia" name="telefono_emergencia" value="{{ $v('telefono_emergencia') }}" maxlength="20" /></div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_municipio">Municipio</label>
                        <select class="form-select" id="id_municipio" name="id_municipio">
                            <option value="">—</option>
                            @foreach ($divisiones as $d)
                                <optgroup label="{{ $d->nombre }}">
                                    @foreach ($municipios->where('id_division', $d->id_division) as $m)<option value="{{ $m->id_municipio }}" @selected((int) $v('id_municipio') === $m->id_municipio)>{{ $m->nombre }}</option>@endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label" for="direccion">Dirección</label><input class="form-control" id="direccion" name="direccion" value="{{ $v('direccion') }}" maxlength="300" /></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Datos laborales</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="codigo_empleado">Código</label>
                        <input class="form-control text-uppercase {{ $invalido('codigo_empleado') }}" id="codigo_empleado" name="codigo_empleado" value="{{ $v('codigo_empleado') }}" maxlength="30" placeholder="{{ $e->exists ? '' : 'Automático' }}" />
                        @error('codigo_empleado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3"><label class="form-label" for="fecha_ingreso">Fecha de ingreso</label><input class="form-control" id="fecha_ingreso" name="fecha_ingreso" type="date" value="{{ $fecha('fecha_ingreso') }}" required /></div>
                    <div class="col-md-3">
                        <label class="form-label" for="tipo_contrato">Tipo de contrato</label>
                        <select class="form-select" id="tipo_contrato" name="tipo_contrato">
                            @foreach ($tipos as $c => $n)<option value="{{ $c }}" @selected($v('tipo_contrato') === $c)>{{ $n }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="modalidad_trabajo">Modalidad</label>
                        <select class="form-select" id="modalidad_trabajo" name="modalidad_trabajo">
                            @foreach (['PRESENCIAL' => 'Presencial', 'REMOTO' => 'Remoto', 'HIBRIDO' => 'Híbrido'] as $c => $n)<option value="{{ $c }}" @selected($v('modalidad_trabajo') === $c)>{{ $n }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_depto_org">Departamento</label>
                        <select class="form-select" id="id_depto_org" name="id_depto_org">
                            <option value="">—</option>
                            @foreach ($departamentos as $d)<option value="{{ $d->id_depto_org }}" @selected((int) $v('id_depto_org') === $d->id_depto_org)>{{ $d->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_cargo">Cargo</label>
                        <select class="form-select" id="id_cargo" name="id_cargo">
                            <option value="">—</option>
                            @foreach ($cargos as $c)<option value="{{ $c->id_cargo }}" @selected((int) $v('id_cargo') === $c->id_cargo)>{{ $c->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_sucursal">Sucursal</label>
                        <select class="form-select" id="id_sucursal" name="id_sucursal">
                            <option value="">—</option>
                            @foreach ($sucursales as $s)<option value="{{ $s->id_sucursal }}" @selected((int) $v('id_sucursal') === $s->id_sucursal)>{{ $s->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="id_supervisor">Jefe inmediato</label>
                        <select class="form-select {{ $invalido('id_supervisor') }}" id="id_supervisor" name="id_supervisor">
                            <option value="">—</option>
                            @foreach ($supervisores as $s)<option value="{{ $s->id_empleado }}" @selected((int) $v('id_supervisor') === $s->id_empleado)>{{ $s->nombre_completo }}</option>@endforeach
                        </select>
                        @error('id_supervisor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if ($e->exists)
                        <div class="col-md-3">
                            <label class="form-label" for="estado">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                @foreach ($estados as $c => [$n])<option value="{{ $c }}" @selected($v('estado') === $c)>{{ $n }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-phoenix-secondary" href="{{ $e->exists ? route('empleados.show', $e->id_empleado) : route('empleados.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
