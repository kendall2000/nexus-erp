@extends('layouts.app')
@section('breadcrumb', 'Centros de Costo')

@section('content')
<div id="centros-costo-app" v-cloak>

    {{-- Encabezado --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 text-900 fw-bold">
                <span data-feather="layers" class="me-2 text-primary"></span>
                Centros de Costo
            </h4>
            <p class="text-700 mb-0 fs--1">
                Unidades organizativas para asignar gastos e ingresos
            </p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-1" style="width:14px"></span>
            Nuevo Centro de Costo
        </button>
    </div>

    {{-- Tabla principal --}}
    <v-smart-table
        title="Centros de Costo Registrados"
        :data="centros"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="centros_costo"
        status-key="activo"
        empty-text="No hay centros de costo registrados"
        @refresh="cargarDatos"
        @edit="abrirModalEditar"
        @toggle="toggleEstado"
        @delete="eliminarRegistro">
    </v-smart-table>

    {{-- ════════════════════════════════════════════════════════
         MODAL CREAR / EDITAR
    ════════════════════════════════════════════════════════ --}}
    <v-modal-form
        v-model="mostrarModal"
        id="modal-centro-costo"
        :title="modoEditar ? 'Editar Centro de Costo' : 'Nuevo Centro de Costo'"
        size="modal-md"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Centro'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">

        <div class="row g-3">
            {{-- Código --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">
                    Código <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control text-uppercase"
                       v-model="form.codigo"
                       maxlength="20"
                       placeholder="Ej: ADM-01"
                       :class="{'is-invalid': errores.codigo}">
                <div class="invalid-feedback">@{{ errores.codigo }}</div>
                <small class="text-muted">Identificador único interno.</small>
            </div>

            {{-- Nombre --}}
            <div class="col-md-8">
                <label class="form-label fw-bold">
                    Nombre <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control"
                       v-model="form.nombre"
                       maxlength="150"
                       placeholder="Ej: Administración Central"
                       :class="{'is-invalid': errores.nombre}">
                <div class="invalid-feedback">@{{ errores.nombre }}</div>
            </div>

            {{-- Descripción --}}
            <div class="col-12">
                <label class="form-label fw-bold">Descripción</label>
                <textarea class="form-control"
                          v-model="form.descripcion"
                          maxlength="300"
                          rows="3"
                          placeholder="Descripción opcional del centro de costo..."></textarea>
                <small class="text-muted">Máximo 300 caracteres.</small>
            </div>

            {{-- Estado --}}
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                           id="activo-switch" v-model="form.activo">
                    <label class="form-check-label fw-bold" for="activo-switch">
                        Centro activo
                    </label>
                </div>
                <small class="text-muted">
                    Los centros inactivos no aparecen en los selects de presupuesto.
                </small>
            </div>
        </div>
    </v-modal-form>

</div>
@endsection