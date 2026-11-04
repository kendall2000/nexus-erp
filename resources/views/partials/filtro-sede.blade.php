{{-- Filtro de listados por sucursal o agencia: $sedes (Sucursal::paraSelect) y $valor (?sucursal=). --}}
@if ($sedes->isNotEmpty())
    <div class="btn-group position-static text-nowrap">
        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="sucursal" onchange="this.form.submit()" aria-label="Sucursal">
            <option value="">Todas las sucursales</option>
            @foreach ($sedes as $s)
                <option value="{{ $s->id_sucursal }}" @selected((string) ($valor ?? '') === (string) $s->id_sucursal)>{{ $s->etiqueta() }}</option>
            @endforeach
            <option value="{{ \App\Support\Sedes::SIN_SEDE }}" @selected(($valor ?? '') === \App\Support\Sedes::SIN_SEDE)>Sin sucursal</option>
        </select>
    </div>
@endif
