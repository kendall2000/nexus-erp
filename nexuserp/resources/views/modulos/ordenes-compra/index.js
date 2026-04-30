new Vue({
    el: '#oc-app',
    data: {
        ordenes: [],
        cargandoTabla: false,
        columnas: [
            { key: 'numero_oc',              label: 'N° OC' },
            { key: 'proveedor',              label: 'Proveedor' },
            { key: 'bodega',                 label: 'Bodega' },
            { key: 'fecha_emision',          label: 'Emisión' },
            { key: 'fecha_entrega_esperada', label: 'Entrega' },
            { key: 'total',                  label: 'Total' },
            { key: 'moneda',                 label: 'Moneda' },
            { key: 'estado',                 label: 'Estado' },
            { key: 'porcentaje_recepcion',   label: 'Recibido' },
        ],

        // Catálogos
        proveedores: [],
        bodegas:     [],
        productos:   [],
        monedas:     [],
        estados:     [],

        // Modal
        mostrarModal: false,
        modoEditar: false,
        guardando: false,
        form: {
            id_oc: null,
            numero_oc: '',
            id_proveedor: null,
            id_bodega: null,
            fecha_emision: new Date().toISOString().slice(0, 10),
            fecha_entrega_esperada: '',
            moneda: 'GTQ',
            estado: 'BORRADOR',
            notas: '',
            detalles: [],
        },
        errores: {},
    },

    computed: {
        totales() {
            const subtotal = this.form.detalles.reduce((sum, l) => sum + (parseFloat(l.subtotal) || 0), 0);
            const iva      = +(subtotal * 0.12).toFixed(4);
            const total    = +(subtotal + iva).toFixed(4);
            return { subtotal, iva, total };
        }
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

        async aprobarDesdeModal() {
            this.mostrarModal = false;
            await this.aprobar({ id_oc: this.form.id_oc, numero_oc: this.form.numero_oc });
        },

        async cancelarDesdeModal() {
            this.mostrarModal = false;
            await this.cancelar({ id_oc: this.form.id_oc, numero_oc: this.form.numero_oc });
        },

        formatear(num) {
            return new Intl.NumberFormat('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
        },

        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra', { headers: this.headers() });
                const data = await res.json();
                if (data.success) this.ordenes = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargandoTabla = false; }
        },

        async cargarCatalogos() {
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra/catalogos', { headers: this.headers() });
                const data = await res.json();
                if (data.success) {
                    this.proveedores = data.data.proveedores || [];
                    this.bodegas     = data.data.bodegas     || [];
                    this.productos   = data.data.productos   || [];
                    this.monedas     = data.data.monedas     || ['GTQ'];
                    this.estados     = data.data.estados     || ['BORRADOR'];
                }
            } catch (e) { console.error(e); }
        },

        // ── Auto-completar moneda al elegir proveedor ───────────
        onProveedorChange() {
            const prov = this.proveedores.find(p => p.id === this.form.id_proveedor);
            if (prov && prov.moneda_pago) {
                this.form.moneda = prov.moneda_pago;
            }
        },

        // ── Líneas ──────────────────────────────────────────────
        agregarLinea() {
            this.form.detalles.push({
                id_producto: null,
                descripcion: '',
                cantidad_pedida: 1,
                precio_unitario: 0,
                descuento: 0,
                subtotal: 0,
            });
        },

        quitarLinea(idx) {
            this.form.detalles.splice(idx, 1);
        },

        onProductoChange(idx) {
            const linea = this.form.detalles[idx];
            const prod  = this.productos.find(p => p.id === linea.id_producto);
            if (prod) {
                linea.descripcion     = prod.name;
                linea.precio_unitario = parseFloat(prod.precio_compra) || 0;
                this.recalcularLinea(idx);
            }
        },

        recalcularLinea(idx) {
            const l = this.form.detalles[idx];
            const cantidad  = parseFloat(l.cantidad_pedida) || 0;
            const precio    = parseFloat(l.precio_unitario) || 0;
            const descuento = parseFloat(l.descuento) || 0;
            l.subtotal = +((cantidad * precio) - descuento).toFixed(4);
        },

        // ── Modal ───────────────────────────────────────────────
        abrirModalCrear() {
            this.modoEditar = false;
            this.errores = {};
            this.form = {
                id_oc: null,
                numero_oc: '',
                id_proveedor: null,
                id_bodega: null,
                fecha_emision: new Date().toISOString().slice(0, 10),
                fecha_entrega_esperada: '',
                moneda: 'GTQ',
                estado: 'BORRADOR',
                notas: '',
                detalles: [],
            };
            this.mostrarModal = true;
        },

        async abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores = {};
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra/' + item.id_oc, {
                    headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    this.form = {
                        id_oc:                  data.data.id_oc,
                        numero_oc:              data.data.numero_oc,
                        id_proveedor:           data.data.id_proveedor,
                        id_bodega:              data.data.id_bodega,
                        fecha_emision:          data.data.fecha_emision,
                        fecha_entrega_esperada: data.data.fecha_entrega_esperada || '',
                        moneda:                 data.data.moneda,
                        estado:                 data.data.estado,
                        notas:                  data.data.notas || '',
                        detalles: data.data.detalles.map(d => ({
                            id_producto:     d.id_producto,
                            descripcion:     d.descripcion || '',
                            cantidad_pedida: parseFloat(d.cantidad_pedida),
                            precio_unitario: parseFloat(d.precio_unitario),
                            descuento:       parseFloat(d.descuento),
                            subtotal:        parseFloat(d.subtotal),
                        })),
                    };
                    this.mostrarModal = true;
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar la orden.', 'error');
            }
        },

        async guardarRegistro() {
            // Validaciones rápidas
            if (!this.form.numero_oc.trim()) {
                this.errores = { numero_oc: 'El número es obligatorio.' };
                return;
            }
            if (!this.form.id_proveedor) {
                this.errores = { id_proveedor: 'Selecciona un proveedor.' };
                return;
            }
            if (this.form.detalles.length === 0) {
                Swal.fire('Aviso', 'Debes agregar al menos un producto.', 'warning');
                return;
            }
            const lineaInvalida = this.form.detalles.find(l =>
                !l.id_producto || !l.cantidad_pedida || l.cantidad_pedida <= 0
            );
            if (lineaInvalida) {
                Swal.fire('Aviso', 'Todas las líneas deben tener producto y cantidad mayor a 0.', 'warning');
                return;
            }

            this.guardando = true;
            this.errores = {};
            try {
                const url = this.modoEditar
                    ? apiUrl + '/inventario/ordenes-compra/' + this.form.id_oc
                    : apiUrl + '/inventario/ordenes-compra';

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

        // ── Aprobar ─────────────────────────────────────────────
        async aprobar(item) {
            const ok = await Swal.fire({
                title: '¿Aprobar orden?',
                html: '<b>' + item.numero_oc + '</b><br><small>Pasará a estado ENVIADA.</small>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00d27a',
                confirmButtonText: 'Sí, aprobar',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra/' + item.id_oc + '/aprobar', {
                    method: 'PATCH', headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    this.cargarDatos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) { Swal.fire('Error', 'Error de conexión', 'error'); }
        },

        // ── Cancelar ────────────────────────────────────────────
        async cancelar(item) {
            const ok = await Swal.fire({
                title: '¿Cancelar orden?',
                html: '<b>' + item.numero_oc + '</b><br><small class="text-danger">Esta acción no se puede revertir.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
                confirmButtonText: 'Sí, cancelar',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra/' + item.id_oc + '/cancelar', {
                    method: 'PATCH', headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    this.cargarDatos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) { Swal.fire('Error', 'Error de conexión', 'error'); }
        },

        // ── Eliminar ────────────────────────────────────────────
        async eliminarRegistro(item) {
            if (item.estado !== 'BORRADOR') {
                Swal.fire('Aviso', 'Solo se pueden eliminar órdenes en BORRADOR. Si quieres anularla, usa Cancelar.', 'warning');
                return;
            }
            const ok = await Swal.fire({
                title: '¿Eliminar orden?',
                html: '<b>' + item.numero_oc + '</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra/' + item.id_oc, {
                    method: 'DELETE', headers: this.headers()
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    this.cargarDatos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) { Swal.fire('Error', 'Error de conexión', 'error'); }
        }
    }
});