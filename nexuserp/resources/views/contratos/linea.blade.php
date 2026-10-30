{{-- Una línea editable del contrato ($i = índice, $l = datos). --}}
<tr>
    <td>
        <select class="form-select form-select-sm servicio" name="lineas[{{ $i }}][id_tipo_servicio]">
            <option value="">Servicio…</option>
            @foreach ($servicios as $s)<option value="{{ $s->id_tipo_servicio }}" @selected((int) ($l['id_tipo_servicio'] ?? 0) === $s->id_tipo_servicio)>{{ $s->nombre }} ({{ $s->lineaNegocio?->nombre }})</option>@endforeach
        </select>
        <input class="form-control form-control-sm mt-1" name="lineas[{{ $i }}][descripcion]" value="{{ $l['descripcion'] ?? '' }}" maxlength="300" placeholder="Descripción (opcional)" />
    </td>
    <td>
        <select class="form-select form-select-sm sitio" name="lineas[{{ $i }}][id_sitio]" data-valor="{{ $l['id_sitio'] ?? '' }}"><option value="">Sin sitio</option></select>
    </td>
    <td><input class="form-control form-control-sm text-end cantidad" name="lineas[{{ $i }}][cantidad]" type="number" step="0.01" min="0" value="{{ $l['cantidad'] ?? 1 }}" /></td>
    <td><input class="form-control form-control-sm text-end precio" name="lineas[{{ $i }}][precio_unitario]" type="number" step="0.01" min="0" value="{{ $l['precio_unitario'] ?? '' }}" /></td>
    <td><input class="form-control form-control-sm text-end descuento" name="lineas[{{ $i }}][descuento_pct]" type="number" step="0.01" min="0" max="100" value="{{ $l['descuento_pct'] ?? 0 }}" /></td>
    <td class="text-end fw-semi-bold importe">0.00</td>
    <td class="text-end"><button class="btn btn-link text-danger p-0 quitar-linea" type="button" title="Quitar"><span class="fas fa-times"></span></button></td>
</tr>
