@extends('layouts.app', ['titulo' => 'Clientes'])

@section('contenido')
    @php $yo = auth()->user(); @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Clientes</h2>
            <p class="text-700 fw-semi-bold mb-0">Cartera de clientes, condiciones de crédito y saldo por cobrar.</p>
        </div>
        <div class="d-flex gap-2">
            @if ($yo->puede('clientes.exportar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('clientes.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($yo->puede('clientes.crear'))
                <a class="btn btn-primary" href="{{ route('clientes.create') }}"><span class="fas fa-plus me-2"></span>Nuevo cliente</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre, NIT o correo" maxlength="100" /></div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="segmento">
                        <option value="">Todos los segmentos</option>
                        @foreach ($segmentos as $codigo => $nombre)<option value="{{ $codigo }}" @selected(($filtros['segmento'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="categoria">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $cat)<option value="{{ $cat }}" @selected(($filtros['categoria'] ?? '') === $cat)>Categoría {{ $cat }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Activos e inactivos</option>
                        <option value="activos" @selected(($filtros['estado'] ?? '') === 'activos')>Activos</option>
                        <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('clientes.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Cliente</th><th>NIT</th><th>Contacto</th><th>Segmento</th><th class="text-end">Crédito</th><th class="text-end">Saldo</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($clientes as $c)
                        @php $saldo = (float) $c->saldo; $excede = $c->limite_credito !== null && $saldo > (float) $c->limite_credito; @endphp
                        <tr class="{{ $c->activo ? '' : 'text-500' }}">
                            <td>
                                <a class="fw-semi-bold" href="{{ route('clientes.show', $c->id_cliente) }}">{{ $c->razon_social }}</a>
                                @if ($c->nombre_comercial && $c->nombre_comercial !== $c->razon_social)<p class="text-600 fs--2 mb-0">{{ $c->nombre_comercial }}</p>@endif
                            </td>
                            <td class="text-nowrap">{{ $c->nit ?? '—' }}</td>
                            <td class="fs--2">{{ $c->email_principal }}@if ($c->email_principal && $c->telefono_principal)<br>@endif{{ $c->telefono_principal }}</td>
                            <td>{{ $segmentos[$c->segmento] ?? '—' }}@if ($c->categoria) <span class="badge badge-phoenix badge-phoenix-secondary">{{ $c->categoria }}</span>@endif</td>
                            <td class="text-end text-nowrap fs--2">{{ $c->dias_credito }} días<br>{{ $c->limite_credito !== null ? $c->moneda_facturacion.' '.number_format((float) $c->limite_credito, 2) : 'Sin límite' }}</td>
                            <td class="text-end text-nowrap fw-semi-bold {{ $excede ? 'text-danger' : '' }}">{{ $saldo > 0 ? $c->moneda_facturacion.' '.number_format($saldo, 2) : '—' }}@if ($excede) <span class="fas fa-exclamation-circle" title="Supera el límite de crédito"></span>@endif</td>
                            <td>
                                @if ($c->activo)
                                    <span class="badge badge-phoenix badge-phoenix-success">Activo</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-danger">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún cliente coincide con los filtros.' : 'Todavía no hay clientes.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $clientes->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
