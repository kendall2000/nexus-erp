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

    <form method="POST" action="{{ route('tickets.store') }}" class="mb-9">
        @csrf
        <div class="row g-4">
            {{-- Columna Principal: Detalle del Incidente y Descripción (8 cols) --}}
            <div class="col-12 col-xl-8">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-body-emphasis border-bottom border-200 py-3 px-4">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-ticket-alt text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Detalle del incidente</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="id_cliente">Cliente <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_cliente') is-invalid @enderror" id="id_cliente" name="id_cliente" required data-choices='{"searchEnabled":true,"itemSelectText":""}'>
                                    <option value="">Selecciona…</option>
                                    @foreach ($clientes as $c)
                                        <option value="{{ $c->id_cliente }}" @selected((int) old('id_cliente', $elegido['cliente'] ?? 0) === $c->id_cliente)>{{ $c->razon_social }}</option>
                                    @endforeach
                                </select>
                                @error('id_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="id_contrato">Contrato de servicio</label>
                                <select class="form-select @error('id_contrato') is-invalid @enderror" id="id_contrato" name="id_contrato" data-valor="{{ old('id_contrato', $elegido['contrato'] ?? '') }}">
                                    <option value="">Sin contrato</option>
                                </select>
                                @error('id_contrato')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="asunto">Asunto / Título del incidente <span class="text-danger">*</span></label>
                                <input class="form-control @error('asunto') is-invalid @enderror" id="asunto" name="asunto" value="{{ old('asunto') }}" required maxlength="300" placeholder="Ej.: Interrupción de servicio en área de servidores" />
                                @error('asunto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="descripcion">Descripción detallada <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="6" required maxlength="10000" placeholder="Describe claramente los hechos ocurridos, personas afectadas y observaciones clave...">{{ old('descripcion') }}</textarea>
                                @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Lateral: Clasificación, SLA y Acciones (4 cols) --}}
            <div class="col-12 col-xl-4">
                {{-- Clasificación y SLA --}}
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-body-emphasis border-bottom border-200 py-3 px-4">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-tags text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Clasificación y SLA</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="id_categoria">Categoría</label>
                            <select class="form-select" id="id_categoria" name="id_categoria">
                                <option value="">— Sin categoría —</option>
                                @foreach ($categorias as $c)
                                    <option value="{{ $c->id_categoria }}" data-prioridad="{{ $c->prioridad_default }}" @selected((int) old('id_categoria') === $c->id_categoria)>{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="tipo">Tipo de caso</label>
                            <select class="form-select" id="tipo" name="tipo">
                                @foreach ($tipos as $k => $n)
                                    <option value="{{ $k }}" @selected(old('tipo', 'INCIDENTE') === $k)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="prioridad">Prioridad</label>
                            <select class="form-select" id="prioridad" name="prioridad">
                                @foreach ($prioridades as $k => [$n])
                                    <option value="{{ $k }}" @selected(old('prioridad', 'MEDIA') === $k)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="canal_origen">Canal de origen</label>
                            <select class="form-select" id="canal_origen" name="canal_origen">
                                @foreach ($canales as $k => $n)
                                    <option value="{{ $k }}" @selected(old('canal_origen', 'TELEFONO') === $k)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="plan_sla">Acuerdo de Nivel de Servicio (SLA)</label>
                            <select class="form-select" id="plan_sla" name="plan_sla">
                                <option value="">Sin SLA específico</option>
                                @foreach ($planes as $p)
                                    <option value="{{ $p }}" @selected(old('plan_sla', $planes->first()) === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($yo->puede('tickets.asignar'))
                            <div class="border-top border-200 pt-3">
                                <label class="form-label" for="id_asignado_a">Asignar agente</label>
                                <select class="form-select @error('id_asignado_a') is-invalid @enderror" id="id_asignado_a" name="id_asignado_a" data-choices='{"searchEnabled":true,"itemSelectText":""}'>
                                    <option value="">Sin asignar</option>
                                    @foreach ($agentes as $a)
                                        <option value="{{ $a->id_empleado }}" @selected((int) old('id_asignado_a') === $a->id_empleado)>{{ $a->nombre_completo }}</option>
                                    @endforeach
                                </select>
                                @error('id_asignado_a')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="card border border-200 shadow-none">
                    <div class="card-header bg-body-emphasis border-bottom border-200 py-3 px-4">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-sliders-h text-primary me-2 fs-0"></span>
                            <h5 class="mb-0 text-1000">Acciones</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" type="submit">
                                <span class="fas fa-paper-plane me-2"></span>Abrir ticket
                            </button>
                            <a class="btn btn-phoenix-secondary" href="{{ route('tickets.index') }}">
                                <span class="fas fa-times me-2"></span>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
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
