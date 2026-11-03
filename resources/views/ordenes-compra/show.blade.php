@extends('layouts.app', ['titulo' => 'Orden '.$oc->numero_oc])

@section('contenido')
    @php
        $yo = auth()->user();
        [$nombreEstado, $color] = $estados[$oc->estado] ?? [$oc->estado, 'secondary'];
        $recibido = $oc->detalles->sum(fn ($d) => (float) $d->cantidad_recibida) > 0;
        $cancelable = in_array($oc->estado, ['BORRADOR', 'ENVIADA'], true) && ! $recibido;
        $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 4), '0'), '.');
        $dinero = fn ($n) => number_format((float) $n, 2);
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('ordenes-compra.index') }}">Órdenes de Compra</a></li>
            <li class="breadcrumb-item active">{{ $oc->numero_oc }}</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Encabezado idéntico a apps/e-commerce/admin/order-details.html --}}
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
            <div>
                <h2 class="mb-1 text-1100">Orden de Compra #{{ $oc->numero_oc }}</h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1">{{ $nombreEstado }}</span>
                    <span class="text-700 fs--1">Proveedor: <strong class="text-1000">{{ $oc->proveedor?->razon_social }}</strong></span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($yo->puede('ordenes_compra.imprimir'))
                    <a class="btn btn-phoenix-secondary" href="{{ route('ordenes-compra.imprimir', $oc->id_oc) }}" target="_blank" rel="noopener">
                        <span class="fas fa-print me-2"></span>Imprimir OC
                    </a>
                    <a class="btn btn-phoenix-secondary" href="{{ route('ordenes-compra.pdf', $oc->id_oc) }}">
                        <span class="fas fa-file-pdf me-2"></span>Descargar PDF
                    </a>
                @endif
                @if (in_array($oc->estado, \App\Http\Controllers\RecepcionController::RECIBIBLES, true) && $yo->puede('recepciones.crear'))
                    <a class="btn btn-primary" href="{{ route('recepciones.create', ['oc' => $oc->id_oc]) }}">
                        <span class="fas fa-truck-loading me-2"></span>Registrar recepción física
                    </a>
                @endif
                @if ($oc->estado === 'BORRADOR' && $yo->puede('ordenes_compra.editar'))
                    <a class="btn btn-phoenix-primary" href="{{ route('ordenes-compra.edit', $oc->id_oc) }}">
                        <span class="fas fa-pen me-2"></span>Editar orden
                    </a>
                @endif
                @if ($oc->estado === 'BORRADOR' && $yo->puede('ordenes_compra.aprobar'))
                    <form method="POST" action="{{ route('ordenes-compra.aprobar', $oc->id_oc) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-success" type="button"
                                data-confirm-phoenix="¿Aprobar la orden de compra #{{ $oc->numero_oc }} por {{ $oc->moneda }} {{ $dinero($oc->total) }}?"
                                data-confirm-titulo="Aprobar Orden de Compra"
                                data-confirm-boton="Sí, aprobar">
                            <span class="fas fa-check me-2"></span>Aprobar orden
                        </button>
                    </form>
                @endif
                @if ($cancelable && $yo->puede('ordenes_compra.cancelar'))
                    <form method="POST" action="{{ route('ordenes-compra.cancelar', $oc->id_oc) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-phoenix-danger" type="button"
                                data-confirm-phoenix="¿Cancelar la orden de compra #{{ $oc->numero_oc }}?{{ $oc->estado === 'ENVIADA' ? ' Se revertirá del presupuesto.' : '' }}"
                                data-confirm-titulo="Cancelar Orden de Compra"
                                data-confirm-boton="Sí, cancelar">
                            <span class="fas fa-ban me-2"></span>Cancelar orden
                        </button>
                    </form>
                @endif
                @if ($oc->estado === 'BORRADOR' && $yo->puede('ordenes_compra.editar'))
                    <form method="POST" action="{{ route('ordenes-compra.destroy', $oc->id_oc) }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-phoenix-danger" type="button" title="Eliminar borrador"
                                data-confirm-phoenix="¿Eliminar el borrador #{{ $oc->numero_oc }}? Esta acción no se puede deshacer."
                                data-confirm-titulo="Eliminar borrador"
                                data-confirm-boton="Sí, eliminar">
                            <span class="fas fa-trash"></span>
                        </button>
                    </form>
                @endif
                <button class="btn btn-phoenix-secondary" type="button" data-auditoria-tabla="orden_compra" data-auditoria-id="{{ $oc->id_oc }}">
                    <span class="fas fa-history me-2"></span>Bitácora
                </button>
            </div>
        </div>

        @if ($sobregiros)
            <div class="alert alert-soft-warning border-0 mb-4" role="alert">
                <h5 class="alert-heading mb-2"><span class="fas fa-exclamation-triangle me-2"></span>La orden excede el saldo del presupuesto</h5>
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-3">
                        <thead><tr><th>Partida (centro / cuenta)</th><th class="text-end">Requerido</th><th class="text-end">Disponible</th><th class="text-end">Sobregiro</th></tr></thead>
                        <tbody>
                            @foreach ($sobregiros as $s)
                                <tr><td>{{ $s['partida'] }}</td><td class="text-end">{{ $dinero($s['requerido']) }}</td><td class="text-end">{{ $dinero($s['disponible']) }}</td><td class="text-end text-danger fw-bold">{{ $dinero($s['sobregiro']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($oc->estado === 'BORRADOR' && $yo->puede('ordenes_compra.aprobar'))
                    <form method="POST" action="{{ route('ordenes-compra.aprobar', $oc->id_oc) }}" class="d-flex flex-wrap gap-2 align-items-center">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="forzar" value="1" />
                        <button class="btn btn-warning btn-sm" type="submit">Aprobar de todos modos</button>
                        @if ($yo->puede('ordenes_compra.editar'))
                            <span class="fs--1">o <a href="{{ route('ordenes-compra.edit', $oc->id_oc) }}">edita la orden</a> para ajustarla.</span>
                        @endif
                    </form>
                @endif
            </div>
        @endif

        {{-- Tarjetas KPI de Estado de la Orden --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Monto Total de Compra</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ $oc->moneda }} {{ $dinero($oc->total) }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Subtotal: {{ $oc->moneda }} {{ $dinero($oc->subtotal) }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-file-invoice-dollar text-primary fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Bodega de Destino</h6>
                                <h3 class="text-info mb-0 fw-bolder">{{ $oc->bodega?->nombre ?? 'Sin asignar' }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Entrega esperada: {{ $oc->fecha_entrega_esperada?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-warehouse text-info fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Progreso de Recepción</h6>
                                <h3 class="text-success mb-0 fw-bolder">{{ count($oc->recepciones) }} ingresos</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">{{ count($oc->detalles) }} líneas solicitadas</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-success-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-truck-loading text-success fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5">
            {{-- Columna Principal: Detalle de Ítems Comprados (8 columnas) --}}
            <div class="col-12 col-xl-8">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-white border-bottom border-200 py-3 px-4">
                        <h5 class="mb-0 text-1100 fw-bold">Productos & Suministros Solicitados</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive scrollbar">
                            <table class="table table-sm fs--1 mb-0 align-middle">
                                <thead class="bg-100">
                                    <tr>
                                        <th class="ps-4 text-700">PRODUCTO / DESCRIPCIÓN</th>
                                        <th class="text-end text-700" style="width: 110px;">PEDIDO</th>
                                        <th class="text-end text-700" style="width: 110px;">RECIBIDO</th>
                                        <th class="text-end text-700" style="width: 120px;">PRECIO UNIT.</th>
                                        <th class="text-end pe-4 text-700" style="width: 130px;">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($oc->detalles as $d)
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <h6 class="mb-0 text-1100 fw-semi-bold">{{ $d->producto?->nombre ?? 'Producto #'.$d->id_producto }}</h6>
                                                <span class="fs--2 text-600">Código: <code>{{ $d->producto?->codigo }}</code> {{ $d->descripcion }}</span>
                                            </td>
                                            <td class="text-end text-900 fw-semi-bold">{{ $cantidad($d->cantidad_pedida) }} {{ $d->producto?->unidad_medida }}</td>
                                            <td class="text-end {{ (float) $d->cantidad_recibida >= (float) $d->cantidad_pedida ? 'text-success fw-bold' : 'text-warning' }}">
                                                {{ $cantidad($d->cantidad_recibida) }}
                                            </td>
                                            <td class="text-end text-700">{{ $dinero($d->precio_unitario) }}</td>
                                            <td class="text-end pe-4 fw-bold text-1000">{{ $dinero($d->subtotal) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-600 py-4">No hay productos en esta orden.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Totales de la Orden --}}
                <div class="row justify-content-end mb-4">
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="card border border-200 shadow-none">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2 fs--1">
                                    <span class="text-700">Subtotal:</span>
                                    <span class="fw-semi-bold text-900">{{ $dinero($oc->subtotal) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 fs--1">
                                    <span class="text-700">IVA Estimado:</span>
                                    <span class="fw-semi-bold text-900">{{ $dinero($oc->iva) }}</span>
                                </div>
                                <div class="d-flex justify-content-between border-top border-200 pt-2 fs-0">
                                    <span class="fw-bold text-1100">Total Orden:</span>
                                    <span class="fw-bolder text-primary">{{ $oc->moneda }} {{ $dinero($oc->total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Lateral: Datos del Proveedor y Logística (4 columnas) --}}
            <div class="col-12 col-xl-4">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3 text-1100"><span class="fas fa-truck text-primary me-2"></span>Datos del Proveedor</h5>
                        <h6 class="mb-1 text-1000 fw-bold">{{ $oc->proveedor?->razon_social ?? '—' }}</h6>
                        <p class="fs--2 text-600 mb-2">NIT: {{ $oc->proveedor?->nit ?? 'C/F' }}</p>
                        <div class="border-top border-200 pt-2">
                            <p class="fs--2 text-700 mb-1">Contacto: {{ $oc->proveedor?->contacto ?? '—' }}</p>
                            <p class="fs--2 text-700 mb-0">Email: {{ $oc->proveedor?->email ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                @if ($oc->recepciones->isNotEmpty())
                    <div class="card border border-200 shadow-none">
                        <div class="card-body">
                            <h5 class="card-title mb-3 text-1100"><span class="fas fa-truck-loading text-success me-2"></span>Recepciones Realizadas</h5>
                            @foreach ($oc->recepciones as $r)
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-200">
                                    <div>
                                        <a class="fw-bold text-primary fs--1" href="{{ route('recepciones.show', $r->id_recepcion) }}">
                                            #{{ $r->numero_recepcion }}
                                        </a>
                                        <span class="d-block fs--2 text-600">{{ $r->fecha_recepcion?->format('d/m/Y') }}</span>
                                    </div>
                                    <span class="badge badge-phoenix badge-phoenix-success fs--2">Ingresado</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
