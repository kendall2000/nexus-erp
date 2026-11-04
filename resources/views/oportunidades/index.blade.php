@extends('layouts.app', ['titulo' => 'Oportunidades'])

@section('contenido')
    @php
        $yo = auth()->user();
        $dinero = fn ($n) => number_format((float) $n, 2);
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">CRM & Ventas</li>
            <li class="breadcrumb-item active">Embudo de Negocios (Deals)</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Encabezado idéntico a apps/crm/deals.html --}}
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-12 col-md-auto">
                <h2 class="mb-1 text-1100">Embudo de Oportunidades & Ventas</h2>
                <h5 class="text-700 fw-semi-bold mb-0">Gestión visual del pipeline comercial por etapas</h5>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                @if ($yo->puede('oportunidades.crear'))
                    <a class="btn btn-primary" href="{{ route('oportunidades.create') }}">
                        <span class="fas fa-plus me-2"></span>Nueva oportunidad
                    </a>
                @endif
            </div>
        </div>

        {{-- Métricas de Resumen estilo Phoenix --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Oportunidades Abiertas</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ number_format($resumen['abiertas']) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-briefcase text-primary fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Valor Total en Juego</h6>
                                <h3 class="text-info mb-0 fw-bolder">Q {{ $dinero($resumen['valor']) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-coins text-info fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Valor Ponderado (Forecast)</h6>
                                <h3 class="text-warning mb-0 fw-bolder">Q {{ $dinero($resumen['ponderado']) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-warning-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-percentage text-warning fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Ganado este Mes</h6>
                                <h3 class="text-success mb-0 fw-bolder">Q {{ $dinero($resumen['ganadasMes']) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-success-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-trophy text-success fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros estilo apps/crm/deals.html --}}
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-body py-3 px-4">
                <form method="GET" action="{{ route('oportunidades.index') }}" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <span class="text-800 fw-semi-bold fs--1"><span class="fas fa-filter text-primary me-2"></span>Filtrar por Vendedor:</span>
                    </div>
                    <div class="col-auto">
                        <select class="form-select btn-phoenix-secondary px-4" name="responsable" onchange="this.form.submit()">
                            <option value="">Todos los ejecutivos de cuenta</option>
                            @foreach ($vendedores as $v)
                                <option value="{{ $v->id_empleado }}" @selected((int) ($filtros['responsable'] ?? 0) === $v->id_empleado)>{{ $v->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (array_filter($filtros))
                        <div class="col-auto">
                            <a class="btn btn-phoenix-danger px-3" href="{{ route('oportunidades.index') }}">
                                <span class="fas fa-times me-1"></span>Quitar filtro
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        {{-- Tablero Kanban Deals idéntico a Phoenix deals.html --}}
        <div class="kanban-deals-wrapper scrollbar mx-n4 px-4 mx-lg-n6 px-lg-6">
            <div class="deals d-flex gap-4 pb-4">
                @foreach ($etapas as $etapa)
                    @php
                        $lista = $porEtapa->get($etapa->id_etapa, collect());
                        $sumaValor = $lista->sum('valor_estimado');
                    @endphp
                    <div class="deals-col flex-shrink-0" style="width: 22rem;" data-etapa-id="{{ $etapa->id_etapa }}" data-etapa-probabilidad="{{ (int) $etapa->probabilidad_cierre }}" data-etapa-nombre="{{ $etapa->nombre }}" data-es-perdida="{{ $etapa->es_perdida ? '1' : '0' }}" data-es-ganada="{{ $etapa->es_ganada ? '1' : '0' }}">
                        {{-- Cabecera de Columna de Etapa --}}
                        <div class="p-3 bg-white rounded-top-3 border-top border-start border-end border-200 position-sticky top-0 z-index-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 10px; height: 10px; background: {{ $etapa->color_hex ?? '#3874ff' }}"></span>
                                    <h5 class="mb-0 text-1100 fw-bold">{{ $etapa->nombre }}</h5>
                                    <span class="badge badge-phoenix badge-phoenix-secondary ms-2 fs--2 count-badge">{{ $lista->count() }}</span>
                                </div>
                                <span class="badge badge-phoenix badge-phoenix-primary fs--2">{{ (int) $etapa->probabilidad_cierre }}%</span>
                            </div>
                            <p class="fs--2 text-600 mb-1">Monto en etapa:</p>
                            <h4 class="mb-0 text-primary fw-bolder total-columna">Q {{ $dinero($sumaValor) }}</h4>
                            <div class="mt-2 rounded-pill" style="height: 3px; background: {{ $etapa->color_hex ?? '#3874ff' }}"></div>
                        </div>

                        {{-- Contenedor de Tarjetas Sortables --}}
                        <div class="p-3 bg-100 rounded-bottom-3 border-bottom border-start border-end border-200 kanban-items-container" data-sortable-container style="min-height: 480px;">
                            @forelse ($lista as $o)
                                <div class="card mb-3 border border-200 shadow-sm position-relative oportunidad-card"
                                     data-id="{{ $o->id_oportunidad }}"
                                     data-valor="{{ (float) $o->valor_estimado }}"
                                     data-nombre="{{ $o->nombre }}"
                                     data-cliente-id="{{ $o->id_cliente }}"
                                     data-cliente-nombre="{{ $o->cliente?->razon_social }}"
                                     data-prospecto-id="{{ $o->id_prospecto }}"
                                     data-responsable-id="{{ $o->id_responsable }}"
                                     data-linea-id="{{ $o->id_linea }}"
                                     data-moneda="{{ $o->moneda }}"
                                     data-probabilidad="{{ $o->probabilidad }}"
                                     data-fecha-cierre="{{ $o->fecha_cierre_estimada?->format('Y-m-d') }}"
                                     data-descripcion="{{ $o->descripcion }}"
                                     @if ($yo->puede('oportunidades.editar')) style="cursor: grab;" @endif>
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center text-700 fs--2">
                                                <span class="far fa-clock me-1 text-500"></span>
                                                <span class="{{ $o->fecha_cierre_estimada?->isPast() && !$etapa->es_ganada && !$etapa->es_perdida ? 'text-danger fw-bold' : '' }}">
                                                    {{ $o->fecha_cierre_real?->format('d/m/Y') ?? $o->fecha_cierre_estimada?->format('d/m/Y') ?? 'Sin fecha' }}
                                                </span>
                                            </div>
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="badge badge-phoenix badge-phoenix-info fs--2 probabilidad-badge">{{ $o->probabilidad }}%</span>
                                                @if ($yo->puede('oportunidades.editar'))
                                                    <button type="button" class="btn btn-sm btn-link text-primary p-0 ms-1 btn-editar-rapido" title="Editar oportunidad" onclick="event.stopPropagation(); window.abrirEdicionOportunidad({{ $o->id_oportunidad }});">
                                                        <span class="fas fa-pen fs--2"></span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <h6 class="mb-1">
                                            <a class="text-1100 text-hover-primary fw-bold fs-0" href="{{ route('oportunidades.show', $o->id_oportunidad) }}">
                                                {{ $o->nombre }}
                                            </a>
                                        </h6>

                                        <div class="d-flex align-items-center text-600 fs--2 mb-3">
                                            <span class="far fa-building me-1 text-primary"></span>
                                            <span class="text-truncate" style="max-width: 15rem;">{{ $o->cliente?->razon_social ?? $o->prospecto?->nombre_empresa ?? 'Sin cliente' }}</span>
                                        </div>

                                        {{-- Barra de Probabilidad estilo Phoenix --}}
                                        <div class="progress mb-3" style="height: 5px;">
                                            <div class="progress-bar rounded-pill bg-{{ $o->probabilidad >= 70 ? 'success' : ($o->probabilidad >= 40 ? 'primary' : 'warning') }}" style="width: {{ $o->probabilidad }}%"></div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-200">
                                            <div>
                                                <span class="fs--2 text-600 d-block">Valor</span>
                                                <span class="fw-bold text-1100 fs--1">{{ $o->moneda }} {{ $dinero($o->valor_estimado) }}</span>
                                            </div>
                                            @if ($o->responsable)
                                                <div class="d-flex align-items-center" title="Ejecutivo: {{ $o->responsable->nombre_completo }}">
                                                    <div class="avatar avatar-s">
                                                        <div class="avatar-name rounded-circle bg-soft-primary text-primary fs--2">
                                                            <span>{{ mb_strtoupper(mb_substr($o->responsable->nombres ?: $o->responsable->nombre_completo, 0, 1)) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="card bg-white border-dashed border-300 empty-placeholder">
                                    <div class="card-body p-4 text-center">
                                        <span class="fas fa-handshake text-300 fs-1 d-block mb-2"></span>
                                        <p class="fs--2 text-500 mb-0">Sin oportunidades en esta etapa.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Modal para Razón de Pérdida si se arrastra a una etapa perdida --}}
    <div class="modal fade" id="modalRazonPerdida" tabindex="-1" aria-labelledby="modalRazonPerdidaLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border border-200 shadow-lg">
                <div class="modal-header border-bottom border-200">
                    <h5 class="modal-title text-1000" id="modalRazonPerdidaLabel">
                        <span class="fas fa-times-circle text-danger me-2"></span>Motivo de Pérdida
                    </h5>
                    <button type="button" class="btn-close" id="btnCancelarPerdida" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-700 fs--1 mb-3">Para mover la oportunidad a una etapa de pérdida, por favor especifica el motivo o competidor ganador:</p>
                    <div class="mb-3">
                        <label class="form-label" for="inputRazonCierre">Razón o motivo del descarte <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="inputRazonCierre" rows="3" placeholder="Ej.: El cliente optó por otra alternativa de menor costo..." maxlength="300" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-200">
                    <button type="button" class="btn btn-phoenix-secondary" id="btnCerrarModalPerdida">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarPerdida">Confirmar descarte</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulario oculto que envía el cambio de etapa al soltar una tarjeta --}}
    @if ($yo->puede('oportunidades.editar'))
        <form id="formMoverEtapa" method="POST" action="" class="d-none">
            @csrf
            @method('PATCH')
            <input type="hidden" name="id_etapa" />
            <input type="hidden" name="razon_cierre" />
        </form>
    @endif

    {{-- Modal de Edición Rápida de Oportunidad (Lápiz) --}}
    <div class="modal fade" id="modalEdicionOportunidad" tabindex="-1" aria-labelledby="modalEdicionOportunidadLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border border-200 shadow-lg">
                <form id="formEdicionRapidaOportunidad" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="volver" value="embudo" />
                    <input type="hidden" id="edit_id_prospecto" name="id_prospecto" />
                    <div class="modal-header border-bottom border-200">
                        <h5 class="modal-title text-1000" id="modalEdicionOportunidadLabel">
                            <span class="fas fa-pen text-primary me-2"></span>Editar Oportunidad
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="edit_nombre">Nombre de la oportunidad <span class="text-danger">*</span></label>
                                <input class="form-control" id="edit_nombre" name="nombre" required maxlength="200" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_id_cliente">Cliente</label>
                                <select class="form-select" id="edit_id_cliente" name="id_cliente">
                                    <option value="">— Ninguno —</option>
                                    @foreach ($clientes as $c)
                                        <option value="{{ $c->id_cliente }}">{{ $c->razon_social }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_id_responsable">Responsable / Ejecutivo <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_id_responsable" name="id_responsable" required>
                                    @foreach ($vendedores as $vend)
                                        <option value="{{ $vend->id_empleado }}">{{ $vend->nombre_completo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_valor_estimado">Valor estimado <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input class="form-control" id="edit_valor_estimado" name="valor_estimado" type="number" step="0.01" min="0" required />
                                    <select class="form-select" id="edit_moneda" name="moneda" style="max-width: 90px;">
                                        @foreach ($monedas as $m)
                                            <option value="{{ $m->codigo }}">{{ $m->codigo }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_probabilidad">Probabilidad estimada (%)</label>
                                <div class="input-group">
                                    <input class="form-control" id="edit_probabilidad" name="probabilidad" type="number" min="0" max="100" />
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_fecha_cierre_estimada">Fecha estimada de cierre</label>
                                <input class="form-control" id="edit_fecha_cierre_estimada" name="fecha_cierre_estimada" type="date" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_id_linea">Línea de negocio</label>
                                <select class="form-select" id="edit_id_linea" name="id_linea">
                                    <option value="">— Sin línea —</option>
                                    @foreach ($lineas as $l)
                                        <option value="{{ $l->id_linea }}">{{ $l->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="edit_descripcion">Descripción / Notas comerciales</label>
                                <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-200">
                        <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="fas fa-save me-2"></span>Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('estilos')
    <style>
        .kanban-deals-wrapper { overflow-x: auto; }
        .deals-col { min-width: 22rem; }
        .sortable-ghost {
            opacity: 0.4;
            transform: scale(0.98);
        }
        .sortable-chosen {
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.15) !important;
        }
        .btn-editar-rapido {
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        .oportunidad-card:hover .btn-editar-rapido {
            opacity: 1;
        }
    </style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sin permiso de edición el tablero es solo de lectura.
        const formMover = document.getElementById('formMoverEtapa');
        if (!formMover) return;

        const urlBase = @json(url('sistema/oportunidades'));
        const modalPerdidaEl = document.getElementById('modalRazonPerdida');
        const modalPerdida = modalPerdidaEl ? new bootstrap.Modal(modalPerdidaEl) : null;
        const modalEdicionEl = document.getElementById('modalEdicionOportunidad');
        const modalEdicion = modalEdicionEl ? new bootstrap.Modal(modalEdicionEl) : null;
        const inputRazon = document.getElementById('inputRazonCierre');
        let movimientoPendiente = null;
        let enviando = false;

        // Recalcula contador, total y aviso de «sin oportunidades» de una columna.
        function actualizarColumna(columna) {
            if (!columna) return;
            const tarjetas = columna.querySelectorAll('.oportunidad-card');
            const contador = columna.querySelector('.count-badge');
            const total = columna.querySelector('.total-columna');
            const vacia = columna.querySelector('.empty-placeholder');
            if (contador) contador.textContent = tarjetas.length;
            if (vacia) vacia.style.display = tarjetas.length === 0 ? '' : 'none';
            let suma = 0;
            tarjetas.forEach(t => { suma += parseFloat(t.dataset.valor || 0); });
            if (total) total.textContent = 'Q ' + suma.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Regresa la tarjeta a su lugar original.
        function devolver(mov) {
            const referencia = mov.desde.children[mov.indiceAnterior] || null;
            mov.desde.insertBefore(mov.tarjeta, referencia);
            actualizarColumna(mov.columnaOrigen);
            actualizarColumna(mov.columnaDestino);
        }

        // Envía el cambio de etapa como un formulario normal (la página se recarga con el mensaje).
        function enviar(mov, razon) {
            if (enviando) return;
            enviando = true;
            formMover.action = urlBase + '/' + encodeURIComponent(mov.id) + '/etapa';
            formMover.elements['id_etapa'].value = mov.idEtapa;
            formMover.elements['razon_cierre'].value = razon || '';
            document.querySelectorAll('[data-sortable-container]').forEach(c => c.sortable?.option('disabled', true));
            formMover.submit();
        }

        if (window.Sortable) {
            document.querySelectorAll('[data-sortable-container]').forEach(contenedor => {
                contenedor.sortable = new Sortable(contenedor, {
                    group: 'embudo-oportunidades',
                    animation: 200,
                    draggable: '.oportunidad-card',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    filter: '.btn-editar-rapido, a',
                    preventOnFilter: false,
                    onEnd: function (evt) {
                        if (evt.from === evt.to) return; // reordenar dentro de la etapa no cambia nada
                        const mov = {
                            tarjeta: evt.item,
                            desde: evt.from,
                            indiceAnterior: evt.oldIndex,
                            columnaOrigen: evt.from.closest('.deals-col'),
                            columnaDestino: evt.to.closest('.deals-col'),
                            id: evt.item.dataset.id,
                        };
                        mov.idEtapa = mov.columnaDestino.dataset.etapaId;
                        actualizarColumna(mov.columnaOrigen);
                        actualizarColumna(mov.columnaDestino);

                        if (mov.columnaDestino.dataset.esPerdida === '1') {
                            movimientoPendiente = mov;
                            inputRazon.value = '';
                            inputRazon.classList.remove('is-invalid');
                            modalPerdida.show();
                            return;
                        }
                        enviar(mov, null);
                    }
                });
            });
        }

        document.getElementById('btnConfirmarPerdida')?.addEventListener('click', function () {
            const razon = inputRazon.value.trim();
            if (!razon) {
                inputRazon.classList.add('is-invalid');
                inputRazon.focus();
                return;
            }
            if (movimientoPendiente) {
                const mov = movimientoPendiente;
                movimientoPendiente = null;
                enviar(mov, razon);
            }
        });

        function cancelarPerdida() {
            if (movimientoPendiente) {
                devolver(movimientoPendiente);
                movimientoPendiente = null;
            }
            modalPerdida.hide();
        }
        document.getElementById('btnCancelarPerdida')?.addEventListener('click', cancelarPerdida);
        document.getElementById('btnCerrarModalPerdida')?.addEventListener('click', cancelarPerdida);

        // Asigna el valor a un select; si la opción no está (p. ej. cliente inactivo) la agrega para no perderla.
        function elegir(select, valor, texto) {
            if (valor && !Array.from(select.options).some(o => o.value === valor)) {
                select.add(new Option(texto || ('#' + valor), valor));
            }
            select.value = valor || '';
        }

        // Edición rápida desde el lápiz de la tarjeta.
        window.abrirEdicionOportunidad = function (id) {
            const t = document.querySelector('.oportunidad-card[data-id="' + id + '"]');
            if (!t || !modalEdicion) return;
            const d = t.dataset;
            document.getElementById('formEdicionRapidaOportunidad').action = urlBase + '/' + encodeURIComponent(id);
            document.getElementById('edit_nombre').value = d.nombre || '';
            document.getElementById('edit_id_prospecto').value = d.prospectoId || '';
            elegir(document.getElementById('edit_id_cliente'), d.clienteId, d.clienteNombre);
            elegir(document.getElementById('edit_id_responsable'), d.responsableId, null);
            document.getElementById('edit_valor_estimado').value = d.valor || '';
            elegir(document.getElementById('edit_moneda'), d.moneda || 'GTQ', d.moneda);
            document.getElementById('edit_probabilidad').value = d.probabilidad || '';
            document.getElementById('edit_fecha_cierre_estimada').value = d.fechaCierre || '';
            elegir(document.getElementById('edit_id_linea'), d.lineaId, null);
            document.getElementById('edit_descripcion').value = d.descripcion || '';
            modalEdicion.show();
        };
    });
</script>
@endpush
