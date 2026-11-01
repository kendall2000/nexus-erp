@extends('layouts.app', ['titulo' => $c->razon_social])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('clientes.editar');
        $moneda = $c->moneda_facturacion;
        $excede = $c->limite_credito !== null && $saldo > (float) $c->limite_credito;
        $iniciales = mb_strtoupper(mb_substr($c->razon_social, 0, 2));
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
            <li class="breadcrumb-item active">{{ $c->razon_social }}</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Encabezado idéntico a apps/e-commerce/admin/customer-details.html --}}
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-auto">
                <div class="d-flex align-items-center gap-3">
                    <h2 class="mb-0 text-1100">{{ $c->razon_social }}</h2>
                    <span class="badge badge-phoenix fs--1 badge-phoenix-{{ $c->activo ? 'success' : 'danger' }}">
                        {{ $c->activo ? 'Cliente Activo' : 'Cliente Inactivo' }}
                    </span>
                </div>
                @if ($c->nombre_comercial && $c->nombre_comercial !== $c->razon_social)
                    <p class="text-700 fs--1 mb-0 mt-1">{{ $c->nombre_comercial }} · NIT: {{ $c->nit ?? 'C/F' }}</p>
                @endif
            </div>
            <div class="col-auto">
                <div class="d-flex flex-wrap gap-2">
                    @if ($yo->puede('clientes.imprimir'))
                        <a class="btn btn-phoenix-secondary" href="{{ route('clientes.imprimir', $c->id_cliente) }}" target="_blank" rel="noopener">
                            <span class="fas fa-print me-2"></span>Estado de cuenta
                        </a>
                    @endif
                    @if ($puedeEditar)
                        <a class="btn btn-phoenix-primary" href="{{ route('clientes.edit', $c->id_cliente) }}">
                            <span class="fas fa-edit me-2"></span>Editar
                        </a>
                        <form method="POST" action="{{ route('clientes.estado', $c->id_cliente) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-phoenix-{{ $c->activo ? 'warning' : 'success' }}" type="submit">
                                <span class="fas {{ $c->activo ? 'fa-ban' : 'fa-check' }} me-2"></span>{{ $c->activo ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                    @endif
                    @if ($yo->puede('facturas.crear'))
                        <a class="btn btn-primary" href="{{ route('facturas.create', ['cliente' => $c->id_cliente]) }}">
                            <span class="fas fa-file-invoice-dollar me-2"></span>Emitir factura
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Métricas Rápidas de Crédito y Cartera estilo Phoenix --}}
        <div class="row g-3 mb-5">
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Saldo Pendiente de Cobro</h6>
                                <h3 class="{{ $excede ? 'text-danger' : 'text-primary' }} mb-0 fw-bolder">{{ $moneda }} {{ number_format($saldo, 2) }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">{{ $excede ? 'Supera el límite autorizado' : 'Cartera al día' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-{{ $excede ? 'danger' : 'primary' }}-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-file-invoice-dollar text-{{ $excede ? 'danger' : 'primary' }} fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Saldo Vencido</h6>
                                <h3 class="{{ $vencido > 0 ? 'text-danger' : 'text-success' }} mb-0 fw-bolder">{{ $moneda }} {{ number_format($vencido, 2) }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">{{ $vencido > 0 ? 'Excedió la fecha límite' : 'Sin facturas atrasadas' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-{{ $vencido > 0 ? 'danger' : 'success' }}-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-clock text-{{ $vencido > 0 ? 'danger' : 'success' }} fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Límite de Crédito</h6>
                                <h3 class="text-info mb-0 fw-bolder">{{ $c->dias_credito }} días</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">{{ $c->limite_credito !== null ? 'Tope: '.$moneda.' '.number_format((float) $c->limite_credito, 2) : 'Sin restricción de límite' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-shield-alt text-info fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Disposición 2 columnas estilo apps/e-commerce/admin/customer-details.html --}}
        <div class="row g-5">
            {{-- Columna Lateral: Perfil y Datos de Contacto (4 columnas) --}}
            <div class="col-12 col-xxl-4">
                <div class="row g-4">
                    {{-- Tarjeta de Perfil --}}
                    <div class="col-12">
                        <div class="card border border-200 shadow-none">
                            <div class="card-body pb-3">
                                <div class="row align-items-center g-3 mb-4 text-center text-sm-start">
                                    <div class="col-12 col-sm-auto">
                                        <div class="avatar avatar-4xl">
                                            <div class="avatar-name rounded-circle bg-primary-100 text-primary-600 fs-2 fw-bolder">
                                                <span>{{ $iniciales }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-auto flex-1">
                                        <h4 class="mb-1 text-1100">{{ $c->razon_social }}</h4>
                                        <p class="text-700 fs--1 mb-1">NIT: <span class="fw-bold text-900">{{ $c->nit ?? 'C/F' }}</span></p>
                                        <span class="badge badge-tag">{{ $c->tipo_persona === 'NATURAL' ? 'Persona Natural' : 'Persona Jurídica' }}</span>
                                    </div>
                                </div>
                                <div class="border-top border-dashed border-200 pt-3">
                                    <div class="d-flex justify-content-between mb-2 fs--1">
                                        <span class="text-700">Segmento:</span>
                                        <span class="fw-semi-bold text-900">{{ $c->segmento ?? 'General' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 fs--1">
                                        <span class="text-700">Categoría:</span>
                                        <span class="fw-semi-bold text-900">{{ $c->categoria ? 'Cat. '.$c->categoria : 'Estándar' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 fs--1">
                                        <span class="text-700">Moneda facturación:</span>
                                        <span class="fw-semi-bold text-900">{{ $c->moneda_facturacion }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tarjeta de Ubicación y Comunicación --}}
                    <div class="col-12">
                        <div class="card border border-200 shadow-none">
                            <div class="card-body">
                                <h5 class="card-title mb-3 text-1100">Ubicación & Contacto Principal</h5>
                                <div class="mb-3">
                                    <h6 class="text-700 fs--2 mb-1 text-uppercase">Dirección de Entrega / Fiscal</h6>
                                    <p class="text-900 fs--1 mb-0">{{ $c->direccion_fiscal ?? 'Sin dirección registrada' }}</p>
                                    @if ($c->municipio || $c->departamento)
                                        <p class="text-600 fs--2 mb-0">{{ $c->municipio?->nombre }}{{ $c->municipio && $c->departamento ? ', ' : '' }}{{ $c->departamento?->nombre }}</p>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <h6 class="text-700 fs--2 mb-1 text-uppercase">Correo Electrónico</h6>
                                    @if ($c->email_principal)
                                        <a class="fs--1 text-primary fw-semi-bold" href="mailto:{{ $c->email_principal }}">{{ $c->email_principal }}</a>
                                    @else
                                        <span class="text-500 fs--1">—</span>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="text-700 fs--2 mb-1 text-uppercase">Teléfono</h6>
                                    @if ($c->telefono_principal)
                                        <a class="fs--1 text-900 fw-semi-bold" href="tel:{{ $c->telefono_principal }}">{{ $c->telefono_principal }}</a>
                                    @else
                                        <span class="text-500 fs--1">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Contactos adicionales --}}
                    <div class="col-12">
                        <div class="card border border-200 shadow-none">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0 text-1100">Contactos Registrados</h5>
                                </div>
                                @forelse ($c->contactos as $ct)
                                    <div class="border-bottom border-dashed border-200 pb-2 mb-2">
                                        <h6 class="mb-0 text-1000 fw-bold">{{ $ct->nombre }}</h6>
                                        <p class="fs--2 text-600 mb-1">{{ $ct->puesto ?? 'Contacto' }} · {{ $ct->telefono ?? $ct->email }}</p>
                                    </div>
                                @empty
                                    <p class="text-600 fs--1 mb-0">No hay contactos secundarios registrados.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Principal: Facturas y Transacciones (8 columnas) --}}
            <div class="col-12 col-xxl-8">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header border-bottom border-200 py-3 px-4 bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-1100 fw-bold">Facturas Pendientes de Cobro</h5>
                            <p class="mb-0 fs--2 text-600">Documentos que componen el saldo actual del cliente</p>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if ($facturas->isEmpty())
                            <div class="text-center py-5">
                                <span class="fas fa-check-circle text-success fs-3 d-block mb-2"></span>
                                <p class="text-700 mb-0 fw-semi-bold">El cliente no tiene facturas pendientes. Cuenta al día.</p>
                            </div>
                        @else
                            <div class="table-responsive scrollbar">
                                <table class="table table-sm fs--1 mb-0 align-middle">
                                    <thead class="bg-100">
                                        <tr>
                                            <th class="ps-4 text-700">FACTURA</th>
                                            <th class="text-700">EMISIÓN</th>
                                            <th class="text-700">VENCE</th>
                                            <th class="text-end text-700">TOTAL</th>
                                            <th class="text-end text-700">PAGADO</th>
                                            <th class="text-end pe-4 text-700">SALDO PENDIENTE</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                        @foreach ($facturas as $f)
                                            @php $vencida = $f->fecha_vencimiento?->isPast(); @endphp
                                            <tr class="hover-actions-trigger position-static">
                                                <td class="ps-4 py-3 fw-bold text-nowrap">
                                                    @if ($yo->puede('facturas.ver'))
                                                        <a class="text-primary" href="{{ route('facturas.show', $f->id_factura) }}">
                                                            #{{ $f->numero_completo }}
                                                        </a>
                                                    @else
                                                        #{{ $f->numero_completo }}
                                                    @endif
                                                </td>
                                                <td class="text-700">{{ $f->fecha_emision?->format('d/m/Y') }}</td>
                                                <td class="{{ $vencida ? 'text-danger fw-bold' : 'text-700' }}">
                                                    {{ $f->fecha_vencimiento?->format('d/m/Y') }}
                                                    @if ($vencida)
                                                        <span class="badge badge-phoenix badge-phoenix-danger fs--2 ms-1">Vencida</span>
                                                    @endif
                                                </td>
                                                <td class="text-end text-900">{{ number_format((float) $f->total, 2) }}</td>
                                                <td class="text-end text-success fw-semi-bold">{{ number_format((float) $f->total_pagado, 2) }}</td>
                                                <td class="text-end pe-4 fw-bold text-1000 fs-0">
                                                    {{ $f->moneda }} {{ number_format((float) $f->saldo_pendiente, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
