{{-- Formulario de contacto (nuevo o existente). Tras un error se reabre con lo escrito solo el formulario que se envió. --}}
@php
    $propio = $errors->contacto->any() && (int) old('id_contacto') === (int) $contacto->id_contacto;
    $v = fn (string $campo) => $propio ? old($campo) : $contacto->{$campo};
@endphp
<form method="POST" action="{{ $accion }}" class="row g-2 mt-1">
    @csrf
    @if ($contacto->exists)
        @method('PUT')
    @endif
    <input type="hidden" name="id_contacto" value="{{ $contacto->id_contacto }}" />
    <div class="col-md-6"><input class="form-control form-control-sm" name="nombre" value="{{ $v('nombre') }}" placeholder="Nombre" required maxlength="200" /></div>
    <div class="col-md-6"><input class="form-control form-control-sm" name="cargo" value="{{ $v('cargo') }}" placeholder="Cargo" maxlength="150" /></div>
    <div class="col-md-6"><input class="form-control form-control-sm" name="email" type="email" value="{{ $v('email') }}" placeholder="Correo" maxlength="150" /></div>
    <div class="col-md-3"><input class="form-control form-control-sm" name="telefono" value="{{ $v('telefono') }}" placeholder="Teléfono" maxlength="20" /></div>
    <div class="col-md-3"><input class="form-control form-control-sm" name="whatsapp" value="{{ $v('whatsapp') }}" placeholder="WhatsApp" maxlength="20" /></div>
    <div class="col-12 d-flex flex-wrap gap-3 align-items-center">
        <div class="form-check mb-0">
            <input class="form-check-input" id="principal-{{ $contacto->id_contacto ?? 'nuevo' }}" name="es_contacto_principal" type="checkbox" value="1" @checked($v('es_contacto_principal')) />
            <label class="form-check-label" for="principal-{{ $contacto->id_contacto ?? 'nuevo' }}">Principal</label>
        </div>
        <div class="form-check mb-0">
            <input class="form-check-input" id="facturas-{{ $contacto->id_contacto ?? 'nuevo' }}" name="recibe_facturas" type="checkbox" value="1" @checked($v('recibe_facturas')) />
            <label class="form-check-label" for="facturas-{{ $contacto->id_contacto ?? 'nuevo' }}">Recibe facturas</label>
        </div>
        <button class="btn btn-primary btn-sm ms-auto" type="submit">Guardar contacto</button>
    </div>
</form>
