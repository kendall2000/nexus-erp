@extends('layouts.app', ['titulo' => 'Presupuesto '.$anio])

@section('contenido')
    @php
        $yo = auth()->user();
        $moneda = $monedas->count() === 1 ? $monedas->first() : 'Q';
        $dinero = fn ($n) => number_format((float) $n, 2);
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Finanzas</li>
            <li class="breadcrumb-item active">Presupuesto Anual {{ $anio }}</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Header superior estilo Phoenix --}}
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-12 col-md-auto">
                <h2 class="mb-1 text-1100">Presupuesto Corporativo {{ $anio }}</h2>
                <h5 class="text-700 fw-semi-bold mb-0">Planificación, techos presupuestarios y ejecución en tiempo real</h5>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                @if ($yo->puede('presupuesto.exportar'))
                    <a class="btn btn-phoenix-secondary" href="{{ route('presupuesto.exportar', request()->query() + ['anio' => $anio]) }}">
                        <span class="fas fa-file-excel me-2"></span>Exportar
                    </a>
                @endif
                @if ($yo->puede('presupuesto.crear'))
                    <button class="btn btn-phoenix-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#copiar-anio" aria-expanded="{{ $errors->has('anio_origen') || $errors->has('anio_destino') ? 'true' : 'false' }}">
                        <span class="fas fa-copy me-2"></span>Copiar de otro año
                    </button>
                    <a class="btn btn-primary" href="{{ route('presupuesto.create', ['anio' => $anio]) }}">
                        <span class="fas fa-plus me-2"></span>Nueva partida
                    </a>
                @endif
            </div>
        </div>

        @if ($yo->puede('presupuesto.crear'))
            <div class="collapse {{ $errors->has('anio_origen') || $errors->has('anio_destino') ? 'show' : '' }} mb-4" id="copiar-anio">
                <div class="card border border-200 shadow-none">
                    <div class="card-body">
                        <h5 class="mb-3 text-1100"><span class="fas fa-clone text-primary me-2"></span>Clonar Partidas Presupuestarias</h5>
                        <form method="POST" action="{{ route('presupuesto.clonar') }}" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-sm-3">
                                <label class="form-label" for="anio_origen">Año de origen</label>
                                <input class="form-control @error('anio_origen') is-invalid @enderror" id="anio_origen" name="anio_origen" type="number" value="{{ old('anio_origen', $anio - 1) }}" required />
                                @error('anio_origen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label" for="anio_destino">Año destino</label>
                                <input class="form-control @error('anio_destino') is-invalid @enderror" id="anio_destino" name="anio_destino" type="number" value="{{ old('anio_destino', $anio) }}" required />
                                @error('anio_destino')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label" for="incremento">Ajuste porcentual (%)</label>
                                <input class="form-control" id="incremento" name="incremento" type="number" step="0.01" min="-100" max="1000" value="{{ old('incremento', 0) }}" placeholder="Ej: 5.0" />
                            </div>
                            <div class="col-sm-3">
                                <button class="btn btn-primary w-100" type="submit">Generar borrador</button>
                            </div>
                            <div class="col-12 form-text mt-1">Se copiarán las partidas y montos asignados como base para el nuevo ejercicio contable.</div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tarjetas KPI de Resumen Financiero estilo Phoenix --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Total Presupuestado</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ $moneda }} {{ $dinero($resumen['presupuestado']) }}</h3>
                                @if ($resumen['borradores'])
                                    <p class="fs--2 text-600 mb-0 mt-1">Excluye {{ $resumen['borradores'] }} en borrador</p>
                                @endif
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-bullseye text-primary fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Total Ejecutado</h6>
                                <h3 class="text-info mb-0 fw-bolder">{{ $moneda }} {{ $dinero($resumen['ejecutado']) }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Gastos cargados al ejercicio</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-chart-line text-info fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Saldo Disponible</h6>
                                <h3 class="{{ $resumen['saldo'] < 0 ? 'text-danger' : 'text-success' }} mb-0 fw-bolder">{{ $moneda }} {{ $dinero($resumen['saldo']) }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Remanente de presupuesto</p>
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
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-700 fs--1 mb-0 fw-semi-bold">Ejecución Global</h6>
                            <span class="badge badge-phoenix badge-phoenix-{{ $resumen['porcentaje'] > 100 ? 'danger' : ($resumen['porcentaje'] >= 90 ? 'warning' : 'primary') }} fs--2">
                                {{ $resumen['porcentaje'] }} %
                            </span>
                        </div>
                        <h3 class="text-1100 mb-2 fw-bolder">{{ $resumen['porcentaje'] }} %</h3>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar rounded-pill bg-{{ $resumen['porcentaje'] > 100 ? 'danger' : ($resumen['porcentaje'] >= 90 ? 'warning' : 'success') }}" style="width: {{ min(100, $resumen['porcentaje']) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráfico Analítico de Barras por Mes --}}
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header border-bottom border-200 py-3 px-4 bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 text-1100 fw-bold">Evolución Mensual: Presupuesto vs. Ejecución</h5>
                    <p class="mb-0 fs--2 text-600">Comparativa de gasto proyectado y consumo real acumulado</p>
                </div>
            </div>
            <div class="card-body">
                <div id="grafico-presupuesto" style="height: 320px; width: 100%;" role="img" aria-label="Gráfico de ejecución presupuestaria"></div>
            </div>
        </div>

        <div id="presupuestoTable">
            {{-- Toolbar idéntico a Phoenix orders.html --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('presupuesto.index') }}" class="row g-3 align-items-center">
                    <input type="hidden" name="anio" value="{{ $anio }}" />
                    <div class="col-auto">
                        <div class="btn-group position-static">
                            <select class="form-select btn-phoenix-secondary px-4" name="anio" onchange="this.form.submit()">
                                @foreach ($anios as $a)
                                    <option value="{{ $a }}" @selected($a === $anio)>Año: {{ $a }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-auto scrollbar overflow-hidden-y flex-grow-1">
                        <div class="btn-group position-static" role="group">
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="centro" onchange="this.form.submit()">
                                    <option value="">Todos los centros de costo</option>
                                    @foreach ($centros as $c)
                                        <option value="{{ $c->id_centro }}" @selected((int) ($filtros['centro'] ?? 0) === $c->id_centro)>{{ $c->etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="btn-group position-static text-nowrap">
                                <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="estado" onchange="this.form.submit()">
                                    <option value="">Todos los estados</option>
                                    @foreach ($estados as $codigo => [$nombre])
                                        <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if (array_filter($filtros))
                                <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('presupuesto.index', ['anio' => $anio]) }}" title="Limpiar filtros">
                                    <span class="fas fa-times me-1"></span>Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabla con estructura visual exacta de Phoenix --}}
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="sort align-middle ps-3" scope="col" style="min-width:220px;">CENTRO DE COSTO</th>
                                <th class="sort align-middle ps-4" scope="col" style="min-width:240px;">CUENTA CONTABLE</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:160px;">PRESUPUESTADO</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:160px;">EJECUTADO</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:160px;">DISPONIBLE</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:180px;">AVANCE DE EJECUCIÓN</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:130px;">ESTADO</th>
                            </tr>
                        </thead>
                        <tbody class="list" id="presupuesto-table-body">
                            @forelse ($partidas as $p)
                                @php
                                    [$nombreEstado, $colorEstado] = $estados[$p->estado] ?? [$p->estado, 'secondary'];
                                    [$nombreEje, $colorEje] = $ejecucion[$p->estado_ejecucion];
                                @endphp
                                <tr class="hover-actions-trigger position-static">
                                    <td class="customer align-middle white-space-nowrap ps-3 py-3">
                                        <span class="fw-semi-bold text-1000">{{ $p->centroCosto?->etiqueta ?? '—' }}</span>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        <a class="fw-bold text-primary" href="{{ route('presupuesto.show', $p->id_presupuesto) }}">
                                            {{ $p->cuentaContable?->etiqueta ?? '—' }}
                                        </a>
                                    </td>
                                    <td class="total align-middle text-end white-space-nowrap text-900 ps-4">
                                        {{ $p->moneda }} {{ $dinero($p->total_presupuestado) }}
                                    </td>
                                    <td class="total align-middle text-end white-space-nowrap fw-bold text-1000 ps-4">
                                        {{ $dinero($p->total_ejecutado) }}
                                    </td>
                                    <td class="total align-middle text-end white-space-nowrap fw-bold ps-4 {{ $p->saldo_disponible < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $dinero($p->saldo_disponible) }}
                                    </td>
                                    <td class="align-middle ps-4" style="min-width: 150px;">
                                        <div class="d-flex align-items-center">
                                            <div class="progress w-100 me-2" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $colorEje }}" style="width: {{ min(100, $p->porcentaje_ejecucion) }}%"></div>
                                            </div>
                                            <span class="fs--2 text-700 fw-semi-bold">{{ $p->porcentaje_ejecucion }}%</span>
                                        </div>
                                        <span class="fs--2 text-600">{{ $nombreEje }}</span>
                                    </td>
                                    <td class="payment_status align-middle white-space-nowrap ps-4">
                                        <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $colorEstado }}">{{ $nombreEstado }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-700 py-5">
                                        <span class="fas fa-chart-pie fs-2 text-300 d-block mb-2"></span>
                                        {{ array_filter($filtros) ? 'Ninguna partida presupuestaria coincide con los filtros.' : "No hay presupuesto formulado para {$anio}." }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendors/echarts/echarts.min.js') }}"></script>
    <script>
        (function () {
            var meses = @json($meses);
            var moneda = @json($moneda);
            var el = document.getElementById('grafico-presupuesto');
            if (!window.echarts || !el) return;
            var grafico = echarts.init(el);
            var tema = {
                claro: { serie1: '#3874ff', serie2: '#e5780b', texto: '#525b75', rejilla: '#e3e6ed', fondo: '#ffffff', tinta: '#141824' },
                oscuro: { serie1: '#3874ff', serie2: '#e5780b', texto: '#9fa6bc', rejilla: '#31374a', fondo: '#141824', tinta: '#ffffff' }
            };
            var dinero = function (n) { return (moneda ? moneda + ' ' : '') + Number(n).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
            var corto = function (n) { return Math.abs(n) >= 1e6 ? (n / 1e6).toFixed(1) + ' M' : Math.abs(n) >= 1e3 ? (n / 1e3).toFixed(0) + ' k' : n; };

            function pintar() {
                var t = document.documentElement.classList.contains('dark') ? tema.oscuro : tema.claro;
                var barra = function (color) { return { color: color, borderRadius: [4, 4, 0, 0] }; };
                grafico.setOption({
                    textStyle: { fontFamily: 'Nunito Sans, sans-serif', color: t.texto },
                    legend: { top: 0, left: 0, icon: 'roundRect', itemWidth: 12, itemHeight: 12, textStyle: { color: t.texto } },
                    grid: { top: 36, left: 8, right: 8, bottom: 4, containLabel: true },
                    tooltip: {
                        trigger: 'axis', axisPointer: { type: 'shadow' }, backgroundColor: t.fondo, borderColor: t.rejilla, textStyle: { color: t.tinta },
                        formatter: function (ps) {
                            var fila = ps.map(function (p) { return p.marker + p.seriesName + ': <b>' + dinero(p.value) + '</b>'; });
                            var pre = ps[0] ? ps[0].value : 0, eje = ps[1] ? ps[1].value : 0;
                            if (pre > 0) fila.push('Ejecución: <b>' + (eje / pre * 100).toFixed(1) + ' %</b>');
                            return '<b>' + ps[0].axisValue + '</b><br>' + fila.join('<br>');
                        }
                    },
                    xAxis: { type: 'category', data: meses.map(function (m) { return m.mes.slice(0, 3); }), axisTick: { show: false }, axisLine: { lineStyle: { color: t.rejilla } }, axisLabel: { color: t.texto } },
                    yAxis: { type: 'value', splitLine: { lineStyle: { color: t.rejilla } }, axisLabel: { color: t.texto, formatter: corto } },
                    series: [
                        { name: 'Presupuestado', type: 'bar', barMaxWidth: 20, barGap: '20%', itemStyle: barra(t.serie1), data: meses.map(function (m) { return m.presupuestado; }) },
                        { name: 'Ejecutado', type: 'bar', barMaxWidth: 20, itemStyle: barra(t.serie2), data: meses.map(function (m) { return m.ejecutado; }) }
                    ]
                });
            }
            pintar();
            new MutationObserver(pintar).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            window.addEventListener('resize', function () { grafico.resize(); });
        })();
    </script>
@endpush
