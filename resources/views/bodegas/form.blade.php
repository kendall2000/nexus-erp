@extends('layouts.app', ['titulo' => $bodega->exists ? 'Editar bodega' : 'Nueva bodega'])

@section('contenido')
    <x-page-header
        :titulo="$bodega->exists ? 'Editar bodega' : 'Nueva bodega'"
        icono="fas fa-warehouse"
        subtitulo="Define almacenes físicos, sucursal asociada y empleado responsable."
        :migas="[
            ['label' => 'Bodegas', 'url' => route('bodegas.index')],
            ['label' => $bodega->exists ? $bodega->nombre : 'Nueva'],
        ]"
    />

    <div class="card border border-200 shadow-none" style="max-width: 40rem">
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
                    <button class="btn btn-primary" type="submit"><span class="fas fa-save me-2"></span>Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('bodegas.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
