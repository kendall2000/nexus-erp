/**
 * NexusERP — Módulo Presupuesto Anual
 * resources/views/modulos/presupuesto/index.js
 * Vue 2 CDN + Phoenix Template + ECharts
 */
new Vue({
    el: '#presupuesto-app',

    data: {
        // ── Tabla ──────────────────────────────────────────────────
        presupuestos:  [],
        cargandoTabla: false,
        anioActivo:    new Date().getFullYear(),
        columnas: [
            { key: 'centro',               label: 'Centro Costo'  },
            { key: 'cuenta',               label: 'Cuenta'        },
            { key: 'tipo_cuenta',          label: 'Tipo'          },
            { key: 'moneda',               label: 'Mon.'          },
            { key: 'total_presupuestado',  label: 'Presupuestado' },
            { key: 'total_ejecutado',      label: 'Ejecutado'     },
            { key: 'saldo_disponible',     label: 'Disponible'    },
            { key: 'porcentaje_ejecucion', label: '% Ejec.'       },
            { key: 'estado',               label: 'Estado'        },
        ],

        // ── Catálogos ──────────────────────────────────────────────
        centros:           [],
        cuentas:           [],
        monedas:           [],
        aniosDisponibles:  [],

        // ── Dashboard ──────────────────────────────────────────────
        kpi: {
            total_presupuestado: 0,
            total_ejecutado:     0,
            saldo_disponible:    0,
            porcentaje_ejecucion: 0,
            moneda:              'GTQ',
        },
        topCuentas:       [],
        chartMensual:     null,
        chartComparativo: null,

        // ── Modal CRUD ─────────────────────────────────────────────
        mostrarModal: false,
        modoEditar:   false,
        guardando:    false,
        errores:      {},
        nombresMeses: ['enero','febrero','marzo','abril','mayo','junio',
                       'julio','agosto','septiembre','octubre','noviembre','diciembre'],
        form: {},

        // ── Modal Clonar ───────────────────────────────────────────
        mostrarModalClonar: false,
        clonando:           false,
        clonarForm: {
            anio_origen:    new Date().getFullYear() - 1,
            anio_destino:   new Date().getFullYear(),
            incremento_pct: 0,
        },
    },

    computed: {
        totalPresupuestado() {
            return this.nombresMeses.reduce(
                (s, m) => s + (parseFloat(this.form['pre_' + m]) || 0), 0
            );
        },
    },

    mounted() {
        this.inicializarForm();
        this.cargarCatalogos().then(() => {
            this.cargarDatos();
            this.cargarDashboard();
        });

        // ── Listener para redimensionar gráficos al cambiar tamaño ──
        window.addEventListener('resize', this.redimensionarGraficos);
    },

    beforeDestroy() {
        // Limpiar gráficos y listener para evitar memory leaks
        window.removeEventListener('resize', this.redimensionarGraficos);
        if (this.chartMensual)     this.chartMensual.dispose();
        if (this.chartComparativo) this.chartComparativo.dispose();
    },

    methods: {
        // ════════════════════════════════════════════════════════════
        // HELPERS DE UI
        // ════════════════════════════════════════════════════════════
        formatear(num) {
            return new Intl.NumberFormat('es-GT', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(num || 0);
        },

        capitalizar(s) {
            return s.charAt(0).toUpperCase() + s.slice(1);
        },

        badgeEstado(estado) {
            const m = {
                BORRADOR: 'badge-soft-secondary',
                APROBADO: 'badge-soft-success',
                CERRADO:  'badge-soft-dark',
            };
            return m[estado] || 'badge-soft-secondary';
        },

        badgeEjecucion(pct) {
            if (pct > 100) return 'bg-danger';
            if (pct >= 90) return 'bg-warning text-dark';
            if (pct >= 75) return 'bg-info';
            return 'bg-success';
        },

        claseProgreso(pct) {
            if (pct > 100) return 'bg-danger';
            if (pct >= 90) return 'bg-warning';
            if (pct >= 75) return 'bg-info';
            return 'bg-success';
        },

        // ── Formato compacto para ejes (1.5K, 2M, etc) ──
        formatoCompacto(v) {
            if (v >= 1_000_000) return (v / 1_000_000).toFixed(1) + 'M';
            if (v >= 1_000)     return (v / 1_000).toFixed(0) + 'K';
            return v;
        },

        // ── Formato monetario para tooltips ──
        formatoMoneda(v) {
            return 'Q ' + this.formatear(v || 0);
        },

        // ════════════════════════════════════════════════════════════
        // INICIALIZAR FORM
        // ════════════════════════════════════════════════════════════
        inicializarForm() {
            const base = {
                id_presupuesto: null,
                id_centro:      null,
                id_cuenta:      null,
                anio:           new Date().getFullYear(),
                moneda:         'GTQ',
                estado:         'BORRADOR',
            };
            this.nombresMeses.forEach(m => {
                base['pre_' + m] = 0;
                base['eje_' + m] = 0;
            });
            this.form = base;
        },

        // ════════════════════════════════════════════════════════════
        // API CALLS
        // ════════════════════════════════════════════════════════════
        async cargarCatalogos() {
            try {
                const res  = await fetch(`${apiUrl}/finanzas/presupuestos/catalogos`);
                const data = await res.json();
                if (data.success) {
                    this.centros          = data.data.centros;
                    this.cuentas          = data.data.cuentas;
                    this.monedas          = data.data.monedas;
                    this.aniosDisponibles = data.data.anios;
                }
            } catch (e) {
                console.error('Error catálogos:', e);
            }
        },

        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const res  = await fetch(`${apiUrl}/finanzas/presupuestos?anio=${this.anioActivo}`);
                const data = await res.json();
                if (data.success) this.presupuestos = data.data;
            } catch (e) {
                console.error('Error listado:', e);
                Swal.fire('Error', 'No se pudo cargar el listado.', 'error');
            } finally {
                this.cargandoTabla = false;
                this.$nextTick(() => {
                    if (typeof feather !== 'undefined') feather.replace();
                });
            }
        },

        async cargarDashboard() {
            try {
                const res  = await fetch(`${apiUrl}/finanzas/presupuestos/dashboard?anio=${this.anioActivo}`);
                const data = await res.json();
                if (data.success) {
                    this.kpi        = { ...data.data.kpi, moneda: 'GTQ' };
                    this.topCuentas = data.data.top_cuentas;

                    this.$nextTick(() => {
                        this.renderChartMensual(data.data.serie_mensual);
                        this.renderChartComparativo(data.data.comparativo);
                    });
                }
            } catch (e) {
                console.error('Error dashboard:', e);
            }
        },

        // ════════════════════════════════════════════════════════════
        // ECHARTS — RENDER MENSUAL (presupuestado vs ejecutado)
        // ════════════════════════════════════════════════════════════
        renderChartMensual(serie) {
            const dom = document.getElementById('chart-mensual');
            if (!dom) return;

            // Reutilizar instancia existente (animación entre datasets)
            if (!this.chartMensual) {
                this.chartMensual = echarts.init(dom);
            }

            this.chartMensual.setOption({
                tooltip: {
                    trigger: 'axis',
                    valueFormatter: (v) => this.formatoMoneda(v),
                },
                legend: {
                    data: ['Presupuestado', 'Ejecutado'],
                    bottom: 0,
                    textStyle: { color: '#748194' },
                },
                grid: { left: 55, right: 25, top: 20, bottom: 40 },
                xAxis: {
                    type: 'category',
                    data: serie.map(s => s.mes.substring(0, 3)),
                    axisLine:  { lineStyle: { color: '#cbd0dd' } },
                    axisLabel: { color: '#748194' },
                },
                yAxis: {
                    type: 'value',
                    axisLine:  { lineStyle: { color: '#cbd0dd' } },
                    axisLabel: {
                        color:     '#748194',
                        formatter: (v) => this.formatoCompacto(v),
                    },
                    splitLine: { lineStyle: { color: '#eaedf2', type: 'dashed' } },
                },
                series: [
                    {
                        name:       'Presupuestado',
                        type:       'line',
                        smooth:     true,
                        symbol:     'circle',
                        symbolSize: 7,
                        data:       serie.map(s => s.presupuestado),
                        lineStyle:  { width: 3, color: '#2c7be5' },
                        itemStyle:  { color: '#2c7be5' },
                        areaStyle: {
                            color: {
                                type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                                colorStops: [
                                    { offset: 0, color: 'rgba(44,123,229,0.25)' },
                                    { offset: 1, color: 'rgba(44,123,229,0.02)' },
                                ],
                            },
                        },
                    },
                    {
                        name:       'Ejecutado',
                        type:       'line',
                        smooth:     true,
                        symbol:     'circle',
                        symbolSize: 7,
                        data:       serie.map(s => s.ejecutado),
                        lineStyle:  { width: 3, color: '#e63757' },
                        itemStyle:  { color: '#e63757' },
                        areaStyle: {
                            color: {
                                type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                                colorStops: [
                                    { offset: 0, color: 'rgba(230,55,87,0.25)' },
                                    { offset: 1, color: 'rgba(230,55,87,0.02)' },
                                ],
                            },
                        },
                    },
                ],
            }, true); // segundo parámetro: notMerge — sustituye toda la config
        },

        // ════════════════════════════════════════════════════════════
        // ECHARTS — RENDER COMPARATIVO (año actual vs anterior)
        // ════════════════════════════════════════════════════════════
        renderChartComparativo(comp) {
            const dom = document.getElementById('chart-comparativo');
            if (!dom) return;

            if (!this.chartComparativo) {
                this.chartComparativo = echarts.init(dom);
            }

            this.chartComparativo.setOption({
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    valueFormatter: (v) => this.formatoMoneda(v),
                },
                legend: {
                    data:   [String(this.anioActivo), String(this.anioActivo - 1)],
                    bottom: 0,
                    textStyle: { color: '#748194' },
                },
                grid: { left: 55, right: 25, top: 20, bottom: 40 },
                xAxis: {
                    type: 'category',
                    data: comp.map(c => c.mes.substring(0, 3)),
                    axisLine:  { lineStyle: { color: '#cbd0dd' } },
                    axisLabel: { color: '#748194' },
                },
                yAxis: {
                    type: 'value',
                    axisLine:  { lineStyle: { color: '#cbd0dd' } },
                    axisLabel: {
                        color:     '#748194',
                        formatter: (v) => this.formatoCompacto(v),
                    },
                    splitLine: { lineStyle: { color: '#eaedf2', type: 'dashed' } },
                },
                series: [
                    {
                        name:        String(this.anioActivo),
                        type:        'bar',
                        data:        comp.map(c => c.anio_actual),
                        itemStyle:   { color: '#00d27a', borderRadius: [3, 3, 0, 0] },
                        barMaxWidth: 18,
                    },
                    {
                        name:        String(this.anioActivo - 1),
                        type:        'bar',
                        data:        comp.map(c => c.anio_anterior),
                        itemStyle:   { color: '#748194', borderRadius: [3, 3, 0, 0] },
                        barMaxWidth: 18,
                    },
                ],
            }, true);
        },

        // ── Resize handler ──
        redimensionarGraficos() {
            if (this.chartMensual)     this.chartMensual.resize();
            if (this.chartComparativo) this.chartComparativo.resize();
        },

        // ════════════════════════════════════════════════════════════
        // CAMBIO DE AÑO
        // ════════════════════════════════════════════════════════════
        cambiarAnio() {
            this.cargarDatos();
            this.cargarDashboard();
        },

        // ════════════════════════════════════════════════════════════
        // MODAL CRUD
        // ════════════════════════════════════════════════════════════
        abrirModalCrear() {
            this.modoEditar   = false;
            this.errores      = {};
            this.inicializarForm();
            this.form.anio    = this.anioActivo;
            this.mostrarModal = true;
        },

        async abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores    = {};
            try {
                const res  = await fetch(`${apiUrl}/finanzas/presupuestos/${item.id_presupuesto}`);
                const data = await res.json();
                if (data.success) {
                    const d = data.data;
                    this.form = {
                        id_presupuesto: d.id_presupuesto,
                        id_centro:      d.id_centro,
                        id_cuenta:      d.id_cuenta,
                        anio:           d.anio,
                        moneda:         d.moneda,
                        estado:         d.estado,
                    };
                    d.meses.forEach(m => {
                        const nombre = this.nombresMeses[m.mes - 1];
                        this.form['pre_' + nombre] = m.presupuestado;
                        this.form['eje_' + nombre] = m.ejecutado;
                    });
                    this.mostrarModal = true;
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar el presupuesto.', 'error');
            }
        },

        // ── Acciones rápidas en el form ──
        async distribuirIgual() {
            const { value } = await Swal.fire({
                title:           'Distribuir total entre 12 meses',
                input:           'number',
                inputLabel:      `Total anual (${this.form.moneda})`,
                inputAttributes: { min: '0', step: '0.01' },
                showCancelButton: true,
            });
            if (!value) return;
            const mensual = parseFloat(value) / 12;
            this.nombresMeses.forEach(m => {
                this.form['pre_' + m] = +mensual.toFixed(4);
            });
        },

        copiarMesAnterior() {
            const enero = this.form.pre_enero || 0;
            this.nombresMeses.forEach(m => {
                this.form['pre_' + m] = enero;
            });
        },

        // ── Guardar ──
        async guardarRegistro() {
            this.guardando = true;
            this.errores   = {};
            try {
                const url    = this.modoEditar
                    ? `${apiUrl}/finanzas/presupuestos/${this.form.id_presupuesto}`
                    : `${apiUrl}/finanzas/presupuestos`;
                const method = this.modoEditar ? 'PUT' : 'POST';
                const res    = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(this.form),
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.mostrarModal = false;
                    await Swal.fire({
                        icon:              'success',
                        title:             data.message,
                        timer:             1500,
                        showConfirmButton: false,
                    });
                    this.cargarDatos();
                    this.cargarDashboard();
                } else if (res.status === 422) {
                    this.errores = Object.fromEntries(
                        Object.entries(data.errors || {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
                    );
                    Swal.fire('Aviso', data.message || 'Revisa los datos.', 'warning');
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar.', 'error');
                }
            } catch (e) {
                console.error('Error guardar:', e);
                Swal.fire('Error', 'Error de conexión.', 'error');
            } finally {
                this.guardando = false;
            }
        },

        // ════════════════════════════════════════════════════════════
        // STATE MACHINE — APROBAR / CERRAR
        // ════════════════════════════════════════════════════════════
        async aprobarPresupuesto() {
            const ok = await Swal.fire({
                title:              '¿Aprobar presupuesto?',
                text:               'Una vez aprobado no se podrán editar los montos.',
                icon:               'question',
                showCancelButton:   true,
                confirmButtonText:  'Sí, aprobar',
                confirmButtonColor: '#00d27a',
            });
            if (!ok.isConfirmed) return;
            await this.cambiarEstado('aprobar');
        },

        async cerrarPresupuesto() {
            const ok = await Swal.fire({
                title:              '¿Cerrar año?',
                text:               'El presupuesto quedará bloqueado permanentemente.',
                icon:               'warning',
                showCancelButton:   true,
                confirmButtonText:  'Sí, cerrar',
                confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            await this.cambiarEstado('cerrar');
        },

        async cambiarEstado(accion) {
            try {
                const res  = await fetch(
                    `${apiUrl}/finanzas/presupuestos/${this.form.id_presupuesto}/${accion}`,
                    { method: 'PATCH', headers: { 'Content-Type': 'application/json' } }
                );
                const data = await res.json();
                if (data.success) {
                    this.mostrarModal = false;
                    Swal.fire({
                        icon:              'success',
                        title:             data.message,
                        timer:             1500,
                        showConfirmButton: false,
                    });
                    this.cargarDatos();
                    this.cargarDashboard();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },

        // ════════════════════════════════════════════════════════════
        // ELIMINAR
        // ════════════════════════════════════════════════════════════
        async eliminarRegistro(item) {
            if (item.estado !== 'BORRADOR') {
                Swal.fire('Aviso', 'Solo se pueden eliminar presupuestos en BORRADOR.', 'warning');
                return;
            }
            const ok = await Swal.fire({
                title:              '¿Eliminar presupuesto?',
                html:               `<b>${item.cuenta}</b><br>${item.centro}`,
                icon:               'warning',
                showCancelButton:   true,
                confirmButtonColor: '#e63757',
                confirmButtonText:  'Sí, eliminar',
            });
            if (!ok.isConfirmed) return;
            try {
                const res  = await fetch(
                    `${apiUrl}/finanzas/presupuestos/${item.id_presupuesto}`,
                    { method: 'DELETE' }
                );
                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        icon:              'success',
                        title:             data.message,
                        timer:             1500,
                        showConfirmButton: false,
                    });
                    this.cargarDatos();
                    this.cargarDashboard();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },

        // ════════════════════════════════════════════════════════════
        // CLONAR
        // ════════════════════════════════════════════════════════════
        abrirModalClonar() {
            this.clonarForm = {
                anio_origen:    this.anioActivo - 1,
                anio_destino:   this.anioActivo,
                incremento_pct: 0,
            };
            this.mostrarModalClonar = true;
        },

        async ejecutarClonar() {
            this.clonando = true;
            try {
                const res  = await fetch(`${apiUrl}/finanzas/presupuestos/clonar`, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(this.clonarForm),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.mostrarModalClonar = false;
                    await Swal.fire({
                        icon:              'success',
                        title:             data.message,
                        timer:             2000,
                        showConfirmButton: false,
                    });
                    this.anioActivo = this.clonarForm.anio_destino;
                    this.cargarDatos();
                    this.cargarDashboard();
                } else {
                    Swal.fire('Aviso', data.message || 'No se pudo clonar.', 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            } finally {
                this.clonando = false;
            }
        },
    },
});