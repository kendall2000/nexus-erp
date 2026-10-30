@extends('layouts.app', ['titulo' => $p->exists ? 'Editar presupuesto' : 'Nueva partida de presupuesto'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $p->{$campo}); @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('presupuesto.index', ['anio' => $p->anio]) }}">Presupuesto {{ $p->anio }}</a></li>
            <li class="breadcrumb-item active">{{ $p->exists ? 'Editar' : 'Nueva partida' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $p->exists ? 'Editar presupuesto' : 'Nueva partida de presupuesto' }}</h2>

    <form method="POST" action="{{ $p->exists ? route('presupuesto.update', $p->id_presupuesto) : route('presupuesto.store') }}" class="mb-9" style="max-width: 60rem">
        @csrf
        @if ($p->exists)
            @method('PUT')
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="id_centro">Centro de costo</label>
                        <select class="form-select @error('id_centro') is-invalid @enderror" id="id_centro" name="id_centro" required>
                            <option value="">Selecciona…</option>
                            @foreach ($centros as $c)<option value="{{ $c->id_centro }}" @selected((int) $v('id_centro') === $c->id_centro)>{{ $c->etiqueta }}</option>@endforeach
                        </select>
                        @error('id_centro')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="id_cuenta">Cuenta</label>
                        <select class="form-select @error('id_cuenta') is-invalid @enderror" id="id_cuenta" name="id_cuenta" required>
                            <option value="">Selecciona…</option>
                            @foreach ($cuentas as $c)<option value="{{ $c->id_cuenta }}" @selected((int) $v('id_cuenta') === $c->id_cuenta)>{{ $c->etiqueta }} ({{ strtolower($c->tipo) }})</option>@endforeach
                        </select>
                        @error('id_cuenta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Cuentas de movimiento de ingreso, gasto o costo.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="anio">Año</label>
                        <input class="form-control @error('anio') is-invalid @enderror" id="anio" name="anio" type="number" min="2000" max="2100" value="{{ $v('anio') }}" required />
                        @error('anio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="moneda">Moneda</label>
                        <select class="form-select" id="moneda" name="moneda" required>
                            @foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <h5 class="mb-0">Montos por mes</h5>
                    <div class="d-flex gap-2 align-items-end">
                        <div>
                            <label class="form-label fs--1 mb-1" for="total-anual">Repartir un total anual</label>
                            <input class="form-control form-control-sm" id="total-anual" type="number" step="0.01" min="0" placeholder="Ej.: 120000" />
                        </div>
                        <button class="btn btn-phoenix-secondary btn-sm" type="button" id="repartir">Repartir en 12 meses</button>
                    </div>
                </div>
                <div class="row g-3">
                    @foreach (\App\Models\Finanzas\PresupuestoAnual::MESES as $mes)
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label fs--1" for="pre_{{ $mes }}">{{ ucfirst($mes) }}</label>
                            <input class="form-control form-control-sm text-end mes @error('pre_'.$mes) is-invalid @enderror" id="pre_{{ $mes }}" name="pre_{{ $mes }}" type="number" step="0.01" min="0" value="{{ old('pre_'.$mes, $p->exists ? round((float) $p->{'pre_'.$mes}, 2) : '') }}" />
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 mb-0 fw-semi-bold">Total anual: <span id="total">0.00</span></p>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar en borrador</button>
            <a class="btn btn-phoenix-secondary" href="{{ $p->exists ? route('presupuesto.show', $p->id_presupuesto) : route('presupuesto.index', ['anio' => $p->anio]) }}">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var campos = Array.prototype.slice.call(document.querySelectorAll('input.mes'));
            var total = document.getElementById('total');
            function sumar() {
                var suma = campos.reduce(function (s, c) { return s + (parseFloat(c.value) || 0); }, 0);
                total.textContent = suma.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            // Reparte en centavos: los 11 primeros meses iguales y diciembre absorbe el redondeo.
            document.getElementById('repartir').addEventListener('click', function () {
                var centavos = Math.round((parseFloat(document.getElementById('total-anual').value) || 0) * 100);
                var cuota = Math.floor(centavos / 12);
                campos.forEach(function (c, i) { c.value = ((i === 11 ? centavos - cuota * 11 : cuota) / 100).toFixed(2); });
                sumar();
            });
            campos.forEach(function (c) { c.addEventListener('input', sumar); });
            sumar();
        })();
    </script>
@endpush
