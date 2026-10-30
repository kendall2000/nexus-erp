{{-- Formulario de cargo (nuevo o existente); tras un error se rellena solo el que se envió. --}}
@php
    $propio = $errors->cargo->any() && (int) old('id') === (int) $c->id_cargo;
    $v = fn (string $campo) => $propio ? old($campo) : $c->{$campo};
@endphp
<form method="POST" action="{{ $c->exists ? route('organizacion.cargos.update', $c->id_cargo) : route('organizacion.cargos.store') }}" class="row g-2 mt-1">
    @csrf
    @if ($c->exists)
        @method('PUT')
    @endif
    <input type="hidden" name="id" value="{{ $c->id_cargo }}" />
    <div class="col-md-6"><input class="form-control form-control-sm" name="nombre" value="{{ $v('nombre') }}" placeholder="Nombre del cargo" required maxlength="150" /></div>
    <div class="col-md-3">
        <select class="form-select form-select-sm" name="nivel_jerarquico" title="Nivel">
            @foreach ($niveles as $n => $nombre)<option value="{{ $n }}" @selected((int) $v('nivel_jerarquico') === $n)>{{ $nombre }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select form-select-sm" name="id_depto_org" title="Departamento">
            <option value="">Sin departamento</option>
            @foreach ($departamentos as $d)<option value="{{ $d->id_depto_org }}" @selected((int) $v('id_depto_org') === $d->id_depto_org)>{{ $d->nombre }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-4"><input class="form-control form-control-sm" name="salario_min" type="number" step="0.01" min="0" value="{{ $v('salario_min') !== null ? (float) $v('salario_min') : '' }}" placeholder="Salario mínimo" /></div>
    <div class="col-md-4"><input class="form-control form-control-sm" name="salario_max" type="number" step="0.01" min="0" value="{{ $v('salario_max') !== null ? (float) $v('salario_max') : '' }}" placeholder="Salario máximo" /></div>
    <div class="col-md-4">
        <select class="form-select form-select-sm" name="moneda">@foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>@endforeach</select>
    </div>
    <div class="col-12"><input class="form-control form-control-sm" name="descripcion" value="{{ $v('descripcion') }}" placeholder="Funciones (opcional)" maxlength="2000" /></div>
    <div class="col-12 d-flex align-items-center gap-3">
        <div class="form-check mb-0">
            <input class="form-check-input" id="cargo-vehiculo-{{ $c->id_cargo ?? 'nuevo' }}" name="requiere_vehiculo" type="checkbox" value="1" @checked($v('requiere_vehiculo')) />
            <label class="form-check-label" for="cargo-vehiculo-{{ $c->id_cargo ?? 'nuevo' }}">Requiere vehículo</label>
        </div>
        <input type="hidden" name="activo" value="0" />
        <div class="form-check mb-0">
            <input class="form-check-input" id="cargo-activo-{{ $c->id_cargo ?? 'nuevo' }}" name="activo" type="checkbox" value="1" @checked($v('activo')) />
            <label class="form-check-label" for="cargo-activo-{{ $c->id_cargo ?? 'nuevo' }}">Activo</label>
        </div>
        <button class="btn btn-primary btn-sm ms-auto" type="submit">Guardar</button>
    </div>
</form>
@if ($c->exists && ! $c->empleados_count)
    <form method="POST" action="{{ route('organizacion.cargos.destroy', $c->id_cargo) }}" class="text-end mt-1" onsubmit="return confirm(@js('¿Eliminar el cargo '.$c->nombre.'?'))">
        @csrf
        @method('DELETE')
        <button class="btn btn-link text-danger btn-sm p-0" type="submit">Eliminar</button>
    </form>
@endif
