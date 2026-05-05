@extends('layouts.app')

@section('breadcrumb', 'Recepciones de Mercadería')

@section('content')
<div id="recepciones-app" v-cloak>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="package" class="me-2"></span>
                Recepciones de Mercadería
            </h4>
            <p class="text-700 mb-0 fs--1">Registra la entrada de productos al inventario</p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>
            Nueva Recepción
        </button>
    </div>

    <v-smart-table
        title="Recepciones Registradas"
        :data="recepciones"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="recepciones"
        empty-text="No hay recepciones registradas"
        @refresh="cargarDatos"
        @edit="verDetalle">
    </v-smart-table>

    {{-- ══════════════════════════════════════════════════════════
         MODAL NUEVA RECEPCIÓN
    ══════════════════════════════════════════════════════════ --}}
    <v-modal-form
        v-model="mostrarModal"
        id="modal-recepcion"
        :title="modoVer ? 'Detalle Recepción #' + form.numero_recepcion : 'Nueva Recepción de Mercadería'"
        size="modal-xl"
        :btn-text="modoVer ? null : 'Registrar Recepción'"
        btn-class="btn-success"
        :loading="guardando"
        @action="guardarRegistro">

        {{-- ── Cabecera ── --}}
        <div class="row g-3" v-if="!modoVer">
            <div class="col-md-4">
                <label class="form-label fw-bold">N° Recepción <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="form.numero_recepcion"
                       placeholder="REC-2026-001"
                       :class="{'is-invalid': errores.numero_recepcion}" />
                <div class="invalid-feedback">@{{ errores.numero_recepcion }}</div>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">Orden de Compra <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.id_oc" @change="onOCChange"
                        :class="{'is-invalid': errores.id_oc}">
                    <option :value="null">— Seleccione OC —</option>
                    <option v-for="o in ordenes" :key="o.id" :value="o.id">@{{ o.name }}</option>
                </select>
                <div class="invalid-feedback">@{{ errores.id_oc }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                <input type="date" class="form-control" v-model="form.fecha_recepcion" />
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Notas</label>
                <textarea class="form-control" v-model="form.notas" rows="2"
                          placeholder="Observaciones de la recepción"></textarea>
            </div>
        </div>

        {{-- ── Líneas de productos ── --}}
        <div v-if="!modoVer && form.id_oc" class="mt-4">
            <div v-if="cargandoLineas" class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando líneas de la OC...</span>
            </div>

            <div v-else-if="form.detalles.length > 0">
                <h6 class="fw-bold text-700 mb-3 border-top pt-3">
                    Productos a Recibir
                    <small class="text-muted fw-normal">— edita la cantidad recibida</small>
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="bg-100">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Pedido</th>
                                <th class="text-center">Ya recibido</th>
                                <th class="text-center">Pendiente</th>
                                <th class="text-center" style="width:130px;">A recibir ahora</th>
                                <th class="text-center" style="width:130px;">Costo unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(linea, idx) in form.detalles" :key="idx"
                                :class="{'table-warning': linea.cantidad_a_recibir <= 0}">
                                <td>
                                    <strong>@{{ linea.producto_codigo }}</strong>
                                    <span class="ms-1">@{{ linea.producto_nombre }}</span>
                                    <small class="text-muted d-block">@{{ linea.unidad_medida }}</small>
                                </td>
                                <td class="text-center">@{{ linea.cantidad_pedida }}</td>
                                <td class="text-center text-success fw-bold">@{{ linea.cantidad_recibida }}</td>
                                <td class="text-center text-warning fw-bold">@{{ linea.pendiente }}</td>
                                <td>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control form-control-sm text-center"
                                           v-model.number="linea.cantidad_a_recibir"
                                           :max="linea.pendiente"
                                           @input="recalcularLinea(idx)" />
                                </td>
                                <td>
                                    <input type="number" step="0.0001" min="0"
                                           class="form-control form-control-sm text-end"
                                           v-model.number="linea.costo_unitario"
                                           @input="recalcularLinea(idx)" />
                                </td>
                                <td class="text-end fw-bold">@{{ formatear(linea.subtotal) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-100">
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Total recepción:</td>
                                <td class="text-end fw-bold text-primary">
                                    @{{ formatear(totalRecepcion) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Modo ver detalle ── --}}
        <div v-if="modoVer">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted">OC Relacionada</label>
                    <p class="mb-0 fw-bold">@{{ detalleVer.numero_oc }}</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted">Proveedor</label>
                    <p class="mb-0">@{{ detalleVer.proveedor }}</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted">Bodega</label>
                    <p class="mb-0">@{{ detalleVer.bodega }}</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="bg-100">
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-center">Costo Unit.</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in detalleVer.detalles" :key="d.id_detalle_rec">
                            <td>@{{ d.producto_codigo }} — @{{ d.producto_nombre }}</td>
                            <td class="text-center">@{{ d.cantidad_recibida }}</td>
                            <td class="text-center">@{{ formatear(d.costo_unitario) }}</td>
                            <td class="text-end">@{{ formatear(d.subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </v-modal-form>
</div>
@endsection