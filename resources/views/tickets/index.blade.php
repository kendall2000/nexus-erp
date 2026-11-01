@extends('layouts.app', ['titulo' => 'Tickets'])

@section('contenido')
    @php
        $yo = auth()->user();
        $totalTickets = $tickets->total();
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Comercial</li>
            <li class="breadcrumb-item active">Tickets de Soporte</li>
        </ol>
    </nav>

    <div class="mb-9">
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Mesa de Ayuda & Tickets</h2>
            </div>
        </div>

        {{-- Tarjetas KPI de Resumen estilo Phoenix widgets --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Tickets Abiertos</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ number_format($resumen['abiertos']) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-inbox text-primary fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Sin Asignar</h6>
                                <h3 class="{{ $resumen['sinAsignar'] ? 'text-warning' : 'text-1100' }} mb-0 fw-bolder">{{ number_format($resumen['sinAsignar']) }}</h3>
                                @if ($resumen['sinAsignar'])
                                    <a class="fs--2 fw-semi-bold text-warning" href="{{ route('tickets.index', ['asignado' => 'ninguno']) }}">Ver tickets sin asignar</a>
                                @endif
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-warning-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-user-clock text-warning fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">SLA Vencido</h6>
                                <h3 class="{{ $resumen['vencidos'] ? 'text-danger' : 'text-1100' }} mb-0 fw-bolder">{{ number_format($resumen['vencidos']) }}</h3>
                                @if ($resumen['vencidos'])
                                    <a class="fs--2 fw-semi-bold text-danger" href="{{ route('tickets.index', ['vencidos' => 1]) }}">Ver tickets con alerta roja</a>
                                @endif
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-danger-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-exclamation-triangle text-danger fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="ticketsTable">
            {{-- Toolbar idéntico a apps/e-commerce/admin/orders.html --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('tickets.index') }}" class="row g-3">
                    <div class="col-auto">
                        <div class="search-box">
                            <div class="position-relative">
                                <input class="form-control search-input search" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número, asunto o cliente..." aria-label="Search" />
                                <span class="fas fa-search search-box-icon"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto scrollbar overflow-hidden-y flex-grow-1">
                        <div class="btn-group position-static" role="group">
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="estado" onchange="this.form.submit()">
                                    <option value="">Abiertos</option>
                                    <option value="todos" @selected(($filtros['estado'] ?? '') === 'todos')>Todos los estados</option>
                                    @foreach ($estados as $codigo => [$nombre])
                                        <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="prioridad" onchange="this.form.submit()">
                                    <option value="">Toda prioridad</option>
                                    @foreach ($prioridades as $codigo => [$nombre])
                                        <option value="{{ $codigo }}" @selected(($filtros['prioridad'] ?? '') === $codigo)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="cliente" onchange="this.form.submit()">
                                    <option value="">Todos los clientes</option>
                                    @foreach ($clientes as $c)
                                        <option value="{{ $c->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="asignado" onchange="this.form.submit()">
                                    <option value="">Cualquier agente</option>
                                    <option value="ninguno" @selected(($filtros['asignado'] ?? '') === 'ninguno')>Sin asignar</option>
                                    @foreach ($agentes as $a)
                                        <option value="{{ $a->id_empleado }}" @selected((string) ($filtros['asignado'] ?? '') === (string) $a->id_empleado)>{{ $a->nombre_completo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if (array_filter($filtros))
                                <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('tickets.index') }}" title="Limpiar filtros">
                                    <span class="fas fa-times me-1"></span>Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        @if ($yo->puede('tickets.configurar'))
                            <a class="btn btn-phoenix-secondary" href="{{ route('tickets.configuracion') }}">
                                <span class="fas fa-cog me-2"></span>Categorías & SLA
                            </a>
                        @endif
                        @if ($yo->puede('tickets.crear'))
                            <a class="btn btn-primary" href="{{ route('tickets.create') }}">
                                <span class="fas fa-plus me-2"></span>Nuevo ticket
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Tabla con estructura visual exacta de Phoenix --}}
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="white-space-nowrap fs--1 align-middle ps-0" style="max-width:20px; width:18px;">
                                    <div class="form-check mb-0 fs-0">
                                        <input class="form-check-input" id="checkbox-bulk-tickets-select" type="checkbox" />
                                    </div>
                                </th>
                                <th class="sort white-space-nowrap align-middle ps-3" scope="col" style="width:140px;">TICKET</th>
                                <th class="sort align-middle ps-4" scope="col" style="min-width:280px;">ASUNTO & DETALLE</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:250px;">CLIENTE</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:130px;">PRIORIDAD</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:180px;">AGENTE</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:160px;">SLA RESOLUCIÓN</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:130px;">ESTADO</th>
                                <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody class="list" id="tickets-table-body">
                            @forelse ($tickets as $t)
                                @php
                                    [$nombreEstado, $color] = $estados[$t->estado] ?? [$t->estado, 'secondary'];
                                    [$nombrePrioridad, $colorPrioridad] = $prioridades[$t->prioridad] ?? [$t->prioridad, 'secondary'];
                                    [$semaforo, $colorSla] = \App\Support\Sla::semaforo($t->fecha_limite_resolucion, $t->fecha_resolucion);
                                @endphp
                                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                                    <td class="fs--1 align-middle ps-0">
                                        <div class="form-check mb-0 fs-0">
                                            <input class="form-check-input" type="checkbox" />
                                        </div>
                                    </td>
                                    <td class="order align-middle white-space-nowrap ps-3 py-3">
                                        <a class="fw-bold text-primary" href="{{ route('tickets.show', $t->id_ticket) }}">
                                            #{{ $t->numero_ticket }}
                                        </a>
                                    </td>
                                    <td class="align-middle ps-4">
                                        <a class="fw-bold text-1100 text-hover-1000 mb-0 d-block line-clamp-1" href="{{ route('tickets.show', $t->id_ticket) }}">
                                            {{ $t->asunto }}
                                        </a>
                                        <span class="fs--2 text-600">{{ $t->categoria?->nombre ?? 'General' }}</span>
                                    </td>
                                    <td class="customer align-middle white-space-nowrap ps-4">
                                        <span class="fw-semi-bold text-1000">{{ $t->cliente?->razon_social ?? '—' }}</span>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $colorPrioridad }}">{{ $nombrePrioridad }}</span>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        @if ($t->asignadoA)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-s me-2">
                                                    <div class="avatar-name rounded-circle bg-soft-primary text-primary">
                                                        <span>{{ mb_strtoupper(mb_substr($t->asignadoA->nombre_completo, 0, 1)) }}</span>
                                                    </div>
                                                </div>
                                                <span class="text-800 fw-semi-bold">{{ $t->asignadoA->nombre_completo }}</span>
                                            </div>
                                        @else
                                            <span class="badge badge-phoenix badge-phoenix-secondary fs--2">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 fs--1">
                                        <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $colorSla }}">{{ $semaforo }}</span>
                                        @if ($t->fecha_limite_resolucion)
                                            <span class="d-block fs--2 text-600 mt-1">{{ $t->fecha_limite_resolucion->format('d/m/Y H:i') }}</span>
                                        @endif
                                    </td>
                                    <td class="payment_status align-middle white-space-nowrap ps-4">
                                        <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                        <div class="font-sans-serif btn-reveal-trigger position-static">
                                            <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                                <span class="fas fa-ellipsis-h fs--2"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end py-2">
                                                <a class="dropdown-item" href="{{ route('tickets.show', $t->id_ticket) }}">
                                                    <span class="fas fa-eye me-2"></span>Atender Ticket
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-700 py-5">
                                        <span class="fas fa-headset fs-2 text-300 d-block mb-2"></span>
                                        {{ array_filter($filtros) ? 'Ningún ticket coincide con los filtros aplicados.' : 'No hay tickets abiertos pendientes.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer de paginación --}}
                <div class="row align-items-center justify-content-between py-2 pe-0 fs--1 border-top border-200">
                    <div class="col-auto d-flex">
                        <p class="mb-0 d-none d-sm-block me-3 fw-semi-bold text-900">
                            Mostrando {{ $tickets->firstItem() ?? 0 }} a {{ $tickets->lastItem() ?? 0 }}
                            <span class="text-600">de</span> {{ $tickets->total() }} tickets
                        </p>
                    </div>
                    <div class="col-auto d-flex">
                        {{ $tickets->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
