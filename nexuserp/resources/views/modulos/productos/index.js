/**
 * NexusERP — Módulo Productos
 * Archivo: resources/views/modulos/productos/index.js
 */

new Vue({
    el: '#productos-app',
    data: {
        // ── Tabla ──
        productos: [],
        cargandoTabla: false,
        columnas: [
            { key: 'codigo',        label: 'SKU' },
            { key: 'nombre',        label: 'Producto' },
            { key: 'categoria',     label: 'Categoría' },     // ← NUEVA columna
            { key: 'unidad_medida', label: 'U/M' },
            { key: 'precio_venta',  label: 'Precio Venta' },
            { key: 'activo',        label: 'Estado' },
        ],

        // ── Catálogos ──
        categorias: [],     // ← NUEVO
        unidades:   [],     // ← NUEVO (ahora dinámico desde el backend)

        // ── Modal ──
        mostrarModal: false,
        modoEditar: false,
        guardando: false,
        form: {
            id_producto: null,
            id_categoria: null,    // ← NUEVO
            codigo: '',
            nombre: '',
            unidad_medida: 'UND',
            precio_compra: 0,
            precio_venta: 0,
            moneda: 'GTQ',
            stock_minimo: 0,
            stock_maximo: null,
            requiere_lote: false,
            es_perecedero: false,
        },
        errores: {},
    },

    mounted() {
        this.cargarDatos();
        this.cargarCatalogos();   // ← NUEVO
        if (typeof feather !== 'undefined') feather.replace();
    },

    methods: {
        // ── Cargar listado de productos ─────────────────────────
        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const res = await fetch(apiUrl + '/inventario/productos', {
                    headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                });
                const data = await res.json();
                if (data.success) this.productos = data.data;
            } catch (e) {
                console.error(e);
            } finally {
                this.cargandoTabla = false;
            }
        },

        // ── Cargar catálogos de categorías y unidades ───────────
        async cargarCatalogos() {
            try {
                const res = await fetch(apiUrl + '/inventario/productos/catalogos', {
                    headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                });
                const data = await res.json();
                if (data.success) {
                    this.categorias = data.data.categorias || [];
                    this.unidades   = data.data.unidades   || ['UND', 'LT', 'KG', 'MT', 'CAJA'];
                }
            } catch (e) {
                console.error('Error cargando catálogos:', e);
                // Fallback por si falla el endpoint
                this.unidades = ['UND', 'LT', 'KG', 'MT', 'CAJA'];
            }
        },

        // ── Modal: Crear ────────────────────────────────────────
        abrirModalCrear() {
            this.modoEditar = false;
            this.errores = {};
            this.form = {
                id_producto: null,
                id_categoria: null,
                codigo: '',
                nombre: '',
                unidad_medida: 'UND',
                precio_compra: 0,
                precio_venta: 0,
                moneda: 'GTQ',
                stock_minimo: 0,
                stock_maximo: null,
                requiere_lote: false,
                es_perecedero: false,
            };
            this.mostrarModal = true;
        },

        // ── Modal: Editar ───────────────────────────────────────
        abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores = {};

            // ⚠️ Importante: la tabla NO trae id_categoria todavía,
            // por eso traemos el producto completo del backend
            this.cargarProductoCompleto(item.id_producto);
        },

        async cargarProductoCompleto(idProducto) {
            try {
                const res = await fetch(apiUrl + '/inventario/productos/' + idProducto, {
                    headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
                });
                const data = await res.json();
                if (data.success) {
                    this.form = {
                        id_producto:   data.data.id_producto,
                        id_categoria:  data.data.id_categoria,
                        codigo:        data.data.codigo,
                        nombre:        data.data.nombre,
                        descripcion:   data.data.descripcion,
                        unidad_medida: data.data.unidad_medida,
                        precio_compra: data.data.precio_compra,
                        precio_venta:  data.data.precio_venta,
                        moneda:        data.data.moneda || 'GTQ',
                        stock_minimo:  data.data.stock_minimo,
                        stock_maximo:  data.data.stock_maximo,
                        requiere_lote: !!data.data.requiere_lote,
                        es_perecedero: !!data.data.es_perecedero,
                        activo:        !!data.data.activo,
                    };
                    this.mostrarModal = true;
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar el producto.', 'error');
            }
        },

        // ── Guardar ─────────────────────────────────────────────
        async guardarRegistro() {
            this.guardando = true;
            this.errores = {};
            try {
                const url = this.modoEditar
                    ? apiUrl + '/inventario/productos/' + this.form.id_producto
                    : apiUrl + '/inventario/productos';

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
                    Swal.fire({ icon: 'success', title: data.message, timer: 2000, showConfirmButton: false });
                    this.mostrarModal = false;
                    this.cargarDatos();
                } else {
                    if (data.errors) {
                        // Aplanar errores de Laravel: { codigo: ["mensaje"] } → { codigo: "mensaje" }
                        this.errores = Object.fromEntries(
                            Object.entries(data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
                        );
                    } else {
                        Swal.fire('Error', data.message || 'Error al guardar.', 'error');
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
            await fetch(apiUrl + '/inventario/productos/' + item.id_producto + '/toggle', {
                method: 'PATCH',
                headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
            });
            this.cargarDatos();
        },

        // ── Eliminar ────────────────────────────────────────────
        async eliminarRegistro(item) {
            const ok = await Swal.fire({
                title: '¿Eliminar?',
                html: '<b>' + item.nombre + '</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;

            const res = await fetch(apiUrl + '/inventario/productos/' + item.id_producto, {
                method: 'DELETE',
                headers: { 'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token') }
            });
            const data = await res.json();
            if (data.success) this.cargarDatos();
            else Swal.fire('Aviso', data.message, 'warning');
        }
    }
});