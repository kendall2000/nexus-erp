@extends('layouts.app', ['titulo' => 'Registrar cobro'])

@section('contenido')
    @php $elegida = old('id_factura', $factura?->id_factura); @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('pagos.index') }}">Pagos</a></li>
            <li class="breadcrumb-item active">Registrar cobro</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">Registrar cobro</h2>

    @if ($facturas->isEmpty())
        <div class="card" style="max-width: 48rem"><div class="card-body">
            <p class="mb-0 text-700"><span class="fas fa-info-circle me-2"></span>No hay facturas emitidas con saldo por cobrar.</p>
        </div></div>
    @else
        <form method="POST" action="{{ route('pagos.store') }}" class="mb-9" style="max-width: 48rem">
            @csrf
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="id_factura">Factura</label>
                            <select class="form-select @error('id_factura') is-invalid @enderror" id="id_factura" name="id_factura" required>
                                <option value="">Selecciona…</option>
                                @foreach ($facturas as $f)
                                    <option value="{{ $f->id_factura }}" data-saldo="{{ round((float) $f->saldo_pendiente, 2) }}" data-moneda="{{ $f->moneda }}" @selected((int) $elegida === $f->id_factura)>
                                        {{ $f->numero_completo }} — {{ $f->cliente?->razon_social }} · saldo {{ $f->moneda }} {{ number_format((float) $f->saldo_pendiente, 2) }}{{ $f->fecha_vencimiento?->lt(today()) ? ' · vencida' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_factura')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="monto">Monto (<span id="moneda">—</span>)</label>
                            <input class="form-control @error('monto') is-invalid @enderror" id="monto" name="monto" type="number" step="0.01" min="0.01" value="{{ old('monto') }}" required />
                            @error('monto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" id="ayuda-saldo"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="forma_pago">Forma de pago</label>
                            <select class="form-select" id="forma_pago" name="forma_pago" required>
                                @foreach ($formas as $codigo => $nombre)<option value="{{ $codigo }}" @selected(old('forma_pago', 'TRANSFERENCIA') === $codigo)>{{ $nombre }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="fecha_pago">Fecha del pago</label>
                            <input class="form-control @error('fecha_pago') is-invalid @enderror" id="fecha_pago" name="fecha_pago" type="date" max="{{ now()->format('Y-m-d') }}" value="{{ old('fecha_pago', now()->format('Y-m-d')) }}" required />
                            @error('fecha_pago')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="referencia">Referencia</label>
                            <input class="form-control @error('referencia') is-invalid @enderror" id="referencia" name="referencia" value="{{ old('referencia') }}" maxlength="100" placeholder="N.º de boleta, transferencia o cheque" />
                            @error('referencia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4"><label class="form-label" for="banco_origen">Banco</label><input class="form-control" id="banco_origen" name="banco_origen" value="{{ old('banco_origen') }}" maxlength="100" /></div>
                        <div class="col-md-4">
                            <label class="form-label" for="fecha_acreditado">Acreditado el</label>
                            <input class="form-control @error('fecha_acreditado') is-invalid @enderror" id="fecha_acreditado" name="fecha_acreditado" type="date" value="{{ old('fecha_acreditado') }}" />
                            @error('fecha_acreditado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Si ya se vio en el banco.</div>
                        </div>
                        <div class="col-12"><label class="form-label" for="notas">Notas</label><input class="form-control" id="notas" name="notas" value="{{ old('notas') }}" maxlength="300" /></div>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit"><span class="fas fa-check me-2"></span>Registrar cobro</button>
                <a class="btn btn-phoenix-secondary" href="{{ $factura ? route('facturas.show', $factura->id_factura) : route('pagos.index') }}">Cancelar</a>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var factura = document.getElementById('id_factura');
            if (!factura) return;
            var monto = document.getElementById('monto');
            function actualizar(proponer) {
                var o = factura.selectedOptions[0];
                var saldo = o && o.dataset.saldo ? parseFloat(o.dataset.saldo) : null;
                document.getElementById('moneda').textContent = (o && o.dataset.moneda) || '—';
                document.getElementById('ayuda-saldo').textContent = saldo !== null ? 'Saldo: ' + saldo.toLocaleString('es-GT', {minimumFractionDigits: 2}) : '';
                if (saldo !== null) { monto.max = saldo; if (proponer || !monto.value) monto.value = saldo.toFixed(2); }
            }
            // La referencia es obligatoria salvo en efectivo.
            var forma = document.getElementById('forma_pago');
            function referencia() { document.getElementById('referencia').required = ['EFECTIVO', 'OTRO'].indexOf(forma.value) === -1; }
            factura.addEventListener('change', function () { actualizar(true); });
            forma.addEventListener('change', referencia);
            actualizar(false);
            referencia();
        })();
    </script>
@endpush
