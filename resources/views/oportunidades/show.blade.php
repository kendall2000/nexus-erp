@extends('layouts.app', ['titulo' => $o->nombre])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('oportunidades.editar');
        $dinero = fn ($n) => number_format((float) $n, 2);
        $cerrada = $o->etapa?->es_ganada || $o->etapa?->es_perdida;
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('oportunidades.index') }}">Oportunidades</a></li>
            <li class="breadcrumb-item active">{{ $o->nombre }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">{{ $o->nombre }}
                <span class="badge fs--1 align-middle" style="background: {{ $o->etapa?->color_hex ?? '#94A3B8' }}">{{ $o->etapa?->nombre }}</span></h2>
            <p class="text-700 mb-0">
                @if ($o->cliente)
                    @if ($yo->puede('clientes.ver'))<a href="{{ route('clientes.show', $o->id_cliente) }}">{{ $o->cliente->razon_social }}</a>@else{{ $o->cliente->razon_social }}@endif
                @elseif ($o->prospecto)
                    Prospecto: @if ($yo->puede('prospectos.ver'))<a href="{{ route('prospectos.show', $o->id_prospecto) }}">{{ $o->prospecto->nombre_empresa }}</a>@else{{ $o->prospecto->nombre_empresa }}@endif
                @endif
                · {{ $o->responsable?->nombre_completo }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($o->etapa?->es_ganada && $o->id_cliente && $yo->puede('contratos.crear'))
                <a class="btn btn-success" href="{{ route('contratos.create', ['cliente' => $o->id_cliente]) }}"><span class="fas fa-file-contract me-2"></span>Registrar contrato</a>
            @endif
            @if ($puedeEditar)
                <a class="btn btn-phoenix-secondary" href="{{ route('oportunidades.edit', $o->id_oportunidad) }}"><span class="fas fa-pen me-2"></span>Editar</a>
            @endif
        </div>
    </div>

    @if ($puedeEditar)
        <div class="card mb-4"><div class="card-body py-3">
            <form method="POST" action="{{ route('oportunidades.mover', $o->id_oportunidad) }}" class="row g-2 align-items-center">
                @csrf
                @method('PATCH')
                <div class="col-md-4">
                    <select class="form-select form-select-sm" name="id_etapa" id="id_etapa">
                        @foreach ($etapas as $e)<option value="{{ $e->id_etapa }}" data-perdida="{{ (int) $e->es_perdida }}" @selected($e->id_etapa === $o->id_etapa)>{{ $e->nombre }} ({{ $e->probabilidad_cierre }} %)</option>@endforeach
                    </select>
                </div>
                <div class="col-md-5"><input class="form-control form-control-sm @error('razon_cierre') is-invalid @enderror" name="razon_cierre" value="{{ old('razon_cierre') }}" maxlength="300" placeholder="Razón (obligatoria si se pierde)" /></div>
                <div class="col-md-3"><button class="btn btn-primary btn-sm w-100" type="submit">Mover de etapa</button></div>
                @error('razon_cierre')<div class="col-12 text-danger fs--1">{{ $message }}</div>@enderror
            </form>
        </div></div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Valor estimado</p><h3 class="mb-0">{{ $o->moneda }} {{ $dinero($o->valor_estimado) }}</h3></div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Probabilidad · ponderado</p><h3 class="mb-0">{{ $o->probabilidad }} % · {{ $dinero((float) $o->valor_estimado * $o->probabilidad / 100) }}</h3></div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">{{ $cerrada ? 'Cerrada el' : 'Cierre estimado' }}</p><h3 class="mb-0">{{ ($o->fecha_cierre_real ?? $o->fecha_cierre_estimada)?->format('d/m/Y') ?? '—' }}</h3>@if ($o->razon_cierre)<p class="fs--2 text-700 mb-0">{{ $o->razon_cierre }}</p>@endif</div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card"><div class="card-body fs--1">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Propuestas</h5>
                    @if ($puedeEditar && ! $cerrada)<button class="btn btn-phoenix-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#nueva-propuesta">Nueva propuesta</button>@endif
                </div>
                @if ($puedeEditar && ! $cerrada)
                    <div class="collapse {{ $errors->propuesta->any() ? 'show' : '' }} mb-3" id="nueva-propuesta">
                        @if ($errors->propuesta->any())<div class="text-danger mb-2">{{ $errors->propuesta->first() }}</div>@endif
                        <form method="POST" action="{{ route('oportunidades.propuestas.store', $o->id_oportunidad) }}" class="row g-2">
                            @csrf
                            <div class="col-md-6"><input class="form-control form-control-sm" name="titulo" value="{{ old('titulo') }}" maxlength="200" placeholder="Título ({{ $siguientePropuesta }})" required /></div>
                            <div class="col-md-3"><input class="form-control form-control-sm" name="valor_propuesto" type="number" step="0.01" min="0" value="{{ old('valor_propuesto', (float) $o->valor_estimado) }}" required /></div>
                            <div class="col-md-3"><select class="form-select form-select-sm" name="id_elaborado_por" required>@foreach ($vendedores as $v)<option value="{{ $v->id_empleado }}" @selected((int) old('id_elaborado_por', $o->id_responsable) === $v->id_empleado)>{{ $v->nombre_completo }}</option>@endforeach</select></div>
                            <div class="col-md-3"><input class="form-control form-control-sm" type="date" name="fecha_emision" value="{{ old('fecha_emision', now()->format('Y-m-d')) }}" required /></div>
                            <div class="col-md-3"><input class="form-control form-control-sm" type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', now()->addDays(30)->format('Y-m-d')) }}" required title="Vigencia" /></div>
                            <div class="col-md-4"><input class="form-control form-control-sm" name="notas_internas" value="{{ old('notas_internas') }}" maxlength="5000" placeholder="Notas internas" /></div>
                            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit">Guardar</button></div>
                        </form>
                    </div>
                @endif
                @forelse ($propuestas as $p)
                    @php [$nombreEstado, $color] = $estadosPropuesta[$p->estado] ?? [$p->estado, 'secondary']; @endphp
                    <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom border-200 py-2">
                        <div>
                            <span class="fw-semi-bold">{{ $p->numero_propuesta }} v{{ $p->version }}</span> · {{ $p->titulo }}
                            <span class="d-block text-700">{{ $p->moneda }} {{ $dinero($p->valor_propuesto) }} · {{ $p->fecha_emision?->format('d/m/Y') }} – vence {{ $p->fecha_vencimiento?->format('d/m/Y') }} · {{ $p->elaboradoPor?->nombre_completo }}</span>
                            @if ($p->motivo_rechazo)<span class="d-block text-danger">{{ $p->motivo_rechazo }}</span>@endif
                        </div>
                        <div class="d-flex gap-1 align-items-start">
                            <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                            @if ($puedeEditar && in_array($p->estado, ['BORRADOR', 'ENVIADA', 'EN_REVISION'], true))
                                <form method="POST" action="{{ route('oportunidades.propuestas.estado', [$o->id_oportunidad, $p->id_propuesta]) }}" class="d-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select class="form-select form-select-sm" name="estado">
                                        @foreach (['BORRADOR' => ['ENVIADA'], 'ENVIADA' => ['EN_REVISION', 'ACEPTADA', 'RECHAZADA', 'VENCIDA'], 'EN_REVISION' => ['ACEPTADA', 'RECHAZADA', 'VENCIDA']][$p->estado] as $e)<option value="{{ $e }}">{{ $estadosPropuesta[$e][0] }}</option>@endforeach
                                    </select>
                                    <input class="form-control form-control-sm" name="motivo_rechazo" maxlength="300" placeholder="Motivo si se rechaza" />
                                    <button class="btn btn-phoenix-primary btn-sm" type="submit">OK</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-700 mb-0">Sin propuestas.</p>
                @endforelse
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-body fs--1">
                <h5 class="mb-3">Detalles</h5>
                <dl class="row mb-0">
                    <dt class="col-5 text-700">Línea</dt><dd class="col-7">{{ $o->lineaNegocio?->nombre ?? '—' }}</dd>
                    <dt class="col-5 text-700">Competidores</dt><dd class="col-7">{{ $o->competidores ?? '—' }}</dd>
                    @if ($o->descripcion)<dt class="col-5 text-700">Descripción</dt><dd class="col-7" style="white-space: pre-line">{{ $o->descripcion }}</dd>@endif
                </dl>
            </div></div>
        </div>
    </div>
@endsection
