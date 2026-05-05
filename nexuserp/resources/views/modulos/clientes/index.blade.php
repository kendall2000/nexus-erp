@extends('layouts.app')
@section('breadcrumb', 'Clientes')
@section('content')
<div id="clientes-app" v-cloak>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="users" class="me-2"></span>Clientes
            </h4>
            <p class="text-700 mb-0 fs--1">Administra tu cartera de clientes</p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>Nuevo Cliente
        </button>
    </div>

    <v-smart-table
        title="Listado de Clientes"
        :data="clientes"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="clientes_nexuserp"
        status-key="activo"
        empty-text="No hay clientes registrados"
        @refresh="cargarDatos"
        @edit="abrirModalEditar"
        @toggle="toggleEstado"
        @delete="eliminarRegistro">
    </v-smart-table>

    <v-modal-form
        v-model="mostrarModal"
        id="modal-cliente"
        :title="modoEditar ? 'Editar Cliente' : 'Nuevo Cliente'"
        size="modal-lg"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Cliente'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">

        <div class="row g-3">
            {{-- Identificación --}}
            <div class="col-md-8">
                <label class="form-label fw-bold">Razón Social <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="form.razon_social"
                       :class="{'is-invalid': errores.razon_social}" />
                <div class="invalid-feedback">@{{ errores.razon_social }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">NIT</label>
                <input type="text" class="form-control" v-model="form.nit"
                       :class="{'is-invalid': errores.nit}" />
                <div class="invalid-feedback">@{{ errores.nit }}</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Nombre Comercial</label>
                <input type="text" class="form-control" v-model="form.nombre_comercial" />
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Tipo Persona</label>
                <select class="form-select" v-model="form.tipo_persona">
                    <option v-for="t in tipos" :key="t" :value="t">@{{ t }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">País <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.id_pais" @change="onPaisChange"
                        :class="{'is-invalid': errores.id_pais}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="p in paises" :key="p.id" :value="p.id">@{{ p.name }}</option>
                </select>
                <div class="invalid-feedback">@{{ errores.id_pais }}</div>
            </div>
            {{-- Departamento en cascada --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">Departamento</label>
                <select class="form-select" v-model="form.id_division"
                        @change="onDivisionChange"
                        :disabled="!form.id_pais || cargandoDivisiones">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="d in divisiones" :key="d.id" :value="d.id">@{{ d.name }}</option>
                </select>
            </div>

            {{-- Municipio en cascada --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">Municipio</label>
                <select class="form-select" v-model="form.id_municipio"
                        :disabled="!form.id_division || cargandoMunicipios">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="m in municipios" :key="m.id" :value="m.id">@{{ m.name }}</option>
                </select>
            </div>

            {{-- Industria --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">Industria</label>
                <select class="form-select" v-model="form.id_industria">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="i in industrias" :key="i.id" :value="i.id">@{{ i.name }}</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Email</label>
                <input type="email" class="form-control" v-model="form.email_principal" />
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Teléfono</label>
                <input type="text" class="form-control" v-model="form.telefono_principal" />
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Dirección Fiscal</label>
                <input type="text" class="form-control" v-model="form.direccion_fiscal" />
            </div>

            {{-- Comercial --}}
            <div class="col-12 border-top pt-3 mt-2">
                <h6 class="text-700 fw-bold mb-3">Información Comercial</h6>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Segmento</label>
                <select class="form-select" v-model="form.segmento">
                    <option :value="null">— Ninguno —</option>
                    <option v-for="s in segmentos" :key="s" :value="s">@{{ s }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Categoría</label>
                <select class="form-select" v-model="form.categoria">
                    <option :value="null">— Ninguna —</option>
                    <option v-for="c in categorias" :key="c" :value="c">@{{ c }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Días de Crédito</label>
                <input type="number" class="form-control" v-model.number="form.dias_credito" min="0" />
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Moneda</label>
                <select class="form-select" v-model="form.moneda_facturacion">
                    <option v-for="m in monedas" :key="m" :value="m">@{{ m }}</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Límite de Crédito</label>
                <input type="number" step="0.01" class="form-control"
                       v-model.number="form.limite_credito" placeholder="Sin límite" />
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Sitio Web</label>
                <input type="text" class="form-control" v-model="form.sitio_web"
                       placeholder="https://..." />
            </div>
        </div>
    </v-modal-form>
</div>
@endsection