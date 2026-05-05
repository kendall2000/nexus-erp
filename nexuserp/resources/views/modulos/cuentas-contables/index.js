/**
 * NexusERP — Módulo Cuentas Contables
 * resources/views/modulos/cuentas-contables/index.js
 * Vue 2 CDN + Phoenix + SheetJS para Excel
 */

// ════════════════════════════════════════════════════════════
// COMPONENTE: FeatherIcon (renderiza SVG sin tocar DOM externo)
// ════════════════════════════════════════════════════════════
Vue.component('feather-icon', {
    props: {
        name:  { type: String, required: true },
        size:  { type: [String, Number], default: 14 },
        color: { type: String, default: '' },
    },
    computed: {
        svgHtml() {
            if (typeof feather === 'undefined' || !feather.icons[this.name]) return '';
            return feather.icons[this.name].toSvg({
                width:  this.size,
                height: this.size,
            });
        },
        svgStyle() {
            return this.color ? { color: this.color } : {};
        },
    },
    template: `<span class="feather-wrapper" :style="svgStyle" v-html="svgHtml"></span>`,
});

// ════════════════════════════════════════════════════════════
// COMPONENTE RECURSIVO: NodoCuenta
// ════════════════════════════════════════════════════════════
Vue.component('nodo-cuenta', {
    template: '#tpl-nodo-cuenta',
    props: {
        cuenta:        { type: Object, required: true },
        nodosAbiertos: { type: Object, required: true },
    },
    computed: {
        abierto() {
            return !!this.nodosAbiertos[this.cuenta.id_cuenta];
        },
        iconoTipo() {
            const m = {
                ACTIVO:     'package',
                PASIVO:     'credit-card',
                PATRIMONIO: 'shield',
                INGRESO:    'trending-up',
                GASTO:      'trending-down',
                COSTO:      'shopping-cart',
            };
            return m[this.cuenta.tipo] || 'circle';
        },
        badgeTipo() {
            const m = {
                ACTIVO:     'badge-soft-primary',
                PASIVO:     'badge-soft-warning',
                PATRIMONIO: 'badge-soft-info',
                INGRESO:    'badge-soft-success',
                GASTO:      'badge-soft-danger',
                COSTO:      'badge-soft-secondary',
            };
            return m[this.cuenta.tipo] || 'badge-soft-secondary';
        },
    },
    // ⚠️ NO usar feather.replace() aquí — el componente <feather-icon> renderiza solo
});

