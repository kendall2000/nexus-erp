@extends('layouts.app', ['titulo' => $serie->exists ? 'Editar serie' : 'Nueva serie'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $serie->{$campo}); @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('series-facturacion.index') }}">Series de facturación</a></li>
            <li class="breadcrumb-item active">{{ $serie->exists ? $serie->codigo_serie : 'Nueva' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $serie->exists ? 'Editar serie '.$serie->codigo_serie : 'Nueva serie' }}</h2>

    <div class="card" style="max-width: 40rem">
        <div class="card-body">
            @if ($usada)
                <div class="alert alert-soft-info fs--1"><span class="fas fa-lock me-2"></span>La serie ya tiene documentos: el código, el tipo y el correlativo no se pueden cambiar.</div>
            @endif
            <form method="POST" action="{{ $serie->exists ? route('series-facturacion.update', $serie->id_serie) : route('series-facturacion.store') }}">
                @csrf
                @if ($serie->exists)
                    @method('PUT')
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="codigo_serie">Código</label>
                        <input class="form-control text-uppercase @error('codigo_serie') is-invalid @enderror" id="codigo_serie" name="codigo_serie" value="{{ $v('codigo_serie') }}" required maxlength="10" placeholder="Ej.: A" @readonly($usada) />
                        @error('codigo_serie')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="tipo">Tipo de documento</label>
                        @if ($usada)
                            <input type="hidden" name="tipo" value="{{ $serie->tipo }}" />
                            <input class="form-control" value="{{ $tipos[$serie->tipo] ?? $serie->tipo }}" disabled />
                        @else
                            <select class="form-select" id="tipo" name="tipo" required>
                                @foreach ($tipos as $codigo => $nombre)<option value="{{ $codigo }}" @selected($v('tipo') === $codigo)>{{ $nombre }}</option>@endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><input class="form-control" id="descripcion" name="descripcion" value="{{ $v('descripcion') }}" maxlength="100" /></div>
                    <div class="col-md-6">
                        <label class="form-label" for="ultimo_numero">Último número usado</label>
                        <input class="form-control @error('ultimo_numero') is-invalid @enderror" id="ultimo_numero" name="ultimo_numero" type="number" min="0" value="{{ $v('ultimo_numero') }}" required @readonly($usada) />
                        @error('ultimo_numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">0 para empezar en 1. Úsalo para continuar la numeración de otro sistema.</div>
                    </div>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked($v('activo')) />
                    <label class="form-check-label" for="activo">Activa</label>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('series-facturacion.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
