@extends('layouts.app', ['titulo' => $proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $proveedor->{$campo}); @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
            <li class="breadcrumb-item active">{{ $proveedor->exists ? $proveedor->razon_social : 'Nuevo' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor' }}</h2>

    <div class="card" style="max-width: 52rem">
        <div class="card-body">
            <form method="POST" action="{{ $proveedor->exists ? route('proveedores.update', $proveedor->id_proveedor) : route('proveedores.store') }}">
                @csrf
                @if ($proveedor->exists)
                    @method('PUT')
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-md-7"><label class="form-label" for="razon_social">Razón social</label><input class="form-control" id="razon_social" name="razon_social" value="{{ $v('razon_social') }}" required maxlength="250" /></div>
                    <div class="col-md-5"><label class="form-label" for="nombre_comercial">Nombre comercial</label><input class="form-control" id="nombre_comercial" name="nombre_comercial" value="{{ $v('nombre_comercial') }}" maxlength="150" /></div>
                    <div class="col-md-4"><label class="form-label" for="nit">NIT</label><input class="form-control text-uppercase" id="nit" name="nit" value="{{ $v('nit') }}" maxlength="20" /></div>
                    <div class="col-md-4">
                        <label class="form-label" for="tipo_proveedor">Tipo</label>
                        <select class="form-select" id="tipo_proveedor" name="tipo_proveedor" required>
                            @foreach ($tipos as $codigo => $nombre)<option value="{{ $codigo }}" @selected($v('tipo_proveedor') === $codigo)>{{ $nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_pais">País</label>
                        <select class="form-select" id="id_pais" name="id_pais" required>
                            <option value="">Selecciona…</option>
                            @foreach ($paises as $p)<option value="{{ $p->id_pais }}" @selected((int) $v('id_pais') === $p->id_pais)>{{ $p->nombre }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <h5 class="mb-3 text-800 border-top border-200 pt-3">Contacto</h5>
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><label class="form-label" for="contacto">Persona de contacto</label><input class="form-control" id="contacto" name="contacto" value="{{ $v('contacto') }}" maxlength="200" /></div>
                    <div class="col-md-4"><label class="form-label" for="email">Correo</label><input class="form-control" id="email" name="email" type="email" value="{{ $v('email') }}" maxlength="150" /></div>
                    <div class="col-md-4"><label class="form-label" for="telefono">Teléfono</label><input class="form-control" id="telefono" name="telefono" value="{{ $v('telefono') }}" maxlength="20" /></div>
                    <div class="col-12"><label class="form-label" for="direccion">Dirección</label><input class="form-control" id="direccion" name="direccion" value="{{ $v('direccion') }}" maxlength="300" /></div>
                </div>
                <h5 class="mb-3 text-800 border-top border-200 pt-3">Condiciones de pago</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><label class="form-label" for="dias_credito">Días de crédito</label><input class="form-control" id="dias_credito" name="dias_credito" type="number" min="0" max="255" value="{{ $v('dias_credito') }}" required /></div>
                    <div class="col-md-4">
                        <label class="form-label" for="moneda_pago">Moneda de pago</label>
                        <select class="form-select" id="moneda_pago" name="moneda_pago" required>
                            @foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda_pago') === $m->codigo)>{{ $m->codigo }} — {{ $m->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked($v('activo')) />
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('proveedores.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
