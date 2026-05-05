new Vue({
    el: '#clientes-app',
    data: {
        clientes: [],
        industrias: [],
        divisiones: [],
        municipios: [],
        cargandoDivisiones: false,
        cargandoMunicipios: false,
        cargandoTabla: false,
        columnas: [
            { key: 'razon_social',      label: 'Razón Social' },
            { key: 'nombre_comercial',  label: 'Nombre Comercial' },
            { key: 'nit',               label: 'NIT' },
            { key: 'pais',              label: 'País' },
            { key: 'tipo_persona',      label: 'Tipo' },
            { key: 'segmento',          label: 'Segmento' },
            { key: 'dias_credito',      label: 'Crédito' },
            { key: 'moneda_facturacion',label: 'Moneda' },
            { key: 'activo',            label: 'Estado' },
        ],
        paises: [], monedas: [], tipos: [], segmentos: [], categorias: [],
        mostrarModal: false, modoEditar: false, guardando: false,
        form: {
            id_cliente: null, id_pais: null,
            razon_social: '', nombre_comercial: '', nit: '',
            tipo_persona: 'JURIDICA', email_principal: '',
            telefono_principal: '', sitio_web: '', direccion_fiscal: '',
            segmento: null, categoria: null,
            moneda_facturacion: 'GTQ', dias_credito: 30, limite_credito: null,
        },
        errores: {},
    },

    mounted() {
        this.cargarDatos();
        this.cargarCatalogos();
        if (typeof feather !== 'undefined') feather.replace();
    },

    methods: {
        async onPaisChange() {
            this.form.id_division  = null;
            this.form.id_municipio = null;
            this.divisiones        = [];
            this.municipios        = [];
            if (!this.form.id_pais) return;

            this.cargandoDivisiones = true;
            try {
                const res  = await fetch(apiUrl + '/geografia/divisiones/' + this.form.id_pais);
                const data = await res.json();
                if (data.success) this.divisiones = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargandoDivisiones = false; }
        },

        async onDivisionChange() {
            this.form.id_municipio = null;
            this.municipios        = [];
            if (!this.form.id_division) return;

            this.cargandoMunicipios = true;
            try {
                const res  = await fetch(apiUrl + '/geografia/municipios/' + this.form.id_division);
                const data = await res.json();
                if (data.success) this.municipios = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargandoMunicipios = false; }
        },

        // Carga cascada al editar
        async cargarCascadaEdicion(idPais, idDivision) {
            if (idPais) {
                const res  = await fetch(apiUrl + '/geografia/divisiones/' + idPais);
                const data = await res.json();
                if (data.success) this.divisiones = data.data;
            }
            if (idDivision) {
                const res  = await fetch(apiUrl + '/geografia/municipios/' + idDivision);
                const data = await res.json();
                if (data.success) this.municipios = data.data;
            }
        },
        async cargarDatos() {
            this.cargandoTabla = true;
            try {
                const res  = await fetch(apiUrl + '/clientes/clientes');
                const data = await res.json();
                if (data.success) this.clientes = data.data;
            } catch (e) { console.error(e); }
            finally { this.cargandoTabla = false; }
        },

        async cargarCatalogos() {
            try {
                const res  = await fetch(apiUrl + '/clientes/clientes/catalogos');
                const data = await res.json();
                if (data.success) {
                    this.paises    = data.data.paises    || [];
                    this.monedas   = data.data.monedas   || ['GTQ'];
                    this.tipos     = data.data.tipos     || [];
                    this.segmentos = data.data.segmentos || [];
                    this.categorias= data.data.categorias|| [];
                    this.industrias = data.data.industrias || [];
                }
            } catch (e) { console.error(e); }
        },

        abrirModalCrear() {
            this.modoEditar = false;
            this.errores    = {};
            this.form = {
                id_cliente: null, id_pais: null,
                razon_social: '', nombre_comercial: '', nit: '',
                tipo_persona: 'JURIDICA', email_principal: '',
                telefono_principal: '', sitio_web: '', direccion_fiscal: '',
                segmento: null, categoria: null,
                moneda_facturacion: 'GTQ', dias_credito: 30, limite_credito: null,
            };
            this.mostrarModal = true;
        },

        async abrirModalEditar(item) {
            this.modoEditar = true;
            this.errores    = {};
            this.divisiones = [];
            this.municipios = [];

            try {
                const res  = await fetch(apiUrl + '/clientes/clientes/' + item.id_cliente);
                const data = await res.json();

                if (data.success) {
                    const c = data.data;

                    // Si tiene municipio → obtener id_division e id_pais desde el municipio
                    let idPais     = c.id_pais;
                    let idDivision = null;

                    if (c.id_municipio) {
                        const resCascada = await fetch(apiUrl + '/geografia/municipio/' + c.id_municipio + '/cascada');
                        const cascada    = await resCascada.json();
                        if (cascada.success) {
                            idPais     = cascada.data.id_pais;
                            idDivision = cascada.data.id_division;
                        }
                    }

                    this.form = {
                        id_cliente:          c.id_cliente,
                        id_pais:             idPais,
                        id_division:         idDivision,
                        id_municipio:        c.id_municipio || null,
                        id_industria:        c.id_industria || null,
                        razon_social:        c.razon_social,
                        nombre_comercial:    c.nombre_comercial || '',
                        nit:                 c.nit || '',
                        tipo_persona:        c.tipo_persona,
                        email_principal:     c.email_principal || '',
                        telefono_principal:  c.telefono_principal || '',
                        sitio_web:           c.sitio_web || '',
                        direccion_fiscal:    c.direccion_fiscal || '',
                        segmento:            c.segmento || null,
                        categoria:           c.categoria || null,
                        moneda_facturacion:  c.moneda_facturacion,
                        dias_credito:        c.dias_credito,
                        limite_credito:      c.limite_credito || null,
                    };

                    // Cargar divisiones y municipios para mostrar en los selects
                    await this.cargarCascadaEdicion(idPais, idDivision);

                    this.mostrarModal = true;
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar el cliente.', 'error');
            }
        },

        async cargarCascadaEdicion(idPais, idDivision) {
            if (idPais) {
                const res  = await fetch(apiUrl + '/geografia/divisiones/' + idPais);
                const data = await res.json();
                if (data.success) this.divisiones = data.data;
            }
            if (idDivision) {
                const res  = await fetch(apiUrl + '/geografia/municipios/' + idDivision);
                const data = await res.json();
                if (data.success) this.municipios = data.data;
            }
        },

        async guardarRegistro() {
            if (!this.form.razon_social.trim()) {
                this.errores = { razon_social: 'La razón social es obligatoria.' };
                return;
            }
            if (!this.form.id_pais) {
                this.errores = { id_pais: 'Selecciona un país.' };
                return;
            }
            this.guardando = true;
            this.errores   = {};
            try {
                const url = this.modoEditar
                    ? apiUrl + '/clientes/clientes/' + this.form.id_cliente
                    : apiUrl + '/clientes/clientes';

                const res  = await fetch(url, {
                    method:  this.modoEditar ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(this.form),
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
            } catch (e) { Swal.fire('Error', 'Error de conexión', 'error'); }
            finally { this.guardando = false; }
        },

        async toggleEstado(item) {
            try {
                const res  = await fetch(apiUrl + '/clientes/clientes/' + item.id_cliente + '/toggle', { method: 'PATCH' });
                const data = await res.json();
                if (data.success) this.cargarDatos();
            } catch (e) { console.error(e); }
        },

        async eliminarRegistro(item) {
            const ok = await Swal.fire({
                title: '¿Eliminar cliente?',
                html: '<b>' + item.razon_social + '</b>',
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#e63757',
            });
            if (!ok.isConfirmed) return;
            try {
                const res  = await fetch(apiUrl + '/clientes/clientes/' + item.id_cliente, { method: 'DELETE' });
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