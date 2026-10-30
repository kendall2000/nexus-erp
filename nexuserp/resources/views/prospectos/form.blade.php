@extends('layouts.app', ['titulo' => $p->exists ? 'Editar prospecto' : 'Nuevo prospecto'])

@section('contenido')
    @php
        $v = fn (string $campo) => old($campo, $p->{$campo});
        $inv = fn (string $campo) => $errors->has($campo) ? 'is-invalid' : '';
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('prospectos.index') }}">Prospectos</a></li>
            <li class="breadcrumb-item active">{{ $p->exists ? $p->nombre_empresa : 'Nuevo' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $p->exists ? 'Editar prospecto' : 'Nuevo prospecto' }}</h2>

    <form method="POST" action="{{ $p->exists ? route('prospectos.update', $p->id_prospecto) : route('prospectos.store') }}" class="mb-9" style="max-width: 60rem">
        @csrf
        @if ($p->exists)
            @method('PUT')
        @endif
        <div class="card mb-4"><div class="card-body">
            <h5 class="mb-3">Empresa y contacto</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="nombre_empresa">Empresa</label><input class="form-control {{ $inv('nombre_empresa') }}" id="nombre_empresa" name="nombre_empresa" value="{{ $v('nombre_empresa') }}" required maxlength="250" /></div>
                <div class="col-md-3">
                    <label class="form-label" for="id_industria">Industria</label>
                    <select class="form-select" id="id_industria" name="id_industria"><option value="">—</option>@foreach ($industrias as $i)<option value="{{ $i->id_industria }}" @selected((int) $v('id_industria') === $i->id_industria)>{{ $i->nombre }}</option>@endforeach</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="id_pais">País</label>
                    <select class="form-select" id="id_pais" name="id_pais"><option value="">—</option>@foreach ($paises as $pa)<option value="{{ $pa->id_pais }}" @selected((int) $v('id_pais') === $pa->id_pais)>{{ $pa->nombre }}</option>@endforeach</select>
                </div>
                <div class="col-md-4"><label class="form-label" for="nombre_contacto">Contacto</label><input class="form-control {{ $inv('nombre_contacto') }}" id="nombre_contacto" name="nombre_contacto" value="{{ $v('nombre_contacto') }}" required maxlength="200" /></div>
                <div class="col-md-3"><label class="form-label" for="cargo_contacto">Cargo</label><input class="form-control" id="cargo_contacto" name="cargo_contacto" value="{{ $v('cargo_contacto') }}" maxlength="150" /></div>
                <div class="col-md-5">
                    <label class="form-label" for="email_contacto">Correo</label>
                    <input class="form-control {{ $inv('email_contacto') }}" id="email_contacto" name="email_contacto" type="email" value="{{ $v('email_contacto') }}" required maxlength="150" />
                    @error('email_contacto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3"><label class="form-label" for="telefono_contacto">Teléfono</label><input class="form-control" id="telefono_contacto" name="telefono_contacto" value="{{ $v('telefono_contacto') }}" maxlength="20" /></div>
                <div class="col-md-3"><label class="form-label" for="whatsapp_contacto">WhatsApp</label><input class="form-control" id="whatsapp_contacto" name="whatsapp_contacto" value="{{ $v('whatsapp_contacto') }}" maxlength="20" /></div>
                <div class="col-md-4">
                    <label class="form-label" for="sitio_web">Sitio web</label>
                    <input class="form-control {{ $inv('sitio_web') }}" id="sitio_web" name="sitio_web" value="{{ $v('sitio_web') }}" maxlength="200" />
                    @error('sitio_web')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2"><label class="form-label" for="empleados_estimados">Empleados</label><input class="form-control" id="empleados_estimados" name="empleados_estimados" value="{{ $v('empleados_estimados') }}" maxlength="20" placeholder="Ej.: 50-100" /></div>
            </div>
        </div></div>
        <div class="card mb-4"><div class="card-body">
            <h5 class="mb-3">Oportunidad</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="id_fuente">Fuente</label>
                    <select class="form-select" id="id_fuente" name="id_fuente"><option value="">—</option>@foreach ($fuentes as $f)<option value="{{ $f->id_fuente }}" @selected((int) $v('id_fuente') === $f->id_fuente)>{{ $f->nombre }}</option>@endforeach</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="id_asignado_a">Vendedor</label>
                    <select class="form-select" id="id_asignado_a" name="id_asignado_a"><option value="">Sin asignar</option>@foreach ($vendedores as $ve)<option value="{{ $ve->id_empleado }}" @selected((int) $v('id_asignado_a') === $ve->id_empleado)>{{ $ve->nombre_completo }}</option>@endforeach</select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="temperatura">Temperatura</label>
                    <select class="form-select" id="temperatura" name="temperatura">@foreach ($temperaturas as $k => [$n])<option value="{{ $k }}" @selected($v('temperatura') === $k)>{{ $n }}</option>@endforeach</select>
                </div>
                <div class="col-md-2"><label class="form-label" for="puntuacion_lead">Puntuación</label><input class="form-control" id="puntuacion_lead" name="puntuacion_lead" type="number" min="0" max="100" value="{{ $v('puntuacion_lead') }}" /></div>
                @if ($p->exists)
                    <div class="col-md-2">
                        <label class="form-label" for="estado">Estado</label>
                        <select class="form-select" id="estado" name="estado">@foreach ($estados as $k => [$n])<option value="{{ $k }}" @selected($v('estado') === $k)>{{ $n }}</option>@endforeach</select>
                    </div>
                @endif
                <div class="col-md-3"><label class="form-label" for="presupuesto_estimado">Presupuesto estimado</label><input class="form-control" id="presupuesto_estimado" name="presupuesto_estimado" type="number" step="0.01" min="0" value="{{ $v('presupuesto_estimado') !== null ? (float) $v('presupuesto_estimado') : '' }}" /></div>
                <div class="col-md-2">
                    <label class="form-label" for="moneda">Moneda</label>
                    <select class="form-select" id="moneda" name="moneda">@foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>@endforeach</select>
                </div>
                <div class="col-md-7"><label class="form-label" for="interes_servicio">Interés</label><input class="form-control" id="interes_servicio" name="interes_servicio" value="{{ $v('interes_servicio') }}" maxlength="5000" placeholder="Qué servicio busca" /></div>
                <div class="col-12"><label class="form-label" for="notas">Notas</label><textarea class="form-control" id="notas" name="notas" rows="2" maxlength="5000">{{ $v('notas') }}</textarea></div>
            </div>
        </div></div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-phoenix-secondary" href="{{ $p->exists ? route('prospectos.show', $p->id_prospecto) : route('prospectos.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
