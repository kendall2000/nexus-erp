/**
 * NexusERP — Módulo Roles y Permisos v2
 * Archivo: resources/views/modulos/roles/index.js
 * Dos pestañas: Menú + Permisos CRUD
 */

var modalAccesos = null;

new Vue({
    el: '#roles-app',

    data: {
        // ── Tabla ────────────────────────────────────────────────
        roles:         [],
        cargandoTabla: false,
        columnas: [
            { key: 'nombre',         label: 'Nombre'          },
            { key: 'descripcion',    label: 'Descripción'     },
            { key: 'total_menus',    label: 'Menú'            },
            { key: 'total_usuarios', label: 'Usuarios'        },
            { key: 'activo',         label: 'Estado'          },
        ],

        // ── Modal Crear/Editar ───────────────────────────────────
        mostrarModal: false,
        modoEditar:   false,
        guardando:    false,
        form:         { id_rol: null, nombre: '', descripcion: '' },
        errores:      {},

        // ── Modal Accesos ────────────────────────────────────────
        rolSeleccionado:  null,
        pestanaActiva:    'menu',
        cargandoAccesos:  false,
        guardandoAccesos: false,

        // Pestaña Menú
        gruposMenu:           [],
        menuIdsSeleccionados: [],

        // Pestaña Permisos
        modulosPermiso: [],
        matrizPermisos: {},
    },

    computed: {
        totalPermisosActivos() {
            return Object.values(this.matrizPermisos).filter(function(p) {
                return p.puede_leer || p.puede_crear ||
                       p.puede_editar || p.puede_eliminar || p.puede_exportar;
            }).length;
        }
    },

    mounted: function () {
        this.cargarRoles();
        var el = document.getElementById('modal-accesos');
        if (el) modalAccesos = new bootstrap.Modal(el);
        if (typeof feather !== 'undefined') feather.replace();
    },

    methods: {

        // ════════════════════════════════════════════════════════
        // TABLA DE ROLES
        // ════════════════════════════════════════════════════════

        async cargarRoles() {
            this.cargandoTabla = true;
            try {
                var res  = await fetch(apiUrl + '/roles');
                var data = await res.json();
                if (data.success) this.roles = data.data;
            } catch(e) {
                console.error('Error:', e);
            } finally {
                this.cargandoTabla = false;
            }
        },

        // ════════════════════════════════════════════════════════
        // MODAL CREAR / EDITAR
        // ════════════════════════════════════════════════════════

        abrirModalCrear() {
            this.modoEditar   = false;
            this.errores      = {};
            this.form         = { id_rol: null, nombre: '', descripcion: '' };
            this.mostrarModal = true;
            this.$nextTick(function() {
                if (typeof feather !== 'undefined') feather.replace();
            });
        },

        async abrirModalEditar(rol) {
            try {
                var res  = await fetch(apiUrl + '/roles/' + rol.id_rol);
                var data = await res.json();
                if (!data.success) return;
                this.modoEditar = true;
                this.errores    = {};
                this.form = {
                    id_rol:      data.data.id_rol,
                    nombre:      data.data.nombre,
                    descripcion: data.data.descripcion || '',
                };
                this.mostrarModal = true;
                this.$nextTick(function() {
                    if (typeof feather !== 'undefined') feather.replace();
                });
            } catch(e) {
                Swal.fire('Error', 'No se pudo cargar el rol.', 'error');
            }
        },

        async guardarRol() {
            if (!this.form.nombre.trim()) {
                this.errores = { nombre: 'El nombre es obligatorio.' };
                return;
            }
            this.guardando = true;
            this.errores   = {};
            try {
                var url = this.modoEditar
                    ? apiUrl + '/roles/' + this.form.id_rol
                    : apiUrl + '/roles';
                var res  = await fetch(url, {
                    method:  this.modoEditar ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(this.form),
                });
                var data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 2000, showConfirmButton: false });
                    this.mostrarModal = false;
                    await this.cargarRoles();
                } else {
                    if (data.errors) this.errores = data.errors;
                    else Swal.fire('Error', data.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            } finally {
                this.guardando = false;
            }
        },

        // ════════════════════════════════════════════════════════
        // TOGGLE / ELIMINAR
        // ════════════════════════════════════════════════════════

        async toggleRol(rol) {
            var accion = rol.activo === 'Activo' ? 'desactivar' : 'activar';
            var ok = await Swal.fire({
                title: '¿' + accion.charAt(0).toUpperCase() + accion.slice(1) + ' rol?',
                html:  'Rol: <b>' + rol.nombre + '</b>',
                icon:  'question', showCancelButton: true,
                confirmButtonText: 'Sí, ' + accion,
                confirmButtonColor: accion === 'activar' ? '#00d27a' : '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                var res  = await fetch(apiUrl + '/roles/' + rol.id_rol + '/toggle', { method: 'PATCH' });
                var data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    await this.cargarRoles();
                } else Swal.fire('Aviso', data.message, 'warning');
            } catch(e) { Swal.fire('Error', 'Error de conexión.', 'error'); }
        },

        async eliminarRol(rol) {
            var ok = await Swal.fire({
                title: '¿Eliminar rol?', html: '<b>' + rol.nombre + '</b>',
                icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Sí, eliminar', confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                var res  = await fetch(apiUrl + '/roles/' + rol.id_rol, { method: 'DELETE' });
                var data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    await this.cargarRoles();
                } else Swal.fire('Aviso', data.message, 'warning');
            } catch(e) { Swal.fire('Error', 'Error de conexión.', 'error'); }
        },

        // ════════════════════════════════════════════════════════
        // MODAL ACCESOS — carga ambas pestañas
        // ════════════════════════════════════════════════════════

        async abrirModalAccesos(rol) {
            this.rolSeleccionado      = rol;
            this.pestanaActiva        = 'menu';
            this.gruposMenu           = [];
            this.menuIdsSeleccionados = [];
            this.modulosPermiso       = [];
            this.matrizPermisos       = {};
            this.cargandoAccesos      = true;

            if (modalAccesos) modalAccesos.show();

            try {
                // 1. Catálogo: grupos de menú + módulos con permisos
                var resCat = await fetch(apiUrl + '/roles/catalogos');
                var cat    = await resCat.json();
                this.gruposMenu     = (cat.data && cat.data.grupos)  ? cat.data.grupos  : [];
                this.modulosPermiso = (cat.data && cat.data.modulos) ? cat.data.modulos : [];

                // Inicializa matriz de permisos vacía
                var self = this;
                this.modulosPermiso.forEach(function(mod) {
                    mod.permisos.forEach(function(p) {
                        Vue.set(self.matrizPermisos, p.id_permiso, {
                            id_permiso:     p.id_permiso,
                            puede_leer:     false,
                            puede_crear:    false,
                            puede_editar:   false,
                            puede_eliminar: false,
                            puede_exportar: false,
                        });
                    });
                });

                // 2. Menú ya asignado al rol
                var resMen = await fetch(apiUrl + '/roles/' + rol.id_rol + '/menu');
                var men    = await resMen.json();
                this.menuIdsSeleccionados = men.data
                    ? men.data.map(function(id) { return Number(id); })
                    : [];

                // 3. Permisos ya asignados al rol
                var resPer = await fetch(apiUrl + '/roles/' + rol.id_rol);
                var per    = await resPer.json();
                if (per.success && per.data.permisos) {
                    per.data.permisos.forEach(function(p) {
                        if (self.matrizPermisos[p.id_permiso]) {
                            self.matrizPermisos[p.id_permiso].puede_leer     = p.puede_leer;
                            self.matrizPermisos[p.id_permiso].puede_crear    = p.puede_crear;
                            self.matrizPermisos[p.id_permiso].puede_editar   = p.puede_editar;
                            self.matrizPermisos[p.id_permiso].puede_eliminar = p.puede_eliminar;
                            self.matrizPermisos[p.id_permiso].puede_exportar = p.puede_exportar;
                        }
                    });
                }

            } catch(e) {
                console.error('Error:', e);
                Swal.fire('Error', 'No se pudieron cargar los accesos.', 'error');
            } finally {
                this.cargandoAccesos = false;
                this.$nextTick(function() {
                    if (typeof feather !== 'undefined') feather.replace();
                });
            }
        },

        // ════════════════════════════════════════════════════════
        // HELPERS MENÚ
        // ════════════════════════════════════════════════════════

        menuSeleccionado(idMenu) {
            return this.menuIdsSeleccionados.indexOf(Number(idMenu)) !== -1;
        },

        toggleMenuItem(idMenu) {
            var id  = Number(idMenu);
            var idx = this.menuIdsSeleccionados.indexOf(id);
            if (idx !== -1) this.menuIdsSeleccionados.splice(idx, 1);
            else            this.menuIdsSeleccionados.push(id);
        },

        toggleGrupoMenu(grupo, valor) {
            var self = this;
            grupo.items.forEach(function(item) {
                var id  = Number(item.id_menu);
                var idx = self.menuIdsSeleccionados.indexOf(id);
                if (valor && idx === -1)   self.menuIdsSeleccionados.push(id);
                if (!valor && idx !== -1)  self.menuIdsSeleccionados.splice(idx, 1);
            });
        },

        contarMenuSeleccionados(grupo) {
            var self = this;
            return grupo.items.filter(function(i) {
                return self.menuIdsSeleccionados.indexOf(Number(i.id_menu)) !== -1;
            }).length;
        },

        // ════════════════════════════════════════════════════════
        // HELPERS PERMISOS
        // ════════════════════════════════════════════════════════

        toggleModuloPermisos(modulo, valor) {
            var self = this;
            modulo.permisos.forEach(function(p) {
                if (self.matrizPermisos[p.id_permiso]) {
                    self.matrizPermisos[p.id_permiso].puede_leer     = valor;
                    self.matrizPermisos[p.id_permiso].puede_crear    = valor;
                    self.matrizPermisos[p.id_permiso].puede_editar   = valor;
                    self.matrizPermisos[p.id_permiso].puede_eliminar = valor;
                    self.matrizPermisos[p.id_permiso].puede_exportar = valor;
                }
            });
        },

        contarPermisosModulo(modulo) {
            var self = this;
            return modulo.permisos.filter(function(p) {
                var m = self.matrizPermisos[p.id_permiso];
                return m && (m.puede_leer || m.puede_crear ||
                             m.puede_editar || m.puede_eliminar || m.puede_exportar);
            }).length;
        },

        soloLectura() {
            var self = this;
            Object.keys(this.matrizPermisos).forEach(function(id) {
                self.matrizPermisos[id].puede_leer     = true;
                self.matrizPermisos[id].puede_crear    = false;
                self.matrizPermisos[id].puede_editar   = false;
                self.matrizPermisos[id].puede_eliminar = false;
                self.matrizPermisos[id].puede_exportar = false;
            });
        },

        // ════════════════════════════════════════════════════════
        // ACCIONES RÁPIDAS (aplica a pestaña activa)
        // ════════════════════════════════════════════════════════

        seleccionarTodo(valor) {
            if (this.pestanaActiva === 'menu') {
                if (!valor) {
                    this.menuIdsSeleccionados = [];
                } else {
                    var todos = [];
                    this.gruposMenu.forEach(function(g) {
                        g.items.forEach(function(i) { todos.push(Number(i.id_menu)); });
                    });
                    this.menuIdsSeleccionados = todos;
                }
            } else {
                var self = this;
                Object.keys(this.matrizPermisos).forEach(function(id) {
                    self.matrizPermisos[id].puede_leer     = valor;
                    self.matrizPermisos[id].puede_crear    = valor;
                    self.matrizPermisos[id].puede_editar   = valor;
                    self.matrizPermisos[id].puede_eliminar = valor;
                    self.matrizPermisos[id].puede_exportar = valor;
                });
            }
        },

        // ════════════════════════════════════════════════════════
        // GUARDAR — envía menú Y permisos en paralelo
        // ════════════════════════════════════════════════════════

        async guardarAccesos() {
            this.guardandoAccesos = true;
            try {
                var idRol = this.rolSeleccionado.id_rol;

                // Permisos activos (solo los que tienen al menos una acción)
                var permisosActivos = Object.values(this.matrizPermisos).filter(function(p) {
                    return p.puede_leer || p.puede_crear ||
                           p.puede_editar || p.puede_eliminar || p.puede_exportar;
                });

                // Envía ambas peticiones en paralelo
                var results = await Promise.all([
                    // Guardar menú
                    fetch(apiUrl + '/roles/' + idRol + '/menu', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body:    JSON.stringify({ menu_ids: this.menuIdsSeleccionados }),
                    }),
                    // Guardar permisos
                    fetch(apiUrl + '/roles/' + idRol + '/permisos', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body:    JSON.stringify({ permisos: permisosActivos }),
                    }),
                ]);

                var resMenu    = await results[0].json();
                var resPermisos= await results[1].json();

                if (resMenu.success && resPermisos.success) {
                    Swal.fire({
                        icon:  'success',
                        title: '¡Accesos guardados!',
                        html:  '<small>' + this.menuIdsSeleccionados.length + ' módulos de menú · ' +
                               permisosActivos.length + ' permisos activos</small>',
                        timer: 2500,
                        showConfirmButton: false,
                    });
                    if (modalAccesos) modalAccesos.hide();
                    await this.cargarRoles();
                } else {
                    var msg = !resMenu.success     ? resMenu.message
                            : !resPermisos.success ? resPermisos.message
                            : 'Error desconocido';
                    Swal.fire('Error', msg, 'error');
                }

            } catch(e) {
                console.error('Error guardando accesos:', e);
                Swal.fire('Error', 'Error de conexión.', 'error');
            } finally {
                this.guardandoAccesos = false;
            }
        },
    },
});