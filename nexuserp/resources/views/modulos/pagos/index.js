new Vue({
    el: '#pagos-app',

    data: {
        pagos:         [],
        cargandoTabla: false,
        columnas: [
            { key: 'factura',          label: 'Factura'        },
            { key: 'factura_estado',   label: 'Estado Fact.'   },
            { key: 'cliente',          label: 'Cliente'        },
            { key: 'forma_pago',       label: 'Forma Pago'     },
            { key: 'monto',            label: 'Monto'          },
            { key: 'moneda',           label: 'Moneda'         },
            { key: 'referencia',       label: 'Referencia'     },
            { key: 'banco_origen',     label: 'Banco'          },
            { key: 'fecha_pago',       label: 'Fecha Pago'     },
            { key: 'fecha_acreditado', label: 'Acreditado'     },
            { key: 'creado_por',       label: 'Registrado por' },
        ],

        filtros: {
            forma_pago:  '',
            fecha_desde: '',
            fecha_hasta: '',
        },

        facturasPendientes: [],
        formasPago:         [],

        mostrarModal: false,
        guardando:    false,
        errores:      {},

        form: {
            id_factura:       null,
            forma_pago:       null,
            monto:            '',
            moneda:           'GTQ',
            referencia:       '',
            banco_origen:     '',
            fecha_pago:       new Date().toISOString().split('T')[0],
            fecha_acreditado: '',
            notas:            '',
        },
    },

    computed: {
        facturaSeleccionada() {
            if (!this.form.id_factura) return null;
            return this.facturasPendientes.find(
                f => f.id_factura === this.form.id_factura
            ) || null;
        },
    },

    mounted() {
        this.cargarDatos();
        this.cargarCatalogos();
    },

    methods: {

        headers() {
            return {
                'Content-Type':  'application/json',
                'Authorization': 'Bearer ' + sessionStorage.getItem('nexus_token'),
                'Accept':        'application/json',
            };
        },

        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const params = new URLSearchParams();
                if (this.filtros.forma_pago)  params.append('forma_pago',  this.filtros.forma_pago);
                if (this.filtros.fecha_desde) params.append('fecha_desde', this.filtros.fecha_desde);
                if (this.filtros.fecha_hasta) params.append('fecha_hasta', this.filtros.fecha_hasta);

                const res  = await fetch(`${apiUrl}/finanzas/pagos?${params}`, {
                    headers: this.headers(),
                });
                const data = await res.json();
                if (data.success) this.pagos = data.data;
            } catch (e) {
                console.error('Error al cargar pagos:', e);
                toastr.error('No se pudo cargar el listado de pagos.');
            } finally {
                this.cargandoTabla = false;
                this.$nextTick(() => {
                    if (typeof feather !== 'undefined') feather.replace();
                });
            }
        },

        async cargarCatalogos() {
            try {
                const res  = await fetch(`${apiUrl}/finanzas/pagos/catalogos`, {
                    headers: this.headers(),
                });
                const data = await res.json();
                if (data.success) {
                    this.facturasPendientes = data.data.facturas;
                    this.formasPago         = data.data.formas_pago;
                }
            } catch (e) {
                console.error('Error al cargar catálogos:', e);
            }
        },

        onFacturaChange() {
            const f = this.facturaSeleccionada;
            if (f) {
                this.form.monto  = f.saldo_pendiente;
                this.form.moneda = f.moneda;
            } else {
                this.form.monto  = '';
                this.form.moneda = 'GTQ';
            }
        },

        abrirModalCrear() {
            this.errores = {};
            this.form    = {
                id_factura:       null,
                forma_pago:       null,
                monto:            '',
                moneda:           'GTQ',
                referencia:       '',
                banco_origen:     '',
                fecha_pago:       new Date().toISOString().split('T')[0],
                fecha_acreditado: '',
                notas:            '',
            };
            this.mostrarModal = true;
            this.$nextTick(() => {
                if (typeof feather !== 'undefined') feather.replace();
            });
        },

        async guardarRegistro() {
            this.guardando = true;
            this.errores   = {};
            try {
                const res  = await fetch(`${apiUrl}/finanzas/pagos`, {
                    method:  'POST',
                    headers: this.headers(),
                    body:    JSON.stringify(this.form),
                });
                const data = await res.json();

                if (data.success) {
                    toastr.success(data.message || 'Pago registrado correctamente.');
                    this.mostrarModal = false;
                    await Promise.all([this.cargarDatos(), this.cargarCatalogos()]);
                } else if (res.status === 422) {
                    this.errores = data.errors || {};
                    toastr.warning(data.message || 'Revisa los datos ingresados.');
                } else {
                    toastr.error(data.message || 'Error al registrar el pago.');
                }
            } catch (e) {
                console.error('Error al guardar pago:', e);
                toastr.error('Error de conexión.');
            } finally {
                this.guardando = false;
            }
        },

        async eliminarRegistro(pago) {
            const resultado = await Swal.fire({
                title:             '¿Revertir este pago?',
                html:              `Se revertirán <strong>${pago.moneda} ${pago.monto}</strong> a la factura <strong>${pago.factura}</strong>.<br><small class="text-muted">Esta acción no se puede deshacer.</small>`,
                icon:              'warning',
                showCancelButton:  true,
                confirmButtonText: 'Sí, revertir',
                cancelButtonText:  'Cancelar',
                confirmButtonColor:'#d33',
            });

            if (!resultado.isConfirmed) return;

            try {
                const res  = await fetch(`${apiUrl}/finanzas/pagos/${pago.id_pago}`, {
                    method:  'DELETE',
                    headers: this.headers(),
                });
                const data = await res.json();

                if (data.success) {
                    toastr.success(data.message || 'Pago revertido correctamente.');
                    await Promise.all([this.cargarDatos(), this.cargarCatalogos()]);
                } else {
                    toastr.error(data.message || 'No se pudo revertir el pago.');
                }
            } catch (e) {
                console.error('Error al revertir pago:', e);
                toastr.error('Error de conexión al revertir.');
            }
        },

        limpiarFiltros() {
            this.filtros = { forma_pago: '', fecha_desde: '', fecha_hasta: '' };
            this.cargarDatos();
        },
    },
});