@extends('layouts.app', ['titulo' => 'Empleados'])

@section('contenido')
    @php
        $yo = auth()->user();
        $totalEmpleados = $empleados->total();
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Recursos Humanos</li>
            <li class="breadcrumb-item active">Empleados</li>
        </ol>
    </nav>

    <div class="mb-9">
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Gestión de Personal & Empleados</h2>
            </div>
        </div>

        {{-- Tarjetas KPI estilo Phoenix widgets --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Colaboradores Activos</h6>
                                <h3 class="text-success mb-0 fw-bolder">{{ number_format($kpis['activos']) }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">{{ $kpis['vigentes'] }} vigentes (incluye licencias)</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-success-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-user-check text-success fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Ingresos del Mes</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ number_format($kpis['ingresos_mes']) }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">{{ ucfirst(\App\Models\Finanzas\PresupuestoAnual::MESES[now()->month] ?? '') }} {{ now()->year }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-user-plus text-primary fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Contratos por Vencer</h6>
                                <h3 class="{{ $kpis['por_vencer'] > 0 ? 'text-warning' : 'text-1100' }} mb-0 fw-bolder">{{ number_format($kpis['por_vencer']) }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Próximos 30 días</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-warning-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-file-contract text-warning fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Cabezas de Área</h6>
                                <h3 class="text-info mb-0 fw-bolder">{{ number_format($kpis['cabezas']) }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Sin jefe inmediato</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-sitemap text-info fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pestañas de Navegación estilo Phoenix --}}
        <ul class="nav nav-underline mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab-listado" role="tab">
                    <span class="fas fa-users me-2"></span>Listado de Personal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-organigrama" role="tab">
                    <span class="fas fa-sitemap me-2"></span>Organigrama Visual
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-listado" role="tabpanel">
                <div id="employees">
                    {{-- Toolbar idéntico a apps/e-commerce/admin/customers.html --}}
                    <div class="mb-4">
                        <form method="GET" action="{{ route('empleados.index') }}" class="row g-3">
                            <div class="col-auto">
                                <div class="search-box">
                                    <div class="position-relative">
                                        <input class="form-control search-input search" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre, código o DPI..." aria-label="Search" />
                                        <span class="fas fa-search search-box-icon"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto scrollbar overflow-hidden-y flex-grow-1">
                                <div class="btn-group position-static" role="group">
                                    <div class="btn-group position-static text-nowrap">
                                        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="departamento" onchange="this.form.submit()">
                                            <option value="">Todos los departamentos</option>
                                            @foreach ($departamentos as $d)
                                                <option value="{{ $d->id_depto_org }}" @selected((int) ($filtros['departamento'] ?? 0) === $d->id_depto_org)>{{ $d->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="btn-group position-static text-nowrap">
                                        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="estado" onchange="this.form.submit()">
                                            <option value="">Vigentes (sin bajas)</option>
                                            @foreach ($estados as $codigo => [$nombre])
                                                <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if (array_filter($filtros))
                                        <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('empleados.index') }}" title="Limpiar filtros">
                                            <span class="fas fa-times me-1"></span>Limpiar
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="col-auto ms-auto d-flex gap-2">
                                @if ($yo->puede('empleados.configurar'))
                                    <a class="btn btn-phoenix-secondary" href="{{ route('organizacion.index') }}">
                                        <span class="fas fa-sitemap me-2"></span>Estructura Org.
                                    </a>
                                @endif
                                @if ($yo->puede('empleados.exportar'))
                                    <a class="btn btn-link text-900 me-2 px-0" href="{{ route('empleados.exportar', request()->query()) }}">
                                        <span class="fa-solid fa-file-export fs--1 me-2"></span>Exportar
                                    </a>
                                @endif
                                @if ($yo->puede('empleados.crear'))
                                    <a class="btn btn-primary" href="{{ route('empleados.create') }}">
                                        <span class="fas fa-plus me-2"></span>Agregar empleado
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    {{-- Tabla con estructura visual exacta de Phoenix --}}
                    <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                        <div class="table-responsive scrollbar-overlay mx-n1 px-1">
                            <table class="table table-sm fs--1 mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th class="white-space-nowrap fs--1 align-middle ps-0" style="max-width:20px; width:18px;">
                                            <div class="form-check mb-0 fs-0">
                                                <input class="form-check-input" id="checkbox-bulk-employees-select" type="checkbox" />
                                            </div>
                                        </th>
                                        <th class="sort align-middle ps-3" scope="col" style="width:350px;">EMPLEADO</th>
                                        <th class="sort align-middle ps-4" scope="col" style="width:200px;">CARGO</th>
                                        <th class="sort align-middle ps-4" scope="col" style="width:200px;">DEPARTAMENTO</th>
                                        <th class="sort align-middle ps-4" scope="col" style="width:130px;">FECHA INGRESO</th>
                                        <th class="sort align-middle text-end ps-4" scope="col" style="width:160px;">SALARIO BASE</th>
                                        <th class="sort align-middle ps-4" scope="col" style="width:130px;">ESTADO</th>
                                        <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:80px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="list" id="employees-table-body">
                                    @forelse ($empleados as $e)
                                        @php
                                            [$nombreEstado, $color] = $estados[$e->estado] ?? [$e->estado, 'secondary'];
                                            $iniciales = mb_strtoupper(mb_substr($e->nombres ?: $e->nombre_completo, 0, 1).mb_substr($e->apellidos ?: '', 0, 1));
                                        @endphp
                                        <tr class="hover-actions-trigger btn-reveal-trigger position-static {{ $e->estado === 'BAJA' ? 'text-500' : '' }}">
                                            <td class="fs--1 align-middle ps-0">
                                                <div class="form-check mb-0 fs-0">
                                                    <input class="form-check-input" type="checkbox" />
                                                </div>
                                            </td>
                                            <td class="customer align-middle white-space-nowrap ps-3 py-3">
                                                <a class="d-flex align-items-center text-900 text-hover-1000" href="{{ route('empleados.show', $e->id_empleado) }}">
                                                    <div class="avatar avatar-m me-3">
                                                        <div class="avatar-name rounded-circle {{ $e->estado === 'BAJA' ? 'bg-200 text-600' : 'bg-primary-100 text-primary-600' }}">
                                                            <span>{{ $iniciales }}</span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 text-1100 fw-bold">{{ $e->nombre_completo }}</h6>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <span class="fs--2 text-600">ID: {{ $e->codigo_empleado }}</span>
                                                            @if ($e->es_rotativo)
                                                                <span class="badge badge-phoenix badge-phoenix-info fs--2 ms-1">Rotativo</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </a>
                                            </td>
                                            <td class="align-middle white-space-nowrap ps-4 text-900 fw-semi-bold">
                                                {{ $e->cargo?->nombre ?? '—' }}
                                            </td>
                                            <td class="align-middle white-space-nowrap ps-4 text-800">
                                                <span class="badge badge-tag">{{ $e->departamento?->nombre ?? '—' }}</span>
                                            </td>
                                            <td class="align-middle white-space-nowrap ps-4 text-700">
                                                {{ $e->fecha_ingreso?->format('d/m/Y') }}
                                            </td>
                                            <td class="total align-middle text-end white-space-nowrap fw-bold text-1000 ps-4">
                                                {{ $e->contratoVigente ? $e->contratoVigente->moneda.' '.number_format((float) \App\Support\Salarios::hoy($e), 2) : ($e->es_rotativo && $e->tarifa_dia ? number_format((float) $e->tarifa_dia, 2).' / día' : 'Sin contrato') }}
                                            </td>
                                            <td class="payment_status align-middle white-space-nowrap ps-4">
                                                <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                                            </td>
                                            <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                                <div class="font-sans-serif btn-reveal-trigger position-static">
                                                    <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                                        <span class="fas fa-ellipsis-h fs--2"></span>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end py-2">
                                                        <a class="dropdown-item" href="{{ route('empleados.show', $e->id_empleado) }}">
                                                            <span class="fas fa-eye me-2"></span>Ver expediente
                                                        </a>
                                                        @if ($yo->puede('empleados.editar'))
                                                            <a class="dropdown-item" href="{{ route('empleados.edit', $e->id_empleado) }}">
                                                                <span class="fas fa-edit me-2"></span>Editar
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-700 py-5">
                                                <span class="fas fa-users fs-2 text-300 d-block mb-2"></span>
                                                {{ array_filter($filtros) ? 'Ningún empleado coincide con los filtros aplicados.' : 'Aún no hay colaboradores registrados.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Footer de paginación --}}
                        <div class="row align-items-center justify-content-between py-2 pe-0 fs--1 border-top border-200">
                            <div class="col-auto d-flex">
                                <p class="mb-0 d-none d-sm-block me-3 fw-semi-bold text-900">
                                    Mostrando {{ $empleados->firstItem() ?? 0 }} a {{ $empleados->lastItem() ?? 0 }}
                                    <span class="text-600">de</span> {{ $empleados->total() }} empleados
                                </p>
                            </div>
                            <div class="col-auto d-flex">
                                {{ $empleados->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Organigrama tab --}}
            <div class="tab-pane fade" id="tab-organigrama" role="tabpanel">
                <div class="card border border-200 shadow-none">
                    <div class="card-body">
                        <p class="fs--1 text-600 mb-4">Estructura jerárquica generada automáticamente a partir del jefe inmediato asignado.</p>
                        @if ($organigrama)
                            <div class="organigrama scrollbar">
                                <ul>
                                    @foreach ($organigrama as $nodo)
                                        @include('empleados.organigrama-nodo', ['nodo' => $nodo])
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="text-700 fs--1 mb-0">Todavía no hay colaboradores vigentes para construir el organigrama.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('estilos')
    <style>
        .organigrama { overflow-x: auto; }
        .organigrama ul { list-style: none; margin: 0; padding-left: 1.75rem; position: relative; }
        .organigrama > ul { padding-left: 0; }
        .organigrama li.org-nodo { position: relative; padding: .35rem 0 .35rem 1rem; }
        .organigrama ul ul li.org-nodo::before { content: ''; position: absolute; left: -0.75rem; top: 0; bottom: 0; border-left: 2px solid var(--phoenix-gray-300, #cbd0dd); }
        .organigrama ul ul li.org-nodo:last-child::before { bottom: 50%; }
        .organigrama ul ul li.org-nodo::after { content: ''; position: absolute; left: -0.75rem; top: 50%; width: 1.5rem; border-top: 2px solid var(--phoenix-gray-300, #cbd0dd); }
        .organigrama .org-tarjeta { border: 1px solid var(--phoenix-gray-200, #e3e6ed); border-radius: .5rem; padding: .4rem .75rem; background: var(--phoenix-white, #fff); }
        .organigrama .org-tarjeta:hover { border-color: var(--phoenix-primary, #3874ff); }
    </style>
@endpush
