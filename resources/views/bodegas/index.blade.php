@extends('layouts.app', ['titulo' => 'Bodegas'])

@section('contenido')
    @php
        $usuario = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [
            $usuario->puede('bodegas.crear'),
            $usuario->puede('bodegas.editar'),
            $usuario->puede('bodegas.eliminar')
        ];
        $moneda = \App\Support\Sistema::config()->moneda ?: 'Q';
        $totalBodegas = $bodegas->count();
        $valorTotal = $bodegas->sum('valor_inventario');
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Inventario y Compras</li>
            <li class="breadcrumb-item active">Bodegas</li>
        </ol>
    </nav>

    <div class="mb-9">
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Bodegas & Almacenes</h2>
            </div>
        </div>

        {{-- Tarjetas KPI de resumen estilo Phoenix widgets --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Bodegas Registradas</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ number_format($totalBodegas) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-warehouse text-primary fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Valor Total en Stock</h6>
                                <h3 class="text-success mb-0 fw-bolder">{{ $moneda }} {{ number_format((float) $valorTotal, 2) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-success-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-coins text-success fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Bodegas Activas</h6>
                                <h3 class="text-info mb-0 fw-bolder">{{ number_format($bodegas->where('activo', true)->count()) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-check-circle text-info fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="warehousesTable">
            {{-- Toolbar idéntico a Phoenix --}}
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                    <span class="text-700 fw-semi-bold">Centros de acopio y custodia de productos</span>
                </div>
                @if ($puedeCrear)
                    <a class="btn btn-primary" href="{{ route('bodegas.create') }}">
                        <span class="fas fa-plus me-2"></span>Nueva bodega
                    </a>
                @endif
            </div>

            {{-- Tabla con estructura visual exacta de Phoenix --}}
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="white-space-nowrap fs--1 align-middle ps-0" style="max-width:20px; width:18px;">
                                    <div class="form-check mb-0 fs-0">
                                        <input class="form-check-input" id="checkbox-bulk-warehouses-select" type="checkbox" />
                                    </div>
                                </th>
                                <th class="sort white-space-nowrap align-middle ps-3" scope="col" style="min-width:250px;">BODEGA</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:200px;">SUCURSAL</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:220px;">RESPONSABLE</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:180px;">PRODUCTOS CON STOCK</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:180px;">VALOR INVENTARIO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:130px;">ESTADO</th>
                                <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody class="list" id="warehouses-table-body">
                            @forelse ($bodegas as $b)
                                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                                    <td class="fs--1 align-middle ps-0">
                                        <div class="form-check mb-0 fs-0">
                                            <input class="form-check-input" type="checkbox" />
                                        </div>
                                    </td>
                                    <td class="customer align-middle white-space-nowrap ps-3 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-m me-3">
                                                <div class="avatar-name rounded-circle bg-soft-primary text-primary">
                                                    <span class="fas fa-warehouse"></span>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-1100 fw-bold">{{ $b->nombre }}</h6>
                                                @if ($b->ubicacion)
                                                    <p class="fs--2 mb-0 text-600 line-clamp-1">{{ $b->ubicacion }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 text-800">
                                        <span class="badge badge-tag">{{ $b->sucursal?->nombre ?? '—' }}</span>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 text-900 fw-semi-bold">
                                        {{ $b->responsable?->nombre_completo ?? '—' }}
                                    </td>
                                    <td class="total align-middle text-end white-space-nowrap text-900 ps-4">
                                        <span class="badge badge-phoenix badge-phoenix-secondary">{{ number_format($b->productos_con_stock) }} artículos</span>
                                    </td>
                                    <td class="total align-middle text-end white-space-nowrap fw-bold text-1000 ps-4">
                                        {{ $moneda }} {{ number_format((float) $b->valor_inventario, 2) }}
                                    </td>
                                    <td class="payment_status align-middle white-space-nowrap ps-4">
                                        @if ($b->activo)
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-success"><span class="badge-label">Activa</span><span class="ms-1 fas fa-check" style="height:10.8px;width:10.8px;"></span></span>
                                        @else
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-danger"><span class="badge-label">Inactiva</span><span class="ms-1 fas fa-times" style="height:10.8px;width:10.8px;"></span></span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                        <div class="font-sans-serif btn-reveal-trigger position-static">
                                            <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                                <span class="fas fa-ellipsis-h fs--2"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end py-2">
                                                @if ($puedeEditar)
                                                    <a class="dropdown-item" href="{{ route('bodegas.edit', $b->id_bodega) }}">
                                                        <span class="fas fa-edit me-2"></span>Editar
                                                    </a>
                                                    <form method="POST" action="{{ route('bodegas.estado', $b->id_bodega) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="dropdown-item" type="submit">
                                                            <span class="fas {{ $b->activo ? 'fa-ban text-warning' : 'fa-check text-success' }} me-2"></span>
                                                            {{ $b->activo ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($puedeEliminar)
                                                    <div class="dropdown-divider"></div>
                                                    <form method="POST" action="{{ route('bodegas.destroy', $b->id_bodega) }}" onsubmit="return confirm(@js('¿Eliminar la bodega '.$b->nombre.'?'))">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit">
                                                            <span class="fas fa-trash me-2"></span>Eliminar
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-700 py-5">
                                        <span class="fas fa-warehouse fs-2 text-300 d-block mb-2"></span>
                                        Aún no hay bodegas registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
