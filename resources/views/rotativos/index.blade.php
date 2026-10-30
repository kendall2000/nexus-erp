@extends('layouts.app', ['titulo' => 'Personal rotativo'])

@section('contenido')
    @php
        $puedeEditar = auth()->user()->puede('asistencia.editar');
        $dinero = fn ($n) => number_format((float) $n, 2);
        $meses = \App\Models\Finanzas\PresupuestoAnual::MESES;
        $invalido = fn (string $campo) => $errors->has($campo) ? 'is-invalid' : '';
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Personal rotativo</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Personal rotativo</h2>
            <p class="text-700 fw-semi-bold mb-0">Quién cubre a quién cuando alguien falta (vacaciones, suspensiones, incapacidades…). Se paga por día cubierto en la nómina.</p>
        </div>
        <form method="GET" class="d-flex gap-2">
            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('rotativos.index', ['mes' => $mes->copy()->subMonth()->format('Y-m')]) }}" title="Mes anterior"><span class="fas fa-chevron-left"></span></a>
            <input class="form-control form-control-sm" type="month" name="mes" value="{{ $mes->format('Y-m') }}" onchange="this.form.submit()" />
            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('rotativos.index', ['mes' => $mes->copy()->addMonth()->format('Y-m')]) }}" title="Mes siguiente"><span class="fas fa-chevron-right"></span></a>
        </form>
    </div>

    @if ($puedeEditar)
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Nueva cobertura</h5>
                @if ($rotativos->isEmpty())
                    <p class="text-700 fs--1 mb-0">Todavía no hay personal rotativo. Marca «Personal rotativo» y su tarifa por día en la ficha del <a href="{{ route('empleados.index') }}">empleado</a>.</p>
                @else
                    <form method="POST" action="{{ route('rotativos.store') }}" class="row g-3" id="nueva-cobertura">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="id_rotativo">Rotativo</label>
                            <select class="form-select {{ $invalido('id_rotativo') }}" id="id_rotativo" name="id_rotativo" required>
                                <option value="">Elegir…</option>
                                @foreach ($rotativos as $r)<option value="{{ $r->id_empleado }}" @selected((int) old('id_rotativo') === $r->id_empleado)>{{ $r->nombre_completo }}{{ $r->tarifa_dia ? ' · '.$dinero($r->tarifa_dia).' / día' : '' }}</option>@endforeach
                            </select>
                            @error('id_rotativo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="motivo">Motivo</label>
                            <select class="form-select" id="motivo" name="motivo">
                                @foreach ($motivos as $k => $n)<option value="{{ $k }}" @selected(old('motivo') === $k)>{{ $n }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="id_titular">Cubre a</label>
                            <select class="form-select {{ $invalido('id_titular') }}" id="id_titular" name="id_titular">
                                <option value="">— (puesto vacante)</option>
                                @foreach ($titulares as $t)<option value="{{ $t->id_empleado }}" @selected((int) old('id_titular') === $t->id_empleado)>{{ $t->nombre_completo }}{{ $t->cargo ? ' · '.$t->cargo->nombre : '' }}</option>@endforeach
                            </select>
                            @error('id_titular')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="fecha_inicio">Desde</label>
                            <input class="form-control {{ $invalido('fecha_inicio') }}" id="fecha_inicio" name="fecha_inicio" type="date" value="{{ old('fecha_inicio') }}" required />
                            @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="fecha_fin">Hasta</label>
                            <input class="form-control {{ $invalido('fecha_fin') }}" id="fecha_fin" name="fecha_fin" type="date" value="{{ old('fecha_fin') }}" required />
                            @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="tarifa_dia">Tarifa por día</label>
                            <input class="form-control {{ $invalido('tarifa_dia') }}" id="tarifa_dia" name="tarifa_dia" type="number" step="0.01" min="0.01" value="{{ old('tarifa_dia') }}" placeholder="La de su ficha" />
                            @error('tarifa_dia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-block" for="paga_fines_semana">Días que se pagan</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" id="paga_fines_semana" name="paga_fines_semana" type="checkbox" value="1" @checked(old('paga_fines_semana', '1')) />
                                <label class="form-check-label" for="paga_fines_semana">Paga fines de semana</label>
                            </div>
                        </div>
                        <div class="col-md-8"><input class="form-control" name="observaciones" value="{{ old('observaciones') }}" maxlength="300" placeholder="Observaciones (opcional)" /></div>
                        <div class="col-md-4 d-flex align-items-center justify-content-end gap-3">
                            <span class="fs--1 text-700" id="calculo"></span>
                            <button class="btn btn-primary" type="submit">Registrar cobertura</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if ($resumen->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body fs--1">
                <h5 class="mb-3">A pagar por {{ $meses[$mes->month] }} {{ $mes->year }}</h5>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead><tr><th>Rotativo</th><th class="text-end">Días cubiertos</th><th class="text-end">Monto</th></tr></thead>
                        <tbody>
                        @foreach ($resumen as $r)
                            <tr><td class="fw-semi-bold">{{ $r['rotativo']?->nombre_completo }}</td><td class="text-end">{{ $r['dias'] }}</td><td class="text-end">{{ $dinero($r['monto']) }}</td></tr>
                        @endforeach
                        </tbody>
                        <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end">{{ $resumen->sum('dias') }}</td><td class="text-end">{{ $dinero($resumen->sum('monto')) }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body fs--1">
            <h5 class="mb-3">Coberturas de {{ $meses[$mes->month] }} {{ $mes->year }}</h5>
            @error('cobertura')<div class="alert alert-soft-danger">{{ $message }}</div>@enderror
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead><tr><th>Rotativo</th><th>Cubre a</th><th>Motivo</th><th>Fechas</th><th class="text-end">Días</th><th class="text-end">Tarifa</th><th class="text-end">Total</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($coberturas as $c)
                        @php [$nombreEstado, $color] = $estados[$c->estado] ?? [$c->estado, 'secondary']; @endphp
                        <tr class="{{ $c->estado === 'ANULADA' ? 'text-500' : '' }}">
                            <td class="fw-semi-bold">{{ $c->rotativo?->nombre_completo }}</td>
                            <td>{{ $c->titular?->nombre_completo ?? 'Puesto vacante' }}@if ($c->titular?->cargo)<span class="d-block fs--2 text-600">{{ $c->titular->cargo->nombre }}</span>@endif</td>
                            <td>{{ $motivos[$c->motivo] ?? $c->motivo }}@if ($c->observaciones)<span class="d-block fs--2 text-600">{{ $c->observaciones }}</span>@endif</td>
                            <td class="text-nowrap">{{ $c->fecha_inicio->format('d/m/Y') }} – {{ $c->fecha_fin->format('d/m/Y') }}@unless ($c->paga_fines_semana)<span class="d-block fs--2 text-600">Sin fines de semana</span>@endunless</td>
                            <td class="text-end">{{ (float) $c->dias }}</td>
                            <td class="text-end">{{ $dinero($c->tarifa_dia) }}</td>
                            <td class="text-end fw-semi-bold">{{ $dinero($c->total) }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span></td>
                            <td class="text-end">
                                @if ($puedeEditar && $c->estado === 'VIGENTE')
                                    <form method="POST" action="{{ route('rotativos.anular', $c->id_cobertura) }}" onsubmit="return confirm(@js('¿Anular esta cobertura? Dejará de pagarse en la nómina.'))">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-link btn-sm text-danger p-0" type="submit">Anular</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-700 py-3">No hay coberturas en este mes.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Propone la tarifa de la ficha del rotativo y muestra días × tarifa antes de guardar.
        (function () {
            var form = document.getElementById('nueva-cobertura');
            if (!form) return;
            var tarifas = @json($rotativos->mapWithKeys(fn ($r) => [$r->id_empleado => $r->tarifa_dia !== null ? (float) $r->tarifa_dia : null]));
            var campo = function (n) { return form.elements[n]; };
            function dias() {
                var a = campo('fecha_inicio').value, b = campo('fecha_fin').value;
                if (!a || !b || b < a) return 0;
                var n = 0, fin = new Date(b + 'T00:00:00');
                for (var d = new Date(a + 'T00:00:00'); d <= fin && n < 1000; d.setDate(d.getDate() + 1)) {
                    if (campo('paga_fines_semana').checked || (d.getDay() !== 0 && d.getDay() !== 6)) n++;
                }
                return n;
            }
            function calcular() {
                var tarifa = parseFloat(campo('tarifa_dia').value) || tarifas[campo('id_rotativo').value] || 0, n = dias();
                document.getElementById('calculo').textContent = n && tarifa
                    ? n + ' días × ' + tarifa.toFixed(2) + ' = ' + (n * tarifa).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
            }
            campo('id_rotativo').addEventListener('change', function () {
                var t = tarifas[this.value];
                campo('tarifa_dia').value = t ? t.toFixed(2) : '';
                calcular();
            });
            ['fecha_inicio', 'fecha_fin', 'tarifa_dia', 'paga_fines_semana'].forEach(function (n) { campo(n).addEventListener('input', calcular); });
            calcular();
        })();
    </script>
@endpush
