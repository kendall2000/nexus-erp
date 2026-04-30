@extends('layouts.app')

@section('breadcrumb', 'Categorías de Productos')

@section('content')
<div id="categorias-app" v-cloak>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="tag" class="me-2"></span>
                Categorías de Productos
            </h4>
            <p class="text-700 mb-0 fs--1">Organiza tu catálogo de productos por categorías</p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>
            Nueva Categoría
        </button>
    </div>

    <v-smart-table
        title="Categorías Registradas"
        :data="categorias"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="categorias_nexuserp"
        status-key="activo"
        empty-text="No hay categorías registradas"
        @refresh="cargarDatos"
        @edit="abrirModalEditar"
        @toggle="toggleEstado"
        @delete="eliminarRegistro">
    </v-smart-table>

    <v-modal-form
        v-model="mostrarModal"
        id="modal-categoria"
        :title="modoEditar ? 'Editar Categoría' : 'Nueva Categoría'"
        size="modal-md"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Categoría'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">
        
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-bold">
                    Nombre <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" v-model="form.nombre"
                       placeholder="Ej: Productos de Limpieza"
                       :class="{'is-invalid': errores.nombre}" />
                <div class="invalid-feedback">@{{ errores.nombre }}</div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Categoría Padre (opcional)</label>
                <select class="form-select" v-model="form.id_padre">
                    <option :value="null">— Categoría raíz —</option>
                    <option v-for="p in padres"
                            :key="p.id"
                            :value="p.id"
                            :disabled="modoEditar && p.id === form.id_categoria">
                        @{{ p.name }}
                    </option>
                </select>
                <small class="text-muted">
                    Déjala vacía si será una categoría principal.
                </small>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Descripción</label>
                <textarea class="form-control" v-model="form.descripcion" rows="2"
                          placeholder="Detalle opcional de la categoría"></textarea>
            </div>
        </div>
    </v-modal-form>
</div>
@endsection