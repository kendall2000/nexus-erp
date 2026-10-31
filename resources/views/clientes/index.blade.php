@extends('layouts.app', ['titulo' => 'Clientes'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <x-page-header
        titulo="Clientes"
        subtitulo="Cartera de clientes, condiciones de crédito y saldo por cobrar."
        icono="fas fa-user-tie"
        :breadcrumbs="['Comercial' => null, 'Clientes' => null]"
    >
        @if ($yo->puede('clientes.exportar'))
            <a class="btn btn-phoenix-secondary" href="{{ route('clientes.exportar', request()->query()) }}">
                <span class="fas fa-file-excel me-2"></span>Exportar
            </a>
        @endif
        @if ($yo->puede('clientes.crear'))
            <a class="btn btn-primary" href="{{ route('clientes.create') }}">
                <span class="fas fa-plus me-2"></span>Nuevo cliente
            </a>
        @endif
    </x-page-header>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre, NIT o correo" maxlength="100" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="segmento">
                    <option value="">Todos los segmentos</option>
                    @foreach ($segmentos as $codigo => $nombre)
                        <option value="{{ $codigo }}" @selected(($filtros['segmento'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="categoria">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $cat)
                        <option value="{{ $cat }}" @selected(($filtros['categoria'] ?? '') === $cat)>Categoría {{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="estado">
                    <option value="">Activos e inactivos</option>
                    <option value="activos" @selected(($filtros['estado'] ?? '') === 'activos')>Activos</option>
                    <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-phoenix-secondary w-100" type="submit">
                    <span class="fas fa-filter me-1"></span>Filtrar
                </button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('clientes.index') }}" title="Limpiar">
                        <span class="fas fa-times"></span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$clientes">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Cliente</th>
                    <th class="text-uppercase text-700">NIT</th>
                    <th class="text-uppercase text-700">Contacto</th>
                    <th class="text-uppercase text-700">Segmento</th>
                    <th class="text-end text-uppercase text-700">Crédito</th>
                    <th class="text-end text-uppercase text-700">Saldo</th>
                    <th class="pe-0 text-end text-uppercase text-700">Estado</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($clientes as $c)
                @php
                    $saldo = (float) $c->saldo;
                    $excede = $c->limite_credito !== null && $saldo > (float) $c->limite_credito;
                    $iniciales = mb_strtoupper(mb_substr($c->razon_social, 0, 2));
                @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static {{ $c->activo ? '' : 'text-500' }}">
                    <td class="ps-0 py-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-m me-3 d-none d-sm-inline-block">
                                <div class="avatar-name rounded-circle {{ $c->activo ? 'bg-soft-primary text-primary' : 'bg-200 text-600' }}">
                                    <span>{{ $iniciales }}</span>
                                </div>
                            </div>
                            <div>
                                <a class="fw-bold text-1100 text-decoration-none" href="{{ route('clientes.show', $c->id_cliente) }}">{{ $c->razon_social }}</a>
                                @if ($c->nombre_comercial && $c->nombre_comercial !== $c->razon_social)
                                    <p class="text-600 fs--2 mb-0">{{ $c->nombre_comercial }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-nowrap fw-semi-bold text-900">{{ $c->nit ?? '—' }}</td>
                    <td class="fs--2 text-800">
                        @if ($c->email_principal)
                            <span class="d-block"><span class="far fa-envelope text-500 me-1"></span>{{ $c->email_principal }}</span>
                        @endif
                        @if ($c->telefono_principal)
                            <span class="d-block"><span class="fas fa-phone-alt text-500 me-1"></span>{{ $c->telefono_principal }}</span>
                        @endif
                        @if (!$c->email_principal && !$c->telefono_principal)
                            —
                        @endif
                    </td>
                    <td>
                        <span class="text-900">{{ $segmentos[$c->segmento] ?? '—' }}</span>
                        @if ($c->categoria)
                            <span class="badge badge-phoenix badge-phoenix-secondary ms-1">{{ $c->categoria }}</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap fs--2 text-800">
                        <span class="fw-semi-bold">{{ $c->dias_credito }} días</span><br>
                        <span class="text-600">{{ $c->limite_credito !== null ? $c->moneda_facturacion.' '.number_format((float) $c->limite_credito, 2) : 'Sin límite' }}</span>
                    </td>
                    <td class="text-end text-nowrap fw-bold {{ $excede ? 'text-danger' : 'text-900' }}">
                        {{ $saldo > 0 ? $c->moneda_facturacion.' '.number_format($saldo, 2) : '—' }}
                        @if ($excede)
                            <span class="fas fa-exclamation-circle ms-1 text-danger" title="Supera el límite de crédito"></span>
                        @endif
                    </td>
                    <td class="pe-0 text-end">
                        @if ($c->activo)
                            <span class="badge badge-phoenix fs--2 badge-phoenix-success"><span class="badge-label">Activo</span><span class="ms-1" data-feather="check" style="height:11px;width:11px;"></span></span>
                        @else
                            <span class="badge badge-phoenix fs--2 badge-phoenix-danger"><span class="badge-label">Inactivo</span></span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-700 py-5">
                        <span class="fas fa-users fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ningún cliente coincide con los filtros.' : 'Todavía no hay clientes.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
