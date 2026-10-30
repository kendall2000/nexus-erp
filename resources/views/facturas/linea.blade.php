{{-- Una línea editable de la factura ($i = índice, $l = datos). --}}
<tr>
    <td>
        <select class="form-select form-select-sm servicio" name="lineas[{{ $i }}][id_tipo_servicio]">
            <option value="">Servicio (opcional)…</option>
            @foreach ($servicios as $s)
                <option value="{{ $s->id_tipo_servicio }}" @selected((int) ($l['id_tipo_servicio'] ?? 0) === $s->id_tipo_servicio)>{{ $s->nombre }} ({{ $s->lineaNegocio?->nombre }})</option>
            @endforeach
        </select>
        <input class="form-control form-control-sm mt-1 descripcion" name="lineas[{{ $i }}][descripcion]" value="{{ $l['descripcion'] ?? '' }}" maxlength="300" placeholder="Descripción" />
    </td>
    <td><input class="form-control form-control-sm text-end cantidad" name="lineas[{{ $i }}][cantidad]" type="number" step="0.01" min="0" value="{{ $l['cantidad'] ?? 1 }}" /></td>
    <td><input class="form-control form-control-sm text-end precio" name="lineas[{{ $i }}][precio_unitario]" type="number" step="0.0001" min="0" value="{{ $l['precio_unitario'] ?? '' }}" /></td>
    <td><input class="form-control form-control-sm text-end descuento" name="lineas[{{ $i }}][descuento]" type="number" step="0.01" min="0" value="{{ $l['descuento'] ?? 0 }}" /></td>
    <td class="text-center">
        <input type="hidden" name="lineas[{{ $i }}][es_afecto_iva]" value="0" />
        <input class="form-check-input afecto" type="checkbox" name="lineas[{{ $i }}][es_afecto_iva]" value="1" @checked((bool) ($l['es_afecto_iva'] ?? true)) title="Afecta a IVA" />
    </td>
    <td>
        <select class="form-select form-select-sm mb-1" name="lineas[{{ $i }}][id_centro]" title="Centro de costo">
            <option value="">Centro: el del servicio</option>
            @foreach ($centros as $c)<option value="{{ $c->id_centro }}" @selected((int) ($l['id_centro'] ?? 0) === $c->id_centro)>{{ $c->etiqueta }}</option>@endforeach
        </select>
        <select class="form-select form-select-sm" name="lineas[{{ $i }}][id_cuenta]" title="Cuenta de ingreso">
            <option value="">Cuenta: la del servicio</option>
            @foreach ($cuentas as $c)<option value="{{ $c->id_cuenta }}" @selected((int) ($l['id_cuenta'] ?? 0) === $c->id_cuenta)>{{ $c->etiqueta }}</option>@endforeach
        </select>
    </td>
    <td class="text-end fw-semi-bold importe">0.00</td>
    <td class="text-end"><button class="btn btn-link text-danger p-0 quitar-linea" type="button" title="Quitar"><span class="fas fa-times"></span></button></td>
</tr>
