@extends('layouts.app', ['titulo' => 'Series de facturación'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$yo->puede('series_facturacion.crear'), $yo->puede('series_facturacion.editar'), $yo->puede('series_facturacion.eliminar')];
    @endphp

    <x-page-header
        titulo="Series de facturación"
        subtitulo="Prefijo y correlativo de cada tipo de documento. El tipo del documento sale de su serie."
        icono="fas fa-hashtag"
        :breadcrumbs="['Finanzas' => null, 'Series de facturación' => null]"
    >
        @if ($puedeCrear)
            <a class="btn btn-primary" href="{{ route('series-facturacion.create') }}">
                <span class="fas fa-plus me-2"></span>Nueva serie
            </a>
        @endif
    </x-page-header>

    <x-phoenix-table>
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Serie</th>
                    <th class="text-uppercase text-700">Tipo</th>
                    <th class="text-uppercase text-700">Descripción</th>
                    <th class="text-end text-uppercase text-700">Documentos</th>
                    <th class="text-uppercase text-700">Siguiente número</th>
                    <th class="text-uppercase text-700">Estado</th>
                    <th class="pe-0 text-end text-uppercase text-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($series as $s)
                <tr class="hover-actions-trigger btn-reveal-trigger position-static {{ $s->activo ? '' : 'text-500' }}">
                    <td class="ps-0 py-2"><code class="fs-0 fw-bold">{{ $s->codigo_serie }}</code></td>
                    <td class="fw-semi-bold text-900">{{ $tipos[$s->tipo] ?? $s->tipo }}</td>
                    <td class="text-800">{{ $s->descripcion ?? '—' }}</td>
                    <td class="text-end text-900">{{ $s->facturas_count }}</td>
                    <td><code>{{ $s->formatearNumero($s->ultimo_numero + 1) }}</code></td>
                    <td><span class="badge badge-phoenix badge-phoenix-{{ $s->activo ? 'success' : 'danger' }}">{{ $s->activo ? 'Activa' : 'Inactiva' }}</span></td>
                    <td class="pe-0 text-end text-nowrap">
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
                <tr><td colspan="7" class="text-center text-700 py-5">No hay series: crea al menos una de tipo Factura para poder facturar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
