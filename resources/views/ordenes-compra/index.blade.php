@extends('layouts.app', ['titulo' => 'Órdenes de compra'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <x-page-header
        titulo="Órdenes de compra"
        subtitulo="Borrador → aprobada → recibida. Al aprobar se ejecuta el presupuesto."
        icono="fas fa-shopping-cart"
        :breadcrumbs="['Inventario y Compras' => null, 'Órdenes de compra' => null]"
    >
        @if ($yo->puede('ordenes_compra.exportar'))
            <a class="btn btn-phoenix-secondary" href="{{ route('ordenes-compra.exportar', request()->query()) }}">
                <span class="fas fa-file-excel me-2"></span>Exportar
            </a>
        @endif
        @if ($yo->puede('ordenes_compra.crear'))
            <a class="btn btn-primary" href="{{ route('ordenes-compra.create') }}">
                <span class="fas fa-plus me-2"></span>Nueva orden
            </a>
        @endif
    </x-page-header>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Número de orden" maxlength="30" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="proveedor">
                    <option value="">Todos los proveedores</option>
                    @foreach ($proveedores as $p)
                        <option value="{{ $p->id_proveedor }}" @selected((int) ($filtros['proveedor'] ?? 0) === $p->id_proveedor)>{{ $p->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="estado">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $codigo => [$nombre])
                        <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-phoenix-secondary w-100" type="submit">Filtrar</button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('ordenes-compra.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$ordenes">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Número</th>
                    <th class="text-uppercase text-700">Proveedor</th>
                    <th class="text-uppercase text-700">Bodega</th>
                    <th class="text-uppercase text-700">Emisión</th>
                    <th class="text-uppercase text-700">Entrega</th>
                    <th class="text-end text-uppercase text-700">Total</th>
                    <th class="text-uppercase text-700">Recepción</th>
                    <th class="pe-0 text-end text-uppercase text-700">Estado</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($ordenes as $oc)
                @php
                    $avance = (float) $oc->pedido > 0 ? round((float) $oc->recibido / (float) $oc->pedido * 100) : 0;
                    [$nombreEstado, $color] = $estados[$oc->estado] ?? [$oc->estado, 'secondary'];
                    $atrasada = in_array($oc->estado, ['ENVIADA', 'PARCIAL'], true) && $oc->fecha_entrega_esperada?->isPast();
                @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2 text-nowrap">
                        <a class="fw-bold text-1100 text-decoration-none" href="{{ route('ordenes-compra.show', $oc->id_oc) }}">{{ $oc->numero_oc }}</a>
                    </td>
                    <td class="fw-semi-bold text-900">{{ $oc->proveedor?->razon_social ?? '—' }}</td>
                    <td class="text-800">{{ $oc->bodega?->nombre ?? '—' }}</td>
                    <td class="text-nowrap text-800">{{ $oc->fecha_emision?->format('d/m/Y') }}</td>
                    <td class="text-nowrap {{ $atrasada ? 'text-danger fw-bold' : 'text-800' }}">
                        {{ $oc->fecha_entrega_esperada?->format('d/m/Y') ?? '—' }}
                        @if ($atrasada) <span class="fas fa-exclamation-circle ms-1" title="Atrasada"></span>@endif
                    </td>
                    <td class="text-end text-nowrap fw-bold text-1000">{{ $oc->moneda }} {{ number_format((float) $oc->total, 2) }}</td>
                    <td style="min-width: 7rem">
                        @if (! in_array($oc->estado, ['BORRADOR', 'CANCELADA'], true))
                            <div class="progress" style="height: 6px" title="{{ $avance }} %"><div class="progress-bar bg-success" style="width: {{ $avance }}%"></div></div>
                            <span class="fs--2 text-600">{{ $avance }} %</span>
                        @else
                            <span class="text-400">—</span>
                        @endif
                    </td>
                    <td class="pe-0 text-end">
                        <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-700 py-5">
                        <span class="fas fa-shopping-cart fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ninguna orden coincide con los filtros.' : 'No hay órdenes de compra.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
