/**
 * NexusERP — Módulo Órdenes de Compra
 * resources/views/modulos/ordenes-compra/index.js
 * Vue 2 CDN + Phoenix Template + Opción C (Centro/Cuenta)
 */
new Vue({
    el: '#oc-app',

    data: {
        ordenes:       [],
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
        centros:     [],
        cuentas:     [],
        monedas:     [],
        estados:     [],

        // Configuración fiscal de la empresa
        configFiscal: {
            tasa_iva:               12,
            tasa_iva_decimal:       0.12,
            iva_incluido_en_precio: false,
        },

        // Modal
        mostrarModal: false,
        modoEditar:   false,
        guardando:    false,
        form: {
            id_oc:                  null,
            numero_oc:              '',
            id_proveedor:           null,
            id_bodega:              null,
            fecha_emision:          new Date().toISOString().slice(0, 10),
            fecha_entrega_esperada: '',
            moneda:                 'GTQ',
            estado:                 'BORRADOR',
            notas:                  '',
            detalles:               [],
        },
        errores: {},
    },

    computed: {
        totales() {
            const tasaIva     = this.configFiscal.tasa_iva_decimal || 0.12;
            const ivaIncluido = this.configFiscal.iva_incluido_en_precio;

            const subtotal = this.form.detalles
                .reduce((sum, l) => sum + (parseFloat(l.subtotal) || 0), 0);
            const iva = +(subtotal * tasaIva).toFixed(4);
            const total = ivaIncluido
                ? +subtotal.toFixed(4)
                : +(subtotal + iva).toFixed(4);

            return {
                subtotal, iva, total,
                tasaIvaPct: this.configFiscal.tasa_iva,
            };
        },
    },

    mounted() {
        this.cargarDatos();
        this.cargarCatalogos();
        if (typeof feather !== 'undefined') feather.replace();
    },

    methods: {
        headers() {
            return {
                'Content-Type':  'application/json',
                'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token'),
            };
        },

        formatear(num) {
            return new Intl.NumberFormat('es-GT', {
                minimumFractionDigits: 2, maximumFractionDigits: 2,
            }).format(num || 0);
        },

        // ── Helpers de defaults centro/cuenta ─────────────────────
        centroDefaultLinea(linea) {
            if (!linea.id_producto) return null;
            const prod = this.productos.find(p => p.id === linea.id_producto);
            return prod?.id_centro_default || null;
        },

        cuentaDefaultLinea(linea) {
            if (!linea.id_producto) return null;
            const prod = this.productos.find(p => p.id === linea.id_producto);
            return prod?.id_cuenta_gasto || null;
        },

        centroEfectivoTexto(linea) {
            const id = linea.id_centro || this.centroDefaultLinea(linea);
            if (!id) return 'Sin asignar';
            const c = this.centros.find(x => x.id === id);
            return c ? `Efectivo: ${c.name}` : 'Sin asignar';
        },

        cuentaEfectivaTexto(linea) {
            const id = linea.id_cuenta || this.cuentaDefaultLinea(linea);
            if (!id) return 'Sin asignar';
            const c = this.cuentas.find(x => x.id === id);
            return c ? `Efectiva: ${c.name}` : 'Sin asignar';
        },

        // ════════════════════════════════════════════════════════════
        // CARGAR DATOS
        // ════════════════════════════════════════════════════════════
        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra', { headers: this.headers() });
                const data = await res.json();
                if (data.success) this.ordenes = data.data;
            } catch (e) {
                console.error(e);
            } finally {
                this.cargandoTabla = false;
            }
        },

        async cargarCatalogos() {
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra/catalogos', { headers: this.headers() });
                const data = await res.json();
                if (data.success) {
                    this.proveedores = data.data.proveedores || [];
                    this.bodegas     = data.data.bodegas     || [];
                    this.productos   = data.data.productos   || [];
                    this.centros     = data.data.centros     || [];
                    this.cuentas     = data.data.cuentas     || [];
                    this.monedas     = data.data.monedas     || ['GTQ'];
                    this.estados     = data.data.estados     || ['BORRADOR'];
                    if (data.data.config_fiscal) {
                        this.configFiscal = data.data.config_fiscal;
                    }
                }
            } catch (e) {
                console.error(e);
            }
        },

        onProveedorChange() {
            const prov = this.proveedores.find(p => p.id === this.form.id_proveedor);
            if (prov?.moneda_pago) {
                this.form.moneda = prov.moneda_pago;
            }
        },

        // ════════════════════════════════════════════════════════════
        // LÍNEAS
        // ════════════════════════════════════════════════════════════
        agregarLinea() {
            this.form.detalles.push({
                id_producto:     null,
                id_centro:       null,
                id_cuenta:       null,
                descripcion:     '',
                cantidad_pedida: 1,
                precio_unitario: 0,
                descuento:       0,
                subtotal:        0,
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

        // ════════════════════════════════════════════════════════════
        // MODAL
        // ════════════════════════════════════════════════════════════
        abrirModalCrear() {
            this.modoEditar = false;
            this.errores    = {};
            this.form = {
                id_oc:                  null,
                numero_oc:              '',
                id_proveedor:           null,
                id_bodega:              null,
                fecha_emision:          new Date().toISOString().slice(0, 10),
                fecha_entrega_esperada: '',
                moneda:                 'GTQ',
                estado:                 'BORRADOR',
                notas:                  '',
                detalles:               [],
            };
            this.mostrarModal = true;
        },

        async abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores    = {};
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra/' + item.id_oc, {
                    headers: this.headers(),
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
                            id_centro:       d.id_centro,
                            id_cuenta:       d.id_cuenta,
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

        // ════════════════════════════════════════════════════════════
        // GUARDAR
        // ════════════════════════════════════════════════════════════
        async guardarRegistro() {
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

                if (res.ok && data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1800, showConfirmButton: false });
                    this.mostrarModal = false;
                    this.cargarDatos();
                } else if (res.status === 422) {
                    if (data.errors) {
                        this.errores = Object.fromEntries(
                            Object.entries(data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
                        );
                        Swal.fire('Aviso', data.message || 'Revisa los datos.', 'warning');
                    } else {
                        Swal.fire('Aviso', data.message, 'warning');
                    }
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión', 'error');
            } finally {
                this.guardando = false;
            }
        },

        // ════════════════════════════════════════════════════════════
        // APROBAR — con validación de saldo presupuestal
        // ════════════════════════════════════════════════════════════
        async aprobarDesdeModal() {
            this.mostrarModal = false;
            await this.aprobar({ id_oc: this.form.id_oc, numero_oc: this.form.numero_oc });
        },

        async aprobar(item, forzar = false) {
            if (!forzar) {
                const ok = await Swal.fire({
                    title: '¿Aprobar orden?',
                    html:  `<b>${item.numero_oc}</b><br><small>Pasará a estado ENVIADA y descontará del presupuesto.</small>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#00d27a',
                    confirmButtonText: 'Sí, aprobar',
                });
                if (!ok.isConfirmed) return;
            }

            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra/' + item.id_oc + '/aprobar', {
                    method:  'PATCH',
                    headers: this.headers(),
                    body:    JSON.stringify({ forzar }),
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    this.cargarDatos();
                    return;
                }

                // ── Sobregiro presupuestal: ofrecer forzar ──
                if (data.requiere_forzar && data.detalles?.length) {
                    let html = '<div class="text-start small">';
                    html += '<p class="mb-2">Las siguientes intersecciones exceden el presupuesto:</p>';
                    html += '<table class="table table-sm table-bordered mb-2">';
                    html += '<thead><tr><th>Centro</th><th>Cuenta</th><th class="text-end">Disponible</th><th class="text-end">Requerido</th><th class="text-end">Sobregiro</th></tr></thead><tbody>';
                    data.detalles.forEach(d => {
                        html += `<tr>
                            <td>${d.centro}</td>
                            <td>${d.cuenta}</td>
                            <td class="text-end">${this.formatear(d.disponible)}</td>
                            <td class="text-end">${this.formatear(d.requerido)}</td>
                            <td class="text-end text-danger fw-bold">${this.formatear(d.sobregiro)}</td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';

                    const decision = await Swal.fire({
                        icon: 'warning',
                        title: 'Sobregiro presupuestal',
                        html,
                        showCancelButton: true,
                        confirmButtonText: 'Aprobar de todos modos',
                        cancelButtonText:  'Cancelar',
                        confirmButtonColor: '#e63757',
                        width: 600,
                    });
                    if (decision.isConfirmed) {
                        await this.aprobar(item, true); // reintentar con forzar=true
                    }
                    return;
                }

                Swal.fire('Aviso', data.message, 'warning');
            } catch (e) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        },

        // ════════════════════════════════════════════════════════════
        // CANCELAR
        // ════════════════════════════════════════════════════════════
        async cancelarDesdeModal() {
            this.mostrarModal = false;
            await this.cancelar({ id_oc: this.form.id_oc, numero_oc: this.form.numero_oc });
        },

        async cancelar(item) {
            const ok = await Swal.fire({
                title: '¿Cancelar orden?',
                html:  `<b>${item.numero_oc}</b><br><small class="text-danger">Si ya fue aprobada, se revertirá del presupuesto.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
                confirmButtonText: 'Sí, cancelar',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra/' + item.id_oc + '/cancelar', {
                    method:  'PATCH',
                    headers: this.headers(),
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
        },

        // ════════════════════════════════════════════════════════════
        // ELIMINAR
        // ════════════════════════════════════════════════════════════
        async eliminarRegistro(item) {
            if (item.estado !== 'BORRADOR') {
                Swal.fire('Aviso', 'Solo se pueden eliminar órdenes en BORRADOR. Si quieres anularla, usa Cancelar.', 'warning');
                return;
            }
            const ok = await Swal.fire({
                title: '¿Eliminar orden?',
                html:  `<b>${item.numero_oc}</b>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(apiUrl + '/inventario/ordenes-compra/' + item.id_oc, {
                    method:  'DELETE',
                    headers: this.headers(),
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
        },
    },
});