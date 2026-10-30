@extends('layouts.app', ['titulo' => 'Presupuesto '.$anio])

@section('contenido')
    @php
        $yo = auth()->user();
        $moneda = $monedas->count() === 1 ? $monedas->first() : '';
        $dinero = fn ($n) => number_format((float) $n, 2);
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Presupuesto {{ $anio }}</h2>
            <p class="text-700 fw-semi-bold mb-0">Por centro de costo y cuenta. Las órdenes de compra aprobadas ejecutan los presupuestos aprobados.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($yo->puede('presupuesto.exportar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('presupuesto.exportar', request()->query() + ['anio' => $anio]) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($yo->puede('presupuesto.crear'))
                <button class="btn btn-phoenix-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#copiar-anio" aria-expanded="{{ $errors->has('anio_origen') || $errors->has('anio_destino') ? 'true' : 'false' }}"><span class="fas fa-copy me-2"></span>Copiar de otro año</button>
                <a class="btn btn-primary" href="{{ route('presupuesto.create', ['anio' => $anio]) }}"><span class="fas fa-plus me-2"></span>Nueva partida</a>
            @endif
        </div>
    </div>

    @if ($yo->puede('presupuesto.crear'))
        <div class="collapse {{ $errors->has('anio_origen') || $errors->has('anio_destino') ? 'show' : '' }} mb-4" id="copiar-anio">
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('presupuesto.clonar') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-sm-3">
                        <label class="form-label" for="anio_origen">Copiar desde</label>
                        <input class="form-control @error('anio_origen') is-invalid @enderror" id="anio_origen" name="anio_origen" type="number" value="{{ old('anio_origen', $anio - 1) }}" required />
                        @error('anio_origen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label" for="anio_destino">Hacia</label>
                        <input class="form-control @error('anio_destino') is-invalid @enderror" id="anio_destino" name="anio_destino" type="number" value="{{ old('anio_destino', $anio) }}" required />
                        @error('anio_destino')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label" for="incremento">Ajuste (%)</label>
                        <input class="form-control" id="incremento" name="incremento" type="number" step="0.01" min="-100" max="1000" value="{{ old('incremento', 0) }}" />
                    </div>
                    <div class="col-sm-3"><button class="btn btn-primary w-100" type="submit">Copiar en borrador</button></div>
                    <div class="col-12 form-text mt-1">Se copian los montos mensuales (sin ejecución). Las partidas que ya existen en el año destino no se tocan.</div>
                </form>
            </div></div>
        </div>
    @endif

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-2">
            <select class="form-select form-select-sm" name="anio" onchange="this.form.submit()">
                @foreach ($anios as $a)<option value="{{ $a }}" @selected($a === $anio)>{{ $a }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-select form-select-sm" name="centro">
                <option value="">Todos los centros de costo</option>
                @foreach ($centros as $c)<option value="{{ $c->id_centro }}" @selected((int) ($filtros['centro'] ?? 0) === $c->id_centro)>{{ $c->etiqueta }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select form-select-sm" name="estado">
                <option value="">Todos los estados</option>
                @foreach ($estados as $codigo => [$nombre])<option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
            @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('presupuesto.index', ['anio' => $anio]) }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
        </div>
    </form>

    @if ($monedas->count() > 1)
        <div class="alert alert-soft-warning fs--1 py-2"><span class="fas fa-exclamation-triangle me-2"></span>Hay partidas en varias monedas ({{ $monedas->implode(', ') }}): el resumen y el gráfico suman montos sin convertir. Filtra por centro para verlos por separado.</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Presupuestado</p><h3 class="mb-0">{{ $moneda }} {{ $dinero($resumen['presupuestado']) }}</h3>
            @if ($resumen['borradores'])<p class="fs--2 text-600 mb-0">Sin contar {{ $resumen['borradores'] }} en borrador</p>@endif
        </div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Ejecutado</p><h3 class="mb-0">{{ $moneda }} {{ $dinero($resumen['ejecutado']) }}</h3>
        </div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Disponible</p><h3 class="mb-0 {{ $resumen['saldo'] < 0 ? 'text-danger' : '' }}">{{ $moneda }} {{ $dinero($resumen['saldo']) }}</h3>
        </div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Ejecución</p><h3 class="mb-1">{{ $resumen['porcentaje'] }} %</h3>
            <div class="progress" style="height: 6px"><div class="progress-bar {{ $resumen['porcentaje'] > 100 ? 'bg-danger' : ($resumen['porcentaje'] >= 90 ? 'bg-warning' : 'bg-success') }}" style="width: {{ min(100, $resumen['porcentaje']) }}%"></div></div>
        </div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-1">Presupuestado y ejecutado por mes</h5>
            <p class="fs--1 text-700 mb-2">Presupuestos aprobados y cerrados{{ $moneda ? ', en '.$moneda : '' }}.</p>
            <div id="grafico-presupuesto" style="height: 300px" role="img" aria-label="Gráfico de barras por mes: presupuestado y ejecutado"></div>
            <details class="mt-2 fs--1">
                <summary class="text-primary" style="cursor: pointer">Ver como tabla</summary>
                <div class="table-responsive mt-2">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Mes</th>@foreach ($meses as $m)<th class="text-end">{{ mb_substr($m['mes'], 0, 3) }}</th>@endforeach</tr></thead>
                        <tbody>
                            <tr><td>Presupuestado</td>@foreach ($meses as $m)<td class="text-end">{{ $dinero($m['presupuestado']) }}</td>@endforeach</tr>
                            <tr><td>Ejecutado</td>@foreach ($meses as $m)<td class="text-end">{{ $dinero($m['ejecutado']) }}</td>@endforeach</tr>
                        </tbody>
                    </table>
                </div>
            </details>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead><tr><th>Centro de costo</th><th>Cuenta</th><th class="text-end">Presupuestado</th><th class="text-end">Ejecutado</th><th class="text-end">Disponible</th><th style="min-width: 9rem">Ejecución</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($partidas as $p)
                        @php
                            [$nombreEstado, $colorEstado] = $estados[$p->estado] ?? [$p->estado, 'secondary'];
                            [$nombreEje, $colorEje] = $ejecucion[$p->estado_ejecucion];
                        @endphp
                        <tr>
                            <td>{{ $p->centroCosto?->etiqueta ?? '—' }}</td>
                            <td><a class="fw-semi-bold" href="{{ route('presupuesto.show', $p->id_presupuesto) }}">{{ $p->cuentaContable?->etiqueta ?? '—' }}</a></td>
                            <td class="text-end text-nowrap">{{ $p->moneda }} {{ $dinero($p->total_presupuestado) }}</td>
                            <td class="text-end text-nowrap">{{ $dinero($p->total_ejecutado) }}</td>
                            <td class="text-end text-nowrap fw-semi-bold {{ $p->saldo_disponible < 0 ? 'text-danger' : '' }}">{{ $dinero($p->saldo_disponible) }}</td>
                            <td>
                                <div class="progress" style="height: 6px" title="{{ $nombreEje }}"><div class="progress-bar bg-{{ $colorEje }}" style="width: {{ min(100, $p->porcentaje_ejecucion) }}%"></div></div>
                                <span class="fs--2 text-700">{{ $p->porcentaje_ejecucion }} % · {{ $nombreEje }}</span>
                            </td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $colorEstado }}">{{ $nombreEstado }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ninguna partida coincide con los filtros.' : "No hay presupuesto para {$anio}." }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
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
            // Paleta validada (ranuras 1 y 2) con su versión para modo oscuro.
            var tema = {
                claro: { serie1: '#2a78d6', serie2: '#eb6834', texto: '#52514e', rejilla: '#e3e2dc', fondo: '#fcfcfb', tinta: '#0b0b0b' },
                oscuro: { serie1: '#3987e5', serie2: '#d95926', texto: '#c3c2b7', rejilla: '#3a3a37', fondo: '#1a1a19', tinta: '#ffffff' }
            };
            var dinero = function (n) { return (moneda ? moneda + ' ' : '') + Number(n).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
            var corto = function (n) { return Math.abs(n) >= 1e6 ? (n / 1e6).toFixed(1) + ' M' : Math.abs(n) >= 1e3 ? (n / 1e3).toFixed(0) + ' mil' : n; };

            function pintar() {
                var t = document.documentElement.classList.contains('dark') ? tema.oscuro : tema.claro;
                var barra = function (color) { return { color: color, borderRadius: [4, 4, 0, 0] }; };
                grafico.setOption({
                    textStyle: { fontFamily: 'inherit', color: t.texto },
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
                        { name: 'Presupuestado', type: 'bar', barMaxWidth: 18, barGap: '12%', itemStyle: barra(t.serie1), data: meses.map(function (m) { return m.presupuestado; }) },
                        { name: 'Ejecutado', type: 'bar', barMaxWidth: 18, itemStyle: barra(t.serie2), data: meses.map(function (m) { return m.ejecutado; }) }
                    ]
                });
            }
            pintar();
            new MutationObserver(pintar).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            window.addEventListener('resize', function () { grafico.resize(); });
        })();
    </script>
@endpush
