@extends('layouts.app')

@section('breadcrumb', 'Catálogo de Proveedores')

@section('content')
<div id="proveedores-app" v-cloak>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="truck" class="me-2"></span>
                Proveedores
            </h4>
            <p class="text-700 mb-0 fs--1">Administra tu cartera de proveedores</p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>
            Nuevo Proveedor
        </button>
    </div>

    <v-smart-table
        title="Listado de Proveedores"
        :data="proveedores"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="proveedores_nexuserp"
        status-key="activo"
        empty-text="No hay proveedores registrados"
        @refresh="cargarDatos"
        @edit="abrirModalEditar"
        @toggle="toggleEstado"
        @delete="eliminarRegistro">
    </v-smart-table>

    <v-modal-form
        v-model="mostrarModal"
        id="modal-proveedor"
        :title="modoEditar ? 'Editar Proveedor' : 'Nuevo Proveedor'"
        size="modal-lg"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Proveedor'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">
        
        <div class="row g-3">
            {{-- ── Identificación ── --}}
            <div class="col-md-8">
                <label class="form-label fw-bold">
                    Razón Social <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" v-model="form.razon_social"
                       placeholder="Ej: Distribuidora Comercial S.A."
                       :class="{'is-invalid': errores.razon_social}" />
                <div class="invalid-feedback">@{{ errores.razon_social }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">NIT</label>
                <input type="text" class="form-control" v-model="form.nit"
                       placeholder="1234567-8"
                       :class="{'is-invalid': errores.nit}" />
                <div class="invalid-feedback">@{{ errores.nit }}</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Nombre Comercial</label>
                <input type="text" class="form-control" v-model="form.nombre_comercial"
                       placeholder="Nombre con el que se le conoce" />
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">
                    País <span class="text-danger">*</span>
                </label>
                <select class="form-select" v-model="form.id_pais"
                        :class="{'is-invalid': errores.id_pais}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="p in paises" :key="p.id" :value="p.id">@{{ p.name }}</option>
                </select>
                <div class="invalid-feedback">@{{ errores.id_pais }}</div>
            </div>

            {{-- ── Contacto ── --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">Email</label>
                <input type="email" class="form-control" v-model="form.email"
                       placeholder="contacto@proveedor.com" />
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Teléfono</label>
                <input type="text" class="form-control" v-model="form.telefono" />
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Persona de Contacto</label>
                <input type="text" class="form-control" v-model="form.contacto"
                       placeholder="Nombre del contacto principal" />
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Dirección</label>
                <input type="text" class="form-control" v-model="form.direccion" />
            </div>

            {{-- ── Comercial ── --}}
            <div class="col-12 border-top pt-3 mt-2">
                <h6 class="text-700 fw-bold mb-3">Información Comercial</h6>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Tipo</label>
                <select class="form-select" v-model="form.tipo_proveedor">
                    <option v-for="t in tipos" :key="t" :value="t">@{{ t }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Días de Crédito</label>
                <input type="number" class="form-control" v-model.number="form.dias_credito"
                       min="0" max="255" />
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Moneda de Pago</label>
                <select class="form-select" v-model="form.moneda_pago">
                    <option v-for="m in monedas" :key="m" :value="m">@{{ m }}</option>
                </select>
            </div>
        </div>
    </v-modal-form>
</div>
@endsection