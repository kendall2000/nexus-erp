@extends('layouts.app')

@section('breadcrumb', 'Gestión de Sucursales')

@section('content')
<div id="sucursales-app" v-cloak>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="map-pin" class="me-2"></span>
                Sucursales
            </h4>
            <p class="text-700 mb-0 fs--1">Administra las sucursales de la empresa</p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>
            Nueva Sucursal
        </button>
    </div>

    <v-smart-table
        title="Sucursales Registradas"
        :data="sucursales"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="sucursales_nexuserp"
        status-key="activo"
        empty-text="No hay sucursales registradas"
        @refresh="cargarDatos"
        @edit="abrirModalEditar"
        @toggle="toggleEstado"
        @delete="eliminarRegistro">
    </v-smart-table>

    <v-modal-form
        v-model="mostrarModal"
        id="modal-sucursal"
        :title="modoEditar ? 'Editar Sucursal' : 'Nueva Sucursal'"
        size="modal-lg"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Sucursal'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">
        
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-bold">
                    Nombre <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" v-model="form.nombre"
                       placeholder="Ej: Sucursal Central"
                       :class="{'is-invalid': errores.nombre}" />
                <div class="invalid-feedback">@{{ errores.nombre }}</div>
            </div>

            {{-- Geografía en cascada: País → Departamento → Municipio --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">País</label>
                <select class="form-select" v-model="form.id_pais" @change="onPaisChange">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="p in paises" :key="p.id" :value="p.id">@{{ p.name }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Departamento</label>
                <select class="form-select" v-model="form.id_division"
                        @change="onDivisionChange"
                        :disabled="!form.id_pais || cargandoDivisiones">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="d in divisiones" :key="d.id" :value="d.id">@{{ d.name }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Municipio</label>
                <select class="form-select" v-model="form.id_municipio"
                        :disabled="!form.id_division || cargandoMunicipios">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="m in municipios" :key="m.id" :value="m.id">@{{ m.name }}</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Teléfono</label>
                <input type="text" class="form-control" v-model="form.telefono" />
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Email</label>
                <input type="email" class="form-control" v-model="form.email" />
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Dirección</label>
                <textarea class="form-control" v-model="form.direccion" rows="2"></textarea>
            </div>

            <div class="col-12 mt-3 border-top pt-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" v-model="form.es_casa_matriz" id="checkMatriz">
                    <label class="form-check-label" for="checkMatriz">
                        Casa Matriz <small class="text-muted">(solo puede haber una)</small>
                    </label>
                </div>
            </div>
        </div>
    </v-modal-form>
</div>
@endsection