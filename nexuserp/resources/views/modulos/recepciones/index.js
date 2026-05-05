new Vue({
    el: '#recepciones-app',
    data: {
        recepciones: [],
        cargandoTabla: false,
        columnas: [
            { key: 'numero_recepcion', label: 'N° Recepción' },
            { key: 'numero_oc',        label: 'OC' },
            { key: 'proveedor',        label: 'Proveedor' },
            { key: 'bodega',           label: 'Bodega' },
            { key: 'fecha_recepcion',  label: 'Fecha' },
            { key: 'total_items',      label: 'Items' },
        ],

        // Catálogos
        ordenes: [],

        // Modal
        mostrarModal:  false,
        modoVer:       false,
        guardando:     false,
        cargandoLineas:false,
        detalleVer:    {},
        form: {
            id_oc:             null,
            id_bodega:         null,
            numero_recepcion:  '',
            fecha_recepcion:   new Date().toISOString().slice(0, 10),
            notas:             '',
            detalles:          [],
        },
        errores: {},
    },

    computed: {
        totalRecepcion() {
            return this.form.detalles.reduce((sum, l) => sum + (parseFloat(l.subtotal) || 0), 0);
        }
    },

    mounted() {
        this.cargarDatos();
        this.cargarCatalogos();
        if (typeof feather !== 'undefined') feather.replace();
    },

    methods: {
        formatear(num) {
            return new Intl.NumberFormat('es-GT', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num || 0);
        },

        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const res  = await fetch(apiUrl + '/inventario/recepciones');
                const data = await res.json();
                if (data.success) this.recepciones = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargandoTabla = false; }
        },

        async cargarCatalogos() {
            try {
                const res  = await fetch(apiUrl + '/inventario/recepciones/catalogos');
                const data = await res.json();
                if (data.success) this.ordenes = data.data.ordenes || [];
            } catch (e) { console.error(e); }
        },

        async onOCChange() {
            this.form.detalles  = [];
            this.form.id_bodega = null;
            if (!this.form.id_oc) return;

            this.cargandoLineas = true;
            try {
                const res  = await fetch(apiUrl + '/inventario/recepciones/oc/' + this.form.id_oc + '/lineas');
                const data = await res.json();
                if (data.success) {
                    this.form.id_bodega = data.data.oc.id_bodega;
                    this.form.detalles  = data.data.lineas
                        .filter(l => l.pendiente > 0)
                        .map(l => ({
                            id_linea:           l.id_linea,
                            id_producto:        l.id_producto,
                            producto_nombre:    l.producto_nombre,
                            producto_codigo:    l.producto_codigo,
                            unidad_medida:      l.unidad_medida,
                            cantidad_pedida:    parseFloat(l.cantidad_pedida),
                            cantidad_recibida:  parseFloat(l.cantidad_recibida),
                            pendiente:          parseFloat(l.pendiente),
                            cantidad_a_recibir: parseFloat(l.pendiente), // default: todo el pendiente
                            costo_unitario:     parseFloat(l.precio_unitario),
                            subtotal:           parseFloat(l.pendiente) * parseFloat(l.precio_unitario),
                        }));
                }
            } catch (e) { console.error(e); }
            finally { this.cargandoLineas = false; }
        },

        recalcularLinea(idx) {
            const l       = this.form.detalles[idx];
            const cant    = parseFloat(l.cantidad_a_recibir) || 0;
            const costo   = parseFloat(l.costo_unitario) || 0;
            l.subtotal    = +(cant * costo).toFixed(4);
        },

        abrirModalCrear() {
            this.modoVer  = false;
            this.errores  = {};
            this.ordenes  = [];
            this.form = {
                id_oc:            null,
                id_bodega:        null,
                numero_recepcion: '',
                fecha_recepcion:  new Date().toISOString().slice(0, 10),
                notas:            '',
                detalles:         [],
            };
            this.cargarCatalogos();
            this.mostrarModal = true;
        },

        async verDetalle(item) {
            this.modoVer = true;
            this.errores = {};
            try {
                const res  = await fetch(apiUrl + '/inventario/recepciones/' + item.id_recepcion);
                const data = await res.json();
                if (data.success) {
                    this.detalleVer   = data.data;
                    this.form.numero_recepcion = data.data.numero_recepcion;
                    this.mostrarModal = true;
                }
            } catch (e) { Swal.fire('Error', 'No se pudo cargar el detalle.', 'error'); }
        },

        async guardarRegistro() {
            if (!this.form.numero_recepcion.trim()) {
                this.errores = { numero_recepcion: 'El número es obligatorio.' };
                return;
            }
            if (!this.form.id_oc) {
                this.errores = { id_oc: 'Selecciona una OC.' };
                return;
            }

            const lineasValidas = this.form.detalles.filter(l => l.cantidad_a_recibir > 0);
            if (lineasValidas.length === 0) {
                Swal.fire('Aviso', 'Debes ingresar al menos una cantidad mayor a 0.', 'warning');
                return;
            }

            this.guardando = true;
            this.errores   = {};

            const payload = {
                id_oc:            this.form.id_oc,
                id_bodega:        this.form.id_bodega,
                numero_recepcion: this.form.numero_recepcion,
                fecha_recepcion:  this.form.fecha_recepcion,
                notas:            this.form.notas,
                detalles: lineasValidas.map(l => ({
                    id_linea:          l.id_linea,
                    id_producto:       l.id_producto,
                    cantidad_recibida: l.cantidad_a_recibir,
                    costo_unitario:    l.costo_unitario,
                })),
            };

            try {
                const res  = await fetch(apiUrl + '/inventario/recepciones', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(payload),
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 2500, showConfirmButton: false });
                    this.mostrarModal = false;
                    this.cargarDatos();
                    this.cargarCatalogos(); // refrescar OCs disponibles
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
        }
    }
});