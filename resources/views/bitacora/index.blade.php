@extends('layouts.app', ['titulo' => 'Bitácora de cambios'])

@section('contenido')
    @php
        $yo = auth()->user();
    @endphp

    <x-page-header
        titulo="Bitácora de cambios"
        :subtitulo="'Quién creó, modificó o eliminó cada registro. '.number_format($cambios->total()).' '.($cambios->total() === 1 ? 'cambio' : 'cambios').(array_filter($filtros) ? ' con los filtros aplicados' : '').'.'"
        icono="fas fa-history"
        :breadcrumbs="['Sistema' => null, 'Bitácora' => null]"
    >
        @if ($yo->puede('bitacora.exportar'))
            <a class="btn btn-phoenix-secondary" href="{{ route('bitacora.exportar', request()->query()) }}">
                <span class="fas fa-file-excel me-2"></span>Exportar
            </a>
        @endif
    </x-page-header>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select class="form-select" name="usuario">
                    <option value="">Todos los usuarios</option>
                    @foreach ($usuarios as $u)
                        <option value="{{ $u->id_usuario }}" @selected((int) ($filtros['usuario'] ?? 0) === $u->id_usuario)>{{ $u->nombre_completo }} ({{ $u->username }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="tabla">
                    <option value="">Todas las tablas</option>
                    @foreach ($tablas as $codigo => $nombre)
                        <option value="{{ $codigo }}" @selected(($filtros['tabla'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="accion">
                    <option value="">Toda acción</option>
                    @foreach ($acciones as $codigo => [$nombre])
                        <option value="{{ $codigo }}" @selected(($filtros['accion'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1"><input class="form-control" name="registro" value="{{ $filtros['registro'] ?? '' }}" placeholder="Id" title="Id del registro" /></div>
            <div class="col-md-1"><input class="form-control" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Desde" /></div>
            <div class="col-md-1"><input class="form-control" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Hasta" /></div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-phoenix-secondary w-100" type="submit">Filtrar</button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('bitacora.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$cambios">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Fecha</th>
                    <th class="text-uppercase text-700">Usuario</th>
                    <th class="text-uppercase text-700">Acción</th>
                    <th class="text-uppercase text-700">Tabla</th>
                    <th class="text-uppercase text-700">Registro</th>
                    <th class="text-uppercase text-700">Cambio</th>
                    <th class="pe-0 text-end"></th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($cambios as $c)
                @php [$etiqueta, $color] = \App\Support\Bitacora::ACCIONES[$c->accion] ?? [$c->accion, 'secondary']; @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2 text-nowrap text-800">{{ $c->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td>
                        <span class="fw-semi-bold text-900">{{ $c->usuario?->nombre_completo ?? 'Sistema' }}</span>
                        @if ($c->ip_address)<span class="d-block fs--2 text-500">{{ $c->ip_address }}</span>@endif
                    </td>
                    <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $etiqueta }}</span></td>
                    <td><a class="fw-semi-bold" href="{{ route('bitacora.index', ['tabla' => $c->tabla_afectada]) }}">{{ \App\Support\Bitacora::tabla($c->tabla_afectada) }}</a></td>
                    <td><a class="fw-bold" href="{{ route('bitacora.index', ['tabla' => $c->tabla_afectada, 'registro' => $c->id_registro]) }}" title="Historial de este registro">#{{ $c->id_registro }}</a></td>
                    <td class="fs--2 text-800 text-break">{{ \App\Support\Bitacora::resumen($c) }}</td>
                    <td class="pe-0 text-end">
                        <a class="btn btn-phoenix-secondary btn-sm px-2" href="{{ route('bitacora.show', $c->id_cambio) }}" title="Ver detalle"><span class="fas fa-eye"></span></a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-700 py-5">
                        <span class="fas fa-history fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ningún cambio coincide con los filtros.' : 'Todavía no hay cambios registrados.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
