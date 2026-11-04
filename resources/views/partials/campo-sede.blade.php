{{--
    Sucursal o agencia de un documento: $sedes (Sucursal::paraSelect), $valor y, opcionales, $ayuda y $vacio (texto de la opción vacía).
    Sin sedes registradas no se muestra (el documento queda sin sucursal).
--}}
@if ($sedes->isNotEmpty())
    <label class="form-label" for="id_sucursal">Sucursal o agencia</label>
    <select class="form-select @error('id_sucursal') is-invalid @enderror" id="id_sucursal" name="id_sucursal">
        <option value="">{{ $vacio ?? 'Sin sucursal' }}</option>
        @foreach ($sedes as $s)
            <option value="{{ $s->id_sucursal }}" @selected((int) $valor === $s->id_sucursal)>{{ $s->etiqueta() }}{{ $s->activo ? '' : ' (inactiva)' }}</option>
        @endforeach
    </select>
    @error('id_sucursal')<div class="invalid-feedback">{{ $message }}</div>@enderror
    @isset($ayuda)<div class="form-text">{{ $ayuda }}</div>@endisset
@endif
