@extends('layouts.app')
@section('breadcrumb', 'Facturas')

@section('content')
<div id="facturas-app" v-cloak>

    {{-- ════════════════════════════════════════════════════════
         ENCABEZADO
    ════════════════════════════════════════════════════════ --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="file-text" class="me-2"></span>Facturas
            </h4>
            <p class="text-700 mb-0 fs--1">Gestiona las facturas de venta y notas</p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>Nueva Factura
        </button>
    </div>

    {{-- ════════════════════════════════════════════════════════
         FILTROS
    ════════════════════════════════════════════════════════ --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Estado</label>
                    <select class="form-select form-select-sm"
                            v-model="filtros.estado" @change="cargarDatos">
                        <option value="">Todos</option>
                        <option v-for="e in estadosFiltro" :key="e.valor" :value="e.valor">
                            @{{ e.label }}
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm"
                           v-model="filtros.fecha_desde" @change="cargarDatos">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm"
                           v-model="filtros.fecha_hasta" @change="cargarDatos">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary btn-sm w-100"
                            @click="limpiarFiltros">
                        <span data-feather="x" class="me-1"></span>Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         TABLA PRINCIPAL
    ════════════════════════════════════════════════════════ --}}
    <v-smart-table
        title="Facturas Registradas"
        :data="facturas"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="facturas_nexuserp"
        empty-text="No hay facturas registradas"
        @refresh="cargarDatos"
        @edit="abrirModalEditar"
        @delete="eliminarRegistro">
    </v-smart-table>

    {{-- ════════════════════════════════════════════════════════
         MODAL FACTURA
    ════════════════════════════════════════════════════════ --}}
    <v-modal-form
        v-model="mostrarModal"
        id="modal-factura"
        :title="modoEditar ? 'Editar Factura ' + form.numero_completo : 'Nueva Factura'"
        size="modal-xl"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Factura'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">

        <div class="row g-3">

            {{-- ── FILA 1: Cliente + Contrato + Serie + Tipo ── --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">
                    Cliente <span class="text-danger">*</span>
                </label>
                <select class="form-select" v-model="form.id_cliente"
                        @change="onClienteChange"
                        :class="{'is-invalid': errores.id_cliente}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="c in clientes" :key="c.id" :value="c.id">
                        @{{ c.name }}
                    </option>
                </select>
                <div class="invalid-feedback">@{{ errores.id_cliente }}</div>
                <small v-if="clienteSeleccionado" class="text-muted">
                    NIT: @{{ clienteSeleccionado.nit }}
                    | @{{ clienteSeleccionado.dias_credito }} días crédito
                </small>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Contrato (opcional)</label>
                <select class="form-select" v-model="form.id_contrato"
                        @change="onContratoChange">
                    <option :value="null">— Sin contrato —</option>
                    <optgroup v-if="contratosFiltrados.length"
                              :label="'Contratos de ' + (clienteSeleccionado ? clienteSeleccionado.name : 'cliente')">
                        <option v-for="c in contratosFiltrados"
                                :key="c.id" :value="c.id">
                            @{{ c.name }}
                        </option>
                    </optgroup>
                </select>
                <small v-if="contratoSeleccionado" class="text-muted">
                    @{{ contratoSeleccionado.periodicidad }}
                    | @{{ contratoSeleccionado.moneda }}
                    @{{ formatear(contratoSeleccionado.valor_mensual) }}/mes
                </small>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold">
                    Serie <span class="text-danger">*</span>
                </label>
                <select class="form-select" v-model="form.id_serie"
                        :class="{'is-invalid': errores.id_serie}">
                    <option :value="null">— Serie —</option>
                    <option v-for="s in series" :key="s.id" :value="s.id">
                        @{{ s.name }}
                    </option>
                </select>
                <div class="invalid-feedback">@{{ errores.id_serie }}</div>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold">Tipo</label>
                <select class="form-select" v-model="form.tipo">
                    <option v-for="t in tipos" :key="t" :value="t">@{{ t }}</option>
                </select>
            </div>

            {{-- ── FILA 2: Fechas ── --}}
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    Fecha Emisión <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control"
                       v-model="form.fecha_emision"
                       @change="calcularVencimiento"
                       :class="{'is-invalid': errores.fecha_emision}">
                <div class="invalid-feedback">@{{ errores.fecha_emision }}</div>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Fecha Vencimiento</label>
                <input type="date" class="form-control"
                       v-model="form.fecha_vencimiento">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Período Inicio</label>
                <input type="date" class="form-control"
                       v-model="form.periodo_servicio_inicio">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Período Fin</label>
                <input type="date" class="form-control"
                       v-model="form.periodo_servicio_fin">
            </div>

            {{-- ── FILA 3: Moneda + Descuento + Notas ── --}}
            <div class="col-md-2">
                <label class="form-label fw-bold">Moneda</label>
                <select class="form-select" v-model="form.moneda">
                    <option v-for="m in monedas" :key="m" :value="m">@{{ m }}</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold">Descuento Global</label>
                <input type="number" step="0.01" min="0"
                       class="form-control" v-model.number="form.descuento">
            </div>

            <div class="col-md-8">
                <label class="form-label fw-bold">Notas</label>
                <input type="text" class="form-control"
                       v-model="form.notas"
                       placeholder="Observaciones de la factura...">
            </div>

            {{-- ════════════════════════════════════════════════════
                 LÍNEAS DE FACTURA
            ════════════════════════════════════════════════════ --}}
            <div class="col-12 border-top pt-3 mt-1">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-700 mb-0">
                        <span data-feather="list" style="width:15px" class="me-1"></span>
                        Líneas de Factura
                    </h6>
                    <div class="d-flex gap-2">
                        <button v-if="form.id_contrato && !modoEditar"
                                type="button"
                                class="btn btn-sm btn-outline-info"
                                @click="cargarLineasContrato"
                                :disabled="cargandoLineas">
                            <span data-feather="download" style="width:13px" class="me-1"></span>
                            @{{ cargandoLineas ? 'Cargando...' : 'Cargar del Contrato' }}
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                @click="agregarLinea">
                            <span data-feather="plus" style="width:13px" class="me-1"></span>
                            Agregar Línea
                        </button>
                    </div>
                </div>

                {{-- Alert dinámico (FUERA de la tabla) --}}
                <div class="alert alert-soft-info py-2 mb-2 small">
                    <span data-feather="info" style="width:14px;height:14px" class="me-1"></span>
                    <strong>Tip:</strong>
                    <template v-if="configFiscal.iva_incluido_en_precio">
                        El precio se ingresa <strong>con IVA incluido</strong> (@{{ configFiscal.tasa_iva }}%).
                    </template>
                    <template v-else>
                        El precio se ingresa <strong>sin IVA</strong>. Se sumará @{{ configFiscal.tasa_iva }}% automáticamente.
                    </template>
                    Si desmarcas <em>IVA</em>, la línea queda como exenta.
                    Los servicios con cuenta y centro default los aplican automáticamente.
                </div>

                {{-- TABLA DE LÍNEAS --}}
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="bg-100">
                            <tr>
                                <th style="width:18%">Servicio</th>
                                <th style="width:20%">Descripción <span class="text-danger">*</span></th>
                                <th style="width:7%" class="text-end">Cant.</th>
                                <th style="width:10%" class="text-end">
                                    Precio @{{ configFiscal.iva_incluido_en_precio ? '(c/IVA)' : '(s/IVA)' }}
                                </th>
                                <th style="width:8%" class="text-end">Descuento</th>
                                <th style="width:5%" class="text-center">IVA</th>
                                <th style="width:11%">Centro Costo</th>
                                <th style="width:11%">Cuenta</th>
                                <th style="width:10%" class="text-end">Subtotal</th>
                                <th style="width:4%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="form.detalles.length === 0">
                                <td colspan="10" class="text-center text-muted py-3 fs--1">
                                    <span data-feather="inbox" style="width:20px;opacity:.4" class="me-1"></span>
                                    Agrega líneas o carga desde el contrato
                                </td>
                            </tr>
                            <tr v-for="(linea, idx) in form.detalles" :key="idx">
                                {{-- Servicio --}}
                                <td>
                                    <select class="form-select form-select-sm"
                                            v-model="linea.id_tipo_servicio"
                                            @change="onServicioChange(idx)">
                                        <option :value="null">— Libre —</option>
                                        <optgroup v-for="(svcs, linNombre) in serviciosAgrupados" :label="linNombre">
                                            <option v-for="s in svcs" :key="s.id" :value="s.id">@{{ s.name }}</option>
                                        </optgroup>
                                    </select>
                                </td>

                                {{-- Descripción --}}
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                           v-model="linea.descripcion" placeholder="Descripción">
                                </td>

                                {{-- Cantidad --}}
                                <td>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control form-control-sm text-end"
                                           v-model.number="linea.cantidad"
                                           @input="recalcularLinea(idx)">
                                </td>

                                {{-- Precio --}}
                                <td>
                                    <input type="number" step="0.0001" min="0"
                                           class="form-control form-control-sm text-end"
                                           v-model.number="linea.precio_unitario"
                                           @input="recalcularLinea(idx)">
                                </td>

                                {{-- Descuento --}}
                                <td>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control form-control-sm text-end"
                                           v-model.number="linea.descuento"
                                           @input="recalcularLinea(idx)">
                                </td>

                                {{-- IVA --}}
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input"
                                           v-model="linea.es_afecto_iva"
                                           @change="recalcularLinea(idx)">
                                </td>

                                {{-- Centro de Costo --}}
                                <td>
                                    <select class="form-select form-select-sm"
                                            v-model="linea.id_centro"
                                            :title="centroEfectivoTexto(linea)">
                                        <option :value="null">
                                            @{{ centroDefaultLinea(linea) ? '(default)' : '— Asignar —' }}
                                        </option>
                                        <option v-for="c in centros" :key="c.id" :value="c.id">@{{ c.name }}</option>
                                    </select>
                                </td>

                                {{-- Cuenta Contable --}}
                                <td>
                                    <select class="form-select form-select-sm"
                                            v-model="linea.id_cuenta"
                                            :title="cuentaEfectivaTexto(linea)">
                                        <option :value="null">
                                            @{{ cuentaDefaultLinea(linea) ? '(default)' : '— Asignar —' }}
                                        </option>
                                        <option v-for="c in cuentas" :key="c.id" :value="c.id">@{{ c.name }}</option>
                                    </select>
                                </td>

                                {{-- Subtotal --}}
                                <td class="text-end fw-bold text-primary">@{{ formatear(linea.subtotal) }}</td>

                                {{-- Eliminar --}}
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                            @click="quitarLinea(idx)">
                                        <span data-feather="x" style="width:14px"></span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-100 fw-bold fs--1">
                            <tr>
                                <td colspan="8" class="text-end">
                                    Subtotal @{{ totales.ivaIncluido ? '(con IVA)' : '(sin IVA)' }}:
                                </td>
                                <td class="text-end">@{{ formatear(totales.subtotal) }}</td>
                                <td></td>
                            </tr>
                            <tr v-if="totales.descuento > 0">
                                <td colspan="8" class="text-end">Descuento Global:</td>
                                <td class="text-end text-danger">− @{{ formatear(totales.descuento) }}</td>
                                <td></td>
                            </tr>
                            <tr v-if="totales.baseExenta > 0">
                                <td colspan="8" class="text-end text-muted">Base Exenta:</td>
                                <td class="text-end text-muted">@{{ formatear(totales.baseExenta) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="8" class="text-end">Base Imponible (sin IVA):</td>
                                <td class="text-end">@{{ formatear(totales.baseAfecta) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="8" class="text-end">IVA (@{{ totales.tasaIvaPct }}%):</td>
                                <td class="text-end">@{{ formatear(totales.iva) }}</td>
                                <td></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="8" class="text-end text-primary">TOTAL A PAGAR:</td>
                                <td class="text-end text-primary fs-0">
                                    @{{ form.moneda }} @{{ formatear(totales.total) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════
                 ACCIONES DE ESTADO (solo al editar)
            ════════════════════════════════════════════════════ --}}
            <div v-if="modoEditar" class="col-12 border-top pt-3 mt-1">
                <div class="d-flex gap-2 justify-content-between align-items-center">
                    <div>
                        <span class="badge fs--1" :class="badgeEstado(form.estado)">
                            @{{ form.estado }}
                        </span>
                        <span v-if="form.saldo_pendiente > 0"
                              class="ms-2 text-muted fs--1">
                            Saldo: @{{ form.moneda }} @{{ formatear(form.saldo_pendiente) }}
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button v-if="form.estado === 'BORRADOR'"
                                type="button"
                                class="btn btn-success btn-sm"
                                @click="emitirDesdeModal">
                            <span data-feather="send" style="width:13px" class="me-1"></span>
                            Emitir
                        </button>
                        <button v-if="form.estado === 'EMITIDA'"
                                type="button"
                                class="btn btn-info btn-sm"
                                @click="cambiarEstado('ENVIADA')">
                            <span data-feather="mail" style="width:13px" class="me-1"></span>
                            Marcar Enviada
                        </button>
                        <button v-if="!['ANULADA','PAGADA','BORRADOR'].includes(form.estado)"
                                type="button"
                                class="btn btn-warning btn-sm"
                                @click="cambiarEstado('VENCIDA')">
                            <span data-feather="alert-triangle" style="width:13px" class="me-1"></span>
                            Marcar Vencida
                        </button>
                        <button v-if="!['ANULADA','PAGADA'].includes(form.estado)"
                                type="button"
                                class="btn btn-danger btn-sm"
                                @click="anularDesdeModal">
                            <span data-feather="slash" style="width:13px" class="me-1"></span>
                            Anular
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </v-modal-form>

</div>
@endsection