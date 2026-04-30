/**
 * NexusERP — Gestión de Menú
 * Archivo: resources/views/modulos/menu/index.js
 * 3 pestañas: Categoría → Item (con ruta opcional) → Módulo (ruta obligatoria)
 */

new Vue({
    el: '#menu-gestion-app',

    data: {
        items:         [],
        cargandoTabla: false,
        columnas: [
            { key: 'nombre',   label: 'Nombre' },
            { key: 'padre',    label: 'Padre' },
            { key: 'icono',    label: 'Icono' },
            { key: 'ruta',     label: 'Ruta' },
            { key: 'orden',    label: 'Orden' },
            { key: 'es_grupo', label: 'Tipo' },
            { key: 'activo',   label: 'Estado' },
        ],

        categorias: [],
        pestana: 'categoria',
        guardando: false,

        formaCategoria: { nombre: '', orden: 1 },
        formaItem:      { nombre: '', id_padre: '', icono: 'chevrons-right', orden: 1, tieneRuta: false, rutaSufijo: '' },
        formaModulo:    { nombre: '', id_padre: '', rutaSufijo: '', orden: 1 },
    },

    mounted() {
        this.cargarItems();
        if (typeof feather !== 'undefined') feather.replace();
    },

    methods: {
        // ── Cargar tabla ─────────────────────────────────────────
        async cargarItems() {
            this.cargandoTabla = true;
            try {
                const res = await fetch(apiUrl + '/gestion-menu', {
                    headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                });
                const data = await res.json();
                if (data.success) this.items = data.data;
            } catch(e) { console.error(e); }
            finally { this.cargandoTabla = false; }
        },

        // ── Cargar árbol de categorías ───────────────────────────
        async cargarCategorias() {
            try {
                const res = await fetch(apiUrl + '/gestion-menu/arbol', {
                    headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                });
                const data = await res.json();
                if (data.success) this.categorias = data.data;
            } catch(e) { console.error(e); }
        },

        // ── Preparar modal ───────────────────────────────────────
        async prepararModal() {
            this.pestana        = 'categoria';
            this.formaCategoria = { nombre: '', orden: 1 };
            this.formaItem      = { nombre: '', id_padre: '', icono: 'chevrons-right', orden: 1, tieneRuta: false, rutaSufijo: '' };
            this.formaModulo    = { nombre: '', id_padre: '', rutaSufijo: '', orden: 1 };
            await this.cargarCategorias();
            this.$nextTick(() => {
                if (typeof feather !== 'undefined') feather.replace();
            });
        },

        reemplazarIconos() {
            this.$nextTick(() => {
                if (typeof feather !== 'undefined') feather.replace();
            });
        },

        // ── Dispatcher ───────────────────────────────────────────
        async guardar() {
            switch (this.pestana) {
                case 'categoria': await this.guardarCategoria(); break;
                case 'item':      await this.guardarItem();      break;
                case 'modulo':    await this.guardarModulo();    break;
            }
        },

        // ── Helper genérico ──────────────────────────────────────
        async enviarItem(payload) {
            this.guardando = true;
            try {
                const res = await fetch(apiUrl + '/gestion-menu', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token')
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (data.success) {
                    this.mostrarExito(data.message);
                    await this.cargarItems();
                    await this.cargarCategorias();
                    return true;
                } else {
                    Swal.fire('Error', data.message, 'error');
                    return false;
                }
            } catch(e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
                return false;
            } finally {
                this.guardando = false;
            }
        },

        // ── Guardar Categoría ────────────────────────────────────
        async guardarCategoria() {
            if (!this.formaCategoria.nombre.trim()) {
                return Swal.fire('Campos incompletos', 'El nombre de la categoría es obligatorio.', 'warning');
            }
            const ok = await this.enviarItem({
                nombre:   this.formaCategoria.nombre,
                id_padre: null,
                icono:    'chevrons-right',
                ruta:     null,
                orden:    this.formaCategoria.orden,
                activo:   true,
            });
            if (ok) this.formaCategoria = { nombre: '', orden: 1 };
        },

        // ── Guardar Item (ruta opcional) ─────────────────────────
        async guardarItem() {
            if (!this.formaItem.nombre.trim() || !this.formaItem.id_padre) {
                return Swal.fire('Campos incompletos', 'El nombre y la categoría son obligatorios.', 'warning');
            }
            if (this.formaItem.tieneRuta && !this.formaItem.rutaSufijo.trim()) {
                return Swal.fire('Campos incompletos', 'Si activas la ruta, debes ingresarla.', 'warning');
            }

            const ruta = this.formaItem.tieneRuta
                ? '/sistema/' + this.formaItem.rutaSufijo.trim()
                : null;

            const ok = await this.enviarItem({
                nombre:   this.formaItem.nombre,
                id_padre: this.formaItem.id_padre,
                icono:    this.formaItem.icono || 'chevrons-right',
                ruta:     ruta,
                orden:    this.formaItem.orden,
                activo:   true,
            });
            if (ok) this.formaItem = { nombre: '', id_padre: '', icono: 'chevrons-right', orden: 1, tieneRuta: false, rutaSufijo: '' };
        },

        // ── Guardar Módulo (ruta obligatoria) ────────────────────
        async guardarModulo() {
            if (!this.formaModulo.nombre.trim() || !this.formaModulo.id_padre || !this.formaModulo.rutaSufijo.trim()) {
                return Swal.fire('Campos incompletos', 'Nombre, item padre y ruta son obligatorios.', 'warning');
            }

            const ok = await this.enviarItem({
                nombre:   this.formaModulo.nombre,
                id_padre: this.formaModulo.id_padre,
                icono:    'chevrons-right',
                ruta:     '/sistema/' + this.formaModulo.rutaSufijo.trim(),
                orden:    this.formaModulo.orden,
                activo:   true,
            });
            if (ok) this.formaModulo = { nombre: '', id_padre: '', rutaSufijo: '', orden: 1 };
        },

        // ── Toggle ───────────────────────────────────────────────
        async toggleItem(item) {
            const accion = item.activo === 'Activo' ? 'desactivar' : 'activar';
            const ok = await Swal.fire({
                title: '¿' + accion.charAt(0).toUpperCase() + accion.slice(1) + ' ítem?',
                html: '<b>' + item.nombre + '</b>' +
                    (item.es_grupo === 'Sí'
                        ? '<br><small class="text-warning">También afectará sus ítems hijos.</small>'
                        : ''),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, ' + accion,
                confirmButtonColor: accion === 'activar' ? '#00d27a' : '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/gestion-menu/' + item.id_menu + '/toggle', {
                    method: 'PATCH',
                    headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                });
                const data = await res.json();
                if (data.success) {
                    this.mostrarExito(data.message);
                    await this.cargarItems();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch(e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },

        // ── Eliminar ─────────────────────────────────────────────
        async eliminarItem(item) {
            const ok = await Swal.fire({
                title: '¿Eliminar ítem?',
                html: '<b>' + item.nombre + '</b>' +
                    (item.es_grupo === 'Sí'
                        ? '<br><small class="text-danger">Primero elimina los ítems hijos.</small>'
                        : ''),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/gestion-menu/' + item.id_menu, {
                    method: 'DELETE',
                    headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                });
                const data = await res.json();
                if (data.success) {
                    this.mostrarExito(data.message);
                    await this.cargarItems();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch(e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },

        mostrarExito(mensaje) {
            Swal.fire({
                icon: 'success', title: mensaje,
                timer: 2000, showConfirmButton: false,
                toast: true, position: 'top-end',
            });
        },
    },
});