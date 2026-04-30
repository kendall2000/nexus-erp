@extends('layouts.app')
@section('breadcrumb', 'Catálogo de Productos')
@section('content')
<div id="productos-app" v-cloak>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h4 class="mb-1 text-900"><span data-feather="package" class="me-2"></span>Productos</h4></div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">Nuevo Producto</button>
    </div>

    <v-smart-table
        title="Inventario Maestro" :data="productos" :columns="columnas"
        :loading="cargandoTabla" :refreshable="true" status-key="activo"
        @refresh="cargarDatos" @edit="abrirModalEditar" @toggle="toggleEstado" @delete="eliminarRegistro">
    </v-smart-table>

    <v-modal-form
        v-model="mostrarModal" id="modal-producto" :title="modoEditar ? 'Editar Producto' : 'Nuevo Producto'" 
        size="modal-lg" btn-class="btn-primary" :loading="guardando" @action="guardarRegistro">
        
        <div class="row g-3">
            {{-- Código y nombre --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">Código SKU <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="form.codigo" :class="{'is-invalid': errores.codigo}">
                <div class="invalid-feedback">@{{ errores.codigo }}</div>
            </div>
            <div class="col-md-8">
                <label class="form-label fw-bold">Nombre del Producto <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="form.nombre" :class="{'is-invalid': errores.nombre}">
                <div class="invalid-feedback">@{{ errores.nombre }}</div>
            </div>

            {{-- ── Categoría ── NUEVO ── --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">Categoría</label>
                <select class="form-select" v-model="form.id_categoria">
                    <option :value="null">— Sin Categoría —</option>
                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                        @{{ cat.name }}
                    </option>
                </select>
                <small v-if="categorias.length === 0" class="text-warning">
                    <i data-feather="alert-triangle" style="width:12px;height:12px;"></i>
                    No hay categorías activas. <a href="/sistema/categorias">Crear ahora</a>
                </small>
            </div>

            {{-- Unidad de medida (ahora dinámico desde catálogos) --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">Unidad de Medida</label>
                <select class="form-select" v-model="form.unidad_medida">
                    <option v-for="u in unidades" :key="u" :value="u">@{{ u }}</option>
                </select>
            </div>

            {{-- Precios --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">Precio Compra</label>
                <input type="number" step="0.01" class="form-control" v-model="form.precio_compra">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Precio Venta</label>
                <input type="number" step="0.01" class="form-control" v-model="form.precio_venta">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Moneda</label>
                <select class="form-select" v-model="form.moneda">
                    <option value="GTQ">GTQ - Quetzal</option>
                    <option value="USD">USD - Dólar</option>
                </select>
            </div>

            {{-- Stock --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">Stock Mínimo</label>
                <input type="number" step="0.01" class="form-control" v-model="form.stock_minimo">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Stock Máximo</label>
                <input type="number" step="0.01" class="form-control" v-model="form.stock_maximo">
            </div>
            
            {{-- Switches --}}
            <div class="col-12 mt-4 border-top pt-3">
                <div class="form-check form-switch form-check-inline">
                    <input class="form-check-input" type="checkbox" v-model="form.requiere_lote" id="checkLote">
                    <label class="form-check-label" for="checkLote">Requiere control de Lote</label>
                </div>
                <div class="form-check form-switch form-check-inline">
                    <input class="form-check-input" type="checkbox" v-model="form.es_perecedero" id="checkPerecedero">
                    <label class="form-check-label" for="checkPerecedero">Es Perecedero (Fechas de caducidad)</label>
                </div>
            </div>
        </div>
    </v-modal-form>
</div>
@endsection