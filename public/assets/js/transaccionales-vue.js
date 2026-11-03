/**
 * Componente Vue 3: FacturaLineas
 * Cálculo reactivo en tiempo real de subtotales, descuentos, IVA incluido/agregado y total general
 */
window.NexusVue.register('FacturaLineas', {
    props: {
        initialLineas: {
            type: Array,
            default: () => []
        },
        servicios: {
            type: Array,
            default: () => []
        },
        centros: {
            type: Array,
            default: () => []
        },
        cuentas: {
            type: Array,
            default: () => []
        },
        fiscal: {
            type: Object,
            default: () => ({ tasa: 0.12, incluido: true })
        },
        initialDescuento: {
            type: Number,
            default: 0
        }
    },
    data() {
        const lineas = this.initialLineas && this.initialLineas.length
            ? this.initialLineas.map(l => ({
                id_tipo_servicio: l.id_tipo_servicio || '',
                descripcion: l.descripcion || '',
                cantidad: parseFloat(l.cantidad) || 1,
                precio_unitario: l.precio_unitario !== undefined && l.precio_unitario !== null ? l.precio_unitario : '',
                descuento: parseFloat(l.descuento) || 0,
                es_afecto_iva: l.es_afecto_iva !== undefined ? Boolean(Number(l.es_afecto_iva)) : true,
                id_centro: l.id_centro || '',
                id_cuenta: l.id_cuenta || ''
            }))
            : [{
                id_tipo_servicio: '',
                descripcion: '',
                cantidad: 1,
                precio_unitario: '',
                descuento: 0,
                es_afecto_iva: true,
                id_centro: '',
                id_cuenta: ''
            }];

        return {
            lineas: lineas,
            descuentoGlobal: this.initialDescuento || 0
        };
    },
    computed: {
        serviciosMap() {
            const map = {};
            this.servicios.forEach(s => {
                map[s.id_tipo_servicio] = s;
            });
            return map;
        },
        totales() {
            let afecto = 0;
            let exento = 0;

            this.lineas.forEach(l => {
                const cant = parseFloat(l.cantidad) || 0;
                const prec = parseFloat(l.precio_unitario) || 0;
                const desc = parseFloat(l.descuento) || 0;
                const importe = Math.max(0, (cant * prec) - desc);

                if (l.es_afecto_iva) {
                    afecto += importe;
                } else {
                    exento += importe;
                }
            });

            const subtotal = afecto + exento;
            const descGlobal = Math.min(parseFloat(this.descuentoGlobal) || 0, subtotal);
            const descAfecto = subtotal > 0 ? (descGlobal * afecto / subtotal) : 0;
            const afectoNeto = afecto - descAfecto;
            const exentoNeto = exento - (descGlobal - descAfecto);

            const tasa = this.fiscal.tasa || 0.12;
            const incluido = Boolean(this.fiscal.incluido);

            const iva = incluido
                ? (afectoNeto - (afectoNeto / (1 + tasa)))
                : (afectoNeto * tasa);

            const total = incluido
                ? (afectoNeto + exentoNeto)
                : (afectoNeto + iva + exentoNeto);

            return {
                subtotal,
                descuentoGlobal: descGlobal,
                iva,
                total
            };
        }
    },
    methods: {
        agregarLinea() {
            this.lineas.push({
                id_tipo_servicio: '',
                descripcion: '',
                cantidad: 1,
                precio_unitario: '',
                descuento: 0,
                es_afecto_iva: true,
                id_centro: '',
                id_cuenta: ''
            });
        },
        quitarLinea(index) {
            if (this.lineas.length > 1) {
                this.lineas.splice(index, 1);
            } else {
                this.lineas[0] = {
                    id_tipo_servicio: '',
                    descripcion: '',
                    cantidad: 1,
                    precio_unitario: '',
                    descuento: 0,
                    es_afecto_iva: true,
                    id_centro: '',
                    id_cuenta: ''
                };
            }
        },
        cambioServicio(linea) {
            const serv = this.serviciosMap[linea.id_tipo_servicio];
            if (serv) {
                if (!linea.descripcion) {
                    linea.descripcion = serv.nombre;
                }
                if (!linea.precio_unitario && serv.precio_base) {
                    linea.precio_unitario = serv.precio_base;
                }
            }
        },
        calcularImporte(linea) {
            const cant = parseFloat(linea.cantidad) || 0;
            const prec = parseFloat(linea.precio_unitario) || 0;
            const desc = parseFloat(linea.descuento) || 0;
            return Math.max(0, (cant * prec) - desc);
        },
        formatoDinero(val) {
            const n = parseFloat(val) || 0;
            return n.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    },
    template: `
        <div>
            <div class="card border border-200 shadow-none mb-4">
                <div class="card-header bg-white border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fas fa-list-ol text-primary me-1"></span>
                        <h5 class="mb-0 text-1100 fw-bold">Partidas & Conceptos Facturados</h5>
                        <span class="badge badge-phoenix badge-phoenix-info fs--2 ms-2">Reactivo Vue 3</span>
                    </div>
                    <button class="btn btn-phoenix-primary btn-sm" type="button" @click="agregarLinea">
                        <span class="fas fa-plus me-1"></span>Agregar línea
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead class="bg-100">
                                <tr>
                                    <th class="ps-4 text-700" style="min-width: 200px;">SERVICIO / CONCEPTO</th>
                                    <th class="text-700" style="min-width: 250px;">DESCRIPCIÓN</th>
                                    <th class="text-end text-700" style="width: 100px;">CANT.</th>
                                    <th class="text-end text-700" style="width: 130px;">PRECIO UNIT.</th>
                                    <th class="text-end text-700" style="width: 120px;">DESCUENTO</th>
                                    <th class="text-center text-700" style="width: 80px;">IVA</th>
                                    <th class="text-700" style="min-width: 200px;">IMPUTACIÓN</th>
                                    <th class="text-end text-700" style="width: 140px;">SUBTOTAL</th>
                                    <th class="text-center pe-4 text-700" style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(l, i) in lineas" :key="i">
                                    <td class="ps-4">
                                        <select class="form-select form-select-sm" :name="'lineas[' + i + '][id_tipo_servicio]'" v-model="l.id_tipo_servicio" @change="cambioServicio(l)">
                                            <option value="">Servicio (opcional)…</option>
                                            <option v-for="s in servicios" :key="s.id_tipo_servicio" :value="s.id_tipo_servicio">
                                                {{ s.nombre }} ({{ s.linea_negocio ? s.linea_negocio.nombre : '' }})
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <input class="form-control form-select-sm" :name="'lineas[' + i + '][descripcion]'" v-model="l.descripcion" maxlength="300" placeholder="Descripción de la línea" />
                                    </td>
                                    <td>
                                        <input class="form-control form-control-sm text-end" :name="'lineas[' + i + '][cantidad]'" v-model.number="l.cantidad" type="number" step="0.01" min="0" />
                                    </td>
                                    <td>
                                        <input class="form-control form-control-sm text-end" :name="'lineas[' + i + '][precio_unitario]'" v-model="l.precio_unitario" type="number" step="0.0001" min="0" placeholder="0.00" />
                                    </td>
                                    <td>
                                        <input class="form-control form-control-sm text-end" :name="'lineas[' + i + '][descuento]'" v-model.number="l.descuento" type="number" step="0.01" min="0" placeholder="0.00" />
                                    </td>
                                    <td class="text-center">
                                        <input type="hidden" :name="'lineas[' + i + '][es_afecto_iva]'" value="0" />
                                        <input class="form-check-input" type="checkbox" :name="'lineas[' + i + '][es_afecto_iva]'" value="1" v-model="l.es_afecto_iva" title="Afecta a IVA" />
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm mb-1" :name="'lineas[' + i + '][id_centro]'" v-model="l.id_centro" title="Centro de costo">
                                            <option value="">Centro: el del servicio</option>
                                            <option v-for="c in centros" :key="c.id_centro" :value="c.id_centro">
                                                {{ c.etiqueta }}
                                            </option>
                                        </select>
                                        <select class="form-select form-select-sm" :name="'lineas[' + i + '][id_cuenta]'" v-model="l.id_cuenta" title="Cuenta de ingreso">
                                            <option value="">Cuenta: la del servicio</option>
                                            <option v-for="c in cuentas" :key="c.id_cuenta" :value="c.id_cuenta">
                                                {{ c.etiqueta }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="text-end fw-semi-bold">
                                        {{ formatoDinero(calcularImporte(l)) }}
                                    </td>
                                    <td class="text-center pe-4">
                                        <button class="btn btn-link text-danger p-0" type="button" title="Quitar" @click="quitarLinea(i)">
                                            <span class="fas fa-times"></span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Resumen y Totales -->
            <div class="row justify-content-end mb-4">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="card border border-200 shadow-none">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label fs--1" for="descuento">Descuento Global Adicional (Monto)</label>
                                <input class="form-control form-control-sm text-end" id="descuento" name="descuento" v-model.number="descuentoGlobal" type="number" step="0.01" min="0" placeholder="0.00" />
                            </div>
                            <div class="d-flex justify-content-between mb-2 fs--1">
                                <span class="text-700">Subtotal Neto:</span>
                                <span class="fw-semi-bold text-900">{{ formatoDinero(totales.subtotal) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 fs--1">
                                <span class="text-700">IVA (12%):</span>
                                <span class="fw-semi-bold text-900">{{ formatoDinero(totales.iva) }}</span>
                            </div>
                            <div class="d-flex justify-content-between border-top border-200 pt-3 fs-1">
                                <span class="fw-bold text-1100">Total Liquidado:</span>
                                <span class="fw-bolder text-primary">{{ formatoDinero(totales.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
});

/**
 * Componente Vue 3: OrdenCompraLineas
 * Cálculo reactivo en tiempo real de subtotales, IVA y total con imputación contable
 */
window.NexusVue.register('OrdenCompraLineas', {
    props: {
        initialLineas: {
            type: Array,
            default: () => []
        },
        productos: {
            type: Array,
            default: () => []
        },
        centros: {
            type: Array,
            default: () => []
        },
        cuentas: {
            type: Array,
            default: () => []
        },
        fiscal: {
            type: Object,
            default: () => ({ tasa: 0.12, incluido: true })
        }
    },
    data() {
        const lineas = this.initialLineas && this.initialLineas.length
            ? this.initialLineas.map(l => ({
                id_producto: l.id_producto || '',
                descripcion: l.descripcion || '',
                cantidad_pedida: parseFloat(l.cantidad_pedida) || 1,
                precio_unitario: l.precio_unitario !== undefined && l.precio_unitario !== null ? l.precio_unitario : '',
                descuento: parseFloat(l.descuento) || 0,
                id_centro: l.id_centro || '',
                id_cuenta: l.id_cuenta || ''
            }))
            : [{
                id_producto: '',
                descripcion: '',
                cantidad_pedida: 1,
                precio_unitario: '',
                descuento: 0,
                id_centro: '',
                id_cuenta: ''
            }];

        return {
            lineas: lineas
        };
    },
    computed: {
        productosMap() {
            const map = {};
            this.productos.forEach(p => {
                map[p.id_producto] = p;
            });
            return map;
        },
        centrosMap() {
            const map = {};
            this.centros.forEach(c => {
                map[c.id_centro] = c.nombre || c.codigo;
            });
            return map;
        },
        cuentasMap() {
            const map = {};
            this.cuentas.forEach(c => {
                map[c.id_cuenta] = (c.codigo || '') + ' ' + (c.nombre || '');
            });
            return map;
        },
        totales() {
            let subtotal = 0;
            this.lineas.forEach(l => {
                const cant = parseFloat(l.cantidad_pedida) || 0;
                const prec = parseFloat(l.precio_unitario) || 0;
                const desc = parseFloat(l.descuento) || 0;
                const importe = Math.max(0, (cant * prec) - desc);
                subtotal += importe;
            });

            const tasa = this.fiscal.tasa || 0.12;
            const incluido = Boolean(this.fiscal.incluido);

            const iva = incluido
                ? (subtotal - (subtotal / (1 + tasa)))
                : (subtotal * tasa);

            const total = incluido
                ? subtotal
                : (subtotal + iva);

            return {
                subtotal,
                iva,
                total
            };
        }
    },
    methods: {
        agregarLinea() {
            this.lineas.push({
                id_producto: '',
                descripcion: '',
                cantidad_pedida: 1,
                precio_unitario: '',
                descuento: 0,
                id_centro: '',
                id_cuenta: ''
            });
        },
        quitarLinea(index) {
            if (this.lineas.length > 1) {
                this.lineas.splice(index, 1);
            } else {
                this.lineas[0] = {
                    id_producto: '',
                    descripcion: '',
                    cantidad_pedida: 1,
                    precio_unitario: '',
                    descuento: 0,
                    id_centro: '',
                    id_cuenta: ''
                };
            }
        },
        cambioProducto(linea) {
            const prod = this.productosMap[linea.id_producto];
            if (prod) {
                if (!linea.precio_unitario && prod.precio_compra) {
                    linea.precio_unitario = prod.precio_compra;
                }
            }
        },
        textoHeredado(linea) {
            const prod = this.productosMap[linea.id_producto];
            if (!prod) return '';
            const partes = [];
            if (prod.id_centro_default && this.centrosMap[prod.id_centro_default]) {
                partes.push(this.centrosMap[prod.id_centro_default]);
            }
            if (prod.id_cuenta_gasto && this.cuentasMap[prod.id_cuenta_gasto]) {
                partes.push(this.cuentasMap[prod.id_cuenta_gasto]);
            }
            return partes.length ? 'Imputa a: ' + partes.join(' / ') : '';
        },
        calcularImporte(linea) {
            const cant = parseFloat(linea.cantidad_pedida) || 0;
            const prec = parseFloat(linea.precio_unitario) || 0;
            const desc = parseFloat(linea.descuento) || 0;
            return Math.max(0, (cant * prec) - desc);
        },
        formatoDinero(val) {
            const n = parseFloat(val) || 0;
            return n.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    },
    template: `
        <div>
            <div class="card border border-200 shadow-none mb-4">
                <div class="card-header bg-white border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fas fa-boxes-stacked text-primary me-1"></span>
                        <h5 class="mb-0 text-1100 fw-bold">Partidas & Productos Adquiridos</h5>
                        <span class="badge badge-phoenix badge-phoenix-info fs--2 ms-2">Reactivo Vue 3</span>
                    </div>
                    <button class="btn btn-phoenix-primary btn-sm" type="button" @click="agregarLinea">
                        <span class="fas fa-plus me-1"></span>Agregar producto
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead class="bg-100">
                                <tr>
                                    <th class="ps-4 text-700" style="min-width: 250px;">PRODUCTO / INSUMO</th>
                                    <th class="text-end text-700" style="width: 100px;">CANT.</th>
                                    <th class="text-end text-700" style="width: 130px;">PRECIO COMPRA</th>
                                    <th class="text-end text-700" style="width: 120px;">DESCUENTO</th>
                                    <th class="text-700" style="min-width: 200px;">CENTRO & CUENTA</th>
                                    <th class="text-end text-700" style="width: 140px;">SUBTOTAL</th>
                                    <th class="text-center pe-4 text-700" style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(l, i) in lineas" :key="i">
                                    <td class="ps-4">
                                        <select class="form-select form-select-sm" :name="'lineas[' + i + '][id_producto]'" v-model="l.id_producto" @change="cambioProducto(l)">
                                            <option value="">Selecciona producto…</option>
                                            <option v-for="p in productos" :key="p.id_producto" :value="p.id_producto">
                                                {{ p.codigo }} — {{ p.nombre }} ({{ p.unidad_medida }})
                                            </option>
                                        </select>
                                        <input class="form-control form-control-sm mt-1" :name="'lineas[' + i + '][descripcion]'" v-model="l.descripcion" maxlength="300" placeholder="Descripción complementaria (opcional)" />
                                    </td>
                                    <td>
                                        <input class="form-control form-control-sm text-end" :name="'lineas[' + i + '][cantidad_pedida]'" v-model.number="l.cantidad_pedida" type="number" step="0.0001" min="0" />
                                    </td>
                                    <td>
                                        <input class="form-control form-control-sm text-end" :name="'lineas[' + i + '][precio_unitario]'" v-model="l.precio_unitario" type="number" step="0.0001" min="0" placeholder="0.00" />
                                    </td>
                                    <td>
                                        <input class="form-control form-control-sm text-end" :name="'lineas[' + i + '][descuento]'" v-model.number="l.descuento" type="number" step="0.01" min="0" placeholder="0.00" />
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm mb-1" :name="'lineas[' + i + '][id_centro]'" v-model="l.id_centro" title="Centro de costo">
                                            <option value="">Centro: el del producto</option>
                                            <option v-for="c in centros" :key="c.id_centro" :value="c.id_centro">
                                                {{ c.codigo }} — {{ c.nombre }}
                                            </option>
                                        </select>
                                        <select class="form-select form-select-sm" :name="'lineas[' + i + '][id_cuenta]'" v-model="l.id_cuenta" title="Cuenta contable">
                                            <option value="">Cuenta: la del producto</option>
                                            <option v-for="c in cuentas" :key="c.id_cuenta" :value="c.id_cuenta">
                                                {{ c.codigo }} — {{ c.nombre }}
                                            </option>
                                        </select>
                                        <div class="fs--2 text-600 mt-1" v-if="textoHeredado(l)">
                                            {{ textoHeredado(l) }}
                                        </div>
                                    </td>
                                    <td class="text-end fw-semi-bold">
                                        {{ formatoDinero(calcularImporte(l)) }}
                                    </td>
                                    <td class="text-center pe-4">
                                        <button class="btn btn-link text-danger p-0" type="button" title="Quitar" @click="quitarLinea(i)">
                                            <span class="fas fa-times"></span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Resumen y Totales -->
            <div class="row justify-content-end mb-4">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="card border border-200 shadow-none">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between mb-2 fs--1">
                                <span class="text-700">Subtotal de Compra:</span>
                                <span class="fw-semi-bold text-900">{{ formatoDinero(totales.subtotal) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 fs--1">
                                <span class="text-700">IVA Estimado:</span>
                                <span class="fw-semi-bold text-900">{{ formatoDinero(totales.iva) }}</span>
                            </div>
                            <div class="d-flex justify-content-between border-top border-200 pt-3 fs-1">
                                <span class="fw-bold text-1100">Total de la Orden:</span>
                                <span class="fw-bolder text-primary">{{ formatoDinero(totales.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
});
