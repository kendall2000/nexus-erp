@extends('layouts.app', ['titulo' => $bodega->exists ? 'Editar bodega' : 'Nueva bodega'])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('bodegas.index') }}">Bodegas</a></li>
            <li class="breadcrumb-item active">{{ $bodega->exists ? $bodega->nombre : 'Nueva' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $bodega->exists ? 'Editar bodega' : 'Nueva bodega' }}</h2>

    <div class="card" style="max-width: 40rem">
        <div class="card-body">
            <form method="POST" action="{{ $bodega->exists ? route('bodegas.update', $bodega->id_bodega) : route('bodegas.store') }}">
                @csrf
                @if ($bodega->exists)
                    @method('PUT')
                @endif
                <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $bodega->nombre) }}" required maxlength="150" placeholder="Ej.: Bodega central" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ubicacion">Ubicación</label>
                    <input class="form-control" id="ubicacion" name="ubicacion" value="{{ old('ubicacion', $bodega->ubicacion) }}" maxlength="300" placeholder="Dirección o referencia" />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="id_sucursal">Sucursal</label>
                        <select class="form-select" id="id_sucursal" name="id_sucursal">
                            <option value="">Sin sucursal</option>
                            @foreach ($sucursales as $s)
                                <option value="{{ $s->id_sucursal }}" @selected((int) old('id_sucursal', $bodega->id_sucursal) === $s->id_sucursal)>{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="responsable_id">Responsable</label>
                        <select class="form-select" id="responsable_id" name="responsable_id" @disabled($empleados->isEmpty())>
                            <option value="">{{ $empleados->isEmpty() ? 'No hay empleados activos' : 'Sin responsable' }}</option>
                            @foreach ($empleados as $e)
                                <option value="{{ $e->id_empleado }}" @selected((int) old('responsable_id', $bodega->responsable_id) === $e->id_empleado)>{{ $e->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $bodega->activo)) />
                    <label class="form-check-label" for="activo">Activa</label>
                    <div class="form-text mt-0">Una bodega inactiva no aparece para nuevas compras ni recepciones.</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('bodegas.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
