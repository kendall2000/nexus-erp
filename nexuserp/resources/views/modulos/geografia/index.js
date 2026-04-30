/**
 * NexusERP — Módulo Geografía
 * Archivo: resources/views/modulos/geografia/index.js
 */

new Vue({
    el: '#geografia-app',
    data: {
        pestana: 'paises',
        cargando: false,
        guardando: false,
        modoEditar: false,
        mostrarModal: false,

        // Datos por pestaña
        paises: [],
        divisiones: [],
        municipios: [],

        // Catálogos para selects
        paisesActivos: [],
        divisionesActivas: [],

        // Columnas
        columnasPaises: [
            { key: 'codigo_iso', label: 'ISO' },
            { key: 'nombre',     label: 'Nombre' },
            { key: 'activo',     label: 'Estado' },
        ],
        columnasDivisiones: [
            { key: 'pais',   label: 'País' },
            { key: 'nombre', label: 'Departamento' },
            { key: 'tipo',   label: 'Tipo' },
            { key: 'activo', label: 'Estado' },
        ],
        columnasMunicipios: [
            { key: 'pais',     label: 'País' },
            { key: 'division', label: 'Departamento' },
            { key: 'nombre',   label: 'Municipio' },
            { key: 'activo',   label: 'Estado' },
        ],

        // Forms
        formPais:      { id_pais: null, codigo_iso: '', nombre: '', activo: true },
        formDivision:  { id_division: null, id_pais: null, nombre: '', tipo: '', activo: true },
        formMunicipio: { id_municipio: null, id_pais: null, id_division: null, nombre: '', activo: true },

        errores: {},
    },

    computed: {
        tituloModal() {
            const accion = this.modoEditar ? 'Editar' : 'Nuevo';
            const labels = { paises: 'País', divisiones: 'Departamento', municipios: 'Municipio' };
            return `${accion} ${labels[this.pestana]}`;
        }
    },

    mounted() {
        this.cargarPaises();
        this.cargarPaisesActivos();
        if (typeof feather !== 'undefined') feather.replace();
    },

    methods: {
        headers() {
            return {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token')
            };
        },

        async cambiarPestana(p) {
            this.pestana = p;
            this.errores = {};
            if (p === 'paises')     await this.cargarPaises();
            if (p === 'divisiones') await this.cargarDivisiones();
            if (p === 'municipios') await this.cargarMunicipios();
        },

        // ════════════════════════════════════════════════════════
        // PAÍSES
        // ════════════════════════════════════════════════════════
        async cargarPaises() {
            this.cargando = true;
            try {
                const res = await fetch(apiUrl + '/geografia/paises', { headers: this.headers() });
                const data = await res.json();
                if (data.success) this.paises = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargando = false; }
        },

        async cargarPaisesActivos() {
            try {
                const res = await fetch(apiUrl + '/geografia/paises-activos', { headers: this.headers() });
                const data = await res.json();
                if (data.success) this.paisesActivos = data.data;
            } catch (e) { console.error(e); }
        },

        abrirModalEditarPais(item) {
            this.modoEditar = true;
            this.errores = {};
            this.formPais = {
                id_pais:    item.id_pais,
                codigo_iso: item.codigo_iso === '—' ? '' : item.codigo_iso,
                nombre:     item.nombre,
                activo:     item.activo === 'Activo',
            };
            this.mostrarModal = true;
        },

        async togglePais(item) {
            try {
                const res = await fetch(apiUrl + '/geografia/paises/' + item.id_pais + '/toggle', {
                    method: 'PATCH', headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    await this.cargarPaises();
                    await this.cargarPaisesActivos();
                }
            } catch (e) { console.error(e); }
        },

        async eliminarPais(item) {
            const ok = await Swal.fire({
                title: '¿Eliminar país?',
                html: '<b>' + item.nombre + '</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/geografia/paises/' + item.id_pais, {
                    method: 'DELETE', headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    await this.cargarPaises();
                    await this.cargarPaisesActivos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) { Swal.fire('Error', 'Error de conexión', 'error'); }
        },

        // ════════════════════════════════════════════════════════
        // DIVISIONES
        // ════════════════════════════════════════════════════════
        async cargarDivisiones() {
            this.cargando = true;
            try {
                const res = await fetch(apiUrl + '/geografia/divisiones', { headers: this.headers() });
                const data = await res.json();
                if (data.success) this.divisiones = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargando = false; }
        },

        abrirModalEditarDivision(item) {
            this.modoEditar = true;
            this.errores = {};
            this.formDivision = {
                id_division: item.id_division,
                id_pais:     item.id_pais,
                nombre:      item.nombre,
                tipo:        item.tipo === '—' ? '' : item.tipo,
                activo:      item.activo === 'Activo',
            };
            this.mostrarModal = true;
        },

        async toggleDivision(item) {
            try {
                const res = await fetch(apiUrl + '/geografia/divisiones/' + item.id_division + '/toggle', {
                    method: 'PATCH', headers: this.headers()
                });
                const data = await res.json();
                if (data.success) await this.cargarDivisiones();
            } catch (e) { console.error(e); }
        },

        async eliminarDivision(item) {
            const ok = await Swal.fire({
                title: '¿Eliminar departamento?',
                html: '<b>' + item.nombre + '</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/geografia/divisiones/' + item.id_division, {
                    method: 'DELETE', headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    await this.cargarDivisiones();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) { Swal.fire('Error', 'Error de conexión', 'error'); }
        },

        // ════════════════════════════════════════════════════════
        // MUNICIPIOS
        // ════════════════════════════════════════════════════════
        async cargarMunicipios() {
            this.cargando = true;
            try {
                const res = await fetch(apiUrl + '/geografia/municipios', { headers: this.headers() });
                const data = await res.json();
                if (data.success) this.municipios = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargando = false; }
        },

        async onPaisMunicipioChange() {
            this.formMunicipio.id_division = null;
            this.divisionesActivas = [];
            if (!this.formMunicipio.id_pais) return;
            try {
                const res = await fetch(apiUrl + '/geografia/divisiones/' + this.formMunicipio.id_pais, {
                    headers: this.headers()
                });
                const data = await res.json();
                if (data.success) this.divisionesActivas = data.data;
            } catch (e) { console.error(e); }
        },

        async abrirModalEditarMunicipio(item) {
            this.modoEditar = true;
            this.errores = {};
            this.formMunicipio = {
                id_municipio: item.id_municipio,
                id_pais:      item.id_pais,
                id_division:  item.id_division,
                nombre:       item.nombre,
                activo:       item.activo === 'Activo',
            };
            // Cargar divisiones del país
            if (item.id_pais) {
                try {
                    const res = await fetch(apiUrl + '/geografia/divisiones/' + item.id_pais, {
                        headers: this.headers()
                    });
                    const data = await res.json();
                    if (data.success) this.divisionesActivas = data.data;
                } catch (e) { console.error(e); }
            }
            this.mostrarModal = true;
        },

        async toggleMunicipio(item) {
            try {
                const res = await fetch(apiUrl + '/geografia/municipios/' + item.id_municipio + '/toggle', {
                    method: 'PATCH', headers: this.headers()
                });
                const data = await res.json();
                if (data.success) await this.cargarMunicipios();
            } catch (e) { console.error(e); }
        },

        async eliminarMunicipio(item) {
            const ok = await Swal.fire({
                title: '¿Eliminar municipio?',
                html: '<b>' + item.nombre + '</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/geografia/municipios/' + item.id_municipio, {
                    method: 'DELETE', headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    await this.cargarMunicipios();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) { Swal.fire('Error', 'Error de conexión', 'error'); }
        },

        // ════════════════════════════════════════════════════════
        // CREAR / GUARDAR (genérico según pestaña)
        // ════════════════════════════════════════════════════════
        abrirModalCrear() {
            this.modoEditar = false;
            this.errores = {};
            if (this.pestana === 'paises') {
                this.formPais = { id_pais: null, codigo_iso: '', nombre: '', activo: true };
            }
            if (this.pestana === 'divisiones') {
                this.formDivision = { id_division: null, id_pais: null, nombre: '', tipo: '', activo: true };
            }
            if (this.pestana === 'municipios') {
                this.formMunicipio = { id_municipio: null, id_pais: null, id_division: null, nombre: '', activo: true };
                this.divisionesActivas = [];
            }
            this.mostrarModal = true;
        },

        async guardar() {
            this.guardando = true;
            this.errores = {};

            try {
                let url, method, body;

                if (this.pestana === 'paises') {
                    if (!this.formPais.nombre.trim()) {
                        this.errores = { nombre: 'El nombre es obligatorio.' };
                        this.guardando = false;
                        return;
                    }
                    url = this.modoEditar
                        ? apiUrl + '/geografia/paises/' + this.formPais.id_pais
                        : apiUrl + '/geografia/paises';
                    method = this.modoEditar ? 'PUT' : 'POST';
                    body = this.formPais;
                }

                if (this.pestana === 'divisiones') {
                    if (!this.formDivision.nombre.trim() || !this.formDivision.id_pais) {
                        this.errores = {
                            nombre:  !this.formDivision.nombre.trim() ? 'El nombre es obligatorio.' : null,
                            id_pais: !this.formDivision.id_pais ? 'Selecciona un país.' : null,
                        };
                        this.guardando = false;
                        return;
                    }
                    url = this.modoEditar
                        ? apiUrl + '/geografia/divisiones/' + this.formDivision.id_division
                        : apiUrl + '/geografia/divisiones';
                    method = this.modoEditar ? 'PUT' : 'POST';
                    body = this.formDivision;
                }

                if (this.pestana === 'municipios') {
                    if (!this.formMunicipio.nombre.trim() || !this.formMunicipio.id_division) {
                        this.errores = {
                            nombre:      !this.formMunicipio.nombre.trim() ? 'El nombre es obligatorio.' : null,
                            id_division: !this.formMunicipio.id_division ? 'Selecciona un departamento.' : null,
                        };
                        this.guardando = false;
                        return;
                    }
                    url = this.modoEditar
                        ? apiUrl + '/geografia/municipios/' + this.formMunicipio.id_municipio
                        : apiUrl + '/geografia/municipios';
                    method = this.modoEditar ? 'PUT' : 'POST';
                    body = this.formMunicipio;
                }

                const res = await fetch(url, {
                    method,
                    headers: this.headers(),
                    body: JSON.stringify(body),
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 2000, showConfirmButton: false });
                    this.mostrarModal = false;
                    if (this.pestana === 'paises')     { await this.cargarPaises();     await this.cargarPaisesActivos(); }
                    if (this.pestana === 'divisiones') { await this.cargarDivisiones(); }
                    if (this.pestana === 'municipios') { await this.cargarMunicipios(); }
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
    }
});