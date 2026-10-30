@extends('layouts.app', ['titulo' => $categoria->exists ? 'Editar categoría' : 'Nueva categoría'])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('categorias.index') }}">Categorías de productos</a></li>
            <li class="breadcrumb-item active">{{ $categoria->exists ? $categoria->nombre : 'Nueva' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $categoria->exists ? 'Editar categoría' : 'Nueva categoría' }}</h2>

    <div class="card" style="max-width: 40rem">
        <div class="card-body">
            <form method="POST" action="{{ $categoria->exists ? route('categorias.update', $categoria->id_categoria) : route('categorias.store') }}">
                @csrf
                @if ($categoria->exists)
                    @method('PUT')
                @endif
                <div class="mb-3">
                    <label class="form-label" for="id_padre">Dentro de</label>
                    <select class="form-select" id="id_padre" name="id_padre">
                        <option value="">Ninguna (categoría principal)</option>
                        @foreach ($padres as ['categoria' => $p, 'nivel' => $nivel])
                            <option value="{{ $p->id_categoria }}" @selected((int) old('id_padre', $categoria->id_padre) === $p->id_categoria)>{{ str_repeat('— ', $nivel) }}{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $categoria->nombre) }}" required maxlength="150" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="2" maxlength="300">{{ old('descripcion', $categoria->descripcion) }}</textarea>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $categoria->activo)) />
                    <label class="form-check-label" for="activo">Activa</label>
                    <div class="form-text mt-0">Una categoría inactiva no aparece para nuevos productos.</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('categorias.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
