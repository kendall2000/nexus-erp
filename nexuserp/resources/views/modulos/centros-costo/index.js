/**
 * NexusERP — Módulo Centros de Costo
 * resources/views/modulos/centros-costo/index.js
 * Vue 2 CDN + Phoenix Template
 */
new Vue({
    el: '#centros-costo-app',

    data: {
        // ── Tabla ──────────────────────────────────────────────────
        centros:       [],
        cargandoTabla: false,
        columnas: [
            { key: 'codigo',             label: 'Código'        },
            { key: 'nombre',             label: 'Nombre'        },
            { key: 'descripcion',        label: 'Descripción'   },
            { key: 'presupuestos_count', label: 'Presupuestos'  },
            { key: 'activo',             label: 'Estado'        },
        ],

        // ── Modal ──────────────────────────────────────────────────
        mostrarModal: false,
        modoEditar:   false,
        guardando:    false,
        errores:      {},
        form: {
            id_centro:   null,
            codigo:      '',
            nombre:      '',
            descripcion: '',
            activo:      true,
        },
    },

    mounted() {
        this.cargarDatos();
    },

    methods: {
        // ── Cargar listado ─────────────────────────────────────────
        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const res  = await fetch(`${apiUrl}/core/centros-costo`);
                const data = await res.json();
                if (data.success) this.centros = data.data;
            } catch (e) {
                console.error('Error cargando centros:', e);
                Swal.fire('Error', 'No se pudo cargar el listado.', 'error');
            } finally {
                this.cargandoTabla = false;
                this.$nextTick(() => {
                    if (typeof feather !== 'undefined') feather.replace();
                });
            }
        },

        // ── Modal: abrir nuevo ─────────────────────────────────────
        abrirModalCrear() {
            this.modoEditar = false;
            this.errores    = {};
            this.form = {
                id_centro:   null,
                codigo:      '',
                nombre:      '',
                descripcion: '',
                activo:      true,
            };
            this.mostrarModal = true;
        },

        // ── Modal: abrir editar ────────────────────────────────────
        async abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores    = {};
            try {
                const res  = await fetch(`${apiUrl}/core/centros-costo/${item.id_centro}`);
                const data = await res.json();
                if (data.success) {
                    this.form = {
                        id_centro:   data.data.id_centro,
                        codigo:      data.data.codigo,
                        nombre:      data.data.nombre,
                        descripcion: data.data.descripcion || '',
                        activo:      !!data.data.activo,
                    };
                    this.mostrarModal = true;
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar el centro.', 'error');
            }
        },

        // ── Guardar (crear o editar) ───────────────────────────────
        async guardarRegistro() {
            this.guardando = true;
            this.errores   = {};
            try {
                const url    = this.modoEditar
                    ? `${apiUrl}/core/centros-costo/${this.form.id_centro}`
                    : `${apiUrl}/core/centros-costo`;
                const method = this.modoEditar ? 'PUT' : 'POST';

                const res  = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(this.form),
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.mostrarModal = false;
                    await Swal.fire({
                        icon:              'success',
                        title:             data.message,
                        timer:             1500,
                        showConfirmButton: false,
                    });
                    this.cargarDatos();
                } else if (res.status === 422) {
                    this.errores = Object.fromEntries(
                        Object.entries(data.errors || {})
                            .map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
                    );
                    Swal.fire('Aviso', data.message || 'Revisa los datos.', 'warning');
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar.', 'error');
                }
            } catch (e) {
                console.error('Error guardar:', e);
                Swal.fire('Error', 'Error de conexión.', 'error');
            } finally {
                this.guardando = false;
            }
        },

        // ── Toggle activo/inactivo ─────────────────────────────────
        async toggleEstado(item) {
            try {
                const res  = await fetch(
                    `${apiUrl}/core/centros-costo/${item.id_centro}/toggle`,
                    { method: 'PATCH' }
                );
                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        icon:              'success',
                        title:             data.message,
                        timer:             1500,
                        showConfirmButton: false,
                    });
                    this.cargarDatos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },

        // ── Eliminar ───────────────────────────────────────────────
        async eliminarRegistro(item) {
            if (item.presupuestos_count > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No se puede eliminar',
                    html:  `Este centro tiene <b>${item.presupuestos_count} presupuesto(s)</b> asociado(s).<br>
                            Considera <b>desactivarlo</b> en su lugar.`,
                });
                return;
            }

            const ok = await Swal.fire({
                title: '¿Eliminar centro de costo?',
                html:  `<b>${item.codigo} — ${item.nombre}</b><br>
                        <small class="text-muted">Esta acción no se puede deshacer.</small>`,
                icon: 'warning',
                showCancelButton:   true,
                confirmButtonColor: '#e63757',
                confirmButtonText:  'Sí, eliminar',
                cancelButtonText:   'Cancelar',
            });
            if (!ok.isConfirmed) return;

            try {
                const res  = await fetch(
                    `${apiUrl}/core/centros-costo/${item.id_centro}`,
                    { method: 'DELETE' }
                );
                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        icon:              'success',
                        title:             data.message,
                        timer:             1500,
                        showConfirmButton: false,
                    });
                    this.cargarDatos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },
    },
});