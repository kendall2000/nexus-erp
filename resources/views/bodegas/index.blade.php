@extends('layouts.app', ['titulo' => 'Bodegas'])

@section('contenido')
    @php
        $usuario = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$usuario->puede('bodegas.crear'), $usuario->puede('bodegas.editar'), $usuario->puede('bodegas.eliminar')];
        $moneda = \App\Support\Sistema::config()->moneda ?: 'Q';
    @endphp

    <x-page-header
        titulo="Bodegas"
        subtitulo="Lugares donde se guarda el inventario y su valor actual."
        icono="fas fa-warehouse"
        :breadcrumbs="['Inventario y Compras' => null, 'Bodegas' => null]"
    >
        @if ($puedeCrear)
            <a class="btn btn-primary" href="{{ route('bodegas.create') }}">
                <span class="fas fa-plus me-2"></span>Nueva bodega
            </a>
        @endif
    </x-page-header>

    <x-phoenix-table>
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Bodega</th>
                    <th class="text-uppercase text-700">Sucursal</th>
                    <th class="text-uppercase text-700">Responsable</th>
                    <th class="text-end text-uppercase text-700">Productos con existencia</th>
                    <th class="text-end text-uppercase text-700">Valor del inventario</th>
                    <th class="text-uppercase text-700">Estado</th>
                    <th class="pe-0 text-end text-uppercase text-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($bodegas as $b)
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2">
                        <span class="fw-bold text-1000">{{ $b->nombre }}</span>
                        @if ($b->ubicacion)<p class="text-600 fs--2 mb-0 line-clamp-1">{{ $b->ubicacion }}</p>@endif
                    </td>
                    <td class="text-800">{{ $b->sucursal?->nombre ?? '—' }}</td>
                    <td class="text-800">{{ $b->responsable?->nombre_completo ?? '—' }}</td>
                    <td class="text-end text-900">{{ number_format($b->productos_con_stock) }}</td>
                    <td class="text-end fw-bold text-1000">{{ $moneda }} {{ number_format((float) $b->valor_inventario, 2) }}</td>
                    <td>
                        @if ($b->activo)
                            <span class="badge badge-phoenix badge-phoenix-success">Activa</span>
                        @else
                            <span class="badge badge-phoenix badge-phoenix-danger">Inactiva</span>
                        @endif
                    </td>
                    <td class="pe-0 text-end text-nowrap">
                        @if ($puedeEditar)
                            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('bodegas.edit', $b->id_bodega) }}">Editar</a>
                            <form method="POST" action="{{ route('bodegas.estado', $b->id_bodega) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-phoenix-{{ $b->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $b->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        @endif
                        @if ($puedeEliminar)
                            <form method="POST" action="{{ route('bodegas.destroy', $b->id_bodega) }}" class="d-inline" onsubmit="return confirm(@js('¿Eliminar la bodega '.$b->nombre.'?'))">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link text-danger btn-sm px-1" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-700 py-5">No hay bodegas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
