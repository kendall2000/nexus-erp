@extends('layouts.app', ['titulo' => 'Campaña: '.$c->nombre])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('campanas.editar');
        [$nombreEstado, $color] = $estados[$c->estado] ?? [$c->estado, 'secondary'];
        $dinero = fn ($n) => number_format((float) $n, 2);
        $costoLead = $c->leads_generados > 0 ? (float) $c->gasto_real / $c->leads_generados : null;
        $pctPresupuesto = $c->presupuesto > 0 ? min(100, round(($c->gasto_real / $c->presupuesto) * 100)) : 0;
        $pctLeads = ($c->meta_leads ?? 0) > 0 ? min(100, round(($c->leads_generados / $c->meta_leads) * 100)) : 0;
    @endphp

    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('campanas.index') }}">Campañas</a></li>
            <li class="breadcrumb-item active">{{ $c->nombre }}</li>
        </ol>
    </nav>

    <!-- Header de Campaña Phoenix -->
    <div class="card shadow-none border mb-4">
        <div class="card-body p-4">
            <div class="row g-3 justify-content-between align-items-center">
                <div class="col-12 col-md d-flex align-items-center">
                    <div class="avatar avatar-4xl me-3 flex-shrink-0">
                        <div class="avatar-name rounded-circle bg-primary-subtle text-primary fw-bold fs-2">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h2 class="mb-0 text-bold text-1100">{{ $c->nombre }}</h2>
                            <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--2 px-2 py-1">{{ $nombreEstado }}</span>
                        </div>
                        <p class="text-700 fs--1 mb-0">
                            <span class="badge badge-phoenix badge-phoenix-secondary me-2">{{ $tipos[$c->tipo] ?? $c->tipo }}</span>
                            <span class="badge badge-phoenix badge-phoenix-info me-2">{{ $objetivos[$c->objetivo] ?? $c->objetivo }}</span>
                            <i class="far fa-calendar-alt me-1 text-400"></i>
                            <strong>{{ $c->fecha_inicio?->format('d/m/Y') }}</strong> hasta <strong>{{ $c->fecha_fin?->format('d/m/Y') ?? 'Indefinido' }}</strong>
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <a class="btn btn-phoenix-secondary" href="{{ route('campanas.index') }}">
                        <span class="fas fa-arrow-left me-2"></span>Volver
                    </a>
                    @if ($puedeEditar)
                        <a class="btn btn-primary" href="{{ route('campanas.edit', $c->id_campana) }}">
                            <span class="fas fa-pen me-2"></span>Editar Campaña
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Indicadores Clave de Campaña -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-primary shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Inversión Real / Presupuesto</h6>
                            <h3 class="fw-bolder mb-1 text-1000">{{ $c->moneda }} {{ $dinero($c->gasto_real) }}</h3>
                            <span class="fs--2 text-muted">de {{ $c->presupuesto !== null ? $c->moneda.' '.$dinero($c->presupuesto) : 'Sin límite' }}</span>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar {{ $pctPresupuesto > 90 ? 'bg-danger' : 'bg-primary' }}" role="progressbar" style="width: {{ $pctPresupuesto }}%"></div>
                            </div>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-primary-subtle text-primary fs-1">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-success shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Prospectos Captados</h6>
                            <h3 class="fw-bolder mb-1 text-success">{{ $c->leads_generados }}</h3>
                            <span class="fs--2 text-muted">Meta fijada: {{ $c->meta_leads ?? '—' }} leads</span>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctLeads }}%"></div>
                            </div>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-success-subtle text-success fs-1">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-info shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Costo Por Lead (CPL)</h6>
                            <h3 class="fw-bolder mb-1 text-info">{{ $costoLead !== null ? $c->moneda.' '.$dinero($costoLead) : '—' }}</h3>
                            <span class="fs--2 text-muted"><i class="fas fa-calculator me-1"></i>Eficiencia económica</span>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-info-subtle text-info fs-1">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-warning shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Contactos Asignados</h6>
                            <h3 class="fw-bolder mb-1 text-warning">{{ $contactos->count() }}</h3>
                            <span class="fs--2 text-muted">{{ collect($estadosEnvio)->filter(fn ($n, $k) => $embudo->get($k))->map(fn ($n, $k) => $n.': '.$embudo->get($k))->implode(' · ') ?: 'Sin envíos' }}</span>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-warning-subtle text-warning fs-1">
                                <i class="fas fa-address-book"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Contactos y Audiencia de Campaña -->
    <div class="card shadow-none border mb-4">
        <div class="card-header p-3 bg-body-emphasis border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-users text-primary me-2"></i>Audiencia y Contactos Impactados</h5>
            <span class="badge badge-phoenix badge-phoenix-secondary">{{ $contactos->count() }} contactos</span>
        </div>
        <div class="card-body p-3">
            @if ($puedeEditar)
                <div class="card border border-200 bg-body-tertiary mb-3">
                    <div class="card-body p-3">
                        <h6 class="fs--1 text-800 mb-2"><i class="fas fa-user-plus text-primary me-1"></i>Vincular Contactos a la Campaña</h6>
                        <form method="POST" action="{{ route('campanas.contactos.store', $c->id_campana) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-12 col-md-5">
                                <label class="form-label fs--2 mb-1 text-muted">Prospectos disponibles (Ctrl + clic para varios)</label>
                                <select class="form-select form-select-sm" name="prospectos[]" multiple size="3" title="Prospectos">
                                    @foreach ($prospectos as $p)
                                        <option value="{{ $p->id_prospecto }}">{{ $p->nombre_empresa }} ({{ $p->nombre_contacto }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label fs--2 mb-1 text-muted">Clientes registrados (Ctrl + clic para varios)</label>
                                <select class="form-select form-select-sm" name="clientes[]" multiple size="3" title="Clientes">
                                    @foreach ($clientes as $cl)
                                        <option value="{{ $cl->id_cliente }}">{{ $cl->razon_social }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <button class="btn btn-primary btn-sm w-100" type="submit">
                                    <i class="fas fa-plus me-1"></i>Vincular
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle table-hover">
                    <thead class="bg-body-secondary text-900">
                        <tr>
                            <th class="ps-3 py-2 text-uppercase text-700">Contacto / Organización</th>
                            <th class="text-uppercase text-700">Tipo</th>
                            <th class="text-uppercase text-700">Estado de Envío</th>
                            <th class="text-uppercase text-700">Resultado Obtenido</th>
                            <th class="pe-3 text-end text-uppercase text-700">Actualizar Estado</th>
                        </tr>
                    </thead>
                    <tbody class="list">
                    @forelse ($contactos as $k)
                        <tr class="hover-actions-trigger position-static">
                            <td class="ps-3 py-2">
                                <div class="fw-bold text-1000">
                                    @if ($k->tipo_contacto === 'PROSPECTO')
                                        <a href="{{ route('prospectos.show', $k->id_prospecto) }}" class="text-decoration-none text-1000">
                                            {{ $k->prospecto?->nombre_empresa }}
                                        </a>
                                        <span class="d-block fs--2 text-muted">{{ $k->prospecto?->nombre_contacto }}</span>
                                    @else
                                        <a href="{{ route('clientes.show', $k->id_cliente) }}" class="text-decoration-none text-1000">
                                            {{ $k->cliente?->razon_social }}
                                        </a>
                                        <span class="d-block fs--2 text-muted">{{ $k->cliente?->contacto_nombre }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($k->tipo_contacto === 'PROSPECTO')
                                    <span class="badge badge-phoenix badge-phoenix-warning fs--2">Prospecto</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-success fs--2">Cliente</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-phoenix badge-phoenix-info fs--2">
                                    {{ $estadosEnvio[$k->estado_envio] ?? $k->estado_envio }}
                                </span>
                                @if ($k->fecha_envio)
                                    <span class="d-block fs--2 text-600 mt-1"><i class="far fa-clock me-1"></i>{{ $k->fecha_envio->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-800">{{ $k->resultado ?: '—' }}</span>
                            </td>
                            <td class="pe-3 text-end">
                                @if ($puedeEditar)
                                    <form method="POST" action="{{ route('campanas.contactos.update', [$c->id_campana, $k->id_contacto_campana]) }}" class="d-flex gap-1 justify-content-end">
                                        @csrf
                                        @method('PATCH')
                                        <select class="form-select form-select-sm" name="estado_envio" style="max-width: 130px;">
                                            @foreach ($estadosEnvio as $e => $n)
                                                <option value="{{ $e }}" @selected($k->estado_envio === $e)>{{ $n }}</option>
                                            @endforeach
                                        </select>
                                        <input class="form-control form-control-sm" name="resultado" value="{{ $k->resultado }}" maxlength="200" placeholder="Resultado..." style="max-width: 140px;" />
                                        <button class="btn btn-phoenix-primary btn-sm px-2" type="submit" title="Actualizar">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-700 py-4">
                                <div class="avatar avatar-l mb-2 mx-auto">
                                    <div class="avatar-name rounded-circle bg-body-secondary text-500 fs-0">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <span class="fs--1">No hay contactos vinculados a esta campaña. Agrega prospectos o clientes arriba para iniciar el envío.</span>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
