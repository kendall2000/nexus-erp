@extends('layouts.app')

@section('breadcrumb', 'Gestión de Menú')

@section('content')

<div id="menu-gestion-app" v-cloak>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="menu" class="me-2"></span>
                Gestión de Menú
            </h4>
            <p class="text-700 mb-0 fs--1">
                Administra los módulos y grupos del menú lateral
            </p>
        </div>
        <button class="btn btn-primary btn-sm px-4"
                data-bs-toggle="modal"
                data-bs-target="#modal-agregar-menu"
                @click="prepararModal">
            <span data-feather="plus" class="me-2"></span>
            Agregar Nuevo Módulo
        </button>
    </div>

    <v-smart-table
        title="Ítems del Menú"
        :data="items"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="menu_nexuserp"
        status-key="activo"
        empty-text="No hay ítems en el menú"
        @refresh="cargarItems"
        @toggle="toggleItem"
        @delete="eliminarItem">
    </v-smart-table>

    {{-- ══════════════════════════════════════════════════════════
         MODAL CON 3 PESTAÑAS — Categoría, Item, Módulo
    ══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-agregar-menu" tabindex="-1"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-sitemap me-2 text-primary"></i>
                        Agregar Nuevo Módulo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="px-4 pt-3">
                    <ul class="nav nav-underline" id="tabs-menu">
                        <li class="nav-item">
                            <button class="nav-link fw-bold px-3"
                                    :class="{ active: pestana === 'categoria' }"
                                    @click="pestana = 'categoria'">
                                <i class="fas fa-folder me-2"></i>Categoría
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold px-3"
                                    :class="{ active: pestana === 'item' }"
                                    @click="pestana = 'item'">
                                <i class="fas fa-list me-2"></i>Menu Item
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold px-3"
                                    :class="{ active: pestana === 'modulo' }"
                                    @click="pestana = 'modulo'">
                                <i class="fas fa-cube me-2"></i>Módulo
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body px-4 pt-3">

                    {{-- ─── PESTAÑA 1: CATEGORÍA (sin ruta) ─── --}}
                    <div v-show="pestana === 'categoria'">
                        <p class="text-muted fs--1 mb-3">
                            Una categoría es un grupo del menú como <strong>CRM</strong>,
                            <strong>ERP</strong> o <strong>Inventario</strong>.
                            No tiene ruta, solo agrupa ítems.
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Nombre de la Categoría <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   v-model="formaCategoria.nombre"
                                   placeholder="Ej: Logística, Proyectos, RRHH" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Orden</label>
                            <input type="number" class="form-control"
                                   v-model.number="formaCategoria.orden"
                                   min="1" max="255" style="max-width:100px;" />
                        </div>
                    </div>

                    {{-- ─── PESTAÑA 2: MENU ITEM (ruta opcional vía toggle) ─── --}}
                    <div v-show="pestana === 'item'">
                        <p class="text-muted fs--1 mb-3">
                            Un menu item es una entrada del sidebar con icono,
                            hijo de una categoría. Por defecto sin ruta.
                            Activa el switch si quieres asignarle una ruta directa.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Nombre del Item <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control"
                                       v-model="formaItem.nombre"
                                       placeholder="Ej: Clientes, Empleados" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Categoría padre <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" v-model="formaItem.id_padre">
                                    <option value="">Seleccione una categoría</option>
                                    <option v-for="cat in categorias"
                                            :key="cat.id_menu"
                                            :value="cat.id_menu">
                                        @{{ cat.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Icono Feather</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i :data-feather="formaItem.icono || 'chevrons-right'"
                                           style="width:16px;height:16px;"></i>
                                    </span>
                                    <input type="text" class="form-control"
                                           v-model="formaItem.icono"
                                           placeholder="home, users, briefcase..."
                                           @input="reemplazarIconos" />
                                </div>
                                <small>
                                    <a href="https://feathericons.com" target="_blank">Ver iconos disponibles</a>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Orden</label>
                                <input type="number" class="form-control"
                                       v-model.number="formaItem.orden"
                                       min="1" max="255" />
                            </div>

                            {{-- ── Toggle: ruta opcional ── --}}
                            <div class="col-12 border-top pt-3 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           v-model="formaItem.tieneRuta" id="switchRutaItem">
                                    <label class="form-check-label fw-bold" for="switchRutaItem">
                                        Asignar ruta directa a este item
                                    </label>
                                </div>
                            </div>
                            <div class="col-12" v-if="formaItem.tieneRuta">
                                <label class="form-label fw-bold">
                                    Ruta de acceso <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text text-muted">/sistema/</span>
                                    <input type="text" class="form-control"
                                           v-model="formaItem.rutaSufijo"
                                           placeholder="dashboard, reportes..." />
                                </div>
                                <small class="text-muted">
                                    Ruta completa: <code>/sistema/@{{ formaItem.rutaSufijo }}</code>
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- ─── PESTAÑA 3: MÓDULO (ruta obligatoria) ─── --}}
                    <div v-show="pestana === 'modulo'">
                        <p class="text-muted fs--1 mb-3">
                            Un módulo es la ruta final del sistema, como
                            <code>/sistema/clientes</code>.
                            Va dentro de un menu item existente.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Nombre del Módulo <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control"
                                       v-model="formaModulo.nombre"
                                       placeholder="Ej: Lista de Clientes" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Menu Item padre <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" v-model="formaModulo.id_padre">
                                    <option value="">Seleccione un menu item</option>
                                    <optgroup v-for="cat in categorias"
                                              :key="cat.id_menu"
                                              :label="cat.nombre">
                                        <option v-for="item in cat.hijos"
                                                :key="item.id_menu"
                                                :value="item.id_menu">
                                            @{{ item.nombre }}
                                        </option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    Ruta de acceso <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text text-muted">/sistema/</span>
                                    <input type="text" class="form-control"
                                           v-model="formaModulo.rutaSufijo"
                                           placeholder="clientes, empleados, facturas..." />
                                </div>
                                <small class="text-muted">
                                    Ruta completa: <code>/sistema/@{{ formaModulo.rutaSufijo }}</code>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Orden</label>
                                <input type="number" class="form-control"
                                       v-model.number="formaModulo.orden"
                                       min="1" max="255" />
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-top-0">
                    <button type="button"
                            class="btn btn-link text-danger fw-bold text-decoration-none"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button"
                            class="btn btn-primary px-4"
                            :disabled="guardando"
                            @click="guardar">
                        <span v-if="guardando"
                              class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="fas fa-plus me-2"></i>
                        Agregar
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection