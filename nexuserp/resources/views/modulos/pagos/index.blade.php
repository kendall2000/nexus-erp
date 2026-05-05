@extends('layouts.app')
@section('breadcrumb', 'Pagos')

@section('content')
<div id="pagos-app" v-cloak>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="credit-card" class="me-2"></span>Pagos
            </h4>
            <p class="text-700 mb-0 fs--1">Registra y gestiona los pagos de facturas</p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>Nuevo Pago
        </button>
    </div>

    {{-- Filtros rápidos --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Forma de Pago</label>
                    <select class="form-select form-select-sm"
                            v-model="filtros.forma_pago" @change="cargarDatos">
                        <option value="">Todas</option>
                        <option v-for="fp in formasPago" :key="fp" :value="fp">@{{ fp }}</option>
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

    <v-smart-table
        title="Pagos Registrados"
        :data="pagos"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="pagos_nexuserp"
        empty-text="No hay pagos registrados"
        @refresh="cargarDatos"
        @delete="eliminarRegistro">
    </v-smart-table>

    <v-modal-form
        v-model="mostrarModal"
        id="modal-pago"
        :title="'Registrar Pago'"
        size="modal-lg"
        btn-text="Registrar Pago"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">

        <div class="row g-3">

            {{-- Factura pendiente --}}
            <div class="col-12">
                <label class="form-label fw-bold">
                    Factura Pendiente <span class="text-danger">*</span>
                </label>
                <select class="form-select" v-model="form.id_factura"
                        @change="onFacturaChange"
                        :class="{'is-invalid': errores.id_factura}">
                    <option :value="null">— Seleccione una factura —</option>
                    <option v-for="f in facturasPendientes"
                            :key="f.id_factura" :value="f.id_factura">
                        @{{ f.label }}
                    </option>
                </select>
                <div class="invalid-feedback">@{{ errores.id_factura }}</div>
            </div>

            {{-- Alerta saldo --}}
            <div class="col-12" v-if="facturaSeleccionada">
                <div class="alert alert-soft-info py-2 mb-0 small">
                    <span data-feather="info" style="width:14px;height:14px" class="me-1"></span>
                    Saldo pendiente: <strong>@{{ facturaSeleccionada.moneda }} @{{ facturaSeleccionada.saldo_pendiente }}</strong>
                    &nbsp;·&nbsp; Vence: <strong>@{{ facturaSeleccionada.fecha_vencimiento }}</strong>
                </div>
            </div>

            {{-- Forma de pago --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Forma de Pago <span class="text-danger">*</span>
                </label>
                <select class="form-select" v-model="form.forma_pago"
                        :class="{'is-invalid': errores.forma_pago}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="fp in formasPago" :key="fp" :value="fp">@{{ fp }}</option>
                </select>
                <div class="invalid-feedback">@{{ errores.forma_pago }}</div>
            </div>

            {{-- Monto --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Monto <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">@{{ form.moneda || 'GTQ' }}</span>
                    <input type="number" class="form-control"
                           v-model.number="form.monto"
                           step="0.01" min="0.01" placeholder="0.00"
                           :class="{'is-invalid': errores.monto}">
                </div>
                <div class="text-danger small mt-1" v-if="errores.monto">
                    @{{ errores.monto }}
                </div>
            </div>

            {{-- Referencia --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">Referencia / No. Cheque</label>
                <input type="text" class="form-control"
                       v-model="form.referencia"
                       placeholder="Ej: TRF-00123" maxlength="100">
            </div>

            {{-- Banco --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">Banco Origen</label>
                <input type="text" class="form-control"
                       v-model="form.banco_origen"
                       placeholder="Ej: Banrural, BAM, G&T..." maxlength="100">
            </div>

            {{-- Fecha Pago --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Fecha de Pago <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control"
                       v-model="form.fecha_pago"
                       :class="{'is-invalid': errores.fecha_pago}">
                <div class="invalid-feedback">@{{ errores.fecha_pago }}</div>
            </div>

            {{-- Fecha Acreditado --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">Fecha Acreditado</label>
                <input type="date" class="form-control"
                       v-model="form.fecha_acreditado">
                <small class="text-muted">
                    Opcional — para transferencias con días hábiles de acreditación.
                </small>
            </div>

            {{-- Notas --}}
            <div class="col-12">
                <label class="form-label fw-bold">Notas</label>
                <textarea class="form-control" v-model="form.notas"
                          rows="2" maxlength="500"
                          placeholder="Observaciones del pago..."></textarea>
            </div>

        </div>
    </v-modal-form>

</div>
@endsection