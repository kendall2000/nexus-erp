@extends('layouts.app')

@section('breadcrumb', 'Roles y Permisos')

@section('content')

<div id="roles-app" v-cloak>

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="shield" class="me-2"></span>
                Roles y Permisos
            </h4>
            <p class="text-700 mb-0 fs--1">
                Controla qué puede ver y hacer cada perfil en el sistema
            </p>
        </div>
        <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>
            Nuevo Rol
        </button>
    </div>

    {{-- ── Tabla ───────────────────────────────────────────────── --}}
    <v-smart-table
        title="Roles del Sistema"
        :data="roles"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="roles_nexuserp"
        status-key="activo"
        empty-text="No hay roles registrados"
        @refresh="cargarRoles"
        @edit="abrirModalEditar"
        @toggle="toggleRol"
        @delete="eliminarRol"
        @view="abrirModalAccesos">
    </v-smart-table>

    {{-- ════════════════════════════════════════════════════════
         MODAL CREAR / EDITAR ROL
    ════════════════════════════════════════════════════════ --}}
    <v-modal-form
        v-model="mostrarModal"
        id="modal-rol"
        :title="modoEditar ? 'Editar Rol' : 'Nuevo Rol'"
        size="modal-md"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Rol'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRol">

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-bold">
                    Nombre del Rol <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-soft-primary">
                        <span data-feather="shield" class="text-primary"></span>
                    </span>
                    <input type="text"
                           class="form-control"
                           v-model="form.nombre"
                           placeholder="Ej: Administrador, Vendedor, Supervisor"
                           :class="{'is-invalid': errores.nombre}" />
                    <div class="invalid-feedback">@{{ errores.nombre }}</div>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">Descripción</label>
                <textarea class="form-control"
                          v-model="form.descripcion"
                          rows="3"
                          placeholder="Describe qué puede hacer este rol...">
                </textarea>
            </div>
        </div>
    </v-modal-form>

    {{-- ════════════════════════════════════════════════════════
         MODAL ACCESOS — DOS PESTAÑAS: MENÚ + PERMISOS
    ════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-accesos" tabindex="-1"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0">

                {{-- Header --}}
                <div class="modal-header border-0 py-3 px-4"
                     style="background:linear-gradient(135deg,#2941ab,#6366f1);">
                    <div>
                        <h5 class="modal-title text-white fw-bold mb-0">
                            <i class="fas fa-lock-open me-2"></i>
                            Accesos del Rol
                        </h5>
                        <small class="text-white opacity-75">
                            <strong>@{{ rolSeleccionado ? rolSeleccionado.nombre : '' }}</strong>
                            &nbsp;·&nbsp;
                            <span class="badge bg-white text-primary">
                                @{{ menuIdsSeleccionados.length }} menú
                            </span>
                            &nbsp;
                            <span class="badge bg-white text-success">
                                @{{ totalPermisosActivos }} permisos
                            </span>
                        </small>
                    </div>
                    <button type="button" class="btn btn-sm btn-light opacity-75"
                            data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Pestañas --}}
                <div class="border-bottom bg-light-subtle px-4 pt-2">
                    <ul class="nav nav-tabs border-0" id="tabs-accesos">
                        <li class="nav-item">
                            <button class="nav-link px-4 py-2 fw-bold"
                                    :class="{ active: pestanaActiva === 'menu' }"
                                    @click="pestanaActiva = 'menu'">
                                <i class="fas fa-sitemap me-2"></i>
                                Menú
                                <span class="badge ms-2"
                                      :class="menuIdsSeleccionados.length ? 'bg-primary' : 'bg-secondary'">
                                    @{{ menuIdsSeleccionados.length }}
                                </span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link px-4 py-2 fw-bold"
                                    :class="{ active: pestanaActiva === 'permisos' }"
                                    @click="pestanaActiva = 'permisos'">
                                <i class="fas fa-key me-2"></i>
                                Permisos CRUD
                                <span class="badge ms-2"
                                      :class="totalPermisosActivos ? 'bg-success' : 'bg-secondary'">
                                    @{{ totalPermisosActivos }}
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>

                {{-- Acciones rápidas --}}
                <div class="d-flex align-items-center gap-2 p-3 border-bottom bg-white">
                    <small class="text-600 fw-bold me-1">Acciones rápidas:</small>
                    <button class="btn btn-sm btn-outline-success"
                            @click="seleccionarTodo(true)">
                        <i class="fas fa-check-double me-1"></i> Marcar todo
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            @click="seleccionarTodo(false)">
                        <i class="fas fa-times me-1"></i> Desmarcar todo
                    </button>
                    <button v-if="pestanaActiva === 'permisos'"
                            class="btn btn-sm btn-outline-info"
                            @click="soloLectura">
                        <i class="fas fa-eye me-1"></i> Solo lectura
                    </button>
                </div>

                {{-- Body --}}
                <div class="modal-body p-0">

                    <div v-if="cargandoAccesos" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3"></div>
                        <p class="text-muted mb-0">Cargando accesos...</p>
                    </div>

                    <div v-else>

                        {{-- ── PESTAÑA MENÚ ─────────────────────────── --}}
                        <div v-show="pestanaActiva === 'menu'">
                            <div v-for="grupo in gruposMenu"
                                 :key="'gm-'+grupo.id_grupo"
                                 class="border-bottom border-200">

                                <div class="d-flex align-items-center justify-content-between px-4 py-2 bg-soft-primary">
                                    <div class="d-flex align-items-center gap-2">
                                        <i :class="'fas fa-' + (grupo.icono || 'grid') + ' text-primary'"></i>
                                        <span class="fw-bold text-primary fs--1">@{{ grupo.nombre }}</span>
                                        <span class="badge bg-soft-primary text-primary fs--2">
                                            @{{ contarMenuSeleccionados(grupo) }} / @{{ grupo.items.length }}
                                        </span>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-link btn-sm py-0 px-2 text-success fw-bold"
                                                @click="toggleGrupoMenu(grupo, true)">Todo</button>
                                        <span class="text-300">|</span>
                                        <button class="btn btn-link btn-sm py-0 px-2 text-danger fw-bold"
                                                @click="toggleGrupoMenu(grupo, false)">Ninguno</button>
                                    </div>
                                </div>

                                <div class="row g-0">
                                    <div v-for="item in grupo.items"
                                         :key="'im-'+item.id_menu"
                                         class="col-md-6 col-lg-4">
                                        <label class="d-flex align-items-center gap-2 px-4 py-2 border-bottom border-200 h-100"
                                               :class="menuSeleccionado(item.id_menu) ? 'bg-soft-success' : ''"
                                               style="cursor:pointer;">
                                            <input type="checkbox"
                                                   class="form-check-input flex-shrink-0 mt-0"
                                                   :checked="menuSeleccionado(item.id_menu)"
                                                   @change="toggleMenuItem(item.id_menu)"
                                                   style="width:18px;height:18px;" />
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar avatar-s bg-soft-primary rounded-2 d-flex align-items-center justify-content-center"
                                                      style="width:28px;height:28px;">
                                                    <i :class="'fas fa-' + (item.icono || 'chevron-right') + ' text-primary fs--2'"></i>
                                                </span>
                                                <div>
                                                    <span class="fw-semi-bold text-800 fs--1 d-block">@{{ item.nombre }}</span>
                                                    <small class="text-400 fs--2">@{{ item.ruta }}</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div v-if="gruposMenu.length === 0" class="text-center py-5 text-400">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                <span class="fs--1">No hay módulos configurados</span>
                            </div>
                        </div>

                        {{-- ── PESTAÑA PERMISOS ─────────────────────── --}}
                        <div v-show="pestanaActiva === 'permisos'">
                            <div class="table-responsive">
                                <table class="table table-sm fs--1 mb-0">
                                    <thead class="bg-200 text-700 position-sticky top-0">
                                        <tr>
                                            <th class="ps-4 py-3" style="min-width:220px;">Módulo / Permiso</th>
                                            <th class="text-center" style="width:80px;">
                                                <i class="fas fa-eye text-info me-1"></i>Ver
                                            </th>
                                            <th class="text-center" style="width:80px;">
                                                <i class="fas fa-plus text-success me-1"></i>Crear
                                            </th>
                                            <th class="text-center" style="width:80px;">
                                                <i class="fas fa-edit text-warning me-1"></i>Editar
                                            </th>
                                            <th class="text-center" style="width:80px;">
                                                <i class="fas fa-trash text-danger me-1"></i>Eliminar
                                            </th>
                                            <th class="text-center" style="width:80px;">
                                                <i class="fas fa-download text-primary me-1"></i>Exportar
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="modulo in modulosPermiso">

                                            {{-- Cabecera módulo --}}
                                            <tr :key="'mod-'+modulo.id_modulo" class="bg-soft-primary">
                                                <td colspan="6" class="ps-4 py-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i :class="'fas fa-' + (modulo.icono || 'cube') + ' text-primary'"></i>
                                                        <span class="fw-bold text-primary fs--1">@{{ modulo.nombre }}</span>
                                                        <span class="badge bg-soft-primary text-primary fs--2">
                                                            @{{ contarPermisosModulo(modulo) }} activos
                                                        </span>
                                                        <button class="btn btn-link btn-sm py-0 ms-1 text-success fw-bold fs--2"
                                                                @click="toggleModuloPermisos(modulo, true)">Todo</button>
                                                        <button class="btn btn-link btn-sm py-0 text-danger fw-bold fs--2"
                                                                @click="toggleModuloPermisos(modulo, false)">Ninguno</button>
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- Filas de permisos --}}
                                            <tr v-for="permiso in modulo.permisos"
                                                :key="'p-'+permiso.id_permiso"
                                                class="border-bottom border-200">
                                                <td class="ps-5 py-2 text-800">
                                                    <i class="fas fa-chevron-right text-300 me-2 fs--2"></i>
                                                    @{{ permiso.descripcion }}
                                                    <small class="text-400 ms-1 fs--2">(@{{ permiso.codigo }})</small>
                                                </td>
                                                <td class="text-center py-2">
                                                    <input type="checkbox" class="form-check-input"
                                                           v-model="matrizPermisos[permiso.id_permiso].puede_leer" />
                                                </td>
                                                <td class="text-center py-2">
                                                    <input type="checkbox" class="form-check-input"
                                                           v-model="matrizPermisos[permiso.id_permiso].puede_crear" />
                                                </td>
                                                <td class="text-center py-2">
                                                    <input type="checkbox" class="form-check-input"
                                                           v-model="matrizPermisos[permiso.id_permiso].puede_editar" />
                                                </td>
                                                <td class="text-center py-2">
                                                    <input type="checkbox" class="form-check-input"
                                                           v-model="matrizPermisos[permiso.id_permiso].puede_eliminar" />
                                                </td>
                                                <td class="text-center py-2">
                                                    <input type="checkbox" class="form-check-input"
                                                           v-model="matrizPermisos[permiso.id_permiso].puede_exportar" />
                                                </td>
                                            </tr>

                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Footer --}}
                <div class="modal-footer border-top px-4 py-3">
                    <div class="me-auto d-flex gap-2">
                        <span class="badge bg-soft-primary text-primary px-3 py-2 fs--2">
                            <i class="fas fa-sitemap me-1"></i>
                            @{{ menuIdsSeleccionados.length }} menú activo(s)
                        </span>
                        <span class="badge bg-soft-success text-success px-3 py-2 fs--2">
                            <i class="fas fa-key me-1"></i>
                            @{{ totalPermisosActivos }} permiso(s) activo(s)
                        </span>
                    </div>
                    <button type="button"
                            class="btn btn-link text-danger text-decoration-none fw-bold"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="button"
                            class="btn btn-primary px-4 shadow-sm"
                            :disabled="guardandoAccesos"
                            @click="guardarAccesos">
                        <span v-if="guardandoAccesos"
                              class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="fas fa-save me-2"></i>
                        Guardar Todo
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection