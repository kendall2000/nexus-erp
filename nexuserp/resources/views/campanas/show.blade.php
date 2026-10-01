@extends('layouts.app', ['titulo' => $c->nombre])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('campanas.editar');
        [$nombreEstado, $color] = $estados[$c->estado] ?? [$c->estado, 'secondary'];
        $dinero = fn ($n) => number_format((float) $n, 2);
        $costoLead = $c->leads_generados > 0 ? (float) $c->gasto_real / $c->leads_generados : null;
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('campanas.index') }}">Campañas</a></li>
            <li class="breadcrumb-item active">{{ $c->nombre }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">{{ $c->nombre }} <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1 align-middle">{{ $nombreEstado }}</span></h2>
            <p class="text-700 mb-0">{{ $tipos[$c->tipo] ?? $c->tipo }} · {{ $objetivos[$c->objetivo] ?? $c->objetivo }} · {{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? '…' }}</p>
        </div>
        @if ($puedeEditar)<a class="btn btn-phoenix-secondary" href="{{ route('campanas.edit', $c->id_campana) }}"><span class="fas fa-pen me-2"></span>Editar</a>@endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-3"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Gasto / presupuesto</p><h4 class="mb-0">{{ $c->moneda }} {{ $dinero($c->gasto_real) }}</h4><p class="fs--2 text-700 mb-0">de {{ $c->presupuesto !== null ? $dinero($c->presupuesto) : 'sin presupuesto' }}</p></div></div></div>
        <div class="col-sm-3"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Prospectos generados</p><h4 class="mb-0">{{ $c->leads_generados }}</h4><p class="fs--2 text-700 mb-0">meta {{ $c->meta_leads ?? '—' }}</p></div></div></div>
        <div class="col-sm-3"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Costo por prospecto</p><h4 class="mb-0">{{ $costoLead !== null ? $dinero($costoLead) : '—' }}</h4></div></div></div>
        <div class="col-sm-3"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Contactos</p><h4 class="mb-0">{{ $contactos->count() }}</h4>
            <p class="fs--2 text-700 mb-0">{{ collect($estadosEnvio)->filter(fn ($n, $k) => $embudo->get($k))->map(fn ($n, $k) => $n.' '.$embudo->get($k))->implode(' · ') }}</p></div></div></div>
    </div>

    <div class="card"><div class="card-body fs--1">
        <h5 class="mb-3">Contactos de la campaña</h5>
        @if ($puedeEditar)
            <form method="POST" action="{{ route('campanas.contactos.store', $c->id_campana) }}" class="row g-2 mb-3">
                @csrf
                <div class="col-md-5"><select class="form-select form-select-sm" name="prospectos[]" multiple size="4" title="Prospectos">@foreach ($prospectos as $p)<option value="{{ $p->id_prospecto }}">{{ $p->nombre_empresa }}</option>@endforeach</select><span class="fs--2 text-600">Prospectos (Ctrl para varios)</span></div>
                <div class="col-md-5"><select class="form-select form-select-sm" name="clientes[]" multiple size="4" title="Clientes">@foreach ($clientes as $cl)<option value="{{ $cl->id_cliente }}">{{ $cl->razon_social }}</option>@endforeach</select><span class="fs--2 text-600">Clientes</span></div>
                <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit">Agregar</button></div>
            </form>
        @endif
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead><tr><th>Contacto</th><th>Tipo</th><th>Envío</th><th>Resultado</th><th></th></tr></thead>
                <tbody>
                @forelse ($contactos as $k)
                    <tr>
                        <td>{{ $k->tipo_contacto === 'PROSPECTO' ? $k->prospecto?->nombre_empresa : $k->cliente?->razon_social }}</td>
                        <td>{{ $k->tipo_contacto === 'PROSPECTO' ? 'Prospecto' : 'Cliente' }}</td>
                        <td>{{ $estadosEnvio[$k->estado_envio] ?? $k->estado_envio }}<span class="d-block fs--2 text-600">{{ $k->fecha_envio?->format('d/m/Y') }}</span></td>
                        <td>{{ $k->resultado ?? '—' }}</td>
                        <td class="text-end">
                            @if ($puedeEditar)
                                <form method="POST" action="{{ route('campanas.contactos.update', [$c->id_campana, $k->id_contacto_campana]) }}" class="d-flex gap-1 justify-content-end">
                                    @csrf
                                    @method('PATCH')
                                    <select class="form-select form-select-sm" name="estado_envio" style="width: 9rem">@foreach ($estadosEnvio as $e => $n)<option value="{{ $e }}" @selected($k->estado_envio === $e)>{{ $n }}</option>@endforeach</select>
                                    <input class="form-control form-control-sm" name="resultado" value="{{ $k->resultado }}" maxlength="200" placeholder="Resultado" style="width: 10rem" />
                                    <button class="btn btn-phoenix-primary btn-sm" type="submit">OK</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-700">Sin contactos todavía.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
@endsection
