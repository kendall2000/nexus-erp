@extends('layouts.app', ['titulo' => 'Prestaciones'])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeProcesar = $yo->puede('prestaciones.procesar');
        $puedeAprobar = $yo->puede('prestaciones.aprobar');
        $dinero = fn ($n) => number_format((float) $n, 2);
        $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
        $prefijo = \App\Support\Prestaciones::PREFIJO_LIQUIDACION;
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Prestaciones</h2>
            <p class="text-700 fw-semi-bold mb-0">Aguinaldo y bono 14 de toda la planilla, y liquidaciones por empleado. Se calculan, se aprueban y se pagan.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($yo->puede('prestaciones.exportar'))<a class="btn btn-phoenix-secondary" href="{{ route('prestaciones.exportar', ['anio' => $anio] + $filtros) }}"><span class="fas fa-file-export me-2"></span>Exportar</a>@endif
            @if ($puedeProcesar)
                <button class="btn btn-phoenix-primary" type="button" data-bs-toggle="collapse" data-bs-target="#liquidar">Liquidar empleado</button>
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#calcular"><span class="fas fa-calculator me-2"></span>Calcular aguinaldo o bono 14</button>
            @endif
        </div>
    </div>

    @if ($puedeProcesar)
        <div class="collapse {{ $errors->hasAny(['tipo', 'anio']) ? 'show' : '' }} mb-4" id="calcular">
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('prestaciones.calcular') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label fs--1" for="calc-tipo">Prestación</label>
                        <select class="form-select form-select-sm" id="calc-tipo" name="tipo">@foreach ($anuales as $t)<option value="{{ $t }}" @selected(old('tipo') === $t)>{{ $tipos[$t] }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fs--1" for="calc-anio">Año de pago</label>
                        <input class="form-control form-control-sm @error('anio') is-invalid @enderror" id="calc-anio" name="anio" type="number" min="2000" max="{{ now()->year + 1 }}" value="{{ old('anio', $anio) }}" required />
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit">Calcular</button></div>
                    <div class="col-md-5 fs--1 text-700">Aguinaldo: 1 de diciembre a 30 de noviembre. Bono 14: 1 de julio a 30 de junio. Volver a calcular actualiza lo que aún no se aprobó.</div>
                </form>
            </div></div>
        </div>

        <div class="collapse {{ $errors->liquidacion->any() ? 'show' : '' }} mb-4" id="liquidar">
            <div class="card"><div class="card-body">
                @if ($errors->liquidacion->any())<div class="text-danger fs--1 mb-2">{{ $errors->liquidacion->first() }}</div>@endif
                <form method="POST" action="{{ route('prestaciones.liquidar') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label fs--1" for="liq-empleado">Empleado</label>
                        <select class="form-select form-select-sm" id="liq-empleado" name="id_empleado" required>
                            <option value="">Elegir…</option>
                            @foreach ($empleados as $e)<option value="{{ $e->id_empleado }}" data-baja="{{ $e->fecha_baja?->format('Y-m-d') }}" @selected((int) old('id_empleado') === $e->id_empleado)>{{ $e->nombre_completo }}{{ $e->estado === 'BAJA' ? ' (de baja)' : '' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label fs--1" for="liq-fecha">Fecha de salida</label><input class="form-control form-control-sm" id="liq-fecha" name="fecha_salida" type="date" value="{{ old('fecha_salida', now()->format('Y-m-d')) }}" required /></div>
                    <div class="col-md-2"><label class="form-label fs--1" for="liq-vacaciones">Días de vacaciones pendientes</label><input class="form-control form-control-sm" id="liq-vacaciones" name="dias_vacaciones" type="number" step="0.5" min="0" max="90" value="{{ old('dias_vacaciones', 0) }}" /></div>
                    <div class="col-md-2">
                        <div class="form-check mb-1">
                            <input class="form-check-input" id="liq-indemnizacion" name="indemnizacion" type="checkbox" value="1" @checked(old('indemnizacion')) />
                            <label class="form-check-label fs--1" for="liq-indemnizacion">Paga indemnización</label>
                        </div>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit">Calcular liquidación</button></div>
                    <div class="col-12 fs--1 text-700">Incluye aguinaldo y bono 14 proporcionales a la fecha de salida. La indemnización solo corresponde en despido injustificado.</div>
                </form>
            </div></div>
        </div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto"><input class="form-control form-control-sm" type="number" name="anio" min="2000" max="2100" value="{{ $anio }}" title="Año" style="width: 6rem" /></div>
        <div class="col-auto"><select class="form-select form-select-sm" name="tipo"><option value="">Todas las prestaciones</option>@foreach ($tipos as $k => $n)<option value="{{ $k }}" @selected(($filtros['tipo'] ?? '') === $k)>{{ $n }}</option>@endforeach</select></div>
        <div class="col-auto"><select class="form-select form-select-sm" name="estado"><option value="">Todos los estados</option>@foreach ($estados as $k => [$n])<option value="{{ $k }}" @selected(($filtros['estado'] ?? '') === $k)>{{ $n }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-phoenix-secondary btn-sm" type="submit">Filtrar</button></div>
        <div class="col text-end fs--1 text-700 align-self-center">
            @foreach ($estados as $k => [$n])@if (($totales[$k] ?? 0) > 0)<span class="ms-3">{{ $n }}: <strong>{{ $dinero($totales[$k]) }}</strong></span>@endif @endforeach
        </div>
    </form>

    @error('prestacion')<div class="alert alert-soft-danger">{{ $message }}</div>@enderror
    @error('fecha_pago')<div class="alert alert-soft-danger">{{ $message }}</div>@enderror

    @forelse ($grupos as $grupo)
        @php
            $primera = $grupo->first();
            $liquidacion = str_starts_with($primera->periodo_calculo, $prefijo);
            $lote = $liquidacion ? ['periodo' => $primera->periodo_calculo, 'id_empleado' => $primera->id_empleado] : ['periodo' => $primera->periodo_calculo, 'tipo' => $primera->tipo];
            $titulo = $liquidacion
                ? 'Liquidación de '.$primera->empleado?->nombre_completo.' · salida el '.\Illuminate\Support\Carbon::createFromFormat('Ymd', substr($primera->periodo_calculo, strlen($prefijo)))->format('d/m/Y')
                : ($tipos[$primera->tipo] ?? $primera->tipo).' '.$primera->periodo_calculo;
        @endphp
        <div class="card mb-4">
            <div class="card-body fs--1">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="mb-0">{{ $titulo }} <span class="text-700 fw-normal fs--1">· {{ $liquidacion ? $grupo->count().' conceptos' : $grupo->count().' empleados' }} · {{ $dinero($grupo->sum('monto_calculado')) }}</span></h5>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($puedeAprobar && $grupo->contains('estado', 'CALCULADO'))
                            <form method="POST" action="{{ route('prestaciones.lote.aprobar') }}">
                                @csrf
                                @method('PATCH')
                                @foreach ($lote as $k => $val)<input type="hidden" name="{{ $k }}" value="{{ $val }}" />@endforeach
                                <button class="btn btn-phoenix-success btn-sm" type="submit">Aprobar calculadas</button>
                            </form>
                        @endif
                        @if ($puedeProcesar && $grupo->contains('estado', 'APROBADO'))
                            <form method="POST" action="{{ route('prestaciones.lote.pagar') }}" class="d-flex gap-1">
                                @csrf
                                @method('PATCH')
                                @foreach ($lote as $k => $val)<input type="hidden" name="{{ $k }}" value="{{ $val }}" />@endforeach
                                <input class="form-control form-control-sm" type="date" name="fecha_pago" value="{{ now()->format('Y-m-d') }}" required title="Fecha de pago" />
                                <button class="btn btn-success btn-sm text-nowrap" type="submit">Pagar aprobadas</button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead><tr><th>{{ $liquidacion ? 'Concepto' : 'Empleado' }}</th><th class="text-end">Días</th><th class="text-end">Base</th><th class="text-end">Monto</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($grupo as $p)
                            @php [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary']; @endphp
                            <tr>
                                <td class="fw-semi-bold">
                                    @if ($liquidacion){{ $tipos[$p->tipo] ?? $p->tipo }}@else<a href="{{ route('empleados.show', $p->id_empleado) }}">{{ $p->empleado?->nombre_completo }}</a>@endif
                                </td>
                                <td class="text-end">{{ $cantidad($p->dias_calculados) }}</td>
                                <td class="text-end">{{ $dinero($p->monto_base) }}</td>
                                <td class="text-end fw-semi-bold">{{ $dinero($p->monto_calculado) }}</td>
                                <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>@if ($p->fecha_pago)<span class="text-600 ms-1">{{ $p->fecha_pago->format('d/m/Y') }}</span>@endif</td>
                                <td class="text-end text-nowrap">
                                    @if ($p->estado === 'CALCULADO' && $puedeAprobar)
                                        <form class="d-inline" method="POST" action="{{ route('prestaciones.aprobar', $p->id_prestacion) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-link btn-sm p-0 me-2" type="submit">Aprobar</button>
                                        </form>
                                    @endif
                                    @if ($p->estado === 'CALCULADO' && $puedeProcesar)
                                        <form class="d-inline" method="POST" action="{{ route('prestaciones.destroy', $p->id_prestacion) }}" onsubmit="return confirm(@js('¿Eliminar este cálculo?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-link btn-sm text-danger p-0" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                                        </form>
                                    @endif
                                    @if ($p->estado === 'APROBADO' && $puedeProcesar)
                                        <form class="d-inline" method="POST" action="{{ route('prestaciones.pagar', $p->id_prestacion) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="fecha_pago" value="{{ now()->format('Y-m-d') }}" />
                                            <button class="btn btn-link btn-sm p-0" type="submit" title="Pagar con fecha de hoy">Pagar hoy</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-700 fs--1">No hay prestaciones de {{ $anio }} con esos filtros.{{ $puedeProcesar ? ' Usa «Calcular aguinaldo o bono 14» o «Liquidar empleado».' : '' }}</div></div>
    @endforelse
@endsection

@push('scripts')
    <script>
        // Al elegir a alguien de baja, propone su fecha de baja como fecha de salida.
        (function () {
            var empleado = document.getElementById('liq-empleado');
            if (!empleado) return;
            empleado.addEventListener('change', function () {
                var baja = this.options[this.selectedIndex].dataset.baja;
                if (baja) document.getElementById('liq-fecha').value = baja;
            });
        })();
    </script>
@endpush
