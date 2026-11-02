@extends('layouts.app', ['titulo' => 'Gestión de Cobros y Pagos'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="#!">Finanzas</a></li>
            <li class="breadcrumb-item active">Cobros y Recibos</li>
        </ol>
    </nav>

    <div class="mb-4">
        <div class="row g-3 justify-content-between align-items-end">
            <div class="col-12 col-md">
                <h2 class="mb-1 text-bold text-1100">
                    <span class="fas fa-hand-holding-usd text-primary me-2"></span>Cobros Recibidos
                </h2>
                <p class="text-700 mb-0">Registro y trazabilidad de ingresos por facturas emitidas, conciliación y acreditación bancaria.</p>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                @if ($yo->puede('pagos.exportar'))
                    <a class="btn btn-phoenix-secondary" href="{{ route('pagos.exportar', request()->query()) }}">
                        <span class="fas fa-file-excel me-2 text-success"></span>Exportar Excel
                    </a>
                @endif
                @if ($yo->puede('pagos.crear') || $yo->puede('facturas.cobrar'))
                    <a class="btn btn-primary" href="{{ route('pagos.create') }}">
                        <span class="fas fa-plus-circle me-2"></span>Registrar Nuevo Cobro
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Tarjetas de métricas Phoenix -->
    <div class="row g-3 mb-4">
        @forelse ($cobrado as $c)
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border-start border-4 border-success shadow-none border">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Total Cobrado ({{ $c->moneda }})</h6>
                                <h3 class="fw-bolder mb-1 text-1000">{{ $c->moneda }} {{ number_format((float) $c->total, 2) }}</h3>
                                <p class="text-muted fs--1 mb-0">
                                    <span class="badge badge-phoenix badge-phoenix-success me-1">
                                        <i class="fas fa-check-double me-1"></i>{{ $c->cantidad }} {{ $c->cantidad == 1 ? 'cobro' : 'cobros' }}
                                    </span>
                                    aplicados en el período
                                </p>
                            </div>
                            <div class="avatar avatar-m">
                                <div class="avatar-name rounded-circle bg-success-subtle text-success fs-1">
                                    <i class="fas fa-receipt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border-start border-4 border-success shadow-none border">
                    <div class="card-body">
                        <h6 class="text-700 text-uppercase fs--2 mb-1">Total Cobrado</h6>
                        <h3 class="fw-bolder mb-1 text-1000">0.00</h3>
                        <p class="text-muted fs--1 mb-0">Sin cobros en el filtro</p>
                    </div>
                </div>
            </div>
        @endforelse

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100 border-start border-4 {{ $porAcreditar ? 'border-warning' : 'border-info' }} shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Por acreditar en banco</h6>
                            <h3 class="fw-bolder mb-1 {{ $porAcreditar ? 'text-warning' : 'text-1000' }}">{{ $porAcreditar }}</h3>
                            @if ($porAcreditar)
                                <a class="fs--1 fw-semi-bold text-warning text-decoration-none" href="{{ route('pagos.index', ['sin_acreditar' => 1]) }}">
                                    <i class="fas fa-filter me-1"></i>Filtrar pendientes de acreditación
                                </a>
                            @else
                                <span class="fs--1 text-success"><i class="fas fa-check-circle me-1"></i>Todos los pagos acreditados</span>
                            @endif
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle {{ $porAcreditar ? 'bg-warning-subtle text-warning' : 'bg-info-subtle text-info' }} fs-1">
                                <i class="fas fa-university"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100 border-start border-4 border-primary shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Política Contable</h6>
                            <div class="fs--1 text-800 fw-semi-bold">Trazabilidad Total</div>
                            <span class="fs--2 text-muted">Un cobro registrado no se destruye físicamente; se anula y revierte su impacto contable.</span>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-primary-subtle text-primary fs-1">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor de Tabla con estilo Phoenix -->
    <div class="card shadow-none border">
        <div class="card-header p-3 border-bottom bg-body-emphasis">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <div class="search-box w-100">
                        <input class="form-control form-control-sm search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Referencia, recibo, factura o cliente..." maxlength="100" />
                        <span class="fas fa-search search-box-icon text-500"></span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <select class="form-select form-select-sm" name="cliente">
                        <option value="">Todos los clientes</option>
                        @foreach ($clientes as $c)
                            <option value="{{ $c->id_cliente }}" @selected((int) ($filtros['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <select class="form-select form-select-sm" name="forma">
                        <option value="">Toda forma de pago</option>
                        @foreach ($formas as $codigo => $nombre)
                            <option value="{{ $codigo }}" @selected(($filtros['forma'] ?? '') === $codigo)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <select class="form-select form-select-sm" name="estado" title="Estado">
                        <option value="">Estado: Todos</option>
                        @foreach ($estados as $codigo => [$nombre])
                            <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <input class="form-control form-control-sm" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Desde" />
                </div>
                <div class="col-6 col-md-1">
                    <input class="form-control form-control-sm" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Hasta" />
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    @if (! empty($filtros['sin_acreditar']))<input type="hidden" name="sin_acreditar" value="1" />@endif
                    <button class="btn btn-sm btn-phoenix-primary w-100" type="submit">
                        <span class="fas fa-filter me-1"></span>Filtrar
                    </button>
                    @if (array_filter($filtros))
                        <a class="btn btn-sm btn-phoenix-danger px-3" href="{{ route('pagos.index') }}" title="Limpiar Filtros">
                            <span class="fas fa-times"></span>
                        </a>
                    @endif
                </div>
            </form>
            @if (! empty($filtros['sin_acreditar']))
                <div class="alert alert-subtle-warning py-1 px-2 mt-2 mb-0 fs--2 d-flex align-items-center">
                    <span class="fas fa-info-circle me-1"></span>
                    <span>Mostrando solo cobros aplicados no en efectivo pendientes de fecha de acreditación bancaria.</span>
                </div>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-sm fs--1 mb-0 align-middle table-hover">
                <thead class="bg-body-secondary text-900">
                    <tr>
                        <th class="ps-3 py-2 text-uppercase text-700" style="width: 100px;">Recibo</th>
                        <th class="text-uppercase text-700">Fecha Pago</th>
                        <th class="text-uppercase text-700">Cliente / Pagador</th>
                        <th class="text-uppercase text-700">Factura Relacionada</th>
                        <th class="text-uppercase text-700">Método / Ref.</th>
                        <th class="text-end text-uppercase text-700">Monto Aplicado</th>
                        <th class="text-uppercase text-700">Acreditación</th>
                        <th class="text-center text-uppercase text-700">Estado</th>
                        <th class="pe-3 text-end text-uppercase text-700">Acción</th>
                    </tr>
                </thead>
                <tbody class="list">
                @forelse ($pagos as $p)
                    @php [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary']; @endphp
                    <tr class="hover-actions-trigger position-static {{ $p->estado !== 'APLICADO' ? 'text-500 bg-body-tertiary' : '' }}">
                        <td class="ps-3 py-2">
                            <a class="fw-bold text-primary font-monospace" href="{{ route('pagos.show', $p->id_pago) }}">
                                #REC-{{ str_pad($p->id_pago, 5, '0', STR_PAD_LEFT) }}
                            </a>
                        </td>
                        <td class="text-nowrap text-800">
                            <i class="far fa-calendar-alt me-1 text-400"></i>{{ $p->fecha_pago?->format('d/m/Y') }}
                        </td>
                        <td>
                            @if ($p->cliente)
                                <a href="{{ route('clientes.show', $p->cliente->id_cliente) }}" class="fw-semi-bold text-1000 text-decoration-none">
                                    {{ $p->cliente->razon_social }}
                                </a>
                                @if ($p->cliente->numero_documento)
                                    <span class="d-block fs--2 text-muted">{{ $p->cliente->tipo_documento }}: {{ $p->cliente->numero_documento }}</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if ($p->factura)
                                <a href="{{ route('facturas.show', $p->factura->id_factura) }}" class="badge badge-phoenix badge-phoenix-info text-decoration-none">
                                    <i class="fas fa-file-invoice me-1"></i>{{ $p->factura->numero_completo }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semi-bold text-800">
                                @if ($p->forma_pago === 'EFECTIVO')
                                    <i class="fas fa-money-bill-wave text-success me-1"></i>
                                @elseif ($p->forma_pago === 'TRANSFERENCIA')
                                    <i class="fas fa-exchange-alt text-primary me-1"></i>
                                @elseif ($p->forma_pago === 'TARJETA')
                                    <i class="fas fa-credit-card text-info me-1"></i>
                                @elseif ($p->forma_pago === 'CHEQUE')
                                    <i class="fas fa-money-check text-warning me-1"></i>
                                @else
                                    <i class="fas fa-wallet text-secondary me-1"></i>
                                @endif
                                {{ $formas[$p->forma_pago] ?? $p->forma_pago }}
                            </div>
                            @if ($p->referencia)
                                <span class="d-block fs--2 text-600 font-monospace">Ref: {{ $p->referencia }}</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <span class="fs-0 fw-bold {{ $p->estado !== 'APLICADO' ? 'text-decoration-line-through text-500' : 'text-success' }}">
                                {{ $p->moneda }} {{ number_format((float) $p->monto, 2) }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            @if ($p->fecha_acreditado)
                                <span class="badge badge-phoenix badge-phoenix-success">
                                    <i class="fas fa-check me-1"></i>{{ $p->fecha_acreditado->format('d/m/Y') }}
                                </span>
                            @elseif ($p->forma_pago === 'EFECTIVO')
                                <span class="text-muted fs--2">Inmediato</span>
                            @else
                                <span class="badge badge-phoenix badge-phoenix-warning">
                                    <i class="fas fa-clock me-1"></i>Pendiente
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-phoenix badge-phoenix-{{ $color }} px-2 py-1">
                                {{ $nombreEstado }}
                            </span>
                        </td>
                        <td class="pe-3 text-end">
                            <div class="btn-reveal-trigger position-static">
                                <a class="btn btn-sm btn-phoenix-secondary px-2" href="{{ route('pagos.show', $p->id_pago) }}" title="Ver Detalles">
                                    <span class="fas fa-eye text-primary"></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-700 py-5">
                            <div class="avatar avatar-xl mb-3 mx-auto">
                                <div class="avatar-name rounded-circle bg-body-secondary text-500 fs-1">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                            </div>
                            <h5 class="text-800">No se encontraron cobros</h5>
                            <p class="text-muted fs--1 mb-0">{{ array_filter($filtros) ? 'Ningún recibo coincide con los filtros aplicados.' : 'Aún no hay cobros registrados en la plataforma.' }}</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($pagos->hasPages())
            <div class="card-footer p-3 border-top">
                {{ $pagos->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
