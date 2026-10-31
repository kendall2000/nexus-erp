@extends('layouts.app', ['titulo' => 'Nuevo ticket'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <x-page-header
        titulo="Nuevo ticket"
        icono="fas fa-headset"
        subtitulo="Abre un caso de soporte o incidente operativo asociado a un cliente y contrato."
        :migas="[
            ['label' => 'Tickets', 'url' => route('tickets.index')],
            ['label' => 'Nuevo'],
        ]"
    />

    <form method="POST" action="{{ route('tickets.store') }}" class="mb-9" style="max-width: 56rem">
        @csrf
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header bg-100 border-bottom border-200 py-3 px-4">
                <h5 class="mb-0 text-1100"><span class="fas fa-ticket-alt text-primary me-2"></span>Detalle del incidente</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="id_cliente">Cliente</label>
                        <select class="form-select @error('id_cliente') is-invalid @enderror" id="id_cliente" name="id_cliente" required>
                            <option value="">Selecciona…</option>
                            @foreach ($clientes as $c)<option value="{{ $c->id_cliente }}" @selected((int) old('id_cliente', $elegido['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>@endforeach
                        </select>
                        @error('id_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="id_contrato">Contrato</label>
                        <select class="form-select @error('id_contrato') is-invalid @enderror" id="id_contrato" name="id_contrato" data-valor="{{ old('id_contrato', $elegido['contrato'] ?? '') }}"><option value="">Sin contrato</option></select>
                        @error('id_contrato')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="asunto">Asunto</label>
                        <input class="form-control @error('asunto') is-invalid @enderror" id="asunto" name="asunto" value="{{ old('asunto') }}" required maxlength="300" />
                        @error('asunto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="5" required maxlength="10000">{{ old('descripcion') }}</textarea>
                        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_categoria">Categoría</label>
                        <select class="form-select" id="id_categoria" name="id_categoria">
                            <option value="">—</option>
                            @foreach ($categorias as $c)<option value="{{ $c->id_categoria }}" data-prioridad="{{ $c->prioridad_default }}" @selected((int) old('id_categoria') === $c->id_categoria)>{{ $c->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo">@foreach ($tipos as $k => $n)<option value="{{ $k }}" @selected(old('tipo', 'INCIDENTE') === $k)>{{ $n }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="canal_origen">Llegó por</label>
                        <select class="form-select" id="canal_origen" name="canal_origen">@foreach ($canales as $k => $n)<option value="{{ $k }}" @selected(old('canal_origen', 'TELEFONO') === $k)>{{ $n }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="prioridad">Prioridad</label>
                        <select class="form-select" id="prioridad" name="prioridad">@foreach ($prioridades as $k => [$n])<option value="{{ $k }}" @selected(old('prioridad', 'MEDIA') === $k)>{{ $n }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="plan_sla">SLA</label>
                        <select class="form-select" id="plan_sla" name="plan_sla">
                            <option value="">Sin SLA</option>
                            @foreach ($planes as $p)<option value="{{ $p }}" @selected(old('plan_sla', $planes->first()) === $p)>{{ $p }}</option>@endforeach
                        </select>
                    </div>
                    @if ($yo->puede('tickets.asignar'))
                        <div class="col-md-4">
                            <label class="form-label" for="id_asignado_a">Asignar a</label>
                            <select class="form-select @error('id_asignado_a') is-invalid @enderror" id="id_asignado_a" name="id_asignado_a">
                                <option value="">Sin asignar</option>
                                @foreach ($agentes as $a)<option value="{{ $a->id_empleado }}" @selected((int) old('id_asignado_a') === $a->id_empleado)>{{ $a->nombre_completo }}</option>@endforeach
                            </select>
                            @error('id_asignado_a')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit"><span class="fas fa-paper-plane me-2"></span>Abrir ticket</button>
            <a class="btn btn-phoenix-secondary" href="{{ route('tickets.index') }}">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var contratos = @json($contratos);
            var cliente = document.getElementById('id_cliente'), contrato = document.getElementById('id_contrato');
            function llenar() {
                var valor = contrato.value || contrato.dataset.valor;
                contrato.innerHTML = '<option value="">Sin contrato</option>';
                contratos.filter(function (c) { return String(c.id_cliente) === cliente.value; }).forEach(function (c) {
                    var o = new Option(c.numero_contrato + (c.nombre_proyecto ? ' — ' + c.nombre_proyecto : ''), c.id_contrato);
                    if (String(c.id_contrato) === String(valor)) o.selected = true;
                    contrato.appendChild(o);
                });
            }
            cliente.addEventListener('change', function () { contrato.dataset.valor = ''; llenar(); });
            // La categoría sugiere su prioridad.
            document.getElementById('id_categoria').addEventListener('change', function () {
                var o = this.selectedOptions[0];
                if (o && o.dataset.prioridad) document.getElementById('prioridad').value = o.dataset.prioridad;
            });
            llenar();
        })();
    </script>
@endpush
