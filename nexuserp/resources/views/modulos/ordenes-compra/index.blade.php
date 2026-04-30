@extends('layouts.app')

@section('breadcrumb', 'Órdenes de Compra')

@section('content')
<div id="oc-app" v-cloak>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="shopping-cart" class="me-2"></span>
                Órdenes de Compra
            </h4>
            <p class="text-700 mb-0 fs--1">Gestiona las compras a proveedores</p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>
            Nueva Orden
        </button>
    </div>

    <v-smart-table
        title="Órdenes Registradas"
        :data="ordenes"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="ordenes_compra"
        empty-text="No hay órdenes registradas"
        @refresh="cargarDatos"
        @edit="abrirModalEditar"
        @delete="eliminarRegistro">
    </v-smart-table>

    {{-- ══════════════════════════════════════════════════════════
         MODAL ORDEN DE COMPRA — Cabecera + tabla de líneas
    ══════════════════════════════════════════════════════════ --}}
    <v-modal-form
        v-model="mostrarModal"
        id="modal-oc"
        :title="modoEditar ? 'Editar Orden #' + form.numero_oc : 'Nueva Orden de Compra'"
        size="modal-xl"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Orden'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">

        <div v-if="modoEditar" class="col-12 border-top pt-3 mt-3">
            <div class="d-flex gap-2 justify-content-end">
                <button v-if="form.estado === 'BORRADOR'"
                        type="button"
                        class="btn btn-success btn-sm"
                        @click="aprobarDesdeModal">
                    <i class="fas fa-check me-1"></i>Aprobar y Enviar
                </button>
                <button v-if="['BORRADOR','ENVIADA','PARCIAL'].includes(form.estado)"
                        type="button"
                        class="btn btn-warning btn-sm"
                        @click="cancelarDesdeModal">
                    <i class="fas fa-ban me-1"></i>Cancelar Orden
                </button>
            </div>
        </div>

        <div class="row g-3">
            {{-- ── Cabecera ── --}}
            <div class="col-md-3">
                <label class="form-label fw-bold">Número OC <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="form.numero_oc"
                       placeholder="OC-2026-001"
                       :class="{'is-invalid': errores.numero_oc}" />
                <div class="invalid-feedback">@{{ errores.numero_oc }}</div>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">Proveedor <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.id_proveedor" @change="onProveedorChange"
                        :class="{'is-invalid': errores.id_proveedor}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="p in proveedores" :key="p.id" :value="p.id">@{{ p.name }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Bodega de destino</label>
                <select class="form-select" v-model="form.id_bodega">
                    <option :value="null">— Sin bodega —</option>
                    <option v-for="b in bodegas" :key="b.id" :value="b.id">@{{ b.name }}</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Fecha emisión <span class="text-danger">*</span></label>
                <input type="date" class="form-control" v-model="form.fecha_emision" />
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Entrega esperada</label>
                <input type="date" class="form-control" v-model="form.fecha_entrega_esperada" />
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Moneda</label>
                <select class="form-select" v-model="form.moneda">
                    <option v-for="m in monedas" :key="m" :value="m">@{{ m }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Estado</label>
                <select class="form-select" v-model="form.estado" :disabled="modoEditar">
                    <option v-for="e in estados" :key="e" :value="e">@{{ e }}</option>
                </select>
            </div>

            {{-- ── Líneas de detalle ── --}}
            <div class="col-12 border-top pt-3 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-700 fw-bold mb-0">Líneas de Productos</h6>
                    <button class="btn btn-sm btn-outline-primary" @click="agregarLinea">
                        <i class="fas fa-plus me-1"></i>Agregar Producto
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="bg-100">
                            <tr>
                                <th style="width: 30%;">Producto <span class="text-danger">*</span></th>
                                <th style="width: 20%;">Descripción</th>
                                <th style="width: 10%;">Cantidad</th>
                                <th style="width: 12%;">Precio Unit.</th>
                                <th style="width: 10%;">Descuento</th>
                                <th style="width: 13%;" class="text-end">Subtotal</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="form.detalles.length === 0">
                                <td colspan="7" class="text-center text-muted py-3">
                                    Agrega productos a esta orden
                                </td>
                            </tr>
                            <tr v-for="(linea, idx) in form.detalles" :key="idx">
                                <td>
                                    <select class="form-select form-select-sm" v-model="linea.id_producto"
                                            @change="onProductoChange(idx)">
                                        <option :value="null">— Producto —</option>
                                        <option v-for="p in productos" :key="p.id" :value="p.id">
                                            @{{ p.codigo }} - @{{ p.name }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                           v-model="linea.descripcion" placeholder="Opcional" />
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end"
                                           v-model.number="linea.cantidad_pedida" @input="recalcularLinea(idx)" />
                                </td>
                                <td>
                                    <input type="number" step="0.0001" min="0" class="form-control form-control-sm text-end"
                                           v-model.number="linea.precio_unitario" @input="recalcularLinea(idx)" />
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end"
                                           v-model.number="linea.descuento" @input="recalcularLinea(idx)" />
                                </td>
                                <td class="text-end fw-bold">
                                    @{{ formatear(linea.subtotal) }}
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-link text-danger p-0"
                                            @click="quitarLinea(idx)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-100">
                            <tr>
                                <td colspan="5" class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end fw-bold">@{{ formatear(totales.subtotal) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end fw-bold">IVA (12%):</td>
                                <td class="text-end fw-bold">@{{ formatear(totales.iva) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end fw-bold fs--1">TOTAL:</td>
                                <td class="text-end fw-bold fs--1 text-primary">@{{ formatear(totales.total) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Notas</label>
                <textarea class="form-control" v-model="form.notas" rows="2"
                          placeholder="Observaciones adicionales"></textarea>
            </div>
        </div>
    </v-modal-form>
</div>
@endsection