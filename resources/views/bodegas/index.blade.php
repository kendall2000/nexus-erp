@extends('layouts.app', ['titulo' => 'Bodegas'])

@section('contenido')
    @php
        $usuario = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$usuario->puede('bodegas.crear'), $usuario->puede('bodegas.editar'), $usuario->puede('bodegas.eliminar')];
        $moneda = \App\Support\Sistema::config()->moneda ?: 'Q';
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Bodegas</h2>
            <p class="text-700 fw-semi-bold mb-0">Lugares donde se guarda el inventario y su valor actual.</p>
        </div>
        @if ($puedeCrear)
            <a class="btn btn-primary" href="{{ route('bodegas.create') }}"><span class="fas fa-plus me-2"></span>Nueva bodega</a>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead><tr><th>Bodega</th><th>Sucursal</th><th>Responsable</th><th class="text-end">Productos con existencia</th><th class="text-end">Valor del inventario</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse ($bodegas as $b)
                        <tr>
                            <td>
                                <span class="fw-semi-bold">{{ $b->nombre }}</span>
                                @if ($b->ubicacion)<p class="text-600 fs--2 mb-0 line-clamp-1">{{ $b->ubicacion }}</p>@endif
                            </td>
                            <td>{{ $b->sucursal?->nombre ?? '—' }}</td>
                            <td>{{ $b->responsable?->nombre_completo ?? '—' }}</td>
                            <td class="text-end">{{ number_format($b->productos_con_stock) }}</td>
                            <td class="text-end fw-semi-bold">{{ $moneda }} {{ number_format((float) $b->valor_inventario, 2) }}</td>
                            <td>
                                @if ($b->activo)
                                    <span class="badge badge-phoenix badge-phoenix-success">Activa</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-danger">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
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
                        <tr><td colspan="7" class="text-center text-700 py-4">No hay bodegas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
