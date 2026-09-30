@extends('layouts.app', ['titulo' => 'Cobro #'.$p->id_pago])

@section('contenido')
    @php
        $yo = auth()->user();
        [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary'];
        $aplicado = $p->estado === 'APLICADO';
        [$puedeRevertir, $puedeDevolver] = [$aplicado && $yo->puede('pagos.eliminar'), $aplicado && $yo->puede('pagos.devolver')];
        $formulario = old('accion');
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('pagos.index') }}">Pagos</a></li>
            <li class="breadcrumb-item active">#{{ $p->id_pago }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Cobro #{{ $p->id_pago }} <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1 align-middle">{{ $nombreEstado }}</span></h2>
            <p class="text-700 mb-0">{{ $p->cliente?->razon_social }} · {{ $p->fecha_pago?->format('d/m/Y') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($yo->puede('pagos.imprimir'))
                <a class="btn btn-phoenix-secondary" href="{{ route('pagos.imprimir', $p->id_pago) }}" target="_blank" rel="noopener"><span class="fas fa-print me-2"></span>Recibo</a>
            @endif
            @if ($puedeDevolver)
                <button class="btn btn-phoenix-warning" type="button" data-bs-toggle="collapse" data-bs-target="#devolver">Devolver dinero</button>
            @endif
            @if ($puedeRevertir)
                <button class="btn btn-phoenix-danger" type="button" data-bs-toggle="collapse" data-bs-target="#revertir">Revertir</button>
            @endif
        </div>
    </div>

    @foreach (['revertir' => [$puedeRevertir, 'danger', 'Revertir: el cobro se capturó por error. Deja de contar y la factura recupera el saldo.'],
               'devolver' => [$puedeDevolver, 'warning', 'Devolver dinero: se reembolsó al cliente. Deja de contar y la factura recupera el saldo.']] as $accion => [$puede, $colorAccion, $texto])
        @if ($puede)
            <div class="collapse {{ $formulario === $accion ? 'show' : '' }} mb-4" id="{{ $accion }}">
                <div class="card border-{{ $colorAccion }}"><div class="card-body">
                    <p class="fs--1 mb-2">{{ $texto }}</p>
                    <form method="POST" action="{{ route('pagos.'.$accion, $p->id_pago) }}" class="row g-2 align-items-start">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="accion" value="{{ $accion }}" />
                        <div class="col-md-9">
                            <input class="form-control @if ($formulario === $accion) @error('motivo') is-invalid @enderror @endif" name="motivo" value="{{ $formulario === $accion ? old('motivo') : '' }}" maxlength="300" placeholder="Motivo" required />
                            @if ($formulario === $accion) @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                        <div class="col-md-3"><button class="btn btn-{{ $colorAccion }} w-100" type="submit">Confirmar</button></div>
                    </form>
                </div></div>
            </div>
        @endif
    @endforeach

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Datos del cobro</h5>
                    <dl class="row mb-0">
                        <dt class="col-5 text-700">Monto</dt><dd class="col-7 fw-bold fs-0">{{ $p->moneda }} {{ number_format((float) $p->monto, 2) }}</dd>
                        <dt class="col-5 text-700">Forma de pago</dt><dd class="col-7">{{ $formas[$p->forma_pago] ?? $p->forma_pago }}</dd>
                        <dt class="col-5 text-700">Referencia</dt><dd class="col-7">{{ $p->referencia ?: '—' }}</dd>
                        <dt class="col-5 text-700">Banco</dt><dd class="col-7">{{ $p->banco_origen ?? '—' }}</dd>
                        <dt class="col-5 text-700">Acreditado</dt>
                        <dd class="col-7">
                            @if ($p->fecha_acreditado)
                                {{ $p->fecha_acreditado->format('d/m/Y') }}
                            @elseif ($p->forma_pago === 'EFECTIVO')
                                —
                            @else
                                <span class="text-warning">Pendiente</span>
                                @if ($aplicado && ($yo->puede('pagos.crear') || $yo->puede('facturas.cobrar')))
                                    <form method="POST" action="{{ route('pagos.acreditar', $p->id_pago) }}" class="d-flex gap-1 mt-1">
                                        @csrf
                                        @method('PATCH')
                                        <input class="form-control form-control-sm @error('fecha_acreditado') is-invalid @enderror" type="date" name="fecha_acreditado" value="{{ old('fecha_acreditado', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required />
                                        <button class="btn btn-phoenix-success btn-sm" type="submit">Acreditar</button>
                                    </form>
                                    @error('fecha_acreditado')<div class="text-danger fs--2">{{ $message }}</div>@enderror
                                @endif
                            @endif
                        </dd>
                        <dt class="col-5 text-700">Registrado por</dt><dd class="col-7">{{ $p->creadoPor?->nombre_completo ?? '—' }}<br><span class="text-600">{{ $p->created_at?->format('d/m/Y H:i') }}</span></dd>
                        @if ($p->notas)<dt class="col-5 text-700">Notas</dt><dd class="col-7">{{ $p->notas }}</dd>@endif
                        @if (! $aplicado)
                            <dt class="col-5 text-700">{{ $nombreEstado }} por</dt><dd class="col-7">{{ $p->revertidoPor?->nombre_completo ?? '—' }}<br><span class="text-600">{{ $p->fecha_reversion?->format('d/m/Y H:i') }}</span></dd>
                            <dt class="col-5 text-700">Motivo</dt><dd class="col-7">{{ $p->motivo_reversion }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Factura</h5>
                    @if ($p->factura)
                        <dl class="row mb-0">
                            <dt class="col-5 text-700">Número</dt>
                            <dd class="col-7">@if ($yo->puede('facturas.ver'))<a href="{{ route('facturas.show', $p->id_factura) }}">{{ $p->factura->numero_completo }}</a>@else{{ $p->factura->numero_completo }}@endif</dd>
                            <dt class="col-5 text-700">Total</dt><dd class="col-7">{{ $p->factura->moneda }} {{ number_format((float) $p->factura->total, 2) }}</dd>
                            <dt class="col-5 text-700">Pagado</dt><dd class="col-7">{{ number_format((float) $p->factura->total_pagado, 2) }}</dd>
                            <dt class="col-5 text-700">Saldo</dt><dd class="col-7 fw-semi-bold">{{ number_format((float) $p->factura->saldo_pendiente, 2) }}</dd>
                            <dt class="col-5 text-700">Estado</dt><dd class="col-7">{{ \App\Http\Controllers\FacturaController::ESTADOS[$p->factura->estado][0] ?? $p->factura->estado }}</dd>
                        </dl>
                    @else
                        <p class="text-700 mb-0">Cobro sin factura asociada.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
