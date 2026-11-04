@extends('layouts.app', ['titulo' => 'Inicio'])

@section('contenido')
    @php
        $moneda = \App\Support\Sistema::config()->moneda ?: 'Q';
        $estadosFactura = \App\Http\Controllers\FacturaController::ESTADOS;
    @endphp

    <div class="pb-5">
        {{-- Banner de bienvenida estilo Phoenix CRM Dashboard --}}
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-12 col-md-auto">
                <h2 class="mb-1 text-1100">Hola, {{ $usuario->nombre_completo ?: $usuario->username }} 👋</h2>
                <h5 class="text-700 fw-semi-bold mb-0">
                    {{ $empresa?->nombre_comercial ? $empresa->nombre_comercial.' · ' : '' }}
                    <span class="text-primary">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM, YYYY')) }}</span>
                </h5>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                @if ($usuario->puede('facturas.crear'))
                    <a class="btn btn-phoenix-primary" href="{{ route('facturas.create') }}">
                        <span class="fas fa-file-invoice-dollar me-2"></span>Nueva factura
                    </a>
                @endif
                @if ($usuario->puede('tickets.crear'))
                    <a class="btn btn-phoenix-secondary" href="{{ route('tickets.create') }}">
                        <span class="fas fa-headset me-2"></span>Nuevo ticket
                    </a>
                @endif
                @if ($usuario->puede('productos.crear'))
                    <a class="btn btn-primary" href="{{ route('productos.create') }}">
                        <span class="fas fa-plus me-2"></span>Agregar producto
                    </a>
                @endif
            </div>
        </div>

        {{-- Cintas de Métricas Rápidas estilo Phoenix index.html --}}
        <div class="row align-items-center g-4 mb-4 border-top border-bottom border-200 py-4 bg-white mx-n4 px-4 mx-lg-n6 px-lg-6">
            @if ($ver['finanzas'])
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <div class="d-flex align-items-center">
                    <span class="fa-stack" style="min-height: 48px;min-width: 48px;">
                        <span class="fa-solid fa-square fa-stack-2x text-success-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                        <span class="fa-solid fa-circle fa-stack-2x stack-circle text-success-100" data-fa-transform="up-4 right-3 grow-2"></span>
                        <span class="fa-stack-1x fa-solid fa-file-invoice-dollar text-success" data-fa-transform="shrink-2 up-8 right-6"></span>
                    </span>
                    <div class="ms-3">
                        <h4 class="mb-0 text-1100">{{ $moneda }} {{ number_format($facturadoMes, 2) }}</h4>
                        <p class="text-700 fs--1 mb-0 fw-semi-bold">Facturado este mes</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <div class="d-flex align-items-center">
                    <span class="fa-stack" style="min-height: 48px;min-width: 48px;">
                        <span class="fa-solid fa-square fa-stack-2x text-warning-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                        <span class="fa-solid fa-circle fa-stack-2x stack-circle text-warning-100" data-fa-transform="up-4 right-3 grow-2"></span>
                        <span class="fa-stack-1x fa-solid fa-coins text-warning" data-fa-transform="shrink-2 up-8 right-6"></span>
                    </span>
                    <div class="ms-3">
                        <h4 class="mb-0 text-1100">{{ $moneda }} {{ number_format($saldoPorCobrar, 2) }}</h4>
                        <p class="text-700 fs--1 mb-0 fw-semi-bold">Cartera por cobrar</p>
                    </div>
                </div>
            </div>
            @endif
            @if ($ver['oportunidades'])
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <div class="d-flex align-items-center">
                    <span class="fa-stack" style="min-height: 48px;min-width: 48px;">
                        <span class="fa-solid fa-square fa-stack-2x text-primary-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                        <span class="fa-solid fa-circle fa-stack-2x stack-circle text-primary-100" data-fa-transform="up-4 right-3 grow-2"></span>
                        <span class="fa-stack-1x fa-solid fa-funnel-dollar text-primary" data-fa-transform="shrink-2 up-8 right-6"></span>
                    </span>
                    <div class="ms-3">
                        <h4 class="mb-0 text-1100">{{ number_format($oportunidadesAbiertas) }}</h4>
                        <p class="text-700 fs--1 mb-0 fw-semi-bold">Oportunidades en curso</p>
                    </div>
                </div>
            </div>
            @endif
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <div class="d-flex align-items-center">
                    <span class="fa-stack" style="min-height: 48px;min-width: 48px;">
                        <span class="fa-solid fa-square fa-stack-2x text-info-300" data-fa-transform="down-4 rotate--10 left-4"></span>
                        <span class="fa-solid fa-circle fa-stack-2x stack-circle text-info-100" data-fa-transform="up-4 right-3 grow-2"></span>
                        <span class="fa-stack-1x fa-solid fa-building-columns text-info" data-fa-transform="shrink-2 up-8 right-6"></span>
                    </span>
                    <div class="ms-3">
                        <h4 class="mb-0 text-1100">Activo</h4>
                        <p class="text-700 fs--1 mb-0 fw-semi-bold">Estado del sistema</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tarjetas KPI estilo dashboard/crm.html con rotación de iconos --}}
        <div class="row g-3 mb-4">
            @foreach ($tarjetas as $t)
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 border border-200 shadow-none">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="d-flex align-items-center icon-wrapper-sm shadow-{{ $t['color'] }}-100" style="transform: rotate(-7.45deg);">
                                        <span class="text-{{ $t['color'] }} z-index-1 ms-2" data-feather="{{ $t['icono'] }}" style="width: 22px; height: 22px;"></span>
                                    </div>
                                    <h6 class="text-700 fs--1 mb-0 ms-3 fw-semi-bold">{{ $t['titulo'] }}</h6>
                                </div>
                                @if ($t['enlace'])
                                    <a class="btn btn-link p-0 fs--2 fw-semi-bold" href="{{ $t['enlace'] }}">
                                        Ver<span class="fas fa-chevron-right ms-1 fs--2"></span>
                                    </a>
                                @endif
                            </div>
                            <h3 class="text-{{ $t['color'] }} mt-2 mb-0 fw-bolder">{{ number_format($t['valor']) }}</h3>
                            <p class="fs--2 text-600 mb-0 mt-1">Registrados en la plataforma</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── SECCIÓN ANALÍTICA ECHARTS: FILA 1 (Facturación vs Cobranza & Flujo de Caja) ── --}}
        <div class="row g-4 mb-4">
            @if ($ver['finanzas'])
            {{-- Gráfico 1: Facturación vs Cobranza del Mes --}}
            <div class="col-12 col-xl-6">
                <div class="card h-100 border border-200 shadow-none">
                    <div class="card-header border-bottom border-200 py-3 px-4 bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-1100 fw-bold"><span class="fas fa-chart-column text-primary me-2"></span>Facturación vs Cobranza del Mes</h5>
                            <p class="mb-0 fs--2 text-600">Rendimiento semanal del ciclo de ingresos y recuperación</p>
                        </div>
                        <span class="badge badge-phoenix badge-phoenix-primary fs--2">{{ now()->locale('es')->isoFormat('MMMM YYYY') }}</span>
                    </div>
                    <div class="card-body p-3">
                        <div id="chart-facturacion-cobranza" style="height: 280px; width: 100%;"></div>
                    </div>
                </div>
            </div>

            @endif
            @if ($ver['presupuesto'])
            {{-- Gráfico 2: Flujo de Caja Proyectado vs Real --}}
            <div class="col-12 col-xl-6">
                <div class="card h-100 border border-200 shadow-none">
                    <div class="card-header border-bottom border-200 py-3 px-4 bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-1100 fw-bold"><span class="fas fa-chart-line text-info me-2"></span>Flujo de Caja: Proyectado vs Real</h5>
                            <p class="mb-0 fs--2 text-600">Comparativa histórica de presupuesto y ejecución semestral</p>
                        </div>
                        <span class="badge badge-phoenix badge-phoenix-info fs--2">Últimos 6 Meses</span>
                    </div>
                    <div class="card-body p-3">
                        <div id="chart-flujo-caja" style="height: 280px; width: 100%;"></div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- ── SECCIÓN ANALÍTICA ECHARTS: FILA 2 (Inventario Bajo Stock & Tickets por Prioridad) ── --}}
        <div class="row g-4 mb-4">
            @if ($ver['inventario'])
            {{-- Gráfico 3: Estado de Inventario bajo Stock Mínimo --}}
            <div class="col-12 col-xl-7">
                <div class="card h-100 border border-200 shadow-none">
                    <div class="card-header border-bottom border-200 py-3 px-4 bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-1100 fw-bold"><span class="fas fa-triangle-exclamation text-warning me-2"></span>Inventario Bajo Stock Mínimo</h5>
                            <p class="mb-0 fs--2 text-600">Productos con necesidad urgente de reposición</p>
                        </div>
                        <a class="btn btn-phoenix-warning btn-sm" href="{{ route('productos.index') }}">Ver catálogo</a>
                    </div>
                    <div class="card-body p-3">
                        @if (count($analitica['inventarioBajoStock']) > 0)
                            <div id="chart-stock-minimo" style="height: 280px; width: 100%;"></div>
                        @else
                            <div class="text-center py-5">
                                <span class="fas fa-boxes-stacked fs-2 text-success mb-2 d-block"></span>
                                <h6 class="text-success fw-bold mb-1">Niveles de Stock Saludables</h6>
                                <p class="text-600 fs--1 mb-0">Ningún producto activo se encuentra por debajo de su umbral mínimo de existencia.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @endif
            @if ($ver['tickets'])
            {{-- Gráfico 4: Tickets Pendientes por Prioridad --}}
            <div class="col-12 col-xl-5">
                <div class="card h-100 border border-200 shadow-none">
                    <div class="card-header border-bottom border-200 py-3 px-4 bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-1100 fw-bold"><span class="fas fa-headset text-danger me-2"></span>Tickets por Prioridad</h5>
                            <p class="mb-0 fs--2 text-600">Distribución de incidentes activos en soporte</p>
                        </div>
                        <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('tickets.index') }}">Mesa de ayuda</a>
                    </div>
                    <div class="card-body p-3">
                        <div id="chart-tickets-prioridad" style="height: 280px; width: 100%;"></div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Tablas operativas principales en estilo Phoenix cards --}}
        <div class="row g-4">
            @if ($usuario->puede('facturas.ver'))
                <div class="col-12 col-xl-6">
                    <div class="card h-100 border border-200 shadow-none">
                        <div class="card-header border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center bg-white">
                            <div>
                                <h5 class="mb-0 text-1100 fw-bold">Últimas Facturas Emitidas</h5>
                                <p class="mb-0 fs--2 text-600">Registro de ventas y estados de cobranza</p>
                            </div>
                            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('facturas.index') }}">Ver todas</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive scrollbar">
                                <table class="table table-sm fs--1 mb-0 align-middle">
                                    <thead class="bg-100">
                                        <tr>
                                            <th class="ps-4 text-700">FACTURA</th>
                                            <th class="text-700">CLIENTE</th>
                                            <th class="text-end text-700">TOTAL</th>
                                            <th class="text-end pe-4 text-700">ESTADO</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                        @forelse ($facturasRecientes as $f)
                                            @php [$nomEst, $colEst] = $estadosFactura[$f->estado] ?? [$f->estado, 'secondary']; @endphp
                                            <tr class="hover-actions-trigger position-static">
                                                <td class="ps-4 py-3 text-nowrap">
                                                    <a class="fw-bold text-primary" href="{{ route('facturas.show', $f->id_factura) }}">
                                                        #{{ $f->numero_completo }}
                                                    </a>
                                                </td>
                                                <td class="text-truncate" style="max-width: 14rem;">
                                                    <span class="text-1000 fw-semi-bold">{{ $f->cliente?->razon_social ?? '—' }}</span>
                                                </td>
                                                <td class="text-end text-nowrap fw-bold text-1000">
                                                    {{ $f->moneda }} {{ number_format((float) $f->total, 2) }}
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $colEst }}">{{ $nomEst }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-600 py-5">
                                                    <span class="fas fa-file-invoice fs-2 text-300 d-block mb-2"></span>
                                                    Sin facturas recientes registradas.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($usuario->puede('tickets.ver'))
                <div class="col-12 col-xl-6">
                    <div class="card h-100 border border-200 shadow-none">
                        <div class="card-header border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center bg-white">
                            <div>
                                <h5 class="mb-0 text-1100 fw-bold">Tickets de Soporte Pendientes</h5>
                                <p class="mb-0 fs--2 text-600">Mesa de ayuda y tiempo de respuesta</p>
                            </div>
                            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('tickets.index') }}">Ver todos</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive scrollbar">
                                <table class="table table-sm fs--1 mb-0 align-middle">
                                    <thead class="bg-100">
                                        <tr>
                                            <th class="ps-4 text-700">TICKET</th>
                                            <th class="text-700">CLIENTE</th>
                                            <th class="text-700">ASIGNADO A</th>
                                            <th class="text-end pe-4 text-700">SLA</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                        @forelse ($ticketsRecientes as $tk)
                                            @php [$sem, $colSla] = \App\Support\Sla::semaforo($tk->fecha_limite_resolucion, $tk->fecha_resolucion); @endphp
                                            <tr class="hover-actions-trigger position-static">
                                                <td class="ps-4 py-3">
                                                    <a class="fw-bold text-primary" href="{{ route('tickets.show', $tk->id_ticket) }}">
                                                        #{{ $tk->numero_ticket }}
                                                    </a>
                                                    <span class="d-block fs--2 text-600 text-truncate" style="max-width: 12rem;">{{ $tk->asunto }}</span>
                                                </td>
                                                <td class="text-truncate" style="max-width: 10rem;">
                                                    <span class="text-900">{{ $tk->cliente?->razon_social ?? '—' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-tag">{{ $tk->asignadoA?->nombre_completo ?? 'Sin asignar' }}</span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $colSla }}">{{ $sem }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-600 py-5">
                                                    <span class="fas fa-headset fs-2 text-300 d-block mb-2"></span>
                                                    No hay tickets abiertos pendientes.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendors/echarts/echarts.min.js') }}"></script>
    <script>
        (function () {
            if (!window.echarts) return;

            var data = @json($analitica);
            var moneda = @json($moneda);

            var colores = {
                primario: '#3874ff',
                exito: '#25b003',
                advertencia: '#e5780b',
                peligro: '#ec1f00',
                info: '#0097eb',
                gris: '#8a94ad',
                borde: '#e3e6ed'
            };

            // 1. Gráfico: Facturación vs Cobranza del Mes
            var elFact = document.getElementById('chart-facturacion-cobranza');
            if (elFact) {
                var chartFact = echarts.init(elFact);
                chartFact.setOption({
                    tooltip: {
                        trigger: 'axis',
                        formatter: function (params) {
                            var res = '<strong>' + params[0].name + '</strong><br/>';
                            params.forEach(function (item) {
                                res += item.marker + ' ' + item.seriesName + ': <strong>' + moneda + ' ' + item.value.toLocaleString('es-GT', {minimumFractionDigits: 2}) + '</strong><br/>';
                            });
                            return res;
                        }
                    },
                    legend: {
                        data: ['Facturación', 'Cobranza'],
                        bottom: 0,
                        textStyle: { color: colores.gris }
                    },
                    grid: { top: '10%', left: '3%', right: '4%', bottom: '15%', containLabel: true },
                    xAxis: {
                        type: 'category',
                        data: data.facturacionVsCobranza.semanas,
                        axisLine: { lineStyle: { color: colores.borde } },
                        axisLabel: { color: colores.gris }
                    },
                    yAxis: {
                        type: 'value',
                        axisLine: { show: false },
                        splitLine: { lineStyle: { color: colores.borde, type: 'dashed' } },
                        axisLabel: { color: colores.gris }
                    },
                    series: [
                        {
                            name: 'Facturación',
                            type: 'bar',
                            data: data.facturacionVsCobranza.facturacion,
                            itemStyle: { color: colores.primario, borderRadius: [4, 4, 0, 0] },
                            barMaxWidth: 24
                        },
                        {
                            name: 'Cobranza',
                            type: 'bar',
                            data: data.facturacionVsCobranza.cobranza,
                            itemStyle: { color: colores.exito, borderRadius: [4, 4, 0, 0] },
                            barMaxWidth: 24
                        }
                    ]
                });
                window.addEventListener('resize', function () { chartFact.resize(); });
            }

            // 2. Gráfico: Flujo de Caja Proyectado vs Real
            var elFlujo = document.getElementById('chart-flujo-caja');
            if (elFlujo) {
                var chartFlujo = echarts.init(elFlujo);
                chartFlujo.setOption({
                    tooltip: {
                        trigger: 'axis',
                        formatter: function (params) {
                            var res = '<strong>' + params[0].name + '</strong><br/>';
                            params.forEach(function (item) {
                                res += item.marker + ' ' + item.seriesName + ': <strong>' + moneda + ' ' + item.value.toLocaleString('es-GT', {minimumFractionDigits: 2}) + '</strong><br/>';
                            });
                            return res;
                        }
                    },
                    legend: {
                        data: ['Proyectado (Presupuesto)', 'Real (Facturado)'],
                        bottom: 0,
                        textStyle: { color: colores.gris }
                    },
                    grid: { top: '10%', left: '3%', right: '4%', bottom: '15%', containLabel: true },
                    xAxis: {
                        type: 'category',
                        data: data.flujoCaja.meses,
                        axisLine: { lineStyle: { color: colores.borde } },
                        axisLabel: { color: colores.gris }
                    },
                    yAxis: {
                        type: 'value',
                        axisLine: { show: false },
                        splitLine: { lineStyle: { color: colores.borde, type: 'dashed' } },
                        axisLabel: { color: colores.gris }
                    },
                    series: [
                        {
                            name: 'Proyectado (Presupuesto)',
                            type: 'line',
                            smooth: true,
                            data: data.flujoCaja.proyectado,
                            itemStyle: { color: colores.advertencia },
                            lineStyle: { width: 3, type: 'dashed' },
                            symbolSize: 6
                        },
                        {
                            name: 'Real (Facturado)',
                            type: 'line',
                            smooth: true,
                            data: data.flujoCaja.real,
                            itemStyle: { color: colores.info },
                            areaStyle: {
                                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                    { offset: 0, color: 'rgba(0, 151, 235, 0.25)' },
                                    { offset: 1, color: 'rgba(0, 151, 235, 0.0)' }
                                ])
                            },
                            lineStyle: { width: 3 },
                            symbolSize: 6
                        }
                    ]
                });
                window.addEventListener('resize', function () { chartFlujo.resize(); });
            }

            // 3. Gráfico: Inventario bajo Stock Mínimo
            var elStock = document.getElementById('chart-stock-minimo');
            if (elStock && data.inventarioBajoStock && data.inventarioBajoStock.length) {
                var chartStock = echarts.init(elStock);
                var nombres = data.inventarioBajoStock.map(function (item) { return item.nombre; });
                var actuales = data.inventarioBajoStock.map(function (item) { return item.stock_actual; });
                var minimos = data.inventarioBajoStock.map(function (item) { return item.stock_minimo; });

                chartStock.setOption({
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: { type: 'shadow' }
                    },
                    legend: {
                        data: ['Stock Actual', 'Stock Mínimo'],
                        bottom: 0,
                        textStyle: { color: colores.gris }
                    },
                    grid: { top: '10%', left: '3%', right: '4%', bottom: '15%', containLabel: true },
                    xAxis: {
                        type: 'category',
                        data: nombres,
                        axisLine: { lineStyle: { color: colores.borde } },
                        axisLabel: {
                            color: colores.gris,
                            rotate: 15,
                            interval: 0
                        }
                    },
                    yAxis: {
                        type: 'value',
                        axisLine: { show: false },
                        splitLine: { lineStyle: { color: colores.borde, type: 'dashed' } },
                        axisLabel: { color: colores.gris }
                    },
                    series: [
                        {
                            name: 'Stock Actual',
                            type: 'bar',
                            data: actuales,
                            itemStyle: { color: colores.peligro, borderRadius: [4, 4, 0, 0] },
                            barMaxWidth: 20
                        },
                        {
                            name: 'Stock Mínimo',
                            type: 'bar',
                            data: minimos,
                            itemStyle: { color: colores.gris, borderRadius: [4, 4, 0, 0] },
                            barMaxWidth: 20
                        }
                    ]
                });
                window.addEventListener('resize', function () { chartStock.resize(); });
            }

            // 4. Gráfico: Tickets Pendientes por Prioridad (Doughnut)
            var elTickets = document.getElementById('chart-tickets-prioridad');
            if (elTickets) {
                var chartTickets = echarts.init(elTickets);
                var pData = [
                    { value: data.ticketsPrioridad.CRITICA, name: 'Crítica', itemStyle: { color: colores.peligro } },
                    { value: data.ticketsPrioridad.ALTA, name: 'Alta', itemStyle: { color: colores.advertencia } },
                    { value: data.ticketsPrioridad.MEDIA, name: 'Media', itemStyle: { color: colores.info } },
                    { value: data.ticketsPrioridad.BAJA, name: 'Baja', itemStyle: { color: colores.gris } }
                ];

                chartTickets.setOption({
                    tooltip: {
                        trigger: 'item',
                        formatter: '{b}: <strong>{c} tickets</strong> ({d}%)'
                    },
                    legend: {
                        bottom: 0,
                        textStyle: { color: colores.gris }
                    },
                    series: [
                        {
                            name: 'Prioridad',
                            type: 'pie',
                            radius: ['45%', '70%'],
                            avoidLabelOverlap: false,
                            itemStyle: {
                                borderRadius: 6,
                                borderColor: '#fff',
                                borderWidth: 2
                            },
                            label: {
                                show: false,
                                position: 'center'
                            },
                            emphasis: {
                                label: {
                                    show: true,
                                    fontSize: 16,
                                    fontWeight: 'bold'
                                }
                            },
                            labelLine: { show: false },
                            data: pData
                        }
                    ]
                });
                window.addEventListener('resize', function () { chartTickets.resize(); });
            }
        })();
    </script>
@endpush
