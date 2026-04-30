/**
 * NexusERP — Módulo Categorías de Productos
 * Archivo: resources/views/modulos/inventario/categorias/index.js
 */

new Vue({
    el: '#categorias-app',
    data: {
        // Tabla
        categorias: [],
        cargandoTabla: false,
        columnas: [
            { key: 'nombre',          label: 'Nombre' },
            { key: 'padre',           label: 'Categoría Padre' },
            { key: 'descripcion',     label: 'Descripción' },
            { key: 'total_productos', label: 'Productos' },
            { key: 'activo',          label: 'Estado' },
        ],

        // Catálogos
        padres: [],

        // Modal
        mostrarModal: false,
        modoEditar: false,
        guardando: false,
        form: {
            id_categoria: null,
            id_padre: null,
            nombre: '',
            descripcion: '',
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
        // ── Cargar listado ──────────────────────────────────────
        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const res = await fetch(apiUrl + '/inventario/categorias', {
                    headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                });
                const data = await res.json();
                if (data.success) this.categorias = data.data;
            } catch (e) {
                console.error('Error cargando categorías:', e);
            } finally {
                this.cargandoTabla = false;
            }
        },

        // ── Cargar catálogo de padres ───────────────────────────
        async cargarCatalogos() {
            try {
                const res = await fetch(apiUrl + '/inventario/categorias/catalogos', {
                    headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                });
                const data = await res.json();
                if (data.success) this.padres = data.data.padres;
            } catch (e) {
                console.error('Error cargando catálogos:', e);
            }
        },

        // ── Modal: Crear ────────────────────────────────────────
        abrirModalCrear() {
            this.modoEditar = false;
            this.errores = {};
            this.form = {
                id_categoria: null,
                id_padre: null,
                nombre: '',
                descripcion: '',
                activo: true,
            };
            this.mostrarModal = true;
        },

        // ── Modal: Editar ───────────────────────────────────────
        abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores = {};
            this.form = {
                id_categoria: item.id_categoria,
                id_padre:     item.id_padre,
                nombre:       item.nombre,
                descripcion:  item.descripcion === '—' ? '' : item.descripcion,
                activo:       item.activo === 'Activo',
            };
            this.mostrarModal = true;
        },

        // ── Guardar ─────────────────────────────────────────────
        async guardarRegistro() {
            if (!this.form.nombre.trim()) {
                this.errores = { nombre: 'El nombre es obligatorio.' };
                return;
            }

            this.guardando = true;
            this.errores = {};

            try {
                const url = this.modoEditar
                    ? apiUrl + '/inventario/categorias/' + this.form.id_categoria
                    : apiUrl + '/inventario/categorias';

                const res = await fetch(url, {
                    method: this.modoEditar ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token')
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await res.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    this.mostrarModal = false;
                    await this.cargarDatos();
                    await this.cargarCatalogos(); // refresca el select de padres
                } else {
                    if (data.errors) {
                        this.errores = Object.fromEntries(
                            Object.entries(data.errors).map(([k, v]) => [k, v[0]])
                        );
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            } finally {
                this.guardando = false;
            }
        },

        // ── Toggle ──────────────────────────────────────────────
        async toggleEstado(item) {
            try {
                const res = await fetch(
                    apiUrl + '/inventario/categorias/' + item.id_categoria + '/toggle',
                    {
                        method: 'PATCH',
                        headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                    }
                );
                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success', title: data.message,
                        timer: 1500, showConfirmButton: false,
                        toast: true, position: 'top-end',
                    });
                    await this.cargarDatos();
                    await this.cargarCatalogos();
                }
            } catch (e) {
                console.error(e);
            }
        },

        // ── Eliminar ────────────────────────────────────────────
        async eliminarRegistro(item) {
            const ok = await Swal.fire({
                title: '¿Eliminar categoría?',
                html: '<b>' + item.nombre + '</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
                confirmButtonText: 'Sí, eliminar',
            });
            if (!ok.isConfirmed) return;

            try {
                const res = await fetch(
                    apiUrl + '/inventario/categorias/' + item.id_categoria,
                    {
                        method: 'DELETE',
                        headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                    }
                );
                const data = await res.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success', title: data.message,
                        timer: 1500, showConfirmButton: false,
                    });
                    await this.cargarDatos();
                    await this.cargarCatalogos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },
    }
});