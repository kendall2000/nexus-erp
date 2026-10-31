@extends('layouts.app', ['titulo' => 'Tickets'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <x-page-header
        titulo="Tickets"
        subtitulo="Solicitudes e incidentes de clientes con su SLA de respuesta y resolución."
        icono="fas fa-headset"
        :breadcrumbs="['Comercial' => null, 'Tickets' => null]"
    >
        @if ($yo->puede('tickets.configurar'))
            <a class="btn btn-phoenix-secondary" href="{{ route('tickets.configuracion') }}">
                <span class="fas fa-cog me-2"></span>Categorías y SLA
            </a>
        @endif
        @if ($yo->puede('tickets.crear'))
            <a class="btn btn-primary" href="{{ route('tickets.create') }}">
                <span class="fas fa-plus me-2"></span>Nuevo ticket
            </a>
        @endif
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <x-stat-card etiqueta="Abiertos" :valor="$resumen['abiertos']" icono="fas fa-inbox" color="primary" />
        </div>
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="fs--1 text-700 fw-semi-bold mb-1">Sin asignar</p>
                        <h3 class="mb-0 {{ $resumen['sinAsignar'] ? 'text-warning' : 'text-1100' }}">{{ $resumen['sinAsignar'] }}</h3>
                        @if ($resumen['sinAsignar'])
                            <a class="fs--2 fw-semi-bold" href="{{ route('tickets.index', ['asignado' => 'ninguno']) }}">Ver cuáles</a>
                        @endif
                    </div>
                    <div class="icon-wrapper-sm bg-warning-100 rounded-3 d-flex align-items-center justify-content-center" style="width:2.5rem;height:2.5rem">
                        <span class="fas fa-user-clock text-warning fs-0"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="fs--1 text-700 fw-semi-bold mb-1">SLA de resolución vencido</p>
                        <h3 class="mb-0 {{ $resumen['vencidos'] ? 'text-danger' : 'text-1100' }}">{{ $resumen['vencidos'] }}</h3>
                        @if ($resumen['vencidos'])
                            <a class="fs--2 fw-semi-bold text-danger" href="{{ route('tickets.index', ['vencidos' => 1]) }}">Ver cuáles</a>
                        @endif
                    </div>
                    <div class="icon-wrapper-sm bg-danger-100 rounded-3 d-flex align-items-center justify-content-center" style="width:2.5rem;height:2.5rem">
                        <span class="fas fa-exclamation-triangle text-danger fs-0"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número, asunto o cliente" maxlength="100" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="estado">
                    <option value="">Abiertos</option>
                    <option value="todos" @selected(($filtros['estado'] ?? '') === 'todos')>Todos</option>
                    @foreach ($estados as $codigo => [$nombre])
                        <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="prioridad">
                    <option value="">Toda prioridad</option>
                    @foreach ($prioridades as $codigo => [$nombre])
                        <option value="{{ $codigo }}" @selected(($filtros['prioridad'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="cliente">
                    <option value="">Todos los clientes</option>
                    @foreach ($clientes as $c)
                        <option value="{{ $c->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="asignado">
                    <option value="">Cualquier agente</option>
                    <option value="ninguno" @selected(($filtros['asignado'] ?? '') === 'ninguno')>Sin asignar</option>
                    @foreach ($agentes as $a)
                        <option value="{{ $a->id_empleado }}" @selected((string) ($filtros['asignado'] ?? '') === (string) $a->id_empleado)>{{ $a->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-flex gap-2">
                @if (! empty($filtros['vencidos']))<input type="hidden" name="vencidos" value="1" />@endif
                <button class="btn btn-phoenix-secondary w-100" type="submit" title="Filtrar"><span class="fas fa-filter"></span></button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-2" href="{{ route('tickets.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$tickets">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Ticket</th>
                    <th class="text-uppercase text-700">Cliente</th>
                    <th class="text-uppercase text-700">Prioridad</th>
                    <th class="text-uppercase text-700">Agente</th>
                    <th class="text-uppercase text-700">SLA resolución</th>
                    <th class="pe-0 text-end text-uppercase text-700">Estado</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($tickets as $t)
                @php
                    [$nombreEstado, $color] = $estados[$t->estado] ?? [$t->estado, 'secondary'];
                    [$nombrePrioridad, $colorPrioridad] = $prioridades[$t->prioridad] ?? [$t->prioridad, 'secondary'];
                    [$semaforo, $colorSla] = \App\Support\Sla::semaforo($t->fecha_limite_resolucion, $t->fecha_resolucion);
                @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2">
                        <a class="fw-bold text-1100 text-decoration-none" href="{{ route('tickets.show', $t->id_ticket) }}">{{ $t->numero_ticket }}</a>
                        <span class="d-block text-700 line-clamp-1">{{ $t->asunto }}</span>
                    </td>
                    <td>
                        <span class="fw-semi-bold text-900">{{ $t->cliente?->razon_social }}</span>
                        <span class="d-block fs--2 text-600">{{ $t->categoria?->nombre }}</span>
                    </td>
                    <td><span class="badge badge-phoenix badge-phoenix-{{ $colorPrioridad }}">{{ $nombrePrioridad }}</span></td>
                    <td class="text-900">{{ $t->asignadoA?->nombre_completo ?? '—' }}</td>
                    <td class="text-nowrap">
                        <span class="badge badge-phoenix badge-phoenix-{{ $colorSla }}">{{ $semaforo }}</span>
                        @if ($t->fecha_limite_resolucion)
                            <span class="d-block fs--2 text-600">{{ $t->fecha_limite_resolucion->format('d/m H:i') }}</span>
                        @endif
                    </td>
                    <td class="pe-0 text-end">
                        <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-700 py-5">
                        <span class="fas fa-headset fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ningún ticket coincide con los filtros.' : 'No hay tickets abiertos.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
