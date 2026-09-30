@extends('layouts.app', ['titulo' => $servicio->exists ? 'Editar servicio' : 'Nuevo tipo de servicio'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $servicio->{$campo}); @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('tipos-servicio.index') }}">Tipos de servicio</a></li>
            <li class="breadcrumb-item active">{{ $servicio->exists ? $servicio->nombre : 'Nuevo' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $servicio->exists ? 'Editar servicio' : 'Nuevo tipo de servicio' }}</h2>

    <div class="card" style="max-width: 48rem">
        <div class="card-body">
            <form method="POST" action="{{ $servicio->exists ? route('tipos-servicio.update', $servicio->id_tipo_servicio) : route('tipos-servicio.store') }}">
                @csrf
                @if ($servicio->exists)
                    @method('PUT')
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label" for="id_linea">Línea de negocio</label>
                        <select class="form-select @error('id_linea') is-invalid @enderror" id="id_linea" name="id_linea" required>
                            <option value="">Selecciona…</option>
                            @foreach ($lineas as $l)<option value="{{ $l->id_linea }}" @selected((int) $v('id_linea') === $l->id_linea)>{{ $l->nombre }}</option>@endforeach
                        </select>
                        @error('id_linea')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="150" />
                        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="2" maxlength="2000">{{ $v('descripcion') }}</textarea></div>
                    <div class="col-md-4">
                        <label class="form-label" for="unidad_medida">Se cobra por</label>
                        <input class="form-control text-uppercase" id="unidad_medida" name="unidad_medida" value="{{ $v('unidad_medida') }}" list="unidades" required maxlength="50" />
                        <datalist id="unidades">@foreach ($unidades as $u)<option value="{{ $u }}">@endforeach</datalist>
                    </div>
                    <div class="col-md-4"><label class="form-label" for="precio_base">Precio base</label><input class="form-control" id="precio_base" name="precio_base" type="number" step="0.01" min="0" value="{{ $v('precio_base') !== null ? (float) $v('precio_base') : '' }}" /></div>
                    <div class="col-md-4">
                        <label class="form-label" for="moneda">Moneda</label>
                        <select class="form-select" id="moneda" name="moneda" required>
                            @foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="id_cuenta_ingreso">Cuenta de ingreso</label>
                        <select class="form-select @error('id_cuenta_ingreso') is-invalid @enderror" id="id_cuenta_ingreso" name="id_cuenta_ingreso">
                            <option value="">Sin cuenta</option>
                            @foreach ($cuentas as $c)<option value="{{ $c->id_cuenta }}" @selected((int) $v('id_cuenta_ingreso') === $c->id_cuenta)>{{ $c->etiqueta }}</option>@endforeach
                        </select>
                        @error('id_cuenta_ingreso')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="id_centro_default">Centro de costo</label>
                        <select class="form-select" id="id_centro_default" name="id_centro_default">
                            <option value="">Sin centro</option>
                            @foreach ($centros as $c)<option value="{{ $c->id_centro }}" @selected((int) $v('id_centro_default') === $c->id_centro)>{{ $c->etiqueta }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12 form-text mt-0">Con cuenta y centro, las facturas de este servicio ejecutan el presupuesto de ingresos.</div>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked($v('activo')) />
                    <label class="form-check-label" for="activo">Activo</label>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('tipos-servicio.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