// ════════════════════════════════════════════════════════════
// INSTANCIA PRINCIPAL
// ════════════════════════════════════════════════════════════
new Vue({
    el: '#cuentas-contables-app',

    data: {
        vista: 'arbol',

        // ── Árbol ──
        arbol:         [],
        cargandoArbol: false,
        nodosAbiertos: {},

        // ── Tabla ──
        cuentas:       [],
        cargandoTabla: false,
        columnas: [
            { key: 'codigo',             label: 'Código'     },
            { key: 'nombre',             label: 'Nombre'     },
            { key: 'tipo',               label: 'Tipo'       },
            { key: 'naturaleza',         label: 'Naturaleza' },
            { key: 'nivel',              label: 'Nivel'      },
            { key: 'permite_movimiento', label: 'Mov.'       },
            { key: 'activo',             label: 'Estado'     },
        ],

        // ── Catálogos ──
        tipos:            [],
        naturalezas:      [],
        cuentasParaPadre: [],

        // ── Modal CRUD ──
        mostrarModal: false,
        modoEditar:   false,
        guardando:    false,
        errores:      {},
        form: {
            id_cuenta: null, id_padre: null,
            codigo: '', nombre: '',
            tipo: null, naturaleza: null,
            permite_movimiento: true, activo: true,
        },

        // ── Modal Import ──
        mostrarModalImport: false,
        paso:               'inicio',
        filasArchivo:       [],
        analisis:           { total: 0, nuevas: 0, actualizar: 0, errores: [] },
        importando:         false,
    },

    mounted() {
        this.cargarArbol();
        this.cargarCatalogos();
    },

    methods: {
        // ════════════════════════════════════════════════════════════
        // CARGA DE DATOS
        // ════════════════════════════════════════════════════════════
        async cargarArbol() {
            this.cargandoArbol = true;
            try {
                const res  = await fetch(`${apiUrl}/core/cuentas-contables/arbol`);
                const data = await res.json();
                if (data.success) this.arbol = data.data;
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar el árbol.', 'error');
            } finally {
                this.cargandoArbol = false;
            }
        },

        async cargarTabla() {
            this.cargandoTabla = true;
            try {
                const res  = await fetch(`${apiUrl}/core/cuentas-contables`);
                const data = await res.json();
                if (data.success) this.cuentas = data.data;
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar la tabla.', 'error');
            } finally {
                this.cargandoTabla = false;
            }
        },

        async cargarCatalogos() {
            try {
                const res  = await fetch(`${apiUrl}/core/cuentas-contables/catalogo`);
                const data = await res.json();
                if (data.success) {
                    this.cuentasParaPadre = data.data.cuentas;
                    this.tipos            = data.data.tipos;
                    this.naturalezas      = data.data.naturalezas;
                }
            } catch (e) {
                console.error('Error cargando catálogos:', e);
            }
        },

        // ════════════════════════════════════════════════════════════
        // ÁRBOL
        // ════════════════════════════════════════════════════════════
        toggleNodo(idCuenta) {
            this.$set(this.nodosAbiertos, idCuenta, !this.nodosAbiertos[idCuenta]);
        },

        expandirTodos(abrir) {
            const aplicar = (nodos) => {
                nodos.forEach(n => {
                    this.$set(this.nodosAbiertos, n.id_cuenta, abrir);
                    if (n.hijas && n.hijas.length) aplicar(n.hijas);
                });
            };
            aplicar(this.arbol);
        },

        // ════════════════════════════════════════════════════════════
        // CRUD
        // ════════════════════════════════════════════════════════════
        abrirModalCrear() {
            this.modoEditar = false;
            this.errores    = {};
            this.form = {
                id_cuenta: null, id_padre: null,
                codigo: '', nombre: '',
                tipo: null, naturaleza: null,
                permite_movimiento: true, activo: true,
            };
            this.mostrarModal = true;
        },

        async abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores    = {};
            try {
                const res  = await fetch(`${apiUrl}/core/cuentas-contables/${item.id_cuenta}`);
                const data = await res.json();
                if (data.success) {
                    this.form = {
                        id_cuenta:          data.data.id_cuenta,
                        id_padre:           data.data.id_padre,
                        codigo:             data.data.codigo,
                        nombre:             data.data.nombre,
                        tipo:               data.data.tipo,
                        naturaleza:         data.data.naturaleza,
                        permite_movimiento: !!data.data.permite_movimiento,
                        activo:             !!data.data.activo,
                    };
                    this.mostrarModal = true;
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar la cuenta.', 'error');
            }
        },

        async guardarRegistro() {
            this.guardando = true;
            this.errores   = {};
            try {
                const url    = this.modoEditar
                    ? `${apiUrl}/core/cuentas-contables/${this.form.id_cuenta}`
                    : `${apiUrl}/core/cuentas-contables`;
                const method = this.modoEditar ? 'PUT' : 'POST';

                const res  = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(this.form),
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.mostrarModal = false;
                    await Swal.fire({ icon:'success', title:data.message,
                                       timer:1500, showConfirmButton:false });
                    this.cargarArbol();
                    this.cargarTabla();
                    this.cargarCatalogos();
                } else if (res.status === 422) {
                    this.errores = Object.fromEntries(
                        Object.entries(data.errors || {})
                            .map(([k,v]) => [k, Array.isArray(v) ? v[0] : v])
                    );
                    Swal.fire('Aviso', data.message || 'Revisa los datos.', 'warning');
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            } finally {
                this.guardando = false;
            }
        },

        async toggleEstado(item) {
            try {
                const res  = await fetch(
                    `${apiUrl}/core/cuentas-contables/${item.id_cuenta}/toggle`,
                    { method: 'PATCH' }
                );
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon:'success', title:data.message,
                                 timer:1500, showConfirmButton:false });
                    this.cargarArbol();
                    this.cargarTabla();
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },

        async eliminarRegistro(item) {
            const ok = await Swal.fire({
                title: '¿Eliminar cuenta?',
                html:  `<b>${item.codigo} — ${item.nombre}</b>`,
                icon: 'warning',
                showCancelButton:   true,
                confirmButtonColor: '#e63757',
                confirmButtonText:  'Sí, eliminar',
            });
            if (!ok.isConfirmed) return;

            try {
                const res  = await fetch(
                    `${apiUrl}/core/cuentas-contables/${item.id_cuenta}`,
                    { method: 'DELETE' }
                );
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon:'success', title:data.message,
                                 timer:1500, showConfirmButton:false });
                    this.cargarArbol();
                    this.cargarTabla();
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            }
        },

        // ════════════════════════════════════════════════════════════
        // IMPORTAR EXCEL
        // ════════════════════════════════════════════════════════════
        abrirModalImport() {
            this.paso         = 'inicio';
            this.filasArchivo = [];
            this.analisis     = { total: 0, nuevas: 0, actualizar: 0, errores: [] };
            this.mostrarModalImport = true;
        },

        async procesarArchivo(event) {
            const file = event.target.files[0];
            if (!file) return;

            try {
                const buffer   = await file.arrayBuffer();
                const workbook = XLSX.read(buffer, { type: 'array' });
                const hoja     = workbook.Sheets[workbook.SheetNames[0]];
                const json     = XLSX.utils.sheet_to_json(hoja, { defval: '' });

                if (!json.length) {
                    Swal.fire('Error', 'El archivo está vacío.', 'error');
                    return;
                }

                this.filasArchivo = json.map(row => ({
                    codigo:             String(row.codigo || row.Codigo || row.CODIGO || '').trim(),
                    nombre:             String(row.nombre || row.Nombre || row.NOMBRE || '').trim(),
                    tipo:               String(row.tipo || row.Tipo || row.TIPO || '').trim().toUpperCase(),
                    naturaleza:         String(row.naturaleza || row.Naturaleza || row.NATURALEZA || '').trim().toUpperCase(),
                    codigo_padre:       String(row.codigo_padre || row.codigoPadre || row.padre || '').trim(),
                    permite_movimiento: ['1','TRUE','SI','SÍ','S','Y','YES'].includes(
                        String(row.permite_movimiento || 'true').trim().toUpperCase()
                    ),
                })).filter(r => r.codigo);

                await this.solicitarPreview();
                this.paso = 'preview';
            } catch (e) {
                console.error('Error parseando archivo:', e);
                Swal.fire('Error', 'No se pudo leer el archivo. Verifica el formato.', 'error');
            }
        },

        async solicitarPreview() {
            try {
                const res = await fetch(`${apiUrl}/core/cuentas-contables/import/preview`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cuentas: this.filasArchivo }),
                });
                const data = await res.json();
                if (data.success) {
                    this.analisis = data.data;
                } else {
                    Swal.fire('Error', data.message || 'Error en la validación.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error al validar archivo.', 'error');
            }
        },

        async confirmarImport() {
            if (this.analisis.errores.length > 0) {
                Swal.fire('Aviso', 'Corrige los errores antes de importar.', 'warning');
                return;
            }

            const ok = await Swal.fire({
                title: '¿Confirmar importación?',
                html: `Se procesarán <b>${this.analisis.nuevas} nuevas</b> y se actualizarán <b>${this.analisis.actualizar}</b> cuentas.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, importar',
                confirmButtonColor: '#00d27a',
            });
            if (!ok.isConfirmed) return;

            this.importando = true;
            try {
                const res = await fetch(`${apiUrl}/core/cuentas-contables/import/commit`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cuentas: this.filasArchivo }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.mostrarModalImport = false;
                    await Swal.fire({ icon:'success', title:data.message,
                                       timer:2500, showConfirmButton:false });
                    this.cargarArbol();
                    this.cargarTabla();
                    this.cargarCatalogos();
                } else {
                    Swal.fire('Error', data.message || 'Error al importar.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión.', 'error');
            } finally {
                this.importando = false;
            }
        },

        descargarPlantilla() {
            const datos = [
                { codigo: '1',         nombre: 'ACTIVO',           tipo: 'ACTIVO',  naturaleza: 'DEUDORA',   codigo_padre: '',     permite_movimiento: false },
                { codigo: '1.01',      nombre: 'Activo Corriente', tipo: 'ACTIVO',  naturaleza: 'DEUDORA',   codigo_padre: '1',    permite_movimiento: false },
                { codigo: '1.01.001',  nombre: 'Caja General',     tipo: 'ACTIVO',  naturaleza: 'DEUDORA',   codigo_padre: '1.01', permite_movimiento: true  },
                { codigo: '4',         nombre: 'INGRESOS',         tipo: 'INGRESO', naturaleza: 'ACREEDORA', codigo_padre: '',     permite_movimiento: false },
                { codigo: '4.01.001',  nombre: 'Ventas Servicios', tipo: 'INGRESO', naturaleza: 'ACREEDORA', codigo_padre: '4',    permite_movimiento: true  },
            ];
            const ws = XLSX.utils.json_to_sheet(datos);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Cuentas');
            XLSX.writeFile(wb, 'plantilla_cuentas_contables.xlsx');
        },
    },
});