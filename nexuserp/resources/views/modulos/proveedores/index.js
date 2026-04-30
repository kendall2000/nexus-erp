new Vue({
    el: '#proveedores-app',
    data: {
        proveedores: [],
        cargandoTabla: false,
        columnas: [
            { key: 'razon_social',     label: 'Razón Social' },
            { key: 'nombre_comercial', label: 'Nombre Comercial' },
            { key: 'nit',              label: 'NIT' },
            { key: 'pais',             label: 'País' },
            { key: 'telefono',         label: 'Teléfono' },
            { key: 'tipo_proveedor',   label: 'Tipo' },
            { key: 'dias_credito',     label: 'Crédito' },
            { key: 'activo',           label: 'Estado' },
        ],

        // Catálogos
        paises:  [],
        monedas: [],
        tipos:   [],

        mostrarModal: false,
        modoEditar: false,
        guardando: false,
        form: {
            id_proveedor: null,
            id_pais: null,
            razon_social: '',
            nombre_comercial: '',
            nit: '',
            email: '',
            telefono: '',
            direccion: '',
            contacto: '',
            tipo_proveedor: 'BIENES',
            dias_credito: 30,
            moneda_pago: 'GTQ',
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
                const res = await fetch(apiUrl + '/inventario/proveedores', { headers: this.headers() });
                const data = await res.json();
                if (data.success) this.proveedores = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargandoTabla = false; }
        },

        async cargarCatalogos() {
            try {
                const res = await fetch(apiUrl + '/inventario/proveedores/catalogos', { headers: this.headers() });
                const data = await res.json();
                if (data.success) {
                    this.paises  = data.data.paises  || [];
                    this.monedas = data.data.monedas || ['GTQ', 'USD'];
                    this.tipos   = data.data.tipos   || ['BIENES', 'SERVICIOS', 'AMBOS'];
                }
            } catch (e) { console.error(e); }
        },

        abrirModalCrear() {
            this.modoEditar = false;
            this.errores = {};
            this.form = {
                id_proveedor: null,
                id_pais: null,
                razon_social: '',
                nombre_comercial: '',
                nit: '',
                email: '',
                telefono: '',
                direccion: '',
                contacto: '',
                tipo_proveedor: 'BIENES',
                dias_credito: 30,
                moneda_pago: 'GTQ',
                activo: true,
            };
            this.mostrarModal = true;
        },

        async abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores = {};
            try {
                const res = await fetch(apiUrl + '/inventario/proveedores/' + item.id_proveedor, {
                    headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    this.form = {
                        id_proveedor:     data.data.id_proveedor,
                        id_pais:          data.data.id_pais,
                        razon_social:     data.data.razon_social,
                        nombre_comercial: data.data.nombre_comercial || '',
                        nit:              data.data.nit || '',
                        email:            data.data.email || '',
                        telefono:         data.data.telefono || '',
                        direccion:        data.data.direccion || '',
                        contacto:         data.data.contacto || '',
                        tipo_proveedor:   data.data.tipo_proveedor,
                        dias_credito:     data.data.dias_credito,
                        moneda_pago:      data.data.moneda_pago,
                        activo:           !!data.data.activo,
                    };
                    this.mostrarModal = true;
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar el proveedor.', 'error');
            }
        },

        async guardarRegistro() {
            if (!this.form.razon_social.trim()) {
                this.errores = { razon_social: 'La razón social es obligatoria.' };
                return;
            }
            if (!this.form.id_pais) {
                this.errores = { id_pais: 'Debes seleccionar un país.' };
                return;
            }
            this.guardando = true;
            this.errores = {};
            try {
                const url = this.modoEditar
                    ? apiUrl + '/inventario/proveedores/' + this.form.id_proveedor
                    : apiUrl + '/inventario/proveedores';

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
                const res = await fetch(apiUrl + '/inventario/proveedores/' + item.id_proveedor + '/toggle', {
                    method: 'PATCH',
                    headers: this.headers()
                });
                const data = await res.json();
                if (data.success) this.cargarDatos();
            } catch (e) { console.error(e); }
        },

        async eliminarRegistro(item) {
            const ok = await Swal.fire({
                title: '¿Eliminar proveedor?',
                html: '<b>' + item.razon_social + '</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/inventario/proveedores/' + item.id_proveedor, {
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