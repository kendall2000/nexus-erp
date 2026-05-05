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
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="agregarLinea">
                        <span data-feather="plus" style="width:13px" class="me-1"></span>
                        Agregar Producto
                    </button>
                </div>

                <div class="alert alert-soft-info py-2 mb-2 small">
                    <span data-feather="info" style="width:14px;height:14px" class="me-1"></span>
                    <strong>Tip:</strong> Cada producto puede tener un <em>centro de costo</em> y <em>cuenta de gasto</em> default.
                    Si lo dejas vacío, se usa el default del producto. Al aprobar la OC, el monto se descontará automáticamente del presupuesto correspondiente.
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="bg-100">
                            <tr>
                                <th style="width:18%">Producto <span class="text-danger">*</span></th>
                                <th style="width:14%">Descripción</th>
                                <th style="width:7%">Cant.</th>
                                <th style="width:9%">Precio</th>
                                <th style="width:7%">Desc.</th>
                                <th style="width:13%">Centro Costo</th>
                                <th style="width:13%">Cuenta</th>
                                <th style="width:11%" class="text-end">Subtotal</th>
                                <th style="width:4%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="form.detalles.length === 0">
                                <td colspan="9" class="text-center text-muted py-3">
                                    Agrega productos a esta orden
                                </td>
                            </tr>
                            <tr v-for="(linea, idx) in form.detalles" :key="idx">
                                <td>
                                    <select class="form-select form-select-sm" v-model="linea.id_producto"
                                            @change="onProductoChange(idx)">
                                        <option :value="null">— Producto —</option>
                                        <option v-for="p in productos" :key="p.id" :value="p.id">
                                            @{{ p.codigo }} — @{{ p.name }}
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
                                <td class="text-end fw-bold">@{{ formatear(linea.subtotal) }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                            @click="quitarLinea(idx)">
                                        <span data-feather="x" style="width:14px"></span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-100">
                            <tr>
                                <td colspan="7" class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end fw-bold">@{{ formatear(totales.subtotal) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-end fw-bold">IVA (@{{ totales.tasaIvaPct }}%):</td>
                                <td class="text-end fw-bold">@{{ formatear(totales.iva) }}</td>
                                <td></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="7" class="text-end fw-bold">TOTAL:</td>
                                <td class="text-end fw-bold fs-0 text-primary">
                                    @{{ form.moneda }} @{{ formatear(totales.total) }}
                                </td>
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