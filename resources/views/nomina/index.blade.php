@extends('layouts.app', ['titulo' => 'Nómina'])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeProcesar = $yo->puede('nomina.procesar');
        $dinero = fn ($n) => number_format((float) $n, 2);
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Recursos Humanos</li>
            <li class="breadcrumb-item active">Nómina y Salarios</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Header superior estilo Phoenix --}}
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-12 col-md-auto">
                <h2 class="mb-1 text-1100">Gestión de Nómina & Salarios</h2>
                <h5 class="text-700 fw-semi-bold mb-0">Procesamiento de planillas quincenales/mensuales, descuentos de ley y provisiones</h5>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                @if ($puedeProcesar)
                    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#nuevo-periodo" aria-expanded="{{ $errors->any() && ! $errors->prestamo->any() ? 'true' : 'false' }}">
                        <span class="fas fa-plus me-2"></span>Nuevo periodo de nómina
                    </button>
                @endif
            </div>
        </div>

        @if ($puedeProcesar)
            <div class="collapse {{ $errors->any() && ! $errors->prestamo->any() ? 'show' : '' }} mb-4" id="nuevo-periodo">
                <div class="card border border-200 shadow-none">
                    <div class="card-body">
                        <h5 class="mb-3 text-1100"><span class="fas fa-calendar-plus text-primary me-2"></span>Apertura de Periodo de Pago</h5>
                        <form method="POST" action="{{ route('nomina.store') }}" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-md-2">
                                <label class="form-label" for="tipo">Frecuencia / Tipo</label>
                                <select data-choices="data-choices" class="form-select" id="tipo" name="tipo">
                                    @foreach ($tipos as $k => $n)
                                        <option value="{{ $k }}" @selected(old('tipo', $sugerido['tipo']) === $k)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="fecha_inicio">Fecha inicio</label>
                                <input class="form-control @error('fecha_inicio') is-invalid @enderror" id="fecha_inicio" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', $sugerido['fecha_inicio']) }}" required />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="fecha_fin">Fecha fin</label>
                                <input class="form-control @error('fecha_fin') is-invalid @enderror" id="fecha_fin" name="fecha_fin" type="date" value="{{ old('fecha_fin', $sugerido['fecha_fin']) }}" required />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="fecha_pago">Fecha de pago</label>
                                <input class="form-control" id="fecha_pago" name="fecha_pago" type="date" value="{{ old('fecha_pago', $sugerido['fecha_pago']) }}" required />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="nombre">Nombre descriptivo</label>
                                <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre') }}" maxlength="100" placeholder="Automático" />
                            </div>
                            <input type="hidden" name="moneda" value="GTQ" />
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100" type="submit">Crear periodo</button>
                            </div>
                            @if ($errors->any() && ! $errors->prestamo->any())
                                <div class="col-12 text-danger fs--1">{{ $errors->first() }}</div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tarjetas KPI de Resumen Salarial estilo Phoenix --}}
        @if ($resumen)
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 border border-200">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Líquido Último Periodo</h6>
                                    <h3 class="text-success mb-0 fw-bolder">{{ $resumen['periodo']->moneda }} {{ $dinero($resumen['periodo']->total_neto) }}</h3>
                                    <p class="fs--2 text-600 mb-0 mt-1">{{ $resumen['periodo']->nombre }} ({{ $resumen['empleados'] }} colaboradores)</p>
                                </div>
                                <div class="d-flex align-items-center icon-wrapper-sm shadow-success-100" style="transform: rotate(-7.45deg);">
                                    <span class="fas fa-wallet text-success fs-1 z-index-1 ms-2"></span>
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
                                    <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Aporte Patronal (12.67%)</h6>
                                    <h3 class="text-info mb-0 fw-bolder">Q {{ $dinero($resumen['patronal']) }}</h3>
                                    <p class="fs--2 text-600 mb-0 mt-1">IGSS 10.67% + INTECAP/IRTRA 2%</p>
                                </div>
                                <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                    <span class="fas fa-building text-info fs-1 z-index-1 ms-2"></span>
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
                                    <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Provisiones Legales</h6>
                                    <h3 class="text-warning mb-0 fw-bolder">Q {{ $dinero($resumen['provisiones']) }}</h3>
                                    <p class="fs--2 text-600 mb-0 mt-1">Aguinaldo, Bono 14 e indemnización</p>
                                </div>
                                <div class="d-flex align-items-center icon-wrapper-sm shadow-warning-100" style="transform: rotate(-7.45deg);">
                                    <span class="fas fa-piggy-bank text-warning fs-1 z-index-1 ms-2"></span>
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
                                    <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Costo Laboral Empresa</h6>
                                    <h3 class="text-primary mb-0 fw-bolder">Q {{ $dinero($resumen['costo_total']) }}</h3>
                                    <p class="fs--2 text-600 mb-0 mt-1">Devengado + patronal + pasivo</p>
                                </div>
                                <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                    <span class="fas fa-chart-pie text-primary fs-1 z-index-1 ms-2"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Pestañas de Navegación estilo Phoenix --}}
        <ul class="nav nav-underline mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $errors->prestamo->any() ? '' : 'active' }}" data-bs-toggle="tab" href="#tab-periodos" role="tab">
                    <span class="fas fa-calendar-alt me-2"></span>Periodos de Nómina
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $errors->prestamo->any() ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-prestamos" role="tab">
                    <span class="fas fa-hand-holding-usd me-2"></span>Préstamos & Anticipos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-reglas" role="tab">
                    <span class="fas fa-book me-2"></span>Parámetros Legales
                </a>
            </li>
        </ul>

        <div class="tab-content">
            {{-- Tab 1: Periodos de Nómina --}}
            <div class="tab-pane fade {{ $errors->prestamo->any() ? '' : 'show active' }}" id="tab-periodos" role="tabpanel">
                <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                    <div class="table-responsive scrollbar mx-n1 px-1">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="white-space-nowrap fs--1 align-middle ps-0" style="max-width:20px; width:18px;">
                                        <div class="form-check mb-0 fs-0">
                                            <input class="form-check-input" id="checkbox-bulk-nomina-select" type="checkbox" />
                                        </div>
                                    </th>
                                    <th class="sort white-space-nowrap align-middle ps-3" scope="col" style="min-width:240px;">PERIODO</th>
                                    <th class="sort align-middle ps-4" scope="col" style="width:150px;">TIPO</th>
                                    <th class="sort align-middle ps-4" scope="col" style="width:200px;">RANGO DE FECHAS</th>
                                    <th class="sort align-middle ps-4" scope="col" style="width:140px;">FECHA PAGO</th>
                                    <th class="sort align-middle text-end ps-4" scope="col" style="width:160px;">TOTAL LÍQUIDO</th>
                                    <th class="sort align-middle ps-4" scope="col" style="width:130px;">ESTADO</th>
                                    <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:80px;"></th>
                                </tr>
                            </thead>
                            <tbody class="list" id="nomina-table-body">
                                @forelse ($periodos as $p)
                                    @php [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary']; @endphp
                                    <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                                        <td class="fs--1 align-middle ps-0">
                                            <div class="form-check mb-0 fs-0">
                                                <input class="form-check-input" type="checkbox" />
                                            </div>
                                        </td>
                                        <td class="order align-middle white-space-nowrap ps-3 py-3">
                                            <a class="fw-bold text-primary" href="{{ route('nomina.show', $p->id_periodo) }}">
                                                {{ $p->nombre }}
                                            </a>
                                        </td>
                                        <td class="align-middle white-space-nowrap ps-4 text-800">
                                            <span class="badge badge-tag">{{ $tipos[$p->tipo] ?? $p->tipo }}</span>
                                        </td>
                                        <td class="align-middle white-space-nowrap ps-4 text-700">
                                            {{ $p->fecha_inicio?->format('d/m/Y') }} — {{ $p->fecha_fin?->format('d/m/Y') }}
                                        </td>
                                        <td class="align-middle white-space-nowrap ps-4 text-800 fw-semi-bold">
                                            {{ $p->fecha_pago?->format('d/m/Y') }}
                                        </td>
                                        <td class="total align-middle text-end white-space-nowrap fw-bold text-1000 ps-4">
                                            {{ $p->moneda }} {{ $dinero($p->total_neto) }}
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
                                                    <a class="dropdown-item" href="{{ route('nomina.show', $p->id_periodo) }}">
                                                        <span class="fas fa-eye me-2"></span>Detalle de boletas
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-700 py-5">
                                            <span class="fas fa-money-check-alt fs-2 text-300 d-block mb-2"></span>
                                            Aún no hay periodos de nómina creados.
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
                                Mostrando {{ $periodos->firstItem() ?? 0 }} a {{ $periodos->lastItem() ?? 0 }}
                                <span class="text-600">de</span> {{ $periodos->total() }} periodos
                            </p>
                        </div>
                        <div class="col-auto d-flex">
                            {{ $periodos->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 2: Préstamos y Anticipos --}}
            <div class="tab-pane fade {{ $errors->prestamo->any() ? 'show active' : '' }}" id="tab-prestamos" role="tabpanel">
                <div class="card border border-200 shadow-none">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 text-1100 fw-bold">Préstamos Activos a Empleados</h5>
                            @if ($puedeProcesar)
                                <button class="btn btn-phoenix-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#form-prestamo">
                                    <span class="fas fa-plus me-1"></span>Registrar nuevo préstamo
                                </button>
                            @endif
                        </div>

                        @if ($puedeProcesar)
                            <div class="collapse {{ $errors->prestamo->any() ? 'show' : '' }} mb-4" id="form-prestamo">
                                <div class="bg-100 p-3 rounded-3 border border-200">
                                    @if ($errors->prestamo->any())
                                        <div class="text-danger mb-2 fs--1">{{ $errors->prestamo->first() }}</div>
                                    @endif
                                    <form method="POST" action="{{ route('nomina.prestamos.store') }}" class="row g-2">
                                        @csrf
                                        <div class="col-md-4">
                                            <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select form-select-sm" name="id_empleado" required>
                                                <option value="">Selecciona el empleado…</option>
                                                @foreach ($empleados as $e)
                                                    <option value="{{ $e->id_empleado }}" @selected((int) old('id_empleado') === $e->id_empleado)>{{ $e->nombre_completo }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input class="form-control form-control-sm" name="monto_total" type="number" step="0.01" min="1" value="{{ old('monto_total') }}" placeholder="Monto total" required />
                                        </div>
                                        <div class="col-md-2">
                                            <input class="form-control form-control-sm" name="cuota_quincenal" type="number" step="0.01" min="1" value="{{ old('cuota_quincenal') }}" placeholder="Cuota descuento" required />
                                        </div>
                                        <div class="col-md-2">
                                            <input class="form-control form-control-sm" name="fecha_otorgamiento" type="date" value="{{ old('fecha_otorgamiento', now()->format('Y-m-d')) }}" required />
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-primary btn-sm w-100" type="submit">Guardar préstamo</button>
                                        </div>
                                        <div class="col-12">
                                            <input class="form-control form-control-sm" name="motivo" value="{{ old('motivo') }}" maxlength="300" placeholder="Motivo o justificación del desembolso (opcional)" />
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif

                        <div class="table-responsive scrollbar">
                            <table class="table table-sm fs--1 mb-0 align-middle">
                                <thead>
                                    <tr class="bg-100">
                                        <th class="ps-3 text-700">COLABORADOR</th>
                                        <th class="text-700">FECHA</th>
                                        <th class="text-end text-700">MONTO TOTAL</th>
                                        <th class="text-end text-700">CUOTA POR NÓMINA</th>
                                        <th class="text-end pe-3 text-700">SALDO PENDIENTE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($prestamos as $pr)
                                        <tr>
                                            <td class="ps-3 py-2 fw-semi-bold text-1000">{{ $pr->empleado?->nombre_completo }}</td>
                                            <td class="text-700">{{ $pr->fecha_otorgamiento?->format('d/m/Y') }}</td>
                                            <td class="text-end text-900">Q {{ number_format((float) $pr->monto_total, 2) }}</td>
                                            <td class="text-end text-800">Q {{ number_format((float) $pr->cuota_quincenal, 2) }}</td>
                                            <td class="text-end pe-3 fw-bold text-danger">Q {{ number_format((float) $pr->monto_pendiente, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-600 py-4">No hay préstamos activos registrados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 3: Parámetros Legales --}}
            <div class="tab-pane fade" id="tab-reglas" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border border-200 shadow-none h-100">
                            <div class="card-header bg-white border-bottom border-200 py-3">
                                <h5 class="mb-0 text-1100 fw-bold"><span class="fas fa-user-minus me-2 text-primary"></span>Descuentos de Ley al Colaborador</h5>
                            </div>
                            <div class="card-body fs--1 p-0">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <tr><td class="ps-4 py-2">IGSS Laboral</td><td class="text-end pe-4 fw-semi-bold">{{ \App\Support\Nomina::IGSS_LABORAL * 100 }}%</td></tr>
                                        <tr><td class="ps-4 py-2">ISR Asalariados (escala progresiva)</td><td class="text-end pe-4 fw-semi-bold">5% / 7%</td></tr>
                                        <tr><td class="ps-4 py-2">Deducción anual fija ISR</td><td class="text-end pe-4">Q {{ number_format(\App\Support\Nomina::ISR_DEDUCCION_UNICA, 2) }}</td></tr>
                                        <tr><td class="ps-4 py-2">Bonificación Incentivo (Decreto 37-2001)</td><td class="text-end pe-4">Q {{ number_format(\App\Support\Nomina::BONIFICACION_INCENTIVO_MENSUAL, 2) }} / mes</td></tr>
                                        <tr><td class="ps-4 py-2">Factor recargo horas extra</td><td class="text-end pe-4 fw-semi-bold">× {{ \App\Support\Nomina::RECARGO_HORA_EXTRA }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border border-200 shadow-none h-100">
                            <div class="card-header bg-white border-bottom border-200 py-3">
                                <h5 class="mb-0 text-1100 fw-bold"><span class="fas fa-building me-2 text-primary"></span>Aporte Patronal & Provisiones de Prestaciones</h5>
                            </div>
                            <div class="card-body fs--1 p-0">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <tr><td class="ps-4 py-2">Cuota Patronal (IGSS 10.67% + INTECAP 1% + IRTRA 1%)</td><td class="text-end pe-4 fw-semi-bold text-primary">{{ \App\Support\Nomina::IGSS_PATRONAL * 100 }}%</td></tr>
                                        @foreach ($provisiones as $prov)
                                            <tr><td class="ps-4 py-2">{{ $prov['nombre'] }}</td><td class="text-end pe-4 fw-semi-bold">{{ number_format($prov['tasa'] * 100, 2) }}%</td></tr>
                                        @endforeach
                                        <tr class="fw-bold bg-100"><td class="ps-4 py-2">Total Provisiones Acumuladas</td><td class="text-end pe-4 text-1100">{{ number_format(collect($provisiones)->sum('tasa') * 100, 2) }}%</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
