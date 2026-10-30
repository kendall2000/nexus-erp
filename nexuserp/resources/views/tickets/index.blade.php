@extends('layouts.app', ['titulo' => 'Tickets'])

@section('contenido')
    @php $yo = auth()->user(); @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Tickets</h2>
            <p class="text-700 fw-semi-bold mb-0">Solicitudes e incidentes de clientes con su SLA de respuesta y resolución.</p>
        </div>
        <div class="d-flex gap-2">
            @if ($yo->puede('tickets.configurar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('tickets.configuracion') }}"><span class="fas fa-cog me-2"></span>Categorías y SLA</a>
            @endif
            @if ($yo->puede('tickets.crear'))
                <a class="btn btn-primary" href="{{ route('tickets.create') }}"><span class="fas fa-plus me-2"></span>Nuevo ticket</a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Abiertos</p><h3 class="mb-0">{{ $resumen['abiertos'] }}</h3></div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Sin asignar</p><h3 class="mb-0 {{ $resumen['sinAsignar'] ? 'text-warning' : '' }}">{{ $resumen['sinAsignar'] }}</h3>
            @if ($resumen['sinAsignar'])<a class="fs--2" href="{{ route('tickets.index', ['asignado' => 'ninguno']) }}">Ver cuáles</a>@endif
        </div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">SLA de resolución vencido</p><h3 class="mb-0 {{ $resumen['vencidos'] ? 'text-danger' : '' }}">{{ $resumen['vencidos'] }}</h3>
            @if ($resumen['vencidos'])<a class="fs--2" href="{{ route('tickets.index', ['vencidos' => 1]) }}">Ver cuáles</a>@endif
        </div></div></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número, asunto o cliente" maxlength="100" /></div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Abiertos</option>
                        <option value="todos" @selected(($filtros['estado'] ?? '') === 'todos')>Todos</option>
                        @foreach ($estados as $codigo => [$nombre])<option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="prioridad">
                        <option value="">Toda prioridad</option>
                        @foreach ($prioridades as $codigo => [$nombre])<option value="{{ $codigo }}" @selected(($filtros['prioridad'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="cliente">
                        <option value="">Todos los clientes</option>
                        @foreach ($clientes as $c)<option value="{{ $c->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="asignado">
                        <option value="">Cualquier agente</option>
                        <option value="ninguno" @selected(($filtros['asignado'] ?? '') === 'ninguno')>Sin asignar</option>
                        @foreach ($agentes as $a)<option value="{{ $a->id_empleado }}" @selected((string) ($filtros['asignado'] ?? '') === (string) $a->id_empleado)>{{ $a->nombre_completo }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-2">
                    @if (! empty($filtros['vencidos']))<input type="hidden" name="vencidos" value="1" />@endif
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit" title="Filtrar"><span class="fas fa-filter"></span></button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('tickets.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Ticket</th><th>Cliente</th><th>Prioridad</th><th>Agente</th><th>SLA resolución</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($tickets as $t)
                        @php
                            [$nombreEstado, $color] = $estados[$t->estado] ?? [$t->estado, 'secondary'];
                            [$nombrePrioridad, $colorPrioridad] = $prioridades[$t->prioridad] ?? [$t->prioridad, 'secondary'];
                            [$semaforo, $colorSla] = \App\Support\Sla::semaforo($t->fecha_limite_resolucion, $t->fecha_resolucion);
                        @endphp
                        <tr>
                            <td>
                                <a class="fw-semi-bold" href="{{ route('tickets.show', $t->id_ticket) }}">{{ $t->numero_ticket }}</a>
                                <span class="d-block text-700 line-clamp-1">{{ $t->asunto }}</span>
                            </td>
                            <td>{{ $t->cliente?->razon_social }}<span class="d-block fs--2 text-600">{{ $t->categoria?->nombre }}</span></td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $colorPrioridad }}">{{ $nombrePrioridad }}</span></td>
                            <td>{{ $t->asignadoA?->nombre_completo ?? '—' }}</td>
                            <td class="text-nowrap">
                                <span class="badge badge-phoenix badge-phoenix-{{ $colorSla }}">{{ $semaforo }}</span>
                                @if ($t->fecha_limite_resolucion)<span class="d-block fs--2 text-600">{{ $t->fecha_limite_resolucion->format('d/m H:i') }}</span>@endif
                            </td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún ticket coincide con los filtros.' : 'No hay tickets abiertos.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $tickets->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
