@extends('layouts.app', ['titulo' => 'Tipos de servicio'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$yo->puede('tipos_servicio.crear'), $yo->puede('tipos_servicio.editar'), $yo->puede('tipos_servicio.eliminar')];
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Tipos de servicio</h2>
            <p class="text-700 fw-semi-bold mb-0">Lo que se vende. La cuenta de ingreso y el centro de costo se heredan en facturas y contratos.</p>
        </div>
        <div class="d-flex gap-2">
            @if ($yo->puede('tipos_servicio.exportar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('tipos-servicio.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($puedeCrear)
                <a class="btn btn-primary" href="{{ route('tipos-servicio.create', array_filter(['linea' => $filtros['linea'] ?? null])) }}"><span class="fas fa-plus me-2"></span>Nuevo servicio</a>
            @endif
        </div>
    </div>

    @if ($lineas->isEmpty())
        <div class="alert alert-soft-info fs--1"><span class="fas fa-info-circle me-2"></span>Primero crea una línea de negocio: cada servicio pertenece a una.</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-5"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre del servicio" maxlength="100" /></div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="linea">
                        <option value="">Todas las líneas</option>
                        @foreach ($lineas as $l)<option value="{{ $l->id_linea }}" @selected((int) ($filtros['linea'] ?? 0) === $l->id_linea)>{{ $l->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Todos</option>
                        <option value="activos" @selected(($filtros['estado'] ?? '') === 'activos')>Activos</option>
                        <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('tipos-servicio.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead><tr><th>Servicio</th><th>Línea</th><th class="text-end">Precio base</th><th>Cuenta / centro</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse ($servicios as $s)
                        <tr class="{{ $s->activo ? '' : 'text-500' }}">
                            <td><span class="fw-semi-bold">{{ $s->nombre }}</span> <span class="text-600 fs--2">por {{ strtolower($s->unidad_medida) }}</span></td>
                            <td>{{ $s->lineaNegocio?->nombre }}</td>
                            <td class="text-end text-nowrap">{{ $s->precio_base !== null && (float) $s->precio_base > 0 ? $s->moneda.' '.number_format((float) $s->precio_base, 2) : '—' }}</td>
                            <td class="fs--2 text-700">{{ $s->cuentaIngreso?->etiqueta ?? 'Sin cuenta' }}<br>{{ $s->centroDefault?->etiqueta ?? 'Sin centro' }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $s->activo ? 'success' : 'danger' }}">{{ $s->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="text-end text-nowrap">
                                @if ($puedeEditar)
                                    <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('tipos-servicio.edit', $s->id_tipo_servicio) }}">Editar</a>
                                    <form method="POST" action="{{ route('tipos-servicio.estado', $s->id_tipo_servicio) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-phoenix-{{ $s->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $s->activo ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                @endif
                                @if ($puedeEliminar)
                                    <form method="POST" action="{{ route('tipos-servicio.destroy', $s->id_tipo_servicio) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar el servicio '.$s->nombre.'?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link text-danger btn-sm px-1" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún servicio coincide con los filtros.' : 'Todavía no hay tipos de servicio.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
