{{-- Formulario de departamento (nuevo o existente); tras un error se rellena solo el que se envió. --}}
@php
    $propio = $errors->departamento->any() && (int) old('id') === (int) $d->id_depto_org;
    $v = fn (string $campo) => $propio ? old($campo) : $d->{$campo};
@endphp
<form method="POST" action="{{ $d->exists ? route('organizacion.departamentos.update', $d->id_depto_org) : route('organizacion.departamentos.store') }}" class="row g-2 mt-1">
    @csrf
    @if ($d->exists)
        @method('PUT')
    @endif
    <input type="hidden" name="id" value="{{ $d->id_depto_org }}" />
    <div class="col-md-8"><input class="form-control form-control-sm" name="nombre" value="{{ $v('nombre') }}" placeholder="Nombre" required maxlength="150" /></div>
    <div class="col-md-4"><input class="form-control form-control-sm text-uppercase" name="codigo" value="{{ $v('codigo') }}" placeholder="Código" maxlength="20" /></div>
    <div class="col-md-6">
        <select class="form-select form-select-sm" name="id_padre" title="Depende de">
            <option value="">Sin departamento superior</option>
            @foreach ($departamentos->where('id_depto_org', '!=', $d->id_depto_org) as $p)<option value="{{ $p->id_depto_org }}" @selected((int) $v('id_padre') === $p->id_depto_org)>{{ $p->nombre }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-6">
        <select class="form-select form-select-sm" name="centro_costo" title="Centro de costo">
            <option value="">Sin centro de costo</option>
            @foreach ($centros as $cc)<option value="{{ $cc->codigo }}" @selected($v('centro_costo') === $cc->codigo)>{{ $cc->etiqueta }}</option>@endforeach
        </select>
    </div>
    <div class="col-12 d-flex align-items-center gap-3">
        <input type="hidden" name="activo" value="0" />
        <div class="form-check mb-0">
            <input class="form-check-input" id="depto-activo-{{ $d->id_depto_org ?? 'nuevo' }}" name="activo" type="checkbox" value="1" @checked($v('activo')) />
            <label class="form-check-label" for="depto-activo-{{ $d->id_depto_org ?? 'nuevo' }}">Activo</label>
        </div>
        <button class="btn btn-primary btn-sm ms-auto" type="submit">Guardar</button>
    </div>
</form>
@if ($d->exists && ! $d->empleados_count && ! $d->cargos_count)
    <form method="POST" action="{{ route('organizacion.departamentos.destroy', $d->id_depto_org) }}" class="text-end mt-1" onsubmit="return confirm(@js('¿Eliminar el departamento '.$d->nombre.'?'))">
        @csrf
        @method('DELETE')
        <button class="btn btn-link text-danger btn-sm p-0" type="submit">Eliminar</button>
    </form>
@endif
