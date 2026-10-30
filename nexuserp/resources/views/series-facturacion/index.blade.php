@extends('layouts.app', ['titulo' => 'Series de facturación'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$yo->puede('series_facturacion.crear'), $yo->puede('series_facturacion.editar'), $yo->puede('series_facturacion.eliminar')];
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Series de facturación</h2>
            <p class="text-700 fw-semi-bold mb-0">Prefijo y correlativo de cada tipo de documento. El tipo del documento sale de su serie.</p>
        </div>
        @if ($puedeCrear)
            <a class="btn btn-primary" href="{{ route('series-facturacion.create') }}"><span class="fas fa-plus me-2"></span>Nueva serie</a>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead><tr><th>Serie</th><th>Tipo</th><th>Descripción</th><th class="text-end">Documentos</th><th>Siguiente número</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse ($series as $s)
                        <tr class="{{ $s->activo ? '' : 'text-500' }}">
                            <td><code class="fs-0">{{ $s->codigo_serie }}</code></td>
                            <td>{{ $tipos[$s->tipo] ?? $s->tipo }}</td>
                            <td>{{ $s->descripcion ?? '—' }}</td>
                            <td class="text-end">{{ $s->facturas_count }}</td>
                            <td><code>{{ $s->formatearNumero($s->ultimo_numero + 1) }}</code></td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $s->activo ? 'success' : 'danger' }}">{{ $s->activo ? 'Activa' : 'Inactiva' }}</span></td>
                            <td class="text-end text-nowrap">
                                @if ($puedeEditar)
                                    <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('series-facturacion.edit', $s->id_serie) }}">Editar</a>
                                    <form method="POST" action="{{ route('series-facturacion.estado', $s->id_serie) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-phoenix-{{ $s->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $s->activo ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                @endif
                                @if ($puedeEliminar && ! $s->facturas_count)
                                    <form method="POST" action="{{ route('series-facturacion.destroy', $s->id_serie) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar la serie '.$s->codigo_serie.'?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link text-danger btn-sm px-1" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-700 py-4">No hay series: crea al menos una de tipo Factura para poder facturar.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
