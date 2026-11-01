@extends('layouts.app', ['titulo' => $f->numero_completo])

@section('contenido')
    @php
        $yo = auth()->user();
        [$nombreEstado, $color] = $estados[$f->estado] ?? [$f->estado, 'secondary'];
        $tipo = $tipos[$f->tipo] ?? $f->tipo;
        $vencida = in_array($f->estado, \App\Models\Clientes\Cliente::ESTADOS_CON_SALDO, true) && (float) $f->saldo_pendiente > 0 && $f->fecha_vencimiento?->lt(today());
        $anulable = in_array($f->estado, ['BORRADOR', 'EMITIDA', 'ENVIADA', 'VENCIDA'], true) && (float) $f->total_pagado <= 0
            && $f->pagos->where('estado', 'APLICADO')->isEmpty() && (float) $f->monto_condonado <= 0;
        $conSaldo = in_array($f->estado, \App\Models\Clientes\Cliente::ESTADOS_CON_SALDO, true) && (float) $f->saldo_pendiente > 0;
        $puedeCobrar = $yo->puede('pagos.crear') || $yo->puede('facturas.cobrar');
        $dinero = fn ($n) => number_format((float) $n, 2);
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('facturas.index') }}">Facturación</a></li>
            <li class="breadcrumb-item active">{{ $f->numero_completo }}</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Encabezado idéntico a apps/e-commerce/landing/invoice.html --}}
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
            <div>
                <h2 class="mb-1 text-1100">{{ $tipo }} #{{ $f->numero_completo }}</h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1">{{ $nombreEstado }}</span>
                    @if ($vencida)
                        <span class="badge badge-phoenix badge-phoenix-danger fs--1">Vencida hace {{ (int) $f->fecha_vencimiento->diffInDays(today()) }} días</span>
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($conSaldo && $puedeCobrar)
                    <a class="btn btn-success" href="{{ route('pagos.create', ['factura' => $f->id_factura]) }}">
                        <span class="fas fa-hand-holding-usd me-2"></span>Registrar cobro
                    </a>
                @endif
                @if ($f->estado !== 'BORRADOR' && $yo->puede('facturas.imprimir'))
                    <a class="btn btn-phoenix-secondary" href="{{ route('facturas.imprimir', $f->id_factura) }}" target="_blank" rel="noopener">
                        <span class="fa-solid fa-print me-2"></span>Imprimir factura
                    </a>
                @endif
                @if ($f->estado === 'BORRADOR' && $yo->puede('facturas.editar'))
                    <a class="btn btn-phoenix-primary" href="{{ route('facturas.edit', $f->id_factura) }}">
                        <span class="fas fa-edit me-2"></span>Editar borrador
                    </a>
                    <form method="POST" action="{{ route('facturas.emitir', $f->id_factura) }}" class="d-inline" onsubmit="return confirm(@js('¿Emitir '.$tipo.' '.$f->numero_completo.' por '.$f->moneda.' '.$dinero($f->total).'? Ya no se podrá editar.'))">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-primary" type="submit"><span class="fas fa-check me-2"></span>Emitir</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Barra de Resumen Rápido estilo Phoenix Invoice --}}
        <div class="bg-100 p-4 mb-4 rounded-3 border border-200">
            <div class="row g-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <h6 class="text-700 fs--2 text-uppercase mb-1">Número de Factura</h6>
                    <h5 class="mb-0 text-1100 fw-bold">#{{ $f->numero_completo }}</h5>
                    <span class="fs--2 text-600">Serie {{ $f->serie?->codigo_serie }} · N.º {{ $f->numero_factura }}</span>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <h6 class="text-700 fs--2 text-uppercase mb-1">Fecha de Emisión</h6>
                    <h5 class="mb-0 text-1100 fw-bold">{{ $f->fecha_emision?->format('d/m/Y') }}</h5>
                    <span class="fs--2 text-600">Vencimiento: {{ $f->fecha_vencimiento?->format('d/m/Y') }}</span>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <h6 class="text-700 fs--2 text-uppercase mb-1">Cliente Facturado</h6>
                    <h5 class="mb-0 text-1100 fw-bold text-truncate">{{ $f->cliente?->razon_social ?? 'Consumidor Final' }}</h5>
                    <span class="fs--2 text-600">NIT: {{ $f->cliente?->nit ?? 'C/F' }}</span>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <h6 class="text-700 fs--2 text-uppercase mb-1">Saldo Pendiente</h6>
                    <h3 class="mb-0 fw-bolder {{ (float) $f->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $f->moneda }} {{ $f->estado === 'BORRADOR' ? '—' : $dinero($f->saldo_pendiente) }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="row g-5">
            {{-- Columna Principal: Detalle de Líneas de Venta (8 columnas) --}}
            <div class="col-12 col-xl-8">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-white border-bottom border-200 py-3 px-4">
                        <h5 class="mb-0 text-1100 fw-bold">Artículos & Servicios Facturados</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive scrollbar">
                            <table class="table table-sm fs--1 mb-0 align-middle">
                                <thead class="bg-100">
                                    <tr>
                                        <th class="ps-4 text-700" style="width: 50px;">#</th>
                                        <th class="text-700">DESCRIPCIÓN</th>
                                        <th class="text-end text-700" style="width: 100px;">CANT.</th>
                                        <th class="text-end text-700" style="width: 130px;">P. UNITARIO</th>
                                        <th class="text-end pe-4 text-700" style="width: 140px;">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($f->detalles as $i => $d)
                                        <tr>
                                            <td class="ps-4 py-3 text-700">{{ $i + 1 }}</td>
                                            <td>
                                                <h6 class="mb-0 text-1100 fw-semi-bold">{{ $d->descripcion }}</h6>
                                                @if ($d->producto)
                                                    <span class="fs--2 text-600">SKU: {{ $d->producto->codigo }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-900">{{ (float) $d->cantidad }}</td>
                                            <td class="text-end text-700">{{ $dinero($d->precio_unitario) }}</td>
                                            <td class="text-end pe-4 fw-bold text-1000">{{ $dinero($d->subtotal) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-600 py-4">Sin líneas registradas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Resumen de Totales y Liquidación estilo Phoenix --}}
                <div class="row justify-content-end mb-4">
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="card border border-200 shadow-none">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2 fs--1">
                                    <span class="text-700">Subtotal:</span>
                                    <span class="fw-semi-bold text-900">{{ $dinero($f->subtotal) }}</span>
                                </div>
                                @if ((float) $f->descuento > 0)
                                    <div class="d-flex justify-content-between mb-2 fs--1 text-danger">
                                        <span>Descuento aplicado:</span>
                                        <span class="fw-semi-bold">−{{ $dinero($f->descuento) }}</span>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between mb-2 fs--1">
                                    <span class="text-700">IVA (12%):</span>
                                    <span class="fw-semi-bold text-900">{{ $dinero($f->iva) }}</span>
                                </div>
                                <div class="d-flex justify-content-between border-top border-200 pt-2 fs-0">
                                    <span class="fw-bold text-1100">Total a Pagar:</span>
                                    <span class="fw-bolder text-primary">{{ $f->moneda }} {{ $dinero($f->total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Lateral: Estado de Cobros y Pagos (4 columnas) --}}
            <div class="col-12 col-xl-4">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3 text-1100"><span class="fas fa-wallet text-success me-2"></span>Historial de Cobros</h5>
                        <div class="d-flex justify-content-between mb-2 fs--1">
                            <span class="text-700">Total Cobrado:</span>
                            <span class="fw-bold text-success">{{ $f->moneda }} {{ $dinero($f->total_pagado) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 fs--1">
                            <span class="text-700">Saldo Pendiente:</span>
                            <span class="fw-bold {{ (float) $f->saldo_pendiente > 0 ? 'text-danger' : 'text-700' }}">{{ $f->moneda }} {{ $dinero($f->saldo_pendiente) }}</span>
                        </div>

                        @if ($f->pagos->isNotEmpty())
                            <h6 class="text-700 fs--2 text-uppercase mb-2">Comprobantes de Pago</h6>
                            @foreach ($f->pagos as $p)
                                <div class="border-top border-200 pt-2 pb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-semi-bold text-1000 fs--1">{{ $p->fecha_pago?->format('d/m/Y') }}</span>
                                        <span class="fw-bold text-success fs--1">{{ $f->moneda }} {{ $dinero($p->monto) }}</span>
                                    </div>
                                    <p class="fs--2 text-600 mb-0">{{ \App\Models\Finanzas\Pago::FORMAS[$p->forma_pago] ?? $p->forma_pago }} · Ref: {{ $p->referencia }}</p>
                                </div>
                            @endforeach
                        @else
                            <p class="text-600 fs--2 mb-0">No se registran pagos recibidos para esta factura.</p>
                        @endif
                    </div>
                </div>

                @if ($f->notas)
                    <div class="card border border-200 shadow-none">
                        <div class="card-body">
                            <h6 class="text-700 fs--2 text-uppercase mb-2">Notas y Observaciones</h6>
                            <p class="fs--1 text-800 mb-0" style="white-space: pre-line;">{{ $f->notas }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
