@extends('layouts.app', ['titulo' => 'Nómina'])

@section('contenido')
    @php $yo = auth()->user(); $puedeProcesar = $yo->puede('nomina.procesar'); @endphp

    <x-page-header
        titulo="Nómina"
        icono="fas fa-money-check-alt"
        subtitulo="Periodos de pago: salario proporcional, bonificación incentivo, IGSS, ISR y préstamos."
        :migas="[
            ['label' => 'Recursos Humanos'],
            ['label' => 'Nómina'],
        ]"
    >
        <x-slot:acciones>
            @if ($puedeProcesar)
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#nuevo-periodo" aria-expanded="{{ $errors->any() && ! $errors->prestamo->any() ? 'true' : 'false' }}"><span class="fas fa-plus me-2"></span>Nuevo periodo</button>
            @endif
        </x-slot:acciones>
    </x-page-header>

    @if ($puedeProcesar)
        <div class="collapse {{ $errors->any() && ! $errors->prestamo->any() ? 'show' : '' }} mb-4" id="nuevo-periodo">
            <div class="card border border-200 shadow-none"><div class="card-body">
                <form method="POST" action="{{ route('nomina.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label fs--1" for="tipo">Tipo</label>
                        <select class="form-select form-select-sm" id="tipo" name="tipo">@foreach ($tipos as $k => $n)<option value="{{ $k }}" @selected(old('tipo', $sugerido['tipo']) === $k)>{{ $n }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fs--1" for="fecha_inicio">Desde</label>
                        <input class="form-control form-control-sm @error('fecha_inicio') is-invalid @enderror" id="fecha_inicio" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', $sugerido['fecha_inicio']) }}" required />
                    </div>
                    <div class="col-md-2"><label class="form-label fs--1" for="fecha_fin">Hasta</label><input class="form-control form-control-sm @error('fecha_fin') is-invalid @enderror" id="fecha_fin" name="fecha_fin" type="date" value="{{ old('fecha_fin', $sugerido['fecha_fin']) }}" required /></div>
                    <div class="col-md-2"><label class="form-label fs--1" for="fecha_pago">Fecha de pago</label><input class="form-control form-control-sm" id="fecha_pago" name="fecha_pago" type="date" value="{{ old('fecha_pago', $sugerido['fecha_pago']) }}" required /></div>
                    <div class="col-md-2"><label class="form-label fs--1" for="nombre">Nombre</label><input class="form-control form-control-sm" id="nombre" name="nombre" value="{{ old('nombre') }}" maxlength="100" placeholder="Automático" /></div>
                    <input type="hidden" name="moneda" value="GTQ" />
                    <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit">Crear periodo</button></div>
                    @if ($errors->any() && ! $errors->prestamo->any())<div class="col-12 text-danger fs--1">{{ $errors->first() }}</div>@endif
                </form>
            </div></div>
        </div>
    @endif

    @php $dinero = fn ($n) => number_format((float) $n, 2); @endphp
    @if ($resumen)
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <x-stat-card etiqueta="Líquido del último periodo" :valor="$resumen['periodo']->moneda.' '.$dinero($resumen['periodo']->total_neto)" icono="fas fa-wallet" color="success" :subtexto="$resumen['periodo']->nombre.' · '.$resumen['empleados'].' colaborador(es)'" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-stat-card etiqueta="Cuota patronal (12.67 %)" :valor="$dinero($resumen['patronal'])" icono="fas fa-building" color="info" subtexto="IGSS 10.67 % + INTECAP 1 % + IRTRA 1 %" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-stat-card etiqueta="Provisiones de prestaciones" :valor="$dinero($resumen['provisiones'])" icono="fas fa-piggy-bank" color="warning" subtexto="Aguinaldo, Bono 14, indemnización y vacaciones" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-stat-card etiqueta="Costo total empresa" :valor="$dinero($resumen['costo_total'])" icono="fas fa-chart-pie" color="primary" subtexto="Devengado + cuota patronal + provisiones" />
            </div>
        </div>
    @endif

    <ul class="nav nav-underline mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link {{ $errors->prestamo->any() ? '' : 'active' }}" data-bs-toggle="tab" href="#tab-periodos" role="tab"><span class="fas fa-calendar-alt me-1"></span>Periodos de nómina</a></li>
        <li class="nav-item"><a class="nav-link {{ $errors->prestamo->any() ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-prestamos" role="tab"><span class="fas fa-hand-holding-usd me-1"></span>Préstamos activos <span class="badge badge-phoenix badge-phoenix-secondary ms-1">{{ $prestamos->count() }}</span></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-reglas" role="tab"><span class="fas fa-balance-scale me-1"></span>Reglas y provisiones de ley</a></li>
    </ul>

    <div class="tab-content">
    <div class="tab-pane fade {{ $errors->prestamo->any() ? '' : 'show active' }}" id="tab-periodos" role="tabpanel">
    <div class="mb-4">
        <x-phoenix-table :paginator="$periodos">
            <x-slot:head>
                <tr>
                    <th>Periodo</th>
                    <th>Fechas</th>
                    <th class="text-end">Empleados</th>
                    <th class="text-end">Bruto</th>
                    <th class="text-end">Deducciones</th>
                    <th class="text-end">Líquido</th>
                    <th>Estado</th>
                </tr>
            </x-slot:head>
            @forelse ($periodos as $p)
                @php [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary']; @endphp
                <tr>
                    <td><a class="fw-semi-bold text-decoration-none" href="{{ route('nomina.show', $p->id_periodo) }}">{{ $p->nombre }}</a><span class="d-block fs--2 text-600">{{ $tipos[$p->tipo] ?? $p->tipo }}</span></td>
                    <td class="text-nowrap">{{ $p->fecha_inicio?->format('d/m/Y') }} – {{ $p->fecha_fin?->format('d/m/Y') }}<span class="d-block fs--2 text-600">pago {{ $p->fecha_pago?->format('d/m/Y') }}</span></td>
                    <td class="text-end">{{ $p->detalles_count }}</td>
                    <td class="text-end">{{ number_format((float) $p->total_bruto, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $p->total_deducciones, 2) }}</td>
                    <td class="text-end fw-semi-bold">{{ $p->moneda }} {{ number_format((float) $p->total_neto, 2) }}</td>
                    <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-700 py-4">Todavía no hay periodos de nómina.</td></tr>
            @endforelse
        </x-phoenix-table>
    </div>
    </div>

    <div class="tab-pane fade {{ $errors->prestamo->any() ? 'show active' : '' }}" id="tab-prestamos" role="tabpanel">
    <div class="card border border-200 shadow-none">
        <div class="card-body fs--1">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-1100"><span class="fas fa-hand-holding-usd text-primary me-2"></span>Préstamos activos</h5>
                @if ($puedeProcesar)<button class="btn btn-phoenix-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#nuevo-prestamo">Registrar préstamo</button>@endif
            </div>
            @if ($puedeProcesar)
                <div class="collapse {{ $errors->prestamo->any() ? 'show' : '' }} mb-3" id="nuevo-prestamo">
                    @if ($errors->prestamo->any())<div class="text-danger mb-2">{{ $errors->prestamo->first() }}</div>@endif
                    <form method="POST" action="{{ route('nomina.prestamos.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-4"><select class="form-select form-select-sm" name="id_empleado" required><option value="">Empleado…</option>@foreach ($empleados as $e)<option value="{{ $e->id_empleado }}" @selected((int) old('id_empleado') === $e->id_empleado)>{{ $e->nombre_completo }}</option>@endforeach</select></div>
                        <div class="col-md-2"><input class="form-control form-control-sm" name="monto_total" type="number" step="0.01" min="1" value="{{ old('monto_total') }}" placeholder="Monto" required /></div>
                        <div class="col-md-2"><input class="form-control form-control-sm" name="cuota_quincenal" type="number" step="0.01" min="1" value="{{ old('cuota_quincenal') }}" placeholder="Cuota quincenal" required /></div>
                        <div class="col-md-2"><input class="form-control form-control-sm" name="fecha_otorgamiento" type="date" value="{{ old('fecha_otorgamiento', now()->format('Y-m-d')) }}" required /></div>
                        <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit">Guardar</button></div>
                        <div class="col-12"><input class="form-control form-control-sm" name="motivo" value="{{ old('motivo') }}" maxlength="300" placeholder="Motivo (opcional)" /></div>
                    </form>
                </div>
            @endif
            @forelse ($prestamos as $pr)
                <div class="d-flex justify-content-between border-bottom border-200 py-1">
                    <span>{{ $pr->empleado?->nombre_completo }} <span class="text-600">· {{ $pr->fecha_otorgamiento?->format('d/m/Y') }} · cuota quincenal {{ number_format((float) $pr->cuota_quincenal, 2) }}</span></span>
                    <span>Pendiente <strong>{{ number_format((float) $pr->monto_pendiente, 2) }}</strong> de {{ number_format((float) $pr->monto_total, 2) }}</span>
                </div>
            @empty
                <p class="text-700 mb-0">No hay préstamos activos.</p>
            @endforelse
        </div>
    </div>
    </div>

    <div class="tab-pane fade" id="tab-reglas" role="tabpanel">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border border-200 shadow-none h-100"><div class="card-body fs--1">
                    <h5 class="mb-3 text-1100"><span class="fas fa-user me-2 text-primary"></span>Descuentos al colaborador</h5>
                    <table class="table table-sm mb-0">
                        <tr><td>IGSS laboral</td><td class="text-end fw-semi-bold">{{ \App\Support\Nomina::IGSS_LABORAL * 100 }} %</td></tr>
                        <tr><td>ISR asalariados (renta anual proyectada − Q{{ number_format(\App\Support\Nomina::ISR_DEDUCCION_UNICA) }} − IGSS)</td><td class="text-end fw-semi-bold">5 % / 7 %</td></tr>
                        <tr><td>Tramo ISR</td><td class="text-end">Q{{ number_format(\App\Support\Nomina::ISR_TRAMO) }}</td></tr>
                        <tr><td>Bonificación incentivo (Decreto 37-2001, no afecta IGSS)</td><td class="text-end">Q{{ number_format(\App\Support\Nomina::BONIFICACION_INCENTIVO_MENSUAL, 2) }} / mes</td></tr>
                        <tr><td>Horas extra (salario / 30 / 8)</td><td class="text-end">× {{ \App\Support\Nomina::RECARGO_HORA_EXTRA }}</td></tr>
                    </table>
                </div></div>
            </div>
            <div class="col-lg-6">
                <div class="card border border-200 shadow-none h-100"><div class="card-body fs--1">
                    <h5 class="mb-3 text-1100"><span class="fas fa-building me-2 text-primary"></span>Costo patronal y provisiones</h5>
                    <table class="table table-sm mb-0">
                        <tr><td>Cuota patronal (IGSS 10.67 % + INTECAP 1 % + IRTRA 1 %)</td><td class="text-end fw-semi-bold">{{ \App\Support\Nomina::IGSS_PATRONAL * 100 }} %</td></tr>
                        @foreach ($provisiones as $prov)
                            <tr><td>{{ $prov['nombre'] }}</td><td class="text-end fw-semi-bold">{{ number_format($prov['tasa'] * 100, 2) }} %</td></tr>
                        @endforeach
                        <tr class="fw-bold"><td>Total provisiones</td><td class="text-end">{{ number_format(collect($provisiones)->sum('tasa') * 100, 2) }} %</td></tr>
                    </table>
                    <p class="text-600 fs--2 mt-2 mb-0">Las provisiones se calculan sobre salario ordinario y horas extra. Conviene validar las tasas con el contador.</p>
                </div></div>
            </div>
        </div>
    </div>
    </div>
@endsection
