@extends('layouts.app', ['titulo' => 'Inicio'])

{{-- Estructura: index.html y dashboard/crm.html de Phoenix --}}
@section('contenido')
    @php
        $moneda = \App\Support\Sistema::config()->moneda ?: 'Q';
        $estadosFactura = \App\Http\Controllers\FacturaController::ESTADOS;
    @endphp
    <div class="pb-5">
        <div class="row g-4">
            <div class="col-12">
                <div class="mb-4 d-flex flex-wrap justify-content-between align-items-end gap-3">
                    <div>
                        <h2 class="mb-2 text-1100">Hola, {{ $usuario->nombre_completo ?: $usuario->username }}</h2>
                        <h5 class="text-700 fw-semi-bold mb-0">
                            {{ $empresa?->nombre_comercial ? $empresa->nombre_comercial.' · ' : '' }}{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM, H:mm')) }}
                        </h5>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($usuario->puede('facturas.crear'))
                            <a class="btn btn-phoenix-primary btn-sm" href="{{ route('facturas.create') }}"><span class="fas fa-plus me-2"></span>Nueva factura</a>
                        @endif
                        @if ($usuario->puede('tickets.crear'))
                            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('tickets.create') }}"><span class="fas fa-headset me-2"></span>Nuevo ticket</a>
                        @endif
                    </div>
                </div>

                {{-- Tira de indicadores estilo Phoenix index.html --}}
                <div class="row align-items-center g-4 mb-5 border-bottom border-200 pb-4">
                    <div class="col-12 col-md-auto">
                        <div class="d-flex align-items-center">
                            <span class="fa-stack" style="min-height: 46px;min-width: 46px;">
                                <span class="fa-solid fa-square fa-stack-2x dark__text-opacity-50 text-success-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                                <span class="fa-solid fa-circle fa-stack-2x stack-circle text-stats-circle-success" data-fa-transform="up-4 right-3 grow-2"></span>
                                <span class="fa-stack-1x fa-solid fa-file-invoice-dollar text-success" data-fa-transform="shrink-2 up-8 right-6"></span>
                            </span>
                            <div class="ms-3">
                                <h4 class="mb-0">{{ $moneda }} {{ number_format($facturadoMes, 2) }}</h4>
                                <p class="text-800 fs--1 mb-0">Facturado este mes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <div class="d-flex align-items-center">
                            <span class="fa-stack" style="min-height: 46px;min-width: 46px;">
                                <span class="fa-solid fa-square fa-stack-2x dark__text-opacity-50 text-warning-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                                <span class="fa-solid fa-circle fa-stack-2x stack-circle text-stats-circle-warning" data-fa-transform="up-4 right-3 grow-2"></span>
                                <span class="fa-stack-1x fa-solid fa-coins text-warning" data-fa-transform="shrink-2 up-8 right-6"></span>
                            </span>
                            <div class="ms-3">
                                <h4 class="mb-0">{{ $moneda }} {{ number_format($saldoPorCobrar, 2) }}</h4>
                                <p class="text-800 fs--1 mb-0">Cartera por cobrar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <div class="d-flex align-items-center">
                            <span class="fa-stack" style="min-height: 46px;min-width: 46px;">
                                <span class="fa-solid fa-square fa-stack-2x dark__text-opacity-50 text-primary-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                                <span class="fa-solid fa-circle fa-stack-2x stack-circle text-stats-circle-primary" data-fa-transform="up-4 right-3 grow-2"></span>
                                <span class="fa-stack-1x fa-solid fa-funnel-dollar text-primary" data-fa-transform="shrink-2 up-8 right-6"></span>
                            </span>
                            <div class="ms-3">
                                <h4 class="mb-0">{{ number_format($oportunidadesAbiertas) }} oportunidades</h4>
                                <p class="text-800 fs--1 mb-0">En embudo de ventas</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tarjetas principales estilo dashboard/crm.html --}}
                <div class="row g-3 mb-5">
                    @foreach ($tarjetas as $t)
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex align-items-center icon-wrapper-sm shadow-{{ $t['color'] }}-100" style="transform: rotate(-7.45deg);">
                                                <span class="text-{{ $t['color'] }} z-index-1 ms-2" data-feather="{{ $t['icono'] }}" style="width: 22px; height: 22px;"></span>
                                            </div>
                                            <p class="text-700 fs--1 mb-0 ms-3 fw-semi-bold">{{ $t['titulo'] }}</p>
                                        </div>
                                        @if ($t['enlace'])
                                            <a class="btn btn-link p-0 fs--1 white-space-nowrap" href="{{ $t['enlace'] }}">Ver<span class="fas fa-chevron-right ms-1 fs--2"></span></a>
                                        @endif
                                    </div>
                                    <p class="text-{{ $t['color'] }} mt-2 fs-2 fw-bold mb-0">{{ number_format($t['valor']) }} <span class="fs--1 text-700 fw-normal">registros</span></p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Tablas operativas recientes --}}
                <div class="row g-4">
                    @if ($usuario->puede('facturas.ver'))
                        <div class="col-12 col-xl-6">
                            <div class="card h-100">
                                <div class="card-header border-bottom border-200 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0 text-1100">Últimas facturas</h5>
                                        <p class="mb-0 fs--2 text-700">Facturación reciente y saldos pendientes</p>
                                    </div>
                                    <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('facturas.index') }}">Ver todas</a>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive scrollbar">
                                        <table class="table table-sm fs--1 mb-0 align-middle">
                                            <thead class="bg-soft">
                                                <tr>
                                                    <th class="ps-3">Número</th>
                                                    <th>Cliente</th>
                                                    <th class="text-end">Total</th>
                                                    <th class="text-end pe-3">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($facturasRecientes as $f)
                                                    @php [$nomEst, $colEst] = $estadosFactura[$f->estado] ?? [$f->estado, 'secondary']; @endphp
                                                    <tr>
                                                        <td class="ps-3 text-nowrap"><a class="fw-bold" href="{{ route('facturas.show', $f->id_factura) }}">{{ $f->numero_completo }}</a></td>
                                                        <td class="text-truncate" style="max-width: 10rem;">{{ $f->cliente?->razon_social ?? '—' }}</td>
                                                        <td class="text-end text-nowrap fw-semi-bold">{{ $f->moneda }} {{ number_format((float) $f->total, 2) }}</td>
                                                        <td class="text-end pe-3"><span class="badge badge-phoenix badge-phoenix-{{ $colEst }}">{{ $nomEst }}</span></td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center text-600 py-4">Sin facturas recientes.</td></tr>
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
                            <div class="card h-100">
                                <div class="card-header border-bottom border-200 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0 text-1100">Tickets abiertos</h5>
                                        <p class="mb-0 fs--2 text-700">Solicitudes de clientes pendientes de atención</p>
                                    </div>
                                    <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('tickets.index') }}">Ver todos</a>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive scrollbar">
                                        <table class="table table-sm fs--1 mb-0 align-middle">
                                            <thead class="bg-soft">
                                                <tr>
                                                    <th class="ps-3">Ticket</th>
                                                    <th>Cliente</th>
                                                    <th>Agente</th>
                                                    <th class="text-end pe-3">SLA</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($ticketsRecientes as $tk)
                                                    @php [$sem, $colSla] = \App\Support\Sla::semaforo($tk->fecha_limite_resolucion, $tk->fecha_resolucion); @endphp
                                                    <tr>
                                                        <td class="ps-3">
                                                            <a class="fw-bold" href="{{ route('tickets.show', $tk->id_ticket) }}">{{ $tk->numero_ticket }}</a>
                                                            <span class="d-block fs--2 text-600 text-truncate" style="max-width: 11rem;">{{ $tk->asunto }}</span>
                                                        </td>
                                                        <td class="text-truncate" style="max-width: 9rem;">{{ $tk->cliente?->razon_social ?? '—' }}</td>
                                                        <td>{{ $tk->asignadoA?->nombre_completo ?? 'Sin asignar' }}</td>
                                                        <td class="text-end pe-3"><span class="badge badge-phoenix badge-phoenix-{{ $colSla }}">{{ $sem }}</span></td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center text-600 py-4">No hay tickets abiertos.</td></tr>
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
        </div>
    </div>
@endsection
