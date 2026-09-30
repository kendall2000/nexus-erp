@extends('layouts.app', ['titulo' => $linea->exists ? 'Editar línea de negocio' : 'Nueva línea de negocio'])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('lineas-negocio.index') }}">Líneas de negocio</a></li>
            <li class="breadcrumb-item active">{{ $linea->exists ? $linea->nombre : 'Nueva' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $linea->exists ? 'Editar línea de negocio' : 'Nueva línea de negocio' }}</h2>

    <div class="card" style="max-width: 40rem">
        <div class="card-body">
            <form method="POST" action="{{ $linea->exists ? route('lineas-negocio.update', $linea->id_linea) : route('lineas-negocio.store') }}">
                @csrf
                @if ($linea->exists)
                    @method('PUT')
                @endif
                <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $linea->nombre) }}" required maxlength="100" placeholder="Ej.: Limpieza profunda" />
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="2000">{{ old('descripcion', $linea->descripcion) }}</textarea>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $linea->activo)) />
                    <label class="form-check-label" for="activo">Activa</label>
                    <div class="form-text mt-0">En una línea inactiva no se crean servicios nuevos.</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('lineas-negocio.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
