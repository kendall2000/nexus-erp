@extends('layouts.app', ['titulo' => $c->razon_social])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('clientes.editar');
        $moneda = $c->moneda_facturacion;
        $excede = $c->limite_credito !== null && $saldo > (float) $c->limite_credito;
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
            <li class="breadcrumb-item active">{{ $c->razon_social }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">{{ $c->razon_social }}
                <span class="badge badge-phoenix badge-phoenix-{{ $c->activo ? 'success' : 'danger' }} fs--1 align-middle">{{ $c->activo ? 'Activo' : 'Inactivo' }}</span>
            </h2>
            <p class="text-700 mb-0">{{ $c->nombre_comercial && $c->nombre_comercial !== $c->razon_social ? $c->nombre_comercial.' · ' : '' }}NIT {{ $c->nit ?? '—' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($yo->puede('clientes.imprimir'))
                <a class="btn btn-phoenix-secondary" href="{{ route('clientes.imprimir', $c->id_cliente) }}" target="_blank" rel="noopener"><span class="fas fa-print me-2"></span>Estado de cuenta</a>
            @endif
            @if ($puedeEditar)
                <a class="btn btn-phoenix-secondary" href="{{ route('clientes.edit', $c->id_cliente) }}"><span class="fas fa-pen me-2"></span>Editar</a>
                <form method="POST" action="{{ route('clientes.estado', $c->id_cliente) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-phoenix-{{ $c->activo ? 'danger' : 'success' }}" type="submit">{{ $c->activo ? 'Desactivar' : 'Activar' }}</button>
                </form>
            @endif
            @if ($yo->puede('clientes.eliminar'))
                <form method="POST" action="{{ route('clientes.destroy', $c->id_cliente) }}" onsubmit="return confirm(@js('¿Eliminar el cliente '.$c->razon_social.'?'))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link text-danger" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Saldo por cobrar</p>
            <h3 class="mb-0 {{ $excede ? 'text-danger' : '' }}">{{ $moneda }} {{ number_format($saldo, 2) }}</h3>
            @if ($excede)<p class="fs--2 text-danger mb-0">Supera el límite de crédito</p>@endif
        </div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Vencido</p>
            <h3 class="mb-0 {{ $vencido > 0 ? 'text-warning' : '' }}">{{ $moneda }} {{ number_format($vencido, 2) }}</h3>
        </div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Crédito</p>
            <h3 class="mb-0">{{ $c->dias_credito }} días</h3>
            <p class="fs--2 text-700 mb-0">{{ $c->limite_credito !== null ? 'Límite '.$moneda.' '.number_format((float) $c->limite_credito, 2).' · disponible '.number_format(max(0, (float) $c->limite_credito - $saldo), 2) : 'Sin límite' }}</p>
        </div></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Datos</h5>
                    <dl class="row mb-0">
                        <dt class="col-5 text-700">Tipo</dt><dd class="col-7">{{ $c->tipo_persona === 'NATURAL' ? 'Persona natural' : 'Persona jurídica' }}</dd>
                        <dt class="col-5 text-700">Industria</dt><dd class="col-7">{{ $c->industria?->nombre ?? '—' }}</dd>
                        <dt class="col-5 text-700">Segmento</dt><dd class="col-7">{{ \App\Http\Controllers\ClienteController::SEGMENTOS[$c->segmento] ?? '—' }}{{ $c->categoria ? ' · categoría '.$c->categoria : '' }}</dd>
                        <dt class="col-5 text-700">Correo</dt><dd class="col-7">@if ($c->email_principal)<a href="mailto:{{ $c->email_principal }}">{{ $c->email_principal }}</a>@else — @endif</dd>
                        <dt class="col-5 text-700">Teléfono</dt><dd class="col-7">{{ $c->telefono_principal ?? '—' }}</dd>
                        <dt class="col-5 text-700">Sitio web</dt><dd class="col-7 text-break">@if ($c->sitio_web)<a href="{{ $c->sitio_web }}" target="_blank" rel="noopener noreferrer">{{ $c->sitio_web }}</a>@else — @endif</dd>
                        <dt class="col-5 text-700">Ubicación</dt><dd class="col-7">{{ collect([$c->municipio?->nombre, $c->municipio?->division?->nombre, $c->pais?->nombre])->filter()->implode(', ') ?: '—' }}</dd>
                        <dt class="col-5 text-700">Dirección fiscal</dt><dd class="col-7">{{ $c->direccion_fiscal ?? '—' }}</dd>
                        <dt class="col-5 text-700">Moneda</dt><dd class="col-7">{{ $moneda }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Contactos</h5>
                    @if ($errors->contacto->any())
                        <div class="alert alert-soft-danger fs--1">{{ $errors->contacto->first() }}</div>
                    @endif
                    @forelse ($c->contactos as $ct)
                        <div class="border-bottom border-200 py-2 fs--1">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="fw-semi-bold">{{ $ct->nombre }}</span>
                                    @if ($ct->es_contacto_principal)<span class="badge badge-phoenix badge-phoenix-primary ms-1">Principal</span>@endif
                                    @if ($ct->recibe_facturas)<span class="badge badge-phoenix badge-phoenix-info ms-1">Recibe facturas</span>@endif
                                    <div class="text-700">{{ collect([$ct->cargo, $ct->email, $ct->telefono, $ct->whatsapp ? 'WhatsApp '.$ct->whatsapp : null])->filter()->implode(' · ') }}</div>
                                </div>
                                @if ($puedeEditar)
                                    <form method="POST" action="{{ route('clientes.contactos.destroy', [$c->id_cliente, $ct->id_contacto]) }}" onsubmit="return confirm(@js('¿Eliminar el contacto '.$ct->nombre.'?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link text-danger btn-sm p-0" type="submit" title="Eliminar contacto"><span class="fas fa-trash"></span></button>
                                    </form>
                                @endif
                            </div>
                            @if ($puedeEditar)
                                <details class="mt-1" @if ($errors->contacto->any() && (int) old('id_contacto') === $ct->id_contacto) open @endif>
                                    <summary class="text-primary" style="cursor: pointer">Editar</summary>
                                    @include('clientes.contacto', ['contacto' => $ct, 'accion' => route('clientes.contactos.update', [$c->id_cliente, $ct->id_contacto])])
                                </details>
                            @endif
                        </div>
                    @empty
                        <p class="text-700 fs--1">Sin contactos.</p>
                    @endforelse
                    @if ($puedeEditar)
                        <details class="mt-3" @if ($errors->contacto->any() && ! old('id_contacto')) open @endif>
                            <summary class="btn btn-phoenix-primary btn-sm"><span class="fas fa-plus me-1"></span>Agregar contacto</summary>
                            @include('clientes.contacto', ['contacto' => new \App\Models\Clientes\ContactoCliente(), 'accion' => route('clientes.contactos.store', $c->id_cliente)])
                        </details>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Facturas con saldo</h5>
            @if ($facturas->isEmpty())
                <p class="text-700 fs--1 mb-0">El cliente no tiene saldo pendiente.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead><tr><th>Factura</th><th>Emisión</th><th>Vence</th><th class="text-end">Total</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th></tr></thead>
                        <tbody>
                        @foreach ($facturas as $f)
                            @php $vencida = $f->fecha_vencimiento?->isPast(); @endphp
                            <tr>
                                <td class="fw-semi-bold">{{ $f->numero_completo }}</td>
                                <td>{{ $f->fecha_emision?->format('d/m/Y') }}</td>
                                <td class="{{ $vencida ? 'text-danger fw-semi-bold' : '' }}">{{ $f->fecha_vencimiento?->format('d/m/Y') }}@if ($vencida) <span class="fs--2">({{ (int) $f->fecha_vencimiento->diffInDays(now()) }} días)</span>@endif</td>
                                <td class="text-end">{{ number_format((float) $f->total, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $f->total_pagado, 2) }}</td>
                                <td class="text-end fw-semi-bold">{{ $f->moneda }} {{ number_format((float) $f->saldo_pendiente, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
