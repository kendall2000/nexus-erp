@extends('layouts.app', ['titulo' => $p->nombre])

@section('contenido')
    @php
        $yo = auth()->user();
        [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary'];
        $puedeProcesar = $yo->puede('nomina.procesar');
        $dinero = fn ($n) => number_format((float) $n, 2);
        $patronal = $detalles->sum(fn ($d) => (float) $d->cuota_igss_pat);
        $automaticos = \App\Support\Nomina::AUTOMATICOS;
    @endphp

    <x-page-header
        :titulo="$p->nombre"
        icono="fas fa-money-check-alt"
        :migas="[
            ['label' => 'Nómina', 'url' => route('nomina.index')],
            ['label' => $p->nombre],
        ]"
    >
        <x-slot:insignia>
            <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1 align-middle">{{ $nombreEstado }}</span>
        </x-slot:insignia>
        <x-slot:subtituloSlot>
            <p class="text-700 mb-0">{{ $tipos[$p->tipo] ?? $p->tipo }} · {{ $p->fecha_inicio?->format('d/m/Y') }} – {{ $p->fecha_fin?->format('d/m/Y') }} · pago {{ $p->fecha_pago?->format('d/m/Y') }}</p>
        </x-slot:subtituloSlot>
        <x-slot:acciones>
            @if ($editable && $puedeProcesar)
                <form method="POST" action="{{ route('nomina.procesar', $p->id_periodo) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-primary" type="submit"><span class="fas fa-calculator me-2"></span>{{ $detalles->isEmpty() ? 'Calcular nómina' : 'Recalcular' }}</button>
                </form>
            @endif
            @if ($p->estado === 'EN_PROCESO' && $yo->puede('nomina.cerrar'))
                <form method="POST" action="{{ route('nomina.cerrar', $p->id_periodo) }}" onsubmit="return confirm('Al cerrar se descuentan las cuotas de los préstamos y ya no se puede ajustar. ¿Cerrar?')">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-phoenix-warning" type="submit"><span class="fas fa-lock me-2"></span>Cerrar</button>
                </form>
            @endif
            @if ($p->estado === 'CERRADO' && $puedeProcesar)
                <form method="POST" action="{{ route('nomina.pagar', $p->id_periodo) }}" class="d-flex gap-1">
                    @csrf
                    @method('PATCH')
                    <input class="form-control form-control-sm" type="date" name="fecha_pago" value="{{ $p->fecha_pago?->format('Y-m-d') }}" required />
                    <button class="btn btn-success text-nowrap" type="submit">Marcar pagada</button>
                </form>
            @endif
            @if ($p->estado === 'CERRADO' && $yo->puede('nomina.reabrir'))
                <form method="POST" action="{{ route('nomina.reabrir', $p->id_periodo) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-phoenix-secondary" type="submit">Reabrir</button>
                </form>
            @endif
            @if ($detalles->isNotEmpty() && $yo->puede('nomina.imprimir'))
                <a class="btn btn-phoenix-secondary" href="{{ route('nomina.imprimir', $p->id_periodo) }}" target="_blank" rel="noopener"><span class="fas fa-print me-2"></span>Boletas</a>
            @endif
            @if ($detalles->isNotEmpty())
                <div class="dropdown">
                    <button class="btn btn-phoenix-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><span class="fas fa-file-export me-2"></span>Exportar</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('nomina.exportar', $p->id_periodo) }}"><span class="fas fa-file-excel me-2"></span>Planilla (CSV)</a></li>
                        <li><a class="dropdown-item" href="{{ route('nomina.banco', $p->id_periodo) }}"><span class="fas fa-university me-2"></span>Archivo para banco (CSV)</a></li>
                    </ul>
                </div>
            @endif
        </x-slot:acciones>
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-stat-card etiqueta="Total devengado" :valor="$dinero($p->total_bruto)" icono="fas fa-coins" color="primary" :subtexto="$detalles->count().' colaborador(es)'" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card etiqueta="Deducciones" :valor="$dinero($p->total_deducciones)" icono="fas fa-minus-circle" color="danger" subtexto="IGSS, ISR y préstamos" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card etiqueta="Líquido a pagar" :valor="$p->moneda.' '.$dinero($p->total_neto)" icono="fas fa-wallet" color="success" :subtexto="'Pago: '.($p->fecha_pago?->format('d/m/Y') ?? '—')" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card etiqueta="Cuota patronal (IGSS, INTECAP, IRTRA)" :valor="$dinero($patronal)" icono="fas fa-building" color="info" subtexto="Aporte patronal obligatorio" />
        </div>
    </div>

    @if ($detalles->isNotEmpty())
        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <x-stat-card etiqueta="Costo total empresa" :valor="$dinero((float) $p->total_bruto + $patronal + $provisiones['total'])" icono="fas fa-chart-pie" color="primary" subtexto="Devengado + cuota patronal + provisiones" />
            </div>
            <div class="col-lg-8">
                <div class="card h-100"><div class="card-body py-3 fs--1">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-semi-bold text-700">Provisión de prestaciones</span>
                        <span class="fw-bold text-1100">{{ $dinero($provisiones['total']) }} <span class="text-600 fw-normal">sobre {{ $dinero($provisiones['base']) }}</span></span>
                    </div>
                    <div class="row g-2">
                        @foreach ($provisiones['desglose'] as $prov)
                            <div class="col-sm-6 col-xl-3"><div class="border border-200 rounded-2 p-2 h-100">
                                <div class="fs--2 text-600">{{ $prov['nombre'] }}</div>
                                <div class="fw-semi-bold">{{ $dinero($prov['monto']) }}</div>
                            </div></div>
                        @endforeach
                    </div>
                </div></div>
            </div>
        </div>
    @endif

    @if ($errors->ajuste->any())<div class="alert alert-soft-danger fs--1">{{ $errors->ajuste->first() }}</div>@endif

    @forelse ($detalles as $d)
        @php $negativo = (float) $d->liquido_pagar < 0; @endphp
        <details class="card border border-200 shadow-none mb-2" @if ($errors->ajuste->any() && (int) old('id_detalle') === $d->id_detalle) open @endif>
            <summary class="card-body py-2 d-flex flex-wrap justify-content-between gap-2 fs--1" style="cursor: pointer">
                <span><span class="fw-semi-bold text-1000">{{ $d->empleado?->nombre_completo }}</span> <span class="text-600">{{ $d->cargo_snapshot }} · {{ rtrim(rtrim(number_format((float) $d->dias_trabajados, 2), '0'), '.') }} días</span></span>
                <span class="text-nowrap">{{ $dinero($d->total_ingresos) }} − {{ $dinero($d->total_deducciones) }} = <strong class="{{ $negativo ? 'text-danger' : '' }}">{{ $dinero($d->liquido_pagar) }}</strong></span>
            </summary>
            <div class="card-body pt-0 fs--1">
                @if ($negativo)<p class="text-danger"><span class="fas fa-exclamation-triangle me-1"></span>Las deducciones superan los ingresos.</p>@endif
                <div class="row g-3">
                    <div class="col-md-7">
                        <table class="table table-sm mb-0">
                            @foreach ($d->conceptos->sortBy(fn ($l) => $l->tipo === 'INGRESO' ? 0 : 1) as $l)
                                <tr>
                                    <td>{{ $l->concepto?->nombre }} <span class="text-600">{{ $l->descripcion }}</span></td>
                                    <td class="text-end {{ $l->tipo === 'DEDUCCION' ? 'text-danger' : '' }}">{{ $l->tipo === 'DEDUCCION' ? '−' : '' }}{{ $dinero($l->monto) }}</td>
                                    <td class="text-end" style="width: 2rem">
                                        @if ($editable && $puedeProcesar && ! in_array($l->concepto?->codigo, $automaticos, true))
                                            <form method="POST" action="{{ route('nomina.conceptos.destroy', [$p->id_periodo, $l->id_linea]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-link text-danger p-0" type="submit" title="Quitar"><span class="fas fa-times"></span></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    <div class="col-md-5">
                        @if ($editable && $puedeProcesar)
                            <form method="POST" action="{{ route('nomina.ajustar', [$p->id_periodo, $d->id_detalle]) }}" class="row g-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="id_detalle" value="{{ $d->id_detalle }}" />
                                <div class="col-6"><label class="fs--2 text-600">Días trabajados</label><input class="form-control form-control-sm" name="dias_trabajados" type="number" step="0.5" min="0" value="{{ (float) $d->dias_trabajados }}" /></div>
                                <div class="col-6"><label class="fs--2 text-600">Horas extra</label><input class="form-control form-control-sm" name="horas_extra" type="number" step="0.5" min="0" value="{{ (float) $d->horas_extra }}" /></div>
                                <div class="col-7">
                                    <select class="form-select form-select-sm" name="id_concepto"><option value="">Agregar concepto…</option>@foreach ($manuales as $c)<option value="{{ $c->id_concepto }}">{{ $c->nombre }} ({{ $c->tipo === 'INGRESO' ? '+' : '−' }})</option>@endforeach</select>
                                </div>
                                <div class="col-5"><input class="form-control form-control-sm" name="monto" type="number" step="0.01" min="0.01" placeholder="Monto" /></div>
                                <div class="col-8"><input class="form-control form-control-sm" name="descripcion" maxlength="200" placeholder="Detalle (opcional)" /></div>
                                <div class="col-4"><button class="btn btn-primary btn-sm w-100" type="submit">Aplicar</button></div>
                            </form>
                        @endif
                        @if ($yo->puede('nomina.imprimir'))<a class="d-inline-block mt-2 fw-semi-bold text-decoration-none" href="{{ route('nomina.imprimir', [$p->id_periodo, 'detalle' => $d->id_detalle]) }}" target="_blank" rel="noopener"><span class="fas fa-print me-1"></span>Boleta de pago</a>@endif
                    </div>
                </div>
            </div>
        </details>
    @empty
        <div class="card border border-200 shadow-none"><div class="card-body text-700 fs--1">Todavía no se ha calculado este periodo. Usa «Calcular nómina»: incluye a los empleados con contrato vigente en estas fechas.</div></div>
    @endforelse
@endsection
