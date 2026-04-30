new Vue({
    el: '#bodegas-app',
    data: {
        bodegas: [],
        cargandoTabla: false,
        columnas: [
            { key: 'nombre',           label: 'Nombre' },
            { key: 'sucursal',         label: 'Sucursal' },
            { key: 'responsable',      label: 'Responsable' },
            { key: 'ubicacion',        label: 'Ubicación' },
            { key: 'total_productos',  label: 'Productos' },
            { key: 'valor_inventario', label: 'Valor Inv.' },
            { key: 'activo',           label: 'Estado' },
        ],

        // Catálogos
        sucursales: [],
        empleados: [],

        mostrarModal: false,
        modoEditar: false,
        guardando: false,
        form: {
            id_bodega: null,
            id_sucursal: null,
            responsable_id: null,
            nombre: '',
            ubicacion: '',
            activo: true,
        },
        errores: {},
    },

    mounted() {
        this.cargarDatos();
        this.cargarCatalogos();
        if (typeof feather !== 'undefined') feather.replace();
    },

    methods: {
        headers() {
            return {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token')
            };
        },

        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const res = await fetch(apiUrl + '/inventario/bodegas', { headers: this.headers() });
                const data = await res.json();
                if (data.success) this.bodegas = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargandoTabla = false; }
        },

        async cargarCatalogos() {
            try {
                const res = await fetch(apiUrl + '/inventario/bodegas/catalogos', { headers: this.headers() });
                const data = await res.json();
                if (data.success) {
                    this.sucursales = data.data.sucursales || [];
                    this.empleados  = data.data.empleados  || [];
                }
            } catch (e) { console.error(e); }
        },

        abrirModalCrear() {
            this.modoEditar = false;
            this.errores = {};
            this.form = {
                id_bodega: null,
                id_sucursal: null,
                responsable_id: null,
                nombre: '',
                ubicacion: '',
                activo: true,
            };
            this.mostrarModal = true;
        },

        async abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores = {};
            try {
                const res = await fetch(apiUrl + '/inventario/bodegas/' + item.id_bodega, {
                    headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    this.form = {
                        id_bodega:      data.data.id_bodega,
                        id_sucursal:    data.data.id_sucursal,
                        responsable_id: data.data.responsable_id,
                        nombre:         data.data.nombre,
                        ubicacion:      data.data.ubicacion || '',
                        activo:         !!data.data.activo,
                    };
                    this.mostrarModal = true;
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar la bodega.', 'error');
            }
        },

        async guardarRegistro() {
            if (!this.form.nombre.trim()) {
                this.errores = { nombre: 'El nombre es obligatorio.' };
                return;
            }
            this.guardando = true;
            this.errores = {};
            try {
                const url = this.modoEditar
                    ? apiUrl + '/inventario/bodegas/' + this.form.id_bodega
                    : apiUrl + '/inventario/bodegas';

                const res = await fetch(url, {
                    method: this.modoEditar ? 'PUT' : 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 2000, showConfirmButton: false });
                    this.mostrarModal = false;
                    this.cargarDatos();
                } else {
                    if (data.errors) {
                        this.errores = Object.fromEntries(
                            Object.entries(data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
                        );
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión', 'error');
            } finally {
                this.guardando = false;
            }
        },

        async toggleEstado(item) {
            try {
                const res = await fetch(apiUrl + '/inventario/bodegas/' + item.id_bodega + '/toggle', {
                    method: 'PATCH',
                    headers: this.headers()
                });
                const data = await res.json();
                if (data.success) this.cargarDatos();
            } catch (e) { console.error(e); }
        },

        async eliminarRegistro(item) {
            const ok = await Swal.fire({
                title: '¿Eliminar?',
                html: '<b>' + item.nombre + '</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/inventario/bodegas/' + item.id_bodega, {
                    method: 'DELETE',
                    headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    this.cargarDatos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        }
    }
});