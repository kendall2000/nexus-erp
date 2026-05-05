@extends('layouts.app')
@section('breadcrumb', 'Cuentas Contables')

@section('content')
<div id="cuentas-contables-app" v-cloak>

    {{-- Encabezado --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 text-900 fw-bold">
                <span data-feather="book" class="me-2 text-primary"></span>
                Cuentas Contables
            </h4>
            <p class="text-700 mb-0 fs--1">
                Plan de cuentas con jerarquía padre-hija
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-success btn-sm" @click="abrirModalImport">
                <span data-feather="upload" class="me-1" style="width:14px"></span>
                Importar Excel
            </button>
            <button class="btn btn-outline-info btn-sm" @click="vista = vista === 'arbol' ? 'tabla' : 'arbol'">
                <span :data-feather="vista === 'arbol' ? 'list' : 'git-branch'"
                      class="me-1" style="width:14px"></span>
                @{{ vista === 'arbol' ? 'Vista Tabla' : 'Vista Árbol' }}
            </button>
            <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
                <span data-feather="plus" class="me-1" style="width:14px"></span>
                Nueva Cuenta
            </button>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         VISTA ÁRBOL
    ════════════════════════════════════════════════════════ --}}
    <div v-if="vista === 'arbol'" class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">
                <span data-feather="git-branch" class="me-2 text-primary"
                      style="width:18px;height:18px"></span>
                Plan de Cuentas Jerárquico
            </h6>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-link text-700" @click="expandirTodos(true)">
                    <span data-feather="chevrons-down" style="width:13px"></span> Expandir
                </button>
                <button class="btn btn-sm btn-link text-700" @click="expandirTodos(false)">
                    <span data-feather="chevrons-up" style="width:13px"></span> Colapsar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="cargandoArbol" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm me-2"></div>
                Cargando árbol...
            </div>
            <div v-else-if="arbol.length === 0" class="text-center py-5 text-muted">
                <span data-feather="inbox" style="width:40px;opacity:.4"></span>
                <p class="mt-2 mb-0">No hay cuentas registradas</p>
            </div>
            <ul v-else class="list-unstyled mb-0">
                <nodo-cuenta v-for="cuenta in arbol"
                             :key="cuenta.id_cuenta"
                             :cuenta="cuenta"
                             :nodos-abiertos="nodosAbiertos"
                             @editar="abrirModalEditar"
                             @eliminar="eliminarRegistro"
                             @toggle-estado="toggleEstado"
                             @toggle-nodo="toggleNodo">
                </nodo-cuenta>
            </ul>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         VISTA TABLA
    ════════════════════════════════════════════════════════ --}}
    <div v-if="vista === 'tabla'">
        <v-smart-table
            title="Cuentas Contables"
            :data="cuentas"
            :columns="columnas"
            :loading="cargandoTabla"
            :refreshable="true"
            export-name="cuentas_contables"
            status-key="activo"
            empty-text="No hay cuentas registradas"
            @refresh="cargarTabla"
            @edit="abrirModalEditar"
            @toggle="toggleEstado"
            @delete="eliminarRegistro">
        </v-smart-table>
    </div>

    {{-- ════════════════════════════════════════════════════════
         MODAL CREAR / EDITAR
    ════════════════════════════════════════════════════════ --}}
    <v-modal-form
        v-model="mostrarModal"
        id="modal-cuenta"
        :title="modoEditar ? 'Editar Cuenta Contable' : 'Nueva Cuenta Contable'"
        size="modal-lg"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Cuenta'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">

        <div class="row g-3">

            {{-- Código + Nombre --}}
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    Código <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control"
                       v-model="form.codigo" maxlength="20"
                       placeholder="Ej: 1.01.001"
                       :class="{'is-invalid': errores.codigo}">
                <div class="invalid-feedback">@{{ errores.codigo }}</div>
            </div>

            <div class="col-md-9">
                <label class="form-label fw-bold">
                    Nombre <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control"
                       v-model="form.nombre" maxlength="200"
                       placeholder="Ej: Caja General"
                       :class="{'is-invalid': errores.nombre}">
                <div class="invalid-feedback">@{{ errores.nombre }}</div>
            </div>

            {{-- Tipo + Naturaleza --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Tipo <span class="text-danger">*</span>
                </label>
                <select class="form-select" v-model="form.tipo"
                        :class="{'is-invalid': errores.tipo}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="t in tipos" :key="t" :value="t">@{{ t }}</option>
                </select>
                <div class="invalid-feedback">@{{ errores.tipo }}</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Naturaleza <span class="text-danger">*</span>
                </label>
                <select class="form-select" v-model="form.naturaleza"
                        :class="{'is-invalid': errores.naturaleza}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="n in naturalezas" :key="n" :value="n">@{{ n }}</option>
                </select>
                <div class="invalid-feedback">@{{ errores.naturaleza }}</div>
            </div>

            {{-- Cuenta Padre --}}
            <div class="col-12">
                <label class="form-label fw-bold">Cuenta Padre (opcional)</label>
                <select class="form-select" v-model="form.id_padre">
                    <option :value="null">— Cuenta raíz —</option>
                    <option v-for="c in cuentasParaPadre" :key="c.id" :value="c.id">
                        @{{ c.name }}
                    </option>
                </select>
                <small class="text-muted">
                    Si se selecciona padre, esta cuenta será una subcuenta. Su nivel se calcula automáticamente.
                </small>
            </div>

            {{-- Permite movimiento + Activo --}}
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                           id="permite-mov" v-model="form.permite_movimiento">
                    <label class="form-check-label fw-bold" for="permite-mov">
                        Permite movimientos
                    </label>
                </div>
                <small class="text-muted">
                    Solo las cuentas hoja deben permitir movimientos directos.
                </small>
            </div>

            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                           id="cuenta-activa" v-model="form.activo">
                    <label class="form-check-label fw-bold" for="cuenta-activa">
                        Cuenta activa
                    </label>
                </div>
            </div>
        </div>
    </v-modal-form>

    {{-- ════════════════════════════════════════════════════════
         MODAL IMPORTAR EXCEL
    ════════════════════════════════════════════════════════ --}}
    <v-modal-form
        v-model="mostrarModalImport"
        id="modal-import"
        title="Importar Cuentas desde Excel/CSV"
        size="modal-lg"
        :btn-text="paso === 'preview' ? 'Confirmar Importación' : 'Cargar Archivo'"
        btn-class="btn-success"
        :loading="importando"
        :ocultar-boton-accion="paso === 'inicio' && !filasArchivo.length"
        @action="paso === 'preview' ? confirmarImport() : null">

        {{-- Paso 1: Subir archivo --}}
        <div v-if="paso === 'inicio'">
            <div class="alert alert-soft-info mb-3">
                <strong>Formato esperado:</strong> archivo Excel o CSV con las columnas:
                <code>codigo, nombre, tipo, naturaleza, codigo_padre, permite_movimiento</code>.<br>
                <small>Las columnas deben estar en la primera fila como encabezados.</small>
            </div>

            <div class="text-center py-4 border border-2 border-dashed rounded">
                <span data-feather="file-plus" style="width:48px;opacity:.4"></span>
                <p class="mt-2 mb-3 text-muted">Selecciona el archivo a importar</p>
                <input type="file" ref="fileInput"
                       accept=".xlsx,.xls,.csv"
                       @change="procesarArchivo"
                       class="form-control" style="max-width:400px;margin:0 auto">
            </div>

            <div class="mt-3">
                <a href="#" @click.prevent="descargarPlantilla" class="text-primary">
                    <span data-feather="download" style="width:14px"></span>
                    Descargar plantilla de ejemplo
                </a>
            </div>
        </div>

        {{-- Paso 2: Preview --}}
        <div v-if="paso === 'preview'">
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card bg-soft-info">
                        <div class="card-body py-2 text-center">
                            <h6 class="mb-0 fs--1">Total filas</h6>
                            <h4 class="mb-0">@{{ analisis.total }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-soft-success">
                        <div class="card-body py-2 text-center">
                            <h6 class="mb-0 fs--1">Nuevas</h6>
                            <h4 class="mb-0 text-success">@{{ analisis.nuevas }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-soft-warning">
                        <div class="card-body py-2 text-center">
                            <h6 class="mb-0 fs--1">A actualizar</h6>
                            <h4 class="mb-0 text-warning">@{{ analisis.actualizar }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-soft-danger">
                        <div class="card-body py-2 text-center">
                            <h6 class="mb-0 fs--1">Errores</h6>
                            <h4 class="mb-0 text-danger">@{{ analisis.errores.length }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Errores --}}
            <div v-if="analisis.errores.length > 0" class="alert alert-soft-danger">
                <strong>Errores encontrados:</strong>
                <ul class="mb-0 mt-2 small">
                    <li v-for="e in analisis.errores.slice(0, 10)" :key="e.linea">
                        <strong>Línea @{{ e.linea }}</strong> (@{{ e.codigo }}):
                        @{{ e.errores.join(', ') }}
                    </li>
                </ul>
                <small v-if="analisis.errores.length > 10" class="text-muted">
                    ... y @{{ analisis.errores.length - 10 }} errores más
                </small>
            </div>

            {{-- Tabla preview --}}
            <div class="table-responsive" style="max-height:300px">
                <table class="table table-sm table-bordered">
                    <thead class="bg-100 sticky-top">
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Naturaleza</th>
                            <th>Padre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(c, idx) in filasArchivo.slice(0, 50)" :key="idx">
                            <td>@{{ idx + 1 }}</td>
                            <td>@{{ c.codigo }}</td>
                            <td>@{{ c.nombre }}</td>
                            <td>@{{ c.tipo }}</td>
                            <td>@{{ c.naturaleza }}</td>
                            <td>@{{ c.codigo_padre || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
                <small v-if="filasArchivo.length > 50" class="text-muted">
                    Mostrando primeras 50 de @{{ filasArchivo.length }} filas...
                </small>
            </div>

            <button class="btn btn-link btn-sm mt-2 text-700" @click="paso = 'inicio'">
                <span data-feather="arrow-left" style="width:13px"></span> Cargar otro archivo
            </button>
        </div>
    </v-modal-form>

</div>

{{-- ════════════════════════════════════════════════════════
     COMPONENTE INLINE: NodoCuenta (recursivo)
═══════════════════════════════════════════════════════ --}}
<template id="tpl-nodo-cuenta">
    <li class="border-bottom py-2">
        <div class="d-flex align-items-center" :style="`padding-left:${(cuenta.nivel - 1) * 24}px`">

            {{-- Toggle expand/collapse --}}
            <button v-if="cuenta.hijas && cuenta.hijas.length"
                    class="btn btn-sm btn-link p-0 me-2"
                    @click="$emit('toggle-nodo', cuenta.id_cuenta)">
                <feather-icon :name="abierto ? 'chevron-down' : 'chevron-right'" :size="16"></feather-icon>
            </button>
            <span v-else class="me-2" style="width:20px;display:inline-block"></span>

            {{-- Icono según tipo --}}
            <feather-icon :name="iconoTipo" :size="14" class="me-2 text-muted"></feather-icon>

            {{-- Código + nombre --}}
            <span class="fw-bold me-2">@{{ cuenta.codigo }}</span>
            <span class="me-2">@{{ cuenta.nombre }}</span>

            {{-- Badges --}}
            <span class="badge me-1" :class="badgeTipo">@{{ cuenta.tipo }}</span>
            <span class="badge badge-soft-secondary me-1">Nivel @{{ cuenta.nivel }}</span>
            <span v-if="!cuenta.permite_movimiento" class="badge badge-soft-info me-1" title="Cuenta de resumen">
                <feather-icon name="folder" :size="10"></feather-icon> Resumen
            </span>
            <span v-if="!cuenta.activo" class="badge badge-soft-danger me-1">Inactiva</span>

            {{-- Acciones --}}
            <div class="ms-auto d-flex gap-1">
                <button class="btn btn-sm btn-link text-primary p-1"
                        @click="$emit('editar', cuenta)" title="Editar">
                    <feather-icon name="edit-2" :size="14"></feather-icon>
                </button>
                <button class="btn btn-sm btn-link p-1"
                        :class="cuenta.activo ? 'text-warning' : 'text-success'"
                        @click="$emit('toggle-estado', cuenta)"
                        :title="cuenta.activo ? 'Desactivar' : 'Activar'">
                    <feather-icon :name="cuenta.activo ? 'eye-off' : 'eye'" :size="14"></feather-icon>
                </button>
                <button class="btn btn-sm btn-link text-danger p-1"
                        @click="$emit('eliminar', cuenta)" title="Eliminar">
                    <feather-icon name="trash-2" :size="14"></feather-icon>
                </button>
            </div>
        </div>

        {{-- Hijas (recursivo) --}}
        <ul v-if="abierto && cuenta.hijas && cuenta.hijas.length"
            class="list-unstyled mb-0 mt-1">
            <nodo-cuenta v-for="hija in cuenta.hijas"
                         :key="hija.id_cuenta"
                         :cuenta="hija"
                         :nodos-abiertos="nodosAbiertos"
                         @editar="$emit('editar', $event)"
                         @eliminar="$emit('eliminar', $event)"
                         @toggle-estado="$emit('toggle-estado', $event)"
                         @toggle-nodo="$emit('toggle-nodo', $event)">
            </nodo-cuenta>
        </ul>
    </li>
</template>
@endsection