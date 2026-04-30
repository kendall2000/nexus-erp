@extends('layouts.app')

@section('breadcrumb', 'Gestión de Bodegas')

@section('content')
<div id="bodegas-app" v-cloak>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="archive" class="me-2"></span>
                Bodegas
            </h4>
            <p class="text-700 mb-0 fs--1">Administra los centros de almacenamiento</p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>
            Nueva Bodega
        </button>
    </div>

    <v-smart-table
        title="Bodegas Registradas"
        :data="bodegas"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="bodegas_nexuserp"
        status-key="activo"
        empty-text="No hay bodegas registradas"
        @refresh="cargarDatos"
        @edit="abrirModalEditar"
        @toggle="toggleEstado"
        @delete="eliminarRegistro">
    </v-smart-table>

    <v-modal-form
        v-model="mostrarModal"
        id="modal-bodega"
        :title="modoEditar ? 'Editar Bodega' : 'Nueva Bodega'"
        size="modal-lg"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Bodega'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">
        
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-bold">
                    Nombre <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" v-model="form.nombre"
                       placeholder="Ej: Bodega Central"
                       :class="{'is-invalid': errores.nombre}" />
                <div class="invalid-feedback">@{{ errores.nombre }}</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Sucursal</label>
                <select class="form-select" v-model="form.id_sucursal">
                    <option :value="null">— Sin sucursal —</option>
                    <option v-for="s in sucursales" :key="s.id" :value="s.id">@{{ s.name }}</option>
                </select>
                <small v-if="sucursales.length === 0" class="text-warning">
                    No hay sucursales activas. <a href="/sistema/sucursales">Crear ahora</a>
                </small>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Responsable</label>
                <select class="form-select" v-model="form.responsable_id">
                    <option :value="null">— Sin asignar —</option>
                    <option v-for="e in empleados" :key="e.id" :value="e.id">@{{ e.name }}</option>
                </select>
                <small v-if="empleados.length === 0" class="text-warning">
                    No hay empleados activos.
                </small>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Ubicación</label>
                <textarea class="form-control" v-model="form.ubicacion" rows="2"
                          placeholder="Dirección física de la bodega"></textarea>
            </div>
        </div>
    </v-modal-form>
</div>
@endsection