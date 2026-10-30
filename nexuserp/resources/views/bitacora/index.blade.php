@extends('layouts.app', ['titulo' => 'Bitácora de cambios'])

@section('contenido')
    @php
        $yo = auth()->user();
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Bitácora de cambios</h2>
            <p class="text-700 fw-semi-bold mb-0">Quién creó, modificó o eliminó cada registro. {{ number_format($cambios->total()) }} {{ $cambios->total() === 1 ? 'cambio' : 'cambios' }}{{ array_filter($filtros) ? ' con los filtros aplicados' : '' }}.</p>
        </div>
        @if ($yo->puede('bitacora.exportar'))
            <a class="btn btn-phoenix-secondary" href="{{ route('bitacora.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
        @endif
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="usuario">
                        <option value="">Todos los usuarios</option>
                        @foreach ($usuarios as $u)<option value="{{ $u->id_usuario }}" @selected((int) ($filtros['usuario'] ?? 0) === $u->id_usuario)>{{ $u->nombre_completo }} ({{ $u->username }})</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="tabla">
                        <option value="">Todas las tablas</option>
                        @foreach ($tablas as $codigo => $nombre)<option value="{{ $codigo }}" @selected(($filtros['tabla'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="accion">
                        <option value="">Toda acción</option>
                        @foreach ($acciones as $codigo => [$nombre])<option value="{{ $codigo }}" @selected(($filtros['accion'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-1"><input class="form-control form-control-sm" name="registro" value="{{ $filtros['registro'] ?? '' }}" placeholder="Id" title="Id del registro" /></div>
                <div class="col-md-1"><input class="form-control form-control-sm" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Desde" /></div>
                <div class="col-md-1"><input class="form-control form-control-sm" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Hasta" /></div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('bitacora.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead>
                    <tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Tabla</th><th>Registro</th><th>Cambio</th><th></th></tr>
                    </thead>
                    <tbody>
                    @forelse ($cambios as $c)
                        @php [$etiqueta, $color] = \App\Support\Bitacora::ACCIONES[$c->accion] ?? [$c->accion, 'secondary']; @endphp
                        <tr>
                            <td class="text-nowrap">{{ $c->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td>
                                {{ $c->usuario?->nombre_completo ?? 'Sistema' }}
                                @if ($c->ip_address)<span class="d-block fs--2 text-500">{{ $c->ip_address }}</span>@endif
                            </td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $etiqueta }}</span></td>
                            <td><a href="{{ route('bitacora.index', ['tabla' => $c->tabla_afectada]) }}">{{ \App\Support\Bitacora::tabla($c->tabla_afectada) }}</a></td>
                            <td><a href="{{ route('bitacora.index', ['tabla' => $c->tabla_afectada, 'registro' => $c->id_registro]) }}" title="Historial de este registro">#{{ $c->id_registro }}</a></td>
                            <td class="fs--2 text-800 text-break">{{ \App\Support\Bitacora::resumen($c) }}</td>
                            <td class="text-end"><a class="btn btn-link btn-sm p-0" href="{{ route('bitacora.show', $c->id_cambio) }}" title="Ver detalle"><span class="fas fa-eye"></span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún cambio coincide con los filtros.' : 'Todavía no hay cambios registrados.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $cambios->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
