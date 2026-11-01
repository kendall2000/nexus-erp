@extends('layouts.app', ['titulo' => 'Inicio'])

@section('contenido')
    @php
        $moneda = \App\Support\Sistema::config()->moneda ?: 'Q';
        $estadosFactura = \App\Http\Controllers\FacturaController::ESTADOS;
    @endphp

    <div class="pb-5">
        {{-- Banner de bienvenida estilo Phoenix CRM Dashboard --}}
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-12 col-md-auto">
                <h2 class="mb-1 text-1100">Hola, {{ $usuario->nombre_completo ?: $usuario->username }} 👋</h2>
                <h5 class="text-700 fw-semi-bold mb-0">
                    {{ $empresa?->nombre_comercial ? $empresa->nombre_comercial.' · ' : '' }}
                    <span class="text-primary">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM, YYYY')) }}</span>
                </h5>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                @if ($usuario->puede('facturas.crear'))
                    <a class="btn btn-phoenix-primary" href="{{ route('facturas.create') }}">
                        <span class="fas fa-file-invoice-dollar me-2"></span>Nueva factura
                    </a>
                @endif
                @if ($usuario->puede('tickets.crear'))
                    <a class="btn btn-phoenix-secondary" href="{{ route('tickets.create') }}">
                        <span class="fas fa-headset me-2"></span>Nuevo ticket
                    </a>
                @endif
                @if ($usuario->puede('productos.crear'))
                    <a class="btn btn-primary" href="{{ route('productos.create') }}">
                        <span class="fas fa-plus me-2"></span>Agregar producto
                    </a>
                @endif
            </div>
        </div>

        {{-- Cintas de Métricas Rápidas estilo Phoenix index.html --}}
        <div class="row align-items-center g-4 mb-4 border-top border-bottom border-200 py-4 bg-white mx-n4 px-4 mx-lg-n6 px-lg-6">
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <div class="d-flex align-items-center">
                    <span class="fa-stack" style="min-height: 48px;min-width: 48px;">
                        <span class="fa-solid fa-square fa-stack-2x text-success-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                        <span class="fa-solid fa-circle fa-stack-2x stack-circle text-success-100" data-fa-transform="up-4 right-3 grow-2"></span>
                        <span class="fa-stack-1x fa-solid fa-file-invoice-dollar text-success" data-fa-transform="shrink-2 up-8 right-6"></span>
                    </span>
                    <div class="ms-3">
                        <h4 class="mb-0 text-1100">{{ $moneda }} {{ number_format($facturadoMes, 2) }}</h4>
                        <p class="text-700 fs--1 mb-0 fw-semi-bold">Facturado este mes</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <div class="d-flex align-items-center">
                    <span class="fa-stack" style="min-height: 48px;min-width: 48px;">
                        <span class="fa-solid fa-square fa-stack-2x text-warning-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                        <span class="fa-solid fa-circle fa-stack-2x stack-circle text-warning-100" data-fa-transform="up-4 right-3 grow-2"></span>
                        <span class="fa-stack-1x fa-solid fa-coins text-warning" data-fa-transform="shrink-2 up-8 right-6"></span>
                    </span>
                    <div class="ms-3">
                        <h4 class="mb-0 text-1100">{{ $moneda }} {{ number_format($saldoPorCobrar, 2) }}</h4>
                        <p class="text-700 fs--1 mb-0 fw-semi-bold">Cartera por cobrar</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <div class="d-flex align-items-center">
                    <span class="fa-stack" style="min-height: 48px;min-width: 48px;">
                        <span class="fa-solid fa-square fa-stack-2x text-primary-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                        <span class="fa-solid fa-circle fa-stack-2x stack-circle text-primary-100" data-fa-transform="up-4 right-3 grow-2"></span>
                        <span class="fa-stack-1x fa-solid fa-funnel-dollar text-primary" data-fa-transform="shrink-2 up-8 right-6"></span>
                    </span>
                    <div class="ms-3">
                        <h4 class="mb-0 text-1100">{{ number_format($oportunidadesAbiertas) }}</h4>
                        <p class="text-700 fs--1 mb-0 fw-semi-bold">Oportunidades en curso</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <div class="d-flex align-items-center">
                    <span class="fa-stack" style="min-height: 48px;min-width: 48px;">
                        <span class="fa-solid fa-square fa-stack-2x text-info-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                        <span class="fa-solid fa-circle fa-stack-2x stack-circle text-info-100" data-fa-transform="up-4 right-3 grow-2"></span>
                        <span class="fa-stack-1x fa-solid fa-building-columns text-info" data-fa-transform="shrink-2 up-8 right-6"></span>
                    </span>
                    <div class="ms-3">
                        <h4 class="mb-0 text-1100">Activo</h4>
                        <p class="text-700 fs--1 mb-0 fw-semi-bold">Estado del sistema</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tarjetas KPI estilo dashboard/crm.html con rotación de iconos --}}
        <div class="row g-3 mb-5">
            @foreach ($tarjetas as $t)
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 border border-200">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="d-flex align-items-center icon-wrapper-sm shadow-{{ $t['color'] }}-100" style="transform: rotate(-7.45deg);">
                                        <span class="text-{{ $t['color'] }} z-index-1 ms-2" data-feather="{{ $t['icono'] }}" style="width: 22px; height: 22px;"></span>
                                    </div>
                                    <h6 class="text-700 fs--1 mb-0 ms-3 fw-semi-bold">{{ $t['titulo'] }}</h6>
                                </div>
                                @if ($t['enlace'])
                                    <a class="btn btn-link p-0 fs--2 fw-semi-bold" href="{{ $t['enlace'] }}">
                                        Ver<span class="fas fa-chevron-right ms-1 fs--2"></span>
                                    </a>
                                @endif
                            </div>
                            <h3 class="text-{{ $t['color'] }} mt-2 mb-0 fw-bolder">{{ number_format($t['valor']) }}</h3>
                            <p class="fs--2 text-600 mb-0 mt-1">Registrados en la plataforma</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Tablas operativas principales en estilo Phoenix cards --}}
        <div class="row g-4">
            @if ($usuario->puede('facturas.ver'))
                <div class="col-12 col-xl-6">
                    <div class="card h-100 border border-200 shadow-none">
                        <div class="card-header border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center bg-white">
                            <div>
                                <h5 class="mb-0 text-1100 fw-bold">Últimas Facturas Emitidas</h5>
                                <p class="mb-0 fs--2 text-600">Registro de ventas y estados de cobranza</p>
                            </div>
                            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('facturas.index') }}">Ver todas</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive scrollbar">
                                <table class="table table-sm fs--1 mb-0 align-middle">
                                    <thead class="bg-100">
                                        <tr>
                                            <th class="ps-4 text-700">FACTURA</th>
                                            <th class="text-700">CLIENTE</th>
                                            <th class="text-end text-700">TOTAL</th>
                                            <th class="text-end pe-4 text-700">ESTADO</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                        @forelse ($facturasRecientes as $f)
                                            @php [$nomEst, $colEst] = $estadosFactura[$f->estado] ?? [$f->estado, 'secondary']; @endphp
                                            <tr class="hover-actions-trigger position-static">
                                                <td class="ps-4 py-3 text-nowrap">
                                                    <a class="fw-bold text-primary" href="{{ route('facturas.show', $f->id_factura) }}">
                                                        #{{ $f->numero_completo }}
                                                    </a>
                                                </td>
                                                <td class="text-truncate" style="max-width: 14rem;">
                                                    <span class="text-1000 fw-semi-bold">{{ $f->cliente?->razon_social ?? '—' }}</span>
                                                </td>
                                                <td class="text-end text-nowrap fw-bold text-1000">
                                                    {{ $f->moneda }} {{ number_format((float) $f->total, 2) }}
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $colEst }}">{{ $nomEst }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-600 py-5">
                                                    <span class="fas fa-file-invoice fs-2 text-300 d-block mb-2"></span>
                                                    Sin facturas recientes registradas.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($usuario->puede('tickets.ver'))
                <div class="col-12 col-xl-6">
                    <div class="card h-100 border border-200 shadow-none">
                        <div class="card-header border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center bg-white">
                            <div>
                                <h5 class="mb-0 text-1100 fw-bold">Tickets de Soporte Pendientes</h5>
                                <p class="mb-0 fs--2 text-600">Mesa de ayuda y tiempo de respuesta</p>
                            </div>
                            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('tickets.index') }}">Ver todos</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive scrollbar">
                                <table class="table table-sm fs--1 mb-0 align-middle">
                                    <thead class="bg-100">
                                        <tr>
                                            <th class="ps-4 text-700">TICKET</th>
                                            <th class="text-700">CLIENTE</th>
                                            <th class="text-700">ASIGNADO A</th>
                                            <th class="text-end pe-4 text-700">SLA</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                        @forelse ($ticketsRecientes as $tk)
                                            @php [$sem, $colSla] = \App\Support\Sla::semaforo($tk->fecha_limite_resolucion, $tk->fecha_resolucion); @endphp
                                            <tr class="hover-actions-trigger position-static">
                                                <td class="ps-4 py-3">
                                                    <a class="fw-bold text-primary" href="{{ route('tickets.show', $tk->id_ticket) }}">
                                                        #{{ $tk->numero_ticket }}
                                                    </a>
                                                    <span class="d-block fs--2 text-600 text-truncate" style="max-width: 12rem;">{{ $tk->asunto }}</span>
                                                </td>
                                                <td class="text-truncate" style="max-width: 10rem;">
                                                    <span class="text-900">{{ $tk->cliente?->razon_social ?? '—' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-tag">{{ $tk->asignadoA?->nombre_completo ?? 'Sin asignar' }}</span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $colSla }}">{{ $sem }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-600 py-5">
                                                    <span class="fas fa-headset fs-2 text-300 d-block mb-2"></span>
                                                    No hay tickets abiertos pendientes.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
