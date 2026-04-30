@extends('layouts.app')

@section('breadcrumb', 'Geografía')

@section('content')
<div id="geografia-app" v-cloak>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="globe" class="me-2"></span>
                Geografía
            </h4>
            <p class="text-700 mb-0 fs--1">
                Administra países, departamentos y municipios
            </p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>
            Agregar Nuevo
        </button>
    </div>

    {{-- Pestañas --}}
    <ul class="nav nav-underline mb-3">
        <li class="nav-item">
            <button class="nav-link fw-bold px-3"
                    :class="{ active: pestana === 'paises' }"
                    @click="cambiarPestana('paises')">
                <i class="fas fa-flag me-2"></i>Países
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold px-3"
                    :class="{ active: pestana === 'divisiones' }"
                    @click="cambiarPestana('divisiones')">
                <i class="fas fa-map me-2"></i>Departamentos
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold px-3"
                    :class="{ active: pestana === 'municipios' }"
                    @click="cambiarPestana('municipios')">
                <i class="fas fa-map-marker-alt me-2"></i>Municipios
            </button>
        </li>
    </ul>

    {{-- ── TABLA PAÍSES ── --}}
    <v-smart-table
        v-if="pestana === 'paises'"
        title="Países Registrados"
        :data="paises"
        :columns="columnasPaises"
        :loading="cargando"
        :refreshable="true"
        export-name="paises"
        status-key="activo"
        empty-text="No hay países registrados"
        @refresh="cargarPaises"
        @edit="abrirModalEditarPais"
        @toggle="togglePais"
        @delete="eliminarPais">
    </v-smart-table>

    {{-- ── TABLA DIVISIONES ── --}}
    <v-smart-table
        v-if="pestana === 'divisiones'"
        title="Departamentos Registrados"
        :data="divisiones"
        :columns="columnasDivisiones"
        :loading="cargando"
        :refreshable="true"
        export-name="departamentos"
        status-key="activo"
        empty-text="No hay departamentos registrados"
        @refresh="cargarDivisiones"
        @edit="abrirModalEditarDivision"
        @toggle="toggleDivision"
        @delete="eliminarDivision">
    </v-smart-table>

    {{-- ── TABLA MUNICIPIOS ── --}}
    <v-smart-table
        v-if="pestana === 'municipios'"
        title="Municipios Registrados"
        :data="municipios"
        :columns="columnasMunicipios"
        :loading="cargando"
        :refreshable="true"
        export-name="municipios"
        status-key="activo"
        empty-text="No hay municipios registrados"
        @refresh="cargarMunicipios"
        @edit="abrirModalEditarMunicipio"
        @toggle="toggleMunicipio"
        @delete="eliminarMunicipio">
    </v-smart-table>

    {{-- ══════════════════════════════════════════════════════════
         MODAL DINÁMICO según pestaña
    ══════════════════════════════════════════════════════════ --}}
    <v-modal-form
        v-model="mostrarModal"
        id="modal-geografia"
        :title="tituloModal"
        size="modal-md"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardar">

        {{-- ── FORM PAÍS ── --}}
        <div v-if="pestana === 'paises'" class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Código ISO</label>
                <input type="text" class="form-control" maxlength="2"
                       v-model="formPais.codigo_iso"
                       placeholder="GT, SV, HN..."
                       :class="{'is-invalid': errores.codigo_iso}" />
                <div class="invalid-feedback">@{{ errores.codigo_iso }}</div>
            </div>
            <div class="col-md-8">
                <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control"
                       v-model="formPais.nombre"
                       placeholder="Ej: Guatemala"
                       :class="{'is-invalid': errores.nombre}" />
                <div class="invalid-feedback">@{{ errores.nombre }}</div>
            </div>
        </div>

        {{-- ── FORM DIVISIÓN ── --}}
        <div v-if="pestana === 'divisiones'" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">País <span class="text-danger">*</span></label>
                <select class="form-select" v-model="formDivision.id_pais"
                        :class="{'is-invalid': errores.id_pais}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="p in paisesActivos" :key="p.id" :value="p.id">@{{ p.name }}</option>
                </select>
                <div class="invalid-feedback">@{{ errores.id_pais }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Tipo</label>
                <input type="text" class="form-control"
                       v-model="formDivision.tipo"
                       placeholder="DEPARTAMENTO, ESTADO, PROVINCIA..." />
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control"
                       v-model="formDivision.nombre"
                       placeholder="Ej: Guatemala, Sacatepéquez"
                       :class="{'is-invalid': errores.nombre}" />
                <div class="invalid-feedback">@{{ errores.nombre }}</div>
            </div>
        </div>

        {{-- ── FORM MUNICIPIO ── --}}
        <div v-if="pestana === 'municipios'" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">País <span class="text-danger">*</span></label>
                <select class="form-select" v-model="formMunicipio.id_pais" @change="onPaisMunicipioChange">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="p in paisesActivos" :key="p.id" :value="p.id">@{{ p.name }}</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Departamento <span class="text-danger">*</span></label>
                <select class="form-select" v-model="formMunicipio.id_division"
                        :disabled="!formMunicipio.id_pais"
                        :class="{'is-invalid': errores.id_division}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="d in divisionesActivas" :key="d.id" :value="d.id">@{{ d.name }}</option>
                </select>
                <div class="invalid-feedback">@{{ errores.id_division }}</div>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control"
                       v-model="formMunicipio.nombre"
                       placeholder="Ej: Mixco, Villa Nueva"
                       :class="{'is-invalid': errores.nombre}" />
                <div class="invalid-feedback">@{{ errores.nombre }}</div>
            </div>
        </div>

    </v-modal-form>
</div>
@endsection