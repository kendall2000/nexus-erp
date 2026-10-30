{{-- Una línea editable de la orden de compra ($i = índice, $l = datos). --}}
<tr>
    <td>
        <select class="form-select form-select-sm producto" name="lineas[{{ $i }}][id_producto]">
            <option value="">Selecciona…</option>
            @foreach ($productos as $p)
                <option value="{{ $p->id_producto }}" @selected((int) ($l['id_producto'] ?? 0) === $p->id_producto)>{{ $p->codigo }} — {{ $p->nombre }} ({{ $p->unidad_medida }})</option>
            @endforeach
        </select>
        <input class="form-control form-control-sm mt-1" name="lineas[{{ $i }}][descripcion]" value="{{ $l['descripcion'] ?? '' }}" maxlength="300" placeholder="Descripción (opcional)" />
    </td>
    <td><input class="form-control form-control-sm text-end cantidad" name="lineas[{{ $i }}][cantidad_pedida]" type="number" step="0.0001" min="0" value="{{ $l['cantidad_pedida'] ?? 1 }}" /></td>
    <td><input class="form-control form-control-sm text-end precio" name="lineas[{{ $i }}][precio_unitario]" type="number" step="0.0001" min="0" value="{{ $l['precio_unitario'] ?? '' }}" /></td>
    <td><input class="form-control form-control-sm text-end descuento" name="lineas[{{ $i }}][descuento]" type="number" step="0.01" min="0" value="{{ $l['descuento'] ?? 0 }}" /></td>
    <td>
        <select class="form-select form-select-sm mb-1" name="lineas[{{ $i }}][id_centro]" title="Centro de costo">
            <option value="">Centro: el del producto</option>
            @foreach ($centros as $c)<option value="{{ $c->id_centro }}" @selected((int) ($l['id_centro'] ?? 0) === $c->id_centro)>{{ $c->codigo }} — {{ $c->nombre }}</option>@endforeach
        </select>
        <select class="form-select form-select-sm" name="lineas[{{ $i }}][id_cuenta]" title="Cuenta contable">
            <option value="">Cuenta: la del producto</option>
            @foreach ($cuentas as $c)<option value="{{ $c->id_cuenta }}" @selected((int) ($l['id_cuenta'] ?? 0) === $c->id_cuenta)>{{ $c->codigo }} — {{ $c->nombre }}</option>@endforeach
        </select>
        <div class="fs--2 text-600 heredado"></div>
    </td>
    <td class="text-end fw-semi-bold importe">0.00</td>
    <td class="text-end"><button class="btn btn-link text-danger p-0 quitar-linea" type="button" title="Quitar"><span class="fas fa-times"></span></button></td>
</tr>
