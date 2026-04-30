/**
 * NexusERP — Módulo Sucursales
 * Archivo: resources/views/modulos/sucursales/index.js
 */

new Vue({
    el: '#sucursales-app',
    data: {
        sucursales: [],
        cargandoTabla: false,
        columnas: [
            { key: 'nombre',         label: 'Nombre' },
            { key: 'pais',           label: 'País' },
            { key: 'division',       label: 'Departamento' },
            { key: 'municipio',      label: 'Municipio' },
            { key: 'telefono',       label: 'Teléfono' },
            { key: 'es_casa_matriz', label: 'Casa Matriz' },
            { key: 'activo',         label: 'Estado' },
        ],

        // Catálogos geográficos
        paises: [],
        divisiones: [],
        municipios: [],
        cargandoDivisiones: false,
        cargandoMunicipios: false,

        mostrarModal: false,
        modoEditar: false,
        guardando: false,
        form: {
            id_sucursal: null,
            id_pais: null,
            id_division: null,
            id_municipio: null,
            nombre: '',
            direccion: '',
            telefono: '',
            email: '',
            es_casa_matriz: false,
            activo: true,
        },
        errores: {},
    },

    mounted() {
        this.cargarDatos();
        this.cargarPaises();
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
                const res = await fetch(apiUrl + '/sucursales', { headers: this.headers() });
                const data = await res.json();
                if (data.success) this.sucursales = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargandoTabla = false; }
        },

        async cargarPaises() {
            try {
                const res = await fetch(apiUrl + '/geografia/paises-activos', { headers: this.headers() });
                const data = await res.json();
                if (data.success) this.paises = data.data;
            } catch (e) { console.error(e); }
        },

        async onPaisChange() {
            this.form.id_division = null;
            this.form.id_municipio = null;
            this.divisiones = [];
            this.municipios = [];
            if (!this.form.id_pais) return;

            this.cargandoDivisiones = true;
            try {
                const res = await fetch(apiUrl + '/geografia/divisiones/' + this.form.id_pais, {
                    headers: this.headers()
                });
                const data = await res.json();
                if (data.success) this.divisiones = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargandoDivisiones = false; }
        },

        async onDivisionChange() {
            this.form.id_municipio = null;
            this.municipios = [];
            if (!this.form.id_division) return;

            this.cargandoMunicipios = true;
            try {
                const res = await fetch(apiUrl + '/geografia/municipios/' + this.form.id_division, {
                    headers: this.headers()
                });
                const data = await res.json();
                if (data.success) this.municipios = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargandoMunicipios = false; }
        },

        async cargarCascadaEdicion(idPais, idDivision) {
            if (idPais) {
                this.cargandoDivisiones = true;
                try {
                    const res = await fetch(apiUrl + '/geografia/divisiones/' + idPais, {
                        headers: this.headers()
                    });
                    const data = await res.json();
                    if (data.success) this.divisiones = data.data;
                } catch (e) { console.error(e); }
                finally { this.cargandoDivisiones = false; }
            }
            if (idDivision) {
                this.cargandoMunicipios = true;
                try {
                    const res = await fetch(apiUrl + '/geografia/municipios/' + idDivision, {
                        headers: this.headers()
                    });
                    const data = await res.json();
                    if (data.success) this.municipios = data.data;
                } catch (e) { console.error(e); }
                finally { this.cargandoMunicipios = false; }
            }
        },

        abrirModalCrear() {
            this.modoEditar = false;
            this.errores = {};
            this.divisiones = [];
            this.municipios = [];
            this.form = {
                id_sucursal: null,
                id_pais: null,
                id_division: null,
                id_municipio: null,
                nombre: '',
                direccion: '',
                telefono: '',
                email: '',
                es_casa_matriz: false,
                activo: true,
            };
            this.mostrarModal = true;
        },

        async abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores = {};
            try {
                const res = await fetch(apiUrl + '/sucursales/' + item.id_sucursal, {
                    headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    this.form = {
                        id_sucursal:    data.data.id_sucursal,
                        id_pais:        data.data.id_pais,
                        id_division:    data.data.id_division,
                        id_municipio:   data.data.id_municipio,
                        nombre:         data.data.nombre,
                        direccion:      data.data.direccion || '',
                        telefono:       data.data.telefono || '',
                        email:          data.data.email || '',
                        es_casa_matriz: !!data.data.es_casa_matriz,
                        activo:         !!data.data.activo,
                    };

                    await this.cargarCascadaEdicion(this.form.id_pais, this.form.id_division);

                    this.mostrarModal = true;
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar la sucursal.', 'error');
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
                    ? apiUrl + '/sucursales/' + this.form.id_sucursal
                    : apiUrl + '/sucursales';

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
                const res = await fetch(apiUrl + '/sucursales/' + item.id_sucursal + '/toggle', {
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
                const res = await fetch(apiUrl + '/sucursales/' + item.id_sucursal, {
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