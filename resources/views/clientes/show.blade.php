@extends('layouts.app', ['titulo' => $c->razon_social])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('clientes.editar');
        $moneda = $c->moneda_facturacion;
        $excede = $c->limite_credito !== null && $saldo > (float) $c->limite_credito;
        $iniciales = mb_strtoupper(mb_substr($c->razon_social, 0, 2));
    @endphp

    <x-page-header
        :titulo="$c->razon_social"
        :subtitulo="($c->nombre_comercial && $c->nombre_comercial !== $c->razon_social ? $c->nombre_comercial.' · ' : '').'NIT '.($c->nit ?? '—')"
        :breadcrumbs="['Clientes' => route('clientes.index'), $c->razon_social => null]"
    >
        <x-slot:badge>
            <span class="badge badge-phoenix badge-phoenix-{{ $c->activo ? 'success' : 'danger' }} fs--1 align-middle ms-2">
                {{ $c->activo ? 'Activo' : 'Inactivo' }}
            </span>
        </x-slot:badge>

        @if ($yo->puede('clientes.imprimir'))
            <a class="btn btn-phoenix-secondary" href="{{ route('clientes.imprimir', $c->id_cliente) }}" target="_blank" rel="noopener">
                <span class="fas fa-print me-2"></span>Estado de cuenta
            </a>
        @endif
        @if ($puedeEditar)
            <a class="btn btn-phoenix-secondary" href="{{ route('clientes.edit', $c->id_cliente) }}">
                <span class="fas fa-pen me-2"></span>Editar
            </a>
            <form method="POST" action="{{ route('clientes.estado', $c->id_cliente) }}">
                @csrf
                @method('PATCH')
                <button class="btn btn-phoenix-{{ $c->activo ? 'danger' : 'success' }}" type="submit">
                    {{ $c->activo ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        @endif
        @if ($yo->puede('clientes.eliminar'))
            <form method="POST" action="{{ route('clientes.destroy', $c->id_cliente) }}" onsubmit="return confirm(@js('¿Eliminar el cliente '.$c->razon_social.'?'))">
                @csrf
                @method('DELETE')
                <button class="btn btn-phoenix-danger px-3" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
            </form>
        @endif
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <x-stat-card
                etiqueta="Saldo por cobrar"
                :valor="$moneda.' '.number_format($saldo, 2)"
                :subtexto="$excede ? 'Supera el límite de crédito' : 'Cartera activa'"
                icono="fas fa-file-invoice-dollar"
                :color="$excede ? 'danger' : 'primary'"
                :alerta="$excede"
            />
        </div>
        <div class="col-sm-4">
            <x-stat-card
                etiqueta="Vencido"
                :valor="$moneda.' '.number_format($vencido, 2)"
                :subtexto="$vencido > 0 ? 'Facturas con plazo vencido' : 'Al día'"
                icono="fas fa-clock"
                :color="$vencido > 0 ? 'warning' : 'success'"
            />
        </div>
        <div class="col-sm-4">
            <x-stat-card
                etiqueta="Crédito"
                :valor="$c->dias_credito.' días'"
                :subtexto="$c->limite_credito !== null ? 'Límite '.$moneda.' '.number_format((float) $c->limite_credito, 2).' · disponible '.number_format(max(0, (float) $c->limite_credito - $saldo), 2) : 'Sin límite'"
                icono="fas fa-shield-alt"
                color="info"
            />
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center border-bottom border-dashed border-300 pb-3 mb-3">
                        <div class="avatar avatar-2xl me-3">
                            <div class="avatar-name rounded-circle bg-soft-primary text-primary">
                                <span>{{ $iniciales }}</span>
                            </div>
                        </div>
                        <div>
                            <h5 class="mb-1 text-1100">Datos fiscales y perfil</h5>
                            <span class="badge badge-phoenix badge-phoenix-secondary">
                                {{ $c->tipo_persona === 'NATURAL' ? 'Persona natural' : 'Persona jurídica' }}
                            </span>
                        </div>
                    </div>
                    <dl class="row mb-0 fs--1 gy-2">
                        <dt class="col-5 text-700 fw-semi-bold">Tipo</dt>
                        <dd class="col-7 mb-0 text-900">{{ $c->tipo_persona === 'NATURAL' ? 'Persona natural' : 'Persona jurídica' }}</dd>

                        <dt class="col-5 text-700 fw-semi-bold">Industria</dt>
                        <dd class="col-7 mb-0 text-900">{{ $c->industria?->nombre ?? '—' }}</dd>

                        <dt class="col-5 text-700 fw-semi-bold">Segmento</dt>
                        <dd class="col-7 mb-0 text-900">{{ \App\Http\Controllers\ClienteController::SEGMENTOS[$c->segmento] ?? '—' }}{{ $c->categoria ? ' · categoría '.$c->categoria : '' }}</dd>

                        <dt class="col-5 text-700 fw-semi-bold">Correo</dt>
                        <dd class="col-7 mb-0">@if ($c->email_principal)<a href="mailto:{{ $c->email_principal }}">{{ $c->email_principal }}</a>@else — @endif</dd>

                        <dt class="col-5 text-700 fw-semi-bold">Teléfono</dt>
                        <dd class="col-7 mb-0 text-900">{{ $c->telefono_principal ?? '—' }}</dd>

                        <dt class="col-5 text-700 fw-semi-bold">Sitio web</dt>
                        <dd class="col-7 mb-0 text-break">@if ($c->sitio_web)<a href="{{ $c->sitio_web }}" target="_blank" rel="noopener noreferrer">{{ $c->sitio_web }}</a>@else — @endif</dd>

                        <dt class="col-5 text-700 fw-semi-bold">Ubicación</dt>
                        <dd class="col-7 mb-0 text-900">{{ collect([$c->municipio?->nombre, $c->municipio?->division?->nombre, $c->pais?->nombre])->filter()->implode(', ') ?: '—' }}</dd>

                        <dt class="col-5 text-700 fw-semi-bold">Dirección fiscal</dt>
                        <dd class="col-7 mb-0 text-900">{{ $c->direccion_fiscal ?? '—' }}</dd>

                        <dt class="col-5 text-700 fw-semi-bold">Moneda</dt>
                        <dd class="col-7 mb-0 fw-bold text-900">{{ $moneda }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 text-1100"><span class="fas fa-address-book text-primary me-2 fs-0"></span>Contactos</h5>
                        <span class="badge badge-phoenix badge-phoenix-secondary">{{ $c->contactos->count() }}</span>
                    </div>
                    @if ($errors->contacto->any())
                        <div class="alert alert-soft-danger fs--1">{{ $errors->contacto->first() }}</div>
                    @endif
                    @forelse ($c->contactos as $ct)
                        <div class="border-bottom border-200 py-2 fs--1">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="fw-bold text-1000">{{ $ct->nombre }}</span>
                                    @if ($ct->es_contacto_principal)<span class="badge badge-phoenix badge-phoenix-primary ms-1">Principal</span>@endif
                                    @if ($ct->recibe_facturas)<span class="badge badge-phoenix badge-phoenix-info ms-1">Recibe facturas</span>@endif
                                    <div class="text-700 mt-1">{{ collect([$ct->cargo, $ct->email, $ct->telefono, $ct->whatsapp ? 'WhatsApp '.$ct->whatsapp : null])->filter()->implode(' · ') }}</div>
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
                                    <summary class="text-primary fw-semi-bold" style="cursor: pointer">Editar</summary>
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-1100"><span class="fas fa-file-invoice text-primary me-2 fs-0"></span>Facturas con saldo</h5>
                @if ($yo->puede('facturas.crear'))
                    <a class="btn btn-phoenix-primary btn-sm" href="{{ route('facturas.create', ['cliente' => $c->id_cliente]) }}">
                        <span class="fas fa-plus me-1"></span>Emitir factura
                    </a>
                @endif
            </div>
            @if ($facturas->isEmpty())
                <p class="text-700 fs--1 mb-0">El cliente no tiene saldo pendiente.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-700">Factura</th>
                                <th class="text-uppercase text-700">Emisión</th>
                                <th class="text-uppercase text-700">Vence</th>
                                <th class="text-end text-uppercase text-700">Total</th>
                                <th class="text-end text-uppercase text-700">Pagado</th>
                                <th class="text-end text-uppercase text-700">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($facturas as $f)
                            @php $vencida = $f->fecha_vencimiento?->isPast(); @endphp
                            <tr>
                                <td class="fw-bold">
                                    @if ($yo->puede('facturas.ver'))
                                        <a href="{{ route('facturas.show', $f->id_factura) }}">{{ $f->numero_completo }}</a>
                                    @else
                                        {{ $f->numero_completo }}
                                    @endif
                                </td>
                                <td>{{ $f->fecha_emision?->format('d/m/Y') }}</td>
                                <td class="{{ $vencida ? 'text-danger fw-semi-bold' : '' }}">
                                    {{ $f->fecha_vencimiento?->format('d/m/Y') }}
                                    @if ($vencida) <span class="badge badge-phoenix badge-phoenix-danger fs--2 ms-1">{{ (int) $f->fecha_vencimiento->diffInDays(now()) }} días</span>@endif
                                </td>
                                <td class="text-end">{{ number_format((float) $f->total, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $f->total_pagado, 2) }}</td>
                                <td class="text-end fw-bold text-1000">{{ $f->moneda }} {{ number_format((float) $f->saldo_pendiente, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
