@extends('layouts.app', ['titulo' => 'Editar '.$nombreTipo])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('geografia.index', ['pestana' => $tipo]) }}">Geografía</a></li>
            <li class="breadcrumb-item active">{{ $registro->nombre }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">Editar {{ $nombreTipo }}</h2>

    <div class="card" style="max-width: 36rem">
        <div class="card-body">
            <form method="POST" action="{{ route('geografia.update', [$tipo, $registro->getKey()]) }}">
                @csrf
                @method('PUT')
                @if ($tipo === 'division')
                    <div class="mb-3">
                        <label class="form-label" for="id_pais">País</label>
                        <select class="form-select" id="id_pais" name="id_pais" required>
                            @foreach ($paises as $p)<option value="{{ $p->id_pais }}" @selected((int) old('id_pais', $registro->id_pais) === $p->id_pais)>{{ $p->nombre }}</option>@endforeach
                        </select>
                    </div>
                @elseif ($tipo === 'municipio')
                    <div class="mb-3">
                        <label class="form-label" for="id_division">Departamento</label>
                        <select class="form-select" id="id_division" name="id_division" required>
                            @foreach ($divisiones as $d)<option value="{{ $d->id_division }}" @selected((int) old('id_division', $registro->id_division) === $d->id_division)>{{ $d->nombre }} ({{ $d->pais?->nombre }})</option>@endforeach
                        </select>
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $registro->nombre) }}" required maxlength="100" />
                </div>
                @if ($tipo === 'pais')
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3"><label class="form-label" for="codigo_iso2">ISO 2</label><input class="form-control text-uppercase" id="codigo_iso2" name="codigo_iso2" value="{{ old('codigo_iso2', $registro->codigo_iso2) }}" maxlength="2" /></div>
                        <div class="col-6 col-md-3"><label class="form-label" for="codigo_iso3">ISO 3</label><input class="form-control text-uppercase" id="codigo_iso3" name="codigo_iso3" value="{{ old('codigo_iso3', $registro->codigo_iso3) }}" maxlength="3" /></div>
                        <div class="col-6 col-md-3"><label class="form-label" for="prefijo_tel">Prefijo</label><input class="form-control" id="prefijo_tel" name="prefijo_tel" value="{{ old('prefijo_tel', $registro->prefijo_tel) }}" maxlength="5" placeholder="+502" /></div>
                        <div class="col-6 col-md-3"><label class="form-label" for="moneda_defecto">Moneda</label><input class="form-control text-uppercase" id="moneda_defecto" name="moneda_defecto" value="{{ old('moneda_defecto', $registro->moneda_defecto) }}" maxlength="3" placeholder="GTQ" /></div>
                    </div>
                @endif
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $registro->activo)) />
                    <label class="form-check-label" for="activo">Activo</label>
                    <div class="form-text mt-0">Si lo desactivas, deja de aparecer para elegir en formularios nuevos.</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('geografia.index', ['pestana' => $tipo]) }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
