@extends('layouts.app', ['titulo' => $c->exists ? 'Editar campaña' : 'Nueva campaña'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $c->{$campo}); @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('campanas.index') }}">Campañas</a></li>
            <li class="breadcrumb-item active">{{ $c->exists ? $c->nombre : 'Nueva' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $c->exists ? 'Editar campaña' : 'Nueva campaña' }}</h2>
    <form method="POST" action="{{ $c->exists ? route('campanas.update', $c->id_campana) : route('campanas.store') }}" class="mb-9" style="max-width: 52rem">
        @csrf
        @if ($c->exists)
            @method('PUT')
        @endif
        <div class="card mb-4"><div class="card-body">
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label" for="nombre">Nombre</label><input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="200" /></div>
                <div class="col-md-4">
                    <label class="form-label" for="id_linea">Línea de negocio</label>
                    <select class="form-select" id="id_linea" name="id_linea"><option value="">—</option>@foreach ($lineas as $l)<option value="{{ $l->id_linea }}" @selected((int) $v('id_linea') === $l->id_linea)>{{ $l->nombre }}</option>@endforeach</select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tipo">Tipo</label>
                    <select class="form-select" id="tipo" name="tipo">@foreach ($tipos as $k => $n)<option value="{{ $k }}" @selected($v('tipo') === $k)>{{ $n }}</option>@endforeach</select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="objetivo">Objetivo</label>
                    <select class="form-select" id="objetivo" name="objetivo">@foreach ($objetivos as $k => $n)<option value="{{ $k }}" @selected($v('objetivo') === $k)>{{ $n }}</option>@endforeach</select>
                </div>
                @if ($c->exists)
                    <div class="col-md-4">
                        <label class="form-label" for="estado">Estado</label>
                        <select class="form-select" id="estado" name="estado">@foreach ($estados as $k => [$n])<option value="{{ $k }}" @selected($v('estado') === $k)>{{ $n }}</option>@endforeach</select>
                    </div>
                @endif
                <div class="col-md-3"><label class="form-label" for="fecha_inicio">Inicio</label><input class="form-control" id="fecha_inicio" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', $c->fecha_inicio?->format('Y-m-d')) }}" required /></div>
                <div class="col-md-3">
                    <label class="form-label" for="fecha_fin">Fin</label>
                    <input class="form-control @error('fecha_fin') is-invalid @enderror" id="fecha_fin" name="fecha_fin" type="date" value="{{ old('fecha_fin', $c->fecha_fin?->format('Y-m-d')) }}" />
                    @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3"><label class="form-label" for="presupuesto">Presupuesto</label><input class="form-control" id="presupuesto" name="presupuesto" type="number" step="0.01" min="0" value="{{ $v('presupuesto') !== null ? (float) $v('presupuesto') : '' }}" /></div>
                <div class="col-md-3">
                    <label class="form-label" for="moneda">Moneda</label>
                    <select class="form-select" id="moneda" name="moneda">@foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>@endforeach</select>
                </div>
                <div class="col-md-3"><label class="form-label" for="meta_leads">Meta de prospectos</label><input class="form-control" id="meta_leads" name="meta_leads" type="number" min="0" value="{{ $v('meta_leads') }}" /></div>
                @if ($c->exists)
                    <div class="col-md-3"><label class="form-label" for="gasto_real">Gasto real</label><input class="form-control" id="gasto_real" name="gasto_real" type="number" step="0.01" min="0" value="{{ (float) $v('gasto_real') }}" /></div>
                    <div class="col-md-3"><label class="form-label" for="leads_generados">Prospectos generados</label><input class="form-control" id="leads_generados" name="leads_generados" type="number" min="0" value="{{ $v('leads_generados') }}" /></div>
                @endif
                <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="5000">{{ $v('descripcion') }}</textarea></div>
            </div>
        </div></div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-phoenix-secondary" href="{{ $c->exists ? route('campanas.show', $c->id_campana) : route('campanas.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
