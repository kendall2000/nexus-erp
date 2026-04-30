@extends('layouts.app')

@section('breadcrumb', 'Usuarios')

@section('content')

<div id="usuarios-app" v-cloak>

    {{-- ── Header del módulo ──────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="users" class="me-2"></span>
                Gestión de Usuarios
            </h4>
            <p class="text-700 mb-0 fs--1">Administra los accesos al sistema NexusERP</p>
        </div>
        <button class="btn btn-primary btn-sm px-4"
                @click="abrirModalCrear">
            <span data-feather="plus" class="me-2"></span>
            Nuevo Usuario
        </button>
    </div>

    {{-- ── Tabla de usuarios ──────────────────────────────────── --}}
    <v-smart-table
        title="Usuarios del Sistema"
        :data="usuarios"
        :columns="columnas"
        export-name="usuarios_nexuserp"
        photo-key="avatar_url"
        status-key="activo"
        @edit="abrirModalEditar"
        @delete="eliminarUsuario"
        @toggle="toggleUsuario">
    </v-smart-table>

    {{-- ── Modal Crear / Editar ───────────────────────────────── --}}
    <v-modal-form
        v-model="mostrarModal"
        id="modal-usuario"
        :title="modoEditar ? 'Editar Usuario' : 'Nuevo Usuario'"
        size="modal-lg"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Usuario'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarUsuario">

        <div class="row g-3">

            {{-- Nombre completo --}}
            <div class="col-12">
                <label class="form-label fw-bold">
                    Nombre Completo <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       v-model="form.nombre_completo"
                       placeholder="Ej: Juan Carlos Pérez López"
                       :class="{'is-invalid': errores.nombre_completo}" />
                <div class="invalid-feedback">@{{ errores.nombre_completo }}</div>
            </div>

            {{-- Username --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Usuario <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <span data-feather="at-sign"></span>
                    </span>
                    <input type="text"
                           class="form-control"
                           v-model="form.username"
                           placeholder="Ej: jperez"
                           :class="{'is-invalid': errores.username}" />
                    <div class="invalid-feedback">@{{ errores.username }}</div>
                </div>
            </div>

            {{-- Email --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Correo Electrónico <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <span data-feather="mail"></span>
                    </span>
                    <input type="email"
                           class="form-control"
                           v-model="form.email"
                           placeholder="correo@empresa.com"
                           :class="{'is-invalid': errores.email}" />
                    <div class="invalid-feedback">@{{ errores.email }}</div>
                </div>
            </div>

            {{-- Contraseña (solo al crear) --}}
            <div class="col-md-6" v-if="!modoEditar">
                <label class="form-label fw-bold">
                    Contraseña <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <span data-feather="lock"></span>
                    </span>
                    <input :type="mostrarPass ? 'text' : 'password'"
                           class="form-control"
                           v-model="form.password"
                           placeholder="Mínimo 8 caracteres"
                           :class="{'is-invalid': errores.password}" />
                    <button class="btn btn-outline-secondary"
                            type="button"
                            @click="mostrarPass = !mostrarPass">
                        <span :data-feather="mostrarPass ? 'eye-off' : 'eye'"></span>
                    </button>
                    <div class="invalid-feedback">@{{ errores.password }}</div>
                </div>
            </div>

            {{-- Sucursal --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">Sucursal</label>
                <select3
                    v-model="form.id_sucursal"
                    :options="catalogos.sucursales"
                    placeholder="Seleccione una sucursal"
                    value-field="id"
                    label-field="name">
                </select3>
            </div>

            {{-- Rol --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">Rol del sistema</label>
                <select3
                    v-model="form.id_rol"
                    :options="catalogos.roles"
                    placeholder="Seleccione un rol"
                    value-field="id"
                    label-field="name">
                </select3>
            </div>

            {{-- Avatar --}}
            <div class="col-12" v-if="modoEditar">
                <label class="form-label fw-bold">Foto de perfil</label>
                <upload-file-s3
                    v-model="form.avatar_url"
                    carpeta="NexusERP/Usuarios"
                    :multiple="false"
                    formatos=".jpg,.png,.jpeg"
                    :max="2">
                </upload-file-s3>
            </div>

        </div>
    </v-modal-form>

    {{-- ── Modal Reset Password ────────────────────────────────── --}}
    <v-modal-form
        v-model="mostrarModalReset"
        id="modal-reset-password"
        title="Restablecer Contraseña"
        size="modal-md"
        btn-text="Restablecer"
        btn-class="btn-warning"
        :loading="guardando"
        @action="resetearPassword">

        <div class="alert alert-warning d-flex align-items-center mb-3">
            <span data-feather="alert-triangle" class="me-2 flex-shrink-0"></span>
            <span>
                Se restablecerá la contraseña de
                <strong>@{{ usuarioSeleccionado?.nombre_completo }}</strong>.
                Todas sus sesiones activas serán cerradas.
            </span>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">
                Nueva Contraseña <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <input :type="mostrarPassReset ? 'text' : 'password'"
                       class="form-control"
                       v-model="passwordNuevo"
                       placeholder="Mínimo 8 caracteres" />
                <button class="btn btn-outline-secondary"
                        type="button"
                        @click="mostrarPassReset = !mostrarPassReset">
                    <span :data-feather="mostrarPassReset ? 'eye-off' : 'eye'"></span>
                </button>
            </div>
        </div>

    </v-modal-form>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    new Vue({
        el: '#usuarios-app',

        data: {
            // Tabla
            usuarios: [],
            columnas: [
                { key: 'avatar_url',      label: 'Foto'         },
                { key: 'nombre_completo', label: 'Nombre'       },
                { key: 'username',        label: 'Usuario'      },
                { key: 'email',           label: 'Correo'       },
                { key: 'roles',           label: 'Rol'          },
                { key: 'sucursal',        label: 'Sucursal'     },
                { key: 'activo',          label: 'Estado'       },
                { key: 'ultimo_login',    label: 'Último acceso'},
            ],

            // Catálogos para selects
            catalogos: { sucursales: [], roles: [] },

            // Modal crear/editar 
            mostrarModal:   false,
            modoEditar:     false,
            guardando:      false,
            mostrarPass:    false,
            form: {
                id_usuario:      null,
                nombre_completo: '',
                username:        '',
                email:           '',
                password:        '',
                id_sucursal:     null,
                id_rol:          null,
                avatar_url:      '',
            },
            errores: {},

            // Modal reset password
            mostrarModalReset: false,
            usuarioSeleccionado: null,
            passwordNuevo:       '',
            mostrarPassReset:    false,
        },

        mounted: function () {
            this.cargarUsuarios();
            this.cargarCatalogos();
            this.$nextTick(function () {
                if (typeof feather !== 'undefined') feather.replace();
            });
        },

        methods: {

            // ── API ─────────────────────────────────────────────

            async cargarUsuarios() {
                try {
                    var res  = await fetch(apiUrl + '/usuarios');
                    var data = await res.json();
                    if (data.success) this.usuarios = data.data;
                } catch(e) {
                    console.error('Error cargando usuarios:', e);
                }
            },

            async cargarCatalogos() {
                try {
                    var res  = await fetch(apiUrl + '/usuarios/catalogos');
                    var data = await res.json();
                    if (data.success) this.catalogos = data.data;
                } catch(e) {
                    console.error('Error cargando catálogos:', e);
                }
            },

            // ── Modal crear ──────────────────────────────────────

            abrirModalCrear() {
                this.modoEditar     = false;
                this.errores        = {};
                this.mostrarPass    = false;
                this.form = {
                    id_usuario: null, nombre_completo: '',
                    username: '', email: '', password: '',
                    id_sucursal: null, id_rol: null, avatar_url: ''
                };
                this.mostrarModal = true;
                this.$nextTick(() => { if (typeof feather !== 'undefined') feather.replace(); });
            },

            // ── Modal editar ─────────────────────────────────────

            async abrirModalEditar(usuario) {
                try {
                    var res  = await fetch(apiUrl + '/usuarios/' + usuario.id_usuario);
                    var data = await res.json();
                    if (!data.success) return;

                    var u = data.data;
                    this.modoEditar  = true;
                    this.errores     = {};
                    this.form = {
                        id_usuario:      u.id_usuario,
                        nombre_completo: u.nombre_completo,
                        username:        u.username,
                        email:           u.email,
                        password:        '',
                        id_sucursal:     u.id_sucursal,
                        id_rol:          u.roles?.[0] ?? null,
                        avatar_url:      u.avatar_url || '',
                    };
                    this.mostrarModal = true;
                    this.$nextTick(() => { if (typeof feather !== 'undefined') feather.replace(); });
                } catch(e) {
                    Swal.fire('Error', 'No se pudo cargar el usuario.', 'error');
                }
            },

            // ── Guardar ──────────────────────────────────────────

            async guardarUsuario() {
                if (!this.validarFormulario()) return;

                this.guardando = true;
                this.errores   = {};

                try {
                    var url    = this.modoEditar
                        ? apiUrl + '/usuarios/' + this.form.id_usuario
                        : apiUrl + '/usuarios';
                    var method = this.modoEditar ? 'PUT' : 'POST';

                    var res  = await fetch(url, {
                        method:  method,
                        headers: { 'Content-Type': 'application/json' },
                        body:    JSON.stringify(this.form),
                    });
                    var data = await res.json();

                    if (data.success) {
                        Swal.fire({
                            icon:  'success',
                            title: '¡Listo!',
                            text:  data.message,
                            timer: 2000,
                            showConfirmButton: false,
                        });
                        this.mostrarModal = false;
                        await this.cargarUsuarios();
                    } else {
                        // Errores de validación del servidor
                        if (data.errors) this.errores = data.errors;
                        else Swal.fire('Error', data.message || 'No se pudo guardar.', 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'Error de conexión.', 'error');
                } finally {
                    this.guardando = false;
                }
            },

            // ── Toggle activo/inactivo ───────────────────────────

            async toggleUsuario(usuario) {
                var accion = usuario.activo === 'Activo' ? 'desactivar' : 'activar';
                var confirm = await Swal.fire({
                    title:             '¿' + accion.charAt(0).toUpperCase() + accion.slice(1) + ' usuario?',
                    text:              'Usuario: ' + usuario.nombre_completo,
                    icon:              'question',
                    showCancelButton:  true,
                    confirmButtonText: 'Sí, ' + accion,
                    cancelButtonText:  'Cancelar',
                    confirmButtonColor: accion === 'activar' ? '#00d27a' : '#e63757',
                });
                if (!confirm.isConfirmed) return;

                try {
                    var res  = await fetch(apiUrl + '/usuarios/' + usuario.id_usuario + '/toggle', { method: 'PATCH' });
                    var data = await res.json();
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                        await this.cargarUsuarios();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'Error de conexión.', 'error');
                }
            },

            // ── Eliminar ─────────────────────────────────────────

            async eliminarUsuario(usuario) {
                var confirm = await Swal.fire({
                    title:             '¿Eliminar usuario?',
                    html:              '<b>' + usuario.nombre_completo + '</b><br><small class="text-muted">Esta acción no se puede deshacer.</small>',
                    icon:              'warning',
                    showCancelButton:  true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText:  'Cancelar',
                    confirmButtonColor:'#e63757',
                });
                if (!confirm.isConfirmed) return;

                try {
                    var res  = await fetch(apiUrl + '/usuarios/' + usuario.id_usuario, { method: 'DELETE' });
                    var data = await res.json();
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                        await this.cargarUsuarios();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'Error de conexión.', 'error');
                }
            },

            // ── Reset password ───────────────────────────────────

            async resetearPassword() {
                if (!this.passwordNuevo || this.passwordNuevo.length < 8) {
                    Swal.fire('Error', 'La contraseña debe tener al menos 8 caracteres.', 'warning');
                    return;
                }

                this.guardando = true;
                try {
                    var res = await fetch(
                        apiUrl + '/usuarios/' + this.usuarioSeleccionado.id_usuario + '/reset-password',
                        {
                            method:  'PATCH',
                            headers: { 'Content-Type': 'application/json' },
                            body:    JSON.stringify({ password_nuevo: this.passwordNuevo }),
                        }
                    );
                    var data = await res.json();
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: data.message, timer: 2000, showConfirmButton: false });
                        this.mostrarModalReset = false;
                        this.passwordNuevo     = '';
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'Error de conexión.', 'error');
                } finally {
                    this.guardando = false;
                }
            },

            // ── Validación local ─────────────────────────────────

            validarFormulario() {
                this.errores = {};
                if (!this.form.nombre_completo.trim()) this.errores.nombre_completo = 'El nombre es obligatorio.';
                if (!this.form.username.trim())        this.errores.username        = 'El usuario es obligatorio.';
                if (!this.form.email.trim())           this.errores.email           = 'El correo es obligatorio.';
                if (!this.modoEditar && this.form.password.length < 8)
                    this.errores.password = 'Mínimo 8 caracteres.';
                return Object.keys(this.errores).length === 0;
            },
        },
    });

});
</script>
@endpush