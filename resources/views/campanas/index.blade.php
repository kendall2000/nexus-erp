@extends('layouts.app', ['titulo' => 'Campañas de Marketing'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="#!">CRM & Ventas</a></li>
            <li class="breadcrumb-item active">Campañas</li>
        </ol>
    </nav>

    <div class="mb-4">
        <div class="row g-3 justify-content-between align-items-end">
            <div class="col-12 col-md">
                <h2 class="mb-1 text-bold text-1100">
                    <span class="fas fa-bullhorn text-primary me-2"></span>Campañas de Marketing & Atracción
                </h2>
                <p class="text-700 mb-0">Gestión de presupuestos de captación publicitaria, consecución de leads y retorno de inversión.</p>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <a class="btn btn-phoenix-secondary" href="{{ route('prospectos.index') }}">
                    <span class="fas fa-user-plus me-2"></span>Ver Prospectos
                </a>
                @if ($yo->puede('campanas.crear'))
                    <a class="btn btn-primary" href="{{ route('campanas.create') }}">
                        <span class="fas fa-plus-circle me-2"></span>Nueva Campaña
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabla estilizada Phoenix -->
    <div class="card shadow-none border">
        <div class="card-header p-3 border-bottom bg-body-emphasis d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-phoenix badge-phoenix-primary fs--1">
                    <i class="fas fa-chart-line me-1"></i>Listado Global de Acciones
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm fs--1 mb-0 align-middle table-hover">
                <thead class="bg-body-secondary text-900">
                    <tr>
                        <th class="ps-3 py-2 text-uppercase text-700">Nombre de Campaña</th>
                        <th class="text-uppercase text-700">Tipo & Estrategia</th>
                        <th class="text-uppercase text-700">Vigencia</th>
                        <th class="text-uppercase text-700" style="min-width: 170px;">Inversión / Presupuesto</th>
                        <th class="text-uppercase text-700" style="min-width: 150px;">Leads / Meta</th>
                        <th class="text-center text-uppercase text-700">Contactos</th>
                        <th class="text-center text-uppercase text-700">Estado</th>
                        <th class="pe-3 text-end text-uppercase text-700">Acción</th>
                    </tr>
                </thead>
                <tbody class="list">
                @forelse ($campanas as $c)
                    @php
                        [$nombreEstado, $color] = $estados[$c->estado] ?? [$c->estado, 'secondary'];
                        $pctPresupuesto = $c->presupuesto > 0 ? min(100, round(($c->gasto_real / $c->presupuesto) * 100)) : 0;
                        $pctLeads = ($c->meta_leads ?? 0) > 0 ? min(100, round(($c->leads_generados / $c->meta_leads) * 100)) : 0;
                    @endphp
                    <tr class="hover-actions-trigger position-static">
                        <td class="ps-3 py-2">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-m me-3 flex-shrink-0">
                                    <div class="avatar-name rounded-circle bg-primary-subtle text-primary fw-bold fs-0">
                                        <i class="fas fa-bullhorn"></i>
                                    </div>
                                </div>
                                <div>
                                    <a class="fw-bold text-1100 text-decoration-none fs-0" href="{{ route('campanas.show', $c->id_campana) }}">
                                        {{ $c->nombre }}
                                    </a>
                                    <span class="d-block fs--2 text-muted">
                                        <i class="fas fa-tag me-1"></i>{{ $c->lineaNegocio?->nombre ?? 'Línea General' }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semi-bold text-900">{{ $tipos[$c->tipo] ?? $c->tipo }}</div>
                            <span class="fs--2 text-600 badge badge-phoenix badge-phoenix-info mt-1">
                                {{ $objetivos[$c->objetivo] ?? $c->objetivo }}
                            </span>
                        </td>
                        <td class="text-nowrap text-800">
                            <i class="far fa-calendar-alt me-1 text-400"></i>
                            {{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? 'Indefinida' }}
                        </td>
                        <td>
                            <div class="d-flex justify-content-between fs--2 mb-1">
                                <span class="fw-bold text-1000">${{ number_format((float) $c->gasto_real, 2) }}</span>
                                <span class="text-muted">de ${{ $c->presupuesto !== null ? number_format((float) $c->presupuesto, 2) : '0.00' }}</span>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar {{ $pctPresupuesto > 90 ? 'bg-danger' : 'bg-success' }}" role="progressbar" style="width: {{ $pctPresupuesto }}%"></div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex justify-content-between fs--2 mb-1">
                                <span class="fw-bold text-primary">{{ $c->leads_generados }} leads</span>
                                <span class="text-muted">meta: {{ $c->meta_leads ?? '—' }}</span>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pctLeads }}%"></div>
                            </div>
                        </td>
                        <td class="text-center fw-bold text-800">
                            <span class="badge badge-phoenix badge-phoenix-secondary fs--1">
                                <i class="fas fa-users me-1"></i>{{ $c->contactos_count }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-phoenix badge-phoenix-{{ $color }} px-2 py-1">
                                {{ $nombreEstado }}
                            </span>
                        </td>
                        <td class="pe-3 text-end">
                            <div class="btn-reveal-trigger position-static">
                                <a class="btn btn-sm btn-phoenix-secondary px-2" href="{{ route('campanas.show', $c->id_campana) }}" title="Ver Detalles">
                                    <span class="fas fa-arrow-right text-primary"></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-700 py-5">
                            <div class="avatar avatar-xl mb-3 mx-auto">
                                <div class="avatar-name rounded-circle bg-body-secondary text-500 fs-1">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                            </div>
                            <h5 class="text-800">No hay campañas registradas</h5>
                            <p class="text-muted fs--1 mb-0">Crea tu primera campaña para monitorear el gasto publicitario y los prospectos obtenidos.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($campanas->hasPages())
            <div class="card-footer p-3 border-top">
                {{ $campanas->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
