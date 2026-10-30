@extends('layouts.app', ['titulo' => $o->exists ? 'Editar oportunidad' : 'Nueva oportunidad'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $o->{$campo}); @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('oportunidades.index') }}">Oportunidades</a></li>
            <li class="breadcrumb-item active">{{ $o->exists ? $o->nombre : 'Nueva' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $o->exists ? 'Editar oportunidad' : 'Nueva oportunidad' }}</h2>
    <form method="POST" action="{{ $o->exists ? route('oportunidades.update', $o->id_oportunidad) : route('oportunidades.store') }}" class="mb-9" style="max-width: 56rem">
        @csrf
        @if ($o->exists)
            @method('PUT')
        @endif
        <div class="card mb-4"><div class="card-body">
            <div class="row g-3">
                <div class="col-12"><label class="form-label" for="nombre">Nombre</label><input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="200" placeholder="Ej.: Limpieza de oficinas centrales" /></div>
                <div class="col-md-6">
                    <label class="form-label" for="id_cliente">Cliente</label>
                    <select class="form-select @error('id_cliente') is-invalid @enderror" id="id_cliente" name="id_cliente"><option value="">—</option>@foreach ($clientes as $c)<option value="{{ $c->id_cliente }}" @selected((int) $v('id_cliente') === $c->id_cliente)>{{ $c->razon_social }}</option>@endforeach</select>
                    @error('id_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="id_prospecto">o prospecto</label>
                    <select class="form-select" id="id_prospecto" name="id_prospecto"><option value="">—</option>@foreach ($prospectos as $p)<option value="{{ $p->id_prospecto }}" @selected((int) $v('id_prospecto') === $p->id_prospecto)>{{ $p->nombre_empresa }}</option>@endforeach</select>
                </div>
                @unless ($o->exists)
                    <div class="col-md-4">
                        <label class="form-label" for="id_etapa">Etapa</label>
                        <select class="form-select @error('id_etapa') is-invalid @enderror" id="id_etapa" name="id_etapa" required>@foreach ($etapas as $e)<option value="{{ $e->id_etapa }}" data-probabilidad="{{ $e->probabilidad_cierre }}" @selected((int) old('id_etapa') === $e->id_etapa)>{{ $e->nombre }}</option>@endforeach</select>
                    </div>
                @endunless
                <div class="col-md-4">
                    <label class="form-label" for="id_responsable">Vendedor</label>
                    <select class="form-select @error('id_responsable') is-invalid @enderror" id="id_responsable" name="id_responsable" required><option value="">Selecciona…</option>@foreach ($vendedores as $ve)<option value="{{ $ve->id_empleado }}" @selected((int) $v('id_responsable') === $ve->id_empleado)>{{ $ve->nombre_completo }}</option>@endforeach</select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="id_linea">Línea de negocio</label>
                    <select class="form-select" id="id_linea" name="id_linea"><option value="">—</option>@foreach ($lineas as $l)<option value="{{ $l->id_linea }}" @selected((int) $v('id_linea') === $l->id_linea)>{{ $l->nombre }}</option>@endforeach</select>
                </div>
                <div class="col-md-4"><label class="form-label" for="valor_estimado">Valor estimado</label><input class="form-control" id="valor_estimado" name="valor_estimado" type="number" step="0.01" min="0" value="{{ $v('valor_estimado') !== null ? (float) $v('valor_estimado') : '' }}" required /></div>
                <div class="col-md-2">
                    <label class="form-label" for="moneda">Moneda</label>
                    <select class="form-select" id="moneda" name="moneda">@foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>@endforeach</select>
                </div>
                <div class="col-md-2"><label class="form-label" for="probabilidad">Probabilidad %</label><input class="form-control" id="probabilidad" name="probabilidad" type="number" min="0" max="100" value="{{ $v('probabilidad') }}" placeholder="De la etapa" /></div>
                <div class="col-md-4"><label class="form-label" for="fecha_cierre_estimada">Cierre estimado</label><input class="form-control" id="fecha_cierre_estimada" name="fecha_cierre_estimada" type="date" value="{{ old('fecha_cierre_estimada', $o->fecha_cierre_estimada?->format('Y-m-d')) }}" /></div>
                <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="5000">{{ $v('descripcion') }}</textarea></div>
                <div class="col-12"><label class="form-label" for="competidores">Competidores</label><input class="form-control" id="competidores" name="competidores" value="{{ $v('competidores') }}" maxlength="300" /></div>
            </div>
        </div></div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-phoenix-secondary" href="{{ $o->exists ? route('oportunidades.show', $o->id_oportunidad) : route('oportunidades.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
