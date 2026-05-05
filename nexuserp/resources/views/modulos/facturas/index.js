/**
 * NexusERP — Módulo Facturas
 * resources/views/modulos/facturas/index.js
 * Vue 2 CDN + Phoenix Template
 */
new Vue({
    el: '#facturas-app',

    data: {
        // ── Tabla ──────────────────────────────────────────────────
        facturas:      [],
        cargandoTabla: false,
        columnas: [
            { key: 'numero_completo',   label: 'N° Factura'   },
            { key: 'tipo',              label: 'Tipo'         },
            { key: 'cliente',           label: 'Cliente'      },
            { key: 'contrato',          label: 'Contrato'     },
            { key: 'fecha_emision',     label: 'Emisión'      },
            { key: 'fecha_vencimiento', label: 'Vencimiento'  },
            { key: 'total',             label: 'Total'        },
            { key: 'saldo_pendiente',   label: 'Saldo'        },
            { key: 'moneda',            label: 'Moneda'       },
            { key: 'estado',            label: 'Estado'       },
            { key: 'antiguedad',        label: 'Antigüedad'   },
        ],

        // ── Filtros ────────────────────────────────────────────────
        filtros: { estado: '', fecha_desde: '', fecha_hasta: '' },
        estadosFiltro: [
            { valor: 'BORRADOR',  label: '📝 Borrador'  },
            { valor: 'EMITIDA',   label: '✅ Emitida'   },
            { valor: 'ENVIADA',   label: '📤 Enviada'   },
            { valor: 'PARCIAL',   label: '💰 Parcial'   },
            { valor: 'PAGADA',    label: '✔️ Pagada'    },
            { valor: 'VENCIDA',   label: '⚠️ Vencida'   },
            { valor: 'ANULADA',   label: '🚫 Anulada'   },
        ],

        // ── Catálogos ──────────────────────────────────────────────
        clientes: [], series: [], tiposServicio: [],
        contratos: [], monedas: [], tipos: [],

        // ── Modal ──────────────────────────────────────────────────
        mostrarModal:  false,
        modoEditar:    false,
        guardando:     false,
        cargandoLineas:false,
        errores:       {},

        form: {
            id_factura:               null,
            id_cliente:               null,
            id_contrato:              null,
            id_serie:                 null,
            tipo:                     'FACTURA',
            numero_completo:          '',
            fecha_emision:            new Date().toISOString().slice(0, 10),
            fecha_vencimiento:        '',
            periodo_servicio_inicio:  '',
            periodo_servicio_fin:     '',
            moneda:                   'GTQ',
            descuento:                0,
            notas:                    '',
            estado:                   'BORRADOR',
            saldo_pendiente:          0,
            detalles:                 [],
        },
    },

    // ── Computed ───────────────────────────────────────────────────
    computed: {
        clienteSeleccionado() {
            return this.clientes.find(c => c.id === this.form.id_cliente) || null;
        },

        // Solo contratos del cliente seleccionado
        contratosFiltrados() {
            if (!this.form.id_cliente) return this.contratos;
            return this.contratos.filter(c => c.id_cliente === this.form.id_cliente);
        },

        contratoSeleccionado() {
            return this.contratos.find(c => c.id === this.form.id_contrato) || null;
        },

        serviciosAgrupados() {
            return this.tiposServicio.reduce((grupos, s) => {
                if (!grupos[s.linea]) grupos[s.linea] = [];
                grupos[s.linea].push(s);
                return grupos;
            }, {});
        },

        totales() {
            const subtotal      = this.form.detalles
                .reduce((sum, l) => sum + (parseFloat(l.subtotal) || 0), 0);
            const descuento     = parseFloat(this.form.descuento) || 0;
            const baseImponible = +(subtotal - descuento).toFixed(4);
            const iva           = +(baseImponible * 0.12).toFixed(4);
            const total         = +(baseImponible + iva).toFixed(4);
            return { subtotal, descuento, baseImponible, iva, total };
        },
    },

    // ── Lifecycle ──────────────────────────────────────────────────
    mounted() {
        this.cargarDatos();
        this.cargarCatalogos();
        if (typeof feather !== 'undefined') feather.replace();
    },

    // ── Methods ────────────────────────────────────────────────────
    methods: {

        formatear(num) {
            return new Intl.NumberFormat('es-GT', {
                minimumFractionDigits: 2, maximumFractionDigits: 2,
            }).format(num || 0);
        },

        badgeEstado(estado) {
            const mapa = {
                BORRADOR: 'badge-soft-secondary',
                EMITIDA:  'badge-soft-primary',
                ENVIADA:  'badge-soft-info',
                PARCIAL:  'badge-soft-warning',
                PAGADA:   'badge-soft-success',
                VENCIDA:  'badge-soft-danger',
                ANULADA:  'badge-soft-dark',
            };
            return mapa[estado] || 'badge-soft-secondary';
        },

        // ── Cargar listado con filtros ─────────────────────────────
        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const params = new URLSearchParams();
                if (this.filtros.estado)      params.append('estado',      this.filtros.estado);
                if (this.filtros.fecha_desde) params.append('fecha_desde', this.filtros.fecha_desde);
                if (this.filtros.fecha_hasta) params.append('fecha_hasta', this.filtros.fecha_hasta);

                const res  = await fetch(`${apiUrl}/finanzas/facturas?${params}`);
                const data = await res.json();
                if (data.success) this.facturas = data.data;
            } catch (e) {
                console.error('Error cargando facturas:', e);
                toastr.error('No se pudo cargar el listado.');
            } finally {
                this.cargandoTabla = false;
                this.$nextTick(() => feather.replace());
            }
        },

        // ── Cargar catálogos ───────────────────────────────────────
        async cargarCatalogos() {
            try {
                const res  = await fetch(`${apiUrl}/finanzas/facturas/catalogos`);
                const data = await res.json();
                if (data.success) {
                    this.clientes      = data.data.clientes      || [];
                    this.series        = data.data.series        || [];
                    this.tiposServicio = data.data.tiposServicio || [];
                    this.contratos     = data.data.contratos     || [];
                    this.monedas       = data.data.monedas       || ['GTQ'];
                    this.tipos         = data.data.tipos         || ['FACTURA'];
                }
            } catch (e) {
                console.error('Error cargando catálogos:', e);
            }
        },

        // ── Al cambiar cliente ─────────────────────────────────────
        onClienteChange() {
            // Limpiar contrato si no pertenece al nuevo cliente
            if (this.form.id_contrato) {
                const contrato = this.contratos.find(c => c.id === this.form.id_contrato);
                if (contrato && contrato.id_cliente !== this.form.id_cliente) {
                    this.form.id_contrato = null;
                }
            }
            const c = this.clienteSeleccionado;
            if (c) {
                this.form.moneda = c.moneda_facturacion || 'GTQ';
                this.calcularVencimiento();
            }
        },

        // ── Al cambiar contrato ────────────────────────────────────
        onContratoChange() {
            const c = this.contratoSeleccionado;
            if (c) {
                this.form.moneda = c.moneda;
            }
        },

        calcularVencimiento() {
            const c = this.clienteSeleccionado;
            if (c && this.form.fecha_emision) {
                const fecha = new Date(this.form.fecha_emision);
                fecha.setDate(fecha.getDate() + (c.dias_credito || 30));
                this.form.fecha_vencimiento = fecha.toISOString().slice(0, 10);
            }
        },

        // ── Cargar líneas desde el contrato ───────────────────────
        async cargarLineasContrato() {
            if (!this.form.id_contrato) return;
            this.cargandoLineas = true;
            try {
                const res  = await fetch(
                    `${apiUrl}/finanzas/facturas/contrato/${this.form.id_contrato}/lineas`
                );
                const data = await res.json();
                if (data.success && data.data.length > 0) {
                    this.form.detalles = data.data;
                    toastr.success(`${data.data.length} líneas cargadas del contrato.`);
                } else {
                    toastr.warning('El contrato no tiene líneas de detalle.');
                }
            } catch (e) {
                toastr.error('Error al cargar líneas del contrato.');
            } finally {
                this.cargandoLineas = false;
            }
        },

        // ── Manejo de líneas ───────────────────────────────────────
        agregarLinea() {
            this.form.detalles.push({
                id_tipo_servicio: null,
                descripcion:      '',
                cantidad:         1,
                precio_unitario:  0,
                descuento:        0,
                es_afecto_iva:    true,
                subtotal:         0,
            });
            this.$nextTick(() => feather.replace());
        },

        quitarLinea(idx) {
            this.form.detalles.splice(idx, 1);
        },

        onServicioChange(idx) {
            const linea = this.form.detalles[idx];
            const svc   = this.tiposServicio.find(s => s.id === linea.id_tipo_servicio);
            if (svc) {
                linea.descripcion     = svc.name;
                linea.precio_unitario = parseFloat(svc.precio_base) || 0;
                this.recalcularLinea(idx);
            }
        },

        recalcularLinea(idx) {
            const l    = this.form.detalles[idx];
            const base = (parseFloat(l.cantidad) || 0) * (parseFloat(l.precio_unitario) || 0);
            l.subtotal = +(base - (parseFloat(l.descuento) || 0)).toFixed(4);
        },

        // ── Modal: abrir nuevo ─────────────────────────────────────
        abrirModalCrear() {
            this.modoEditar = false;
            this.errores    = {};
            this.form = {
                id_factura:              null,
                id_cliente:              null,
                id_contrato:             null,
                id_serie:                null,
                tipo:                    'FACTURA',
                numero_completo:         '',
                fecha_emision:           new Date().toISOString().slice(0, 10),
                fecha_vencimiento:       '',
                periodo_servicio_inicio: '',
                periodo_servicio_fin:    '',
                moneda:                  'GTQ',
                descuento:               0,
                notas:                   '',
                estado:                  'BORRADOR',
                saldo_pendiente:         0,
                detalles:                [],
            };
            this.mostrarModal = true;
            this.$nextTick(() => feather.replace());
        },

        // ── Modal: abrir editar ────────────────────────────────────
        async abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores    = {};
            try {
                const res  = await fetch(`${apiUrl}/finanzas/facturas/${item.id_factura}`);
                const data = await res.json();
                if (data.success) {
                    const f = data.data;
                    this.form = {
                        id_factura:              f.id_factura,
                        id_cliente:              f.id_cliente,
                        id_contrato:             f.id_contrato || null,
                        id_serie:                f.id_serie,
                        tipo:                    f.tipo,
                        numero_completo:         f.numero_completo,
                        fecha_emision:           f.fecha_emision,
                        fecha_vencimiento:       f.fecha_vencimiento,
                        periodo_servicio_inicio: f.periodo_servicio_inicio || '',
                        periodo_servicio_fin:    f.periodo_servicio_fin    || '',
                        moneda:                  f.moneda,
                        descuento:               parseFloat(f.descuento),
                        notas:                   f.notas || '',
                        estado:                  f.estado,
                        saldo_pendiente:         parseFloat(f.saldo_pendiente),
                        detalles: f.detalles.map(d => ({
                            id_tipo_servicio: d.id_tipo_servicio,
                            descripcion:      d.descripcion,
                            cantidad:         parseFloat(d.cantidad),
                            precio_unitario:  parseFloat(d.precio_unitario),
                            descuento:        parseFloat(d.descuento),
                            es_afecto_iva:    !!d.es_afecto_iva,
                            subtotal:         parseFloat(d.subtotal),
                        })),
                    };
                    this.mostrarModal = true;
                    this.$nextTick(() => feather.replace());
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar la factura.', 'error');
            }
        },

        // ── Guardar (crear o editar) ───────────────────────────────
        async guardarRegistro() {
            if (!this.form.id_cliente) {
                this.errores = { id_cliente: 'Selecciona un cliente.' };
                return;
            }
            if (!this.form.id_serie) {
                this.errores = { id_serie: 'Selecciona una serie.' };
                return;
            }
            if (this.form.detalles.length === 0) {
                Swal.fire('Aviso', 'Agrega al menos una línea a la factura.', 'warning');
                return;
            }

            this.guardando = true;
            this.errores   = {};
            try {
                const url    = this.modoEditar
                    ? `${apiUrl}/finanzas/facturas/${this.form.id_factura}`
                    : `${apiUrl}/finanzas/facturas`;
                const method = this.modoEditar ? 'PUT' : 'POST';

                const res  = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(this.form),
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success', title: data.message,
                        timer: 2000, showConfirmButton: false,
                    });
                    this.mostrarModal = false;
                    this.cargarDatos();
                } else if (res.status === 422) {
                    this.errores = Object.fromEntries(
                        Object.entries(data.errors || {})
                            .map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
                    );
                    toastr.warning(data.message || 'Revisa los campos marcados.');
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            } finally {
                this.guardando = false;
            }
        },

        // ── Cambio de estado genérico ──────────────────────────────
        async cambiarEstado(nuevoEstado) {
            const etiquetas = {
                ENVIADA: 'marcar como enviada',
                VENCIDA: 'marcar como vencida',
            };

            // Capturar valores ANTES de cerrar el modal
            const idFactura      = this.form.id_factura;
            const numeroCompleto = this.form.numero_completo;

            const ok = await Swal.fire({
                title:             `¿${etiquetas[nuevoEstado] || nuevoEstado}?`,
                html:              `Factura <b>${numeroCompleto}</b>`,
                icon:              'question',
                showCancelButton:  true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText:  'Cancelar',
            });
            if (!ok.isConfirmed) return;

            try {
                const res = await fetch(
                    `${apiUrl}/finanzas/facturas/${idFactura}/estado`,
                    {
                        method:  'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept':       'application/json',
                        },
                        body:    JSON.stringify({ estado: nuevoEstado }),
                    }
                );

                // Parsear JSON con manejo de error explícito
                let data;
                try {
                    data = await res.json();
                } catch (parseErr) {
                    console.error('Respuesta no es JSON válido:', parseErr);
                    Swal.fire('Error', 'Respuesta inválida del servidor.', 'error');
                    return;
                }

                if (res.ok && data.success) {
                    this.mostrarModal = false;
                    await Swal.fire({
                        icon:              'success',
                        title:             data.message,
                        timer:             1500,
                        showConfirmButton: false,
                    });
                    await this.cargarDatos();
                } else {
                    Swal.fire('Aviso', data.message || 'No se pudo cambiar el estado.', 'warning');
                }
            } catch (e) {
                // Solo log real para diagnosticar
                console.error('Error real en cambiarEstado:', e);
                Swal.fire('Error', 'Error de conexión: ' + (e.message || 'desconocido'), 'error');
            }
        },

        // ── Emitir ────────────────────────────────────────────────
        async emitirDesdeModal() {
            const ok = await Swal.fire({
                title:             '¿Emitir factura?',
                html:              `<b>${this.form.numero_completo}</b><br><small>Pasará a estado EMITIDA.</small>`,
                icon:              'question',
                showCancelButton:  true,
                confirmButtonColor:'#00d27a',
                confirmButtonText: 'Sí, emitir',
                cancelButtonText:  'Cancelar',
            });
            if (!ok.isConfirmed) return;

            try {
                const res  = await fetch(
                    `${apiUrl}/finanzas/facturas/${this.form.id_factura}/emitir`,
                    { method: 'PATCH' }
                );
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message,
                                timer: 1500, showConfirmButton: false });
                    this.mostrarModal = false;
                    this.cargarDatos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },

        // ── Anular ────────────────────────────────────────────────
        async anularDesdeModal() {
            const ok = await Swal.fire({
                title:             '¿Anular factura?',
                html:              `<b>${this.form.numero_completo}</b><br><small class="text-danger">Esta acción no se puede revertir.</small>`,
                icon:              'warning',
                showCancelButton:  true,
                confirmButtonColor:'#e63757',
                confirmButtonText: 'Sí, anular',
                cancelButtonText:  'Cancelar',
            });
            if (!ok.isConfirmed) return;

            try {
                const res  = await fetch(
                    `${apiUrl}/finanzas/facturas/${this.form.id_factura}/anular`,
                    { method: 'PATCH' }
                );
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message,
                                timer: 1500, showConfirmButton: false });
                    this.mostrarModal = false;
                    this.cargarDatos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },

        // ── Eliminar ──────────────────────────────────────────────
        async eliminarRegistro(item) {
            if (item.estado !== 'BORRADOR') {
                Swal.fire('Aviso', 'Solo se pueden eliminar facturas en BORRADOR.', 'warning');
                return;
            }
            const ok = await Swal.fire({
                title:             '¿Eliminar factura?',
                html:              `<b>${item.numero_completo}</b>`,
                icon:              'warning',
                showCancelButton:  true,
                confirmButtonColor:'#e63757',
                confirmButtonText: 'Sí, eliminar',
            });
            if (!ok.isConfirmed) return;

            try {
                const res  = await fetch(
                    `${apiUrl}/finanzas/facturas/${item.id_factura}`,
                    { method: 'DELETE' }
                );
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message,
                                timer: 1500, showConfirmButton: false });
                    this.cargarDatos();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },

        limpiarFiltros() {
            this.filtros = { estado: '', fecha_desde: '', fecha_hasta: '' };
            this.cargarDatos();
        },
    },
});