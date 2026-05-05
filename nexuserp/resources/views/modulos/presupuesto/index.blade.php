@extends('layouts.app')
@section('breadcrumb', 'Presupuesto Anual')

@section('content')
<div id="presupuesto-app" v-cloak>

    {{-- ════════════════════════════════════════════════════════
         ENCABEZADO
    ════════════════════════════════════════════════════════ --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 text-900 fw-bold">
                <span data-feather="bar-chart-2" class="me-2 text-primary"></span>
                Presupuesto Anual
            </h4>
            <p class="text-700 mb-0 fs--1">
                Planeación financiera con desglose mensual y comparativos
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width:150px">
                <span class="input-group-text bg-white">
                    <span data-feather="calendar" style="width:14px;height:14px"></span>
                </span>
                <select class="form-select form-select-sm"
                        v-model.number="anioActivo" @change="cambiarAnio">
                    <option v-for="a in aniosDisponibles" :key="a" :value="a">@{{ a }}</option>
                </select>
            </div>
            <button class="btn btn-outline-info btn-sm" @click="abrirModalClonar">
                <span data-feather="copy" class="me-1" style="width:14px"></span>
                Clonar Año
            </button>
            <button class="btn btn-primary btn-sm px-4" @click="abrirModalCrear">
                <span data-feather="plus" class="me-1" style="width:14px"></span>
                Nuevo Presupuesto
            </button>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         KPI CARDS
    ════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Presupuestado</h6>
                            <h4 class="mb-0 text-primary fw-bold">
                                @{{ kpi.moneda }} @{{ formatear(kpi.total_presupuestado) }}
                            </h4>
                        </div>
                        <div class="bg-soft-primary rounded-circle p-2 d-flex align-items-center justify-content-center"
                             style="width:48px;height:48px">
                            <span data-feather="dollar-sign" class="text-primary"
                                  style="width:22px;height:22px"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Ejecutado</h6>
                            <h4 class="mb-0 text-warning fw-bold">
                                @{{ kpi.moneda }} @{{ formatear(kpi.total_ejecutado) }}
                            </h4>
                        </div>
                        <div class="bg-soft-warning rounded-circle p-2 d-flex align-items-center justify-content-center"
                             style="width:48px;height:48px">
                            <span data-feather="trending-up" class="text-warning"
                                  style="width:22px;height:22px"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Saldo Disponible</h6>
                            <h4 class="mb-0 fw-bold"
                                :class="kpi.saldo_disponible < 0 ? 'text-danger' : 'text-success'">
                                @{{ kpi.moneda }} @{{ formatear(kpi.saldo_disponible) }}
                            </h4>
                        </div>
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center"
                             :class="kpi.saldo_disponible < 0 ? 'bg-soft-danger' : 'bg-soft-success'"
                             style="width:48px;height:48px">
                            <span data-feather="check-circle"
                                  :class="kpi.saldo_disponible < 0 ? 'text-danger' : 'text-success'"
                                  style="width:22px;height:22px"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <h6 class="text-700 fs--1 mb-1 fw-semi-bold">% Ejecución</h6>
                            <h4 class="mb-0 fw-bold"
                                :class="kpi.porcentaje_ejecucion > 100 ? 'text-danger' : 'text-info'">
                                @{{ kpi.porcentaje_ejecucion }}%
                            </h4>
                        </div>
                        <div class="bg-soft-info rounded-circle p-2 d-flex align-items-center justify-content-center"
                             style="width:48px;height:48px">
                            <span data-feather="activity" class="text-info"
                                  style="width:22px;height:22px"></span>
                        </div>
                    </div>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar"
                             :class="claseProgreso(kpi.porcentaje_ejecucion)"
                             :style="`width:${Math.min(kpi.porcentaje_ejecucion, 100)}%`"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         GRÁFICOS (ECharts)
    ════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <span data-feather="trending-up" class="me-2 text-primary"
                              style="width:18px;height:18px"></span>
                        Presupuestado vs Ejecutado (mensual)
                    </h6>
                    <span class="badge badge-soft-primary">@{{ anioActivo }}</span>
                </div>
                <div class="card-body p-2">
                    <div id="chart-mensual" style="width:100%;height:380px"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <span data-feather="bar-chart-2" class="me-2 text-success"
                              style="width:18px;height:18px"></span>
                        Comparativo Anual
                    </h6>
                    <span class="badge badge-soft-success">
                        @{{ anioActivo }} vs @{{ anioActivo - 1 }}
                    </span>
                </div>
                <div class="card-body p-2">
                    <div id="chart-comparativo" style="width:100%;height:380px"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         TOP 5 CUENTAS MÁS EJECUTADAS
    ════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm mb-3" v-if="topCuentas.length">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-bold">
                <span data-feather="award" class="me-2 text-warning"
                      style="width:18px;height:18px"></span>
                Top 5 cuentas más ejecutadas
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="bg-100">
                        <tr>
                            <th class="ps-3" style="width:50px">#</th>
                            <th>Cuenta Contable</th>
                            <th class="text-end">Presupuestado</th>
                            <th class="text-end">Ejecutado</th>
                            <th class="text-end pe-3" style="width:200px">% Ejecución</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(t, idx) in topCuentas" :key="t.cuenta">
                            <td class="ps-3 fw-bold text-700">@{{ idx + 1 }}</td>
                            <td class="fw-semi-bold">@{{ t.cuenta }}</td>
                            <td class="text-end">@{{ formatear(t.presupuestado) }}</td>
                            <td class="text-end">@{{ formatear(t.ejecutado) }}</td>
                            <td class="text-end pe-3">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;max-width:100px">
                                        <div class="progress-bar"
                                             :class="claseProgreso(t.porcentaje_ejecucion)"
                                             :style="`width:${Math.min(t.porcentaje_ejecucion, 100)}%`"></div>
                                    </div>
                                    <span class="badge"
                                          :class="badgeEjecucion(t.porcentaje_ejecucion)"
                                          style="min-width:55px">
                                        @{{ t.porcentaje_ejecucion }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         TABLA PRINCIPAL
    ════════════════════════════════════════════════════════ --}}
    <v-smart-table
        title="Presupuestos Registrados"
        :data="presupuestos"
        :columns="columnas"
        :loading="cargandoTabla"
        :refreshable="true"
        export-name="presupuesto_anual"
        empty-text="No hay presupuestos registrados para este año"
        @refresh="cargarDatos"
        @edit="abrirModalEditar"
        @delete="eliminarRegistro">
    </v-smart-table>

    {{-- ════════════════════════════════════════════════════════
         MODAL CREAR / EDITAR
    ════════════════════════════════════════════════════════ --}}
    <v-modal-form
        v-model="mostrarModal"
        id="modal-presupuesto"
        :title="modoEditar ? 'Editar Presupuesto' : 'Nuevo Presupuesto'"
        size="modal-xl"
        :btn-text="modoEditar ? 'Guardar Cambios' : 'Crear Presupuesto'"
        btn-class="btn-primary"
        :loading="guardando"
        @action="guardarRegistro">

        <div class="row g-3">
            {{-- ── CABECERA ── --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">
                    Centro de Costo <span class="text-danger">*</span>
                </label>
                <select class="form-select" v-model="form.id_centro"
                        :disabled="modoEditar"
                        :class="{'is-invalid': errores.id_centro}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="c in centros" :key="c.id" :value="c.id">@{{ c.name }}</option>
                </select>
                <div class="invalid-feedback">@{{ errores.id_centro }}</div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">
                    Cuenta Contable <span class="text-danger">*</span>
                </label>
                <select class="form-select" v-model="form.id_cuenta"
                        :disabled="modoEditar"
                        :class="{'is-invalid': errores.id_cuenta}">
                    <option :value="null">— Seleccione —</option>
                    <option v-for="c in cuentas" :key="c.id" :value="c.id">
                        @{{ c.name }} (@{{ c.tipo }})
                    </option>
                </select>
                <div class="invalid-feedback">@{{ errores.id_cuenta }}</div>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold">
                    Año <span class="text-danger">*</span>
                </label>
                <select class="form-select" v-model.number="form.anio" :disabled="modoEditar">
                    <option v-for="a in aniosDisponibles" :key="a" :value="a">@{{ a }}</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold">Moneda</label>
                <select class="form-select" v-model="form.moneda">
                    <option v-for="m in monedas" :key="m" :value="m">@{{ m }}</option>
                </select>
            </div>

            {{-- ── ACCIONES RÁPIDAS ── --}}
            <div class="col-12">
                <div class="bg-soft-info rounded p-2 d-flex flex-wrap gap-2 align-items-center">
                    <small class="text-700 fw-semi-bold me-2">
                        <span data-feather="zap" style="width:13px;height:13px" class="me-1"></span>
                        Acciones rápidas:
                    </small>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            @click="distribuirIgual">
                        <span data-feather="divide" style="width:13px;height:13px"></span>
                        Distribuir total entre 12 meses
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            @click="copiarMesAnterior">
                        <span data-feather="copy" style="width:13px;height:13px"></span>
                        Replicar Enero a todos
                    </button>
                    <span class="ms-auto fw-bold text-primary fs-0">
                        Total: @{{ form.moneda }} @{{ formatear(totalPresupuestado) }}
                    </span>
                </div>
            </div>

            {{-- ── GRILLA DE 12 MESES ── --}}
            <div class="col-12 mt-2">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="bg-100">
                            <tr>
                                <th style="width:25%">Mes</th>
                                <th class="text-end" style="width:25%">
                                    Presupuestado
                                </th>
                                <th v-if="modoEditar" class="text-end" style="width:25%">
                                    Ejecutado
                                </th>
                                <th v-if="modoEditar" class="text-end" style="width:25%">
                                    Disponible
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(mes, idx) in nombresMeses" :key="mes">
                                <td class="fw-bold">
                                    <span class="badge badge-soft-secondary me-2"
                                          style="min-width:30px">@{{ idx + 1 }}</span>
                                    @{{ capitalizar(mes) }}
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control form-control-sm text-end"
                                           v-model.number="form['pre_' + mes]">
                                </td>
                                <td v-if="modoEditar" class="text-end text-warning fw-bold">
                                    @{{ formatear(form['eje_' + mes] || 0) }}
                                </td>
                                <td v-if="modoEditar" class="text-end fw-bold"
                                    :class="(form['pre_' + mes] - (form['eje_' + mes] || 0)) < 0
                                            ? 'text-danger' : 'text-success'">
                                    @{{ formatear((form['pre_' + mes] || 0) - (form['eje_' + mes] || 0)) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-100">
                            <tr class="fw-bold">
                                <td>TOTAL</td>
                                <td class="text-end text-primary fs-0">
                                    @{{ form.moneda }} @{{ formatear(totalPresupuestado) }}
                                </td>
                                <td v-if="modoEditar" class="text-end text-warning fs-0">
                                    @{{ form.moneda }}
                                    @{{ formatear(nombresMeses.reduce((s,m) => s + (form['eje_'+m]||0), 0)) }}
                                </td>
                                <td v-if="modoEditar" class="text-end fs-0"
                                    :class="(totalPresupuestado - nombresMeses.reduce((s,m) => s + (form['eje_'+m]||0), 0)) < 0
                                            ? 'text-danger' : 'text-success'">
                                    @{{ form.moneda }}
                                    @{{ formatear(totalPresupuestado - nombresMeses.reduce((s,m) => s + (form['eje_'+m]||0), 0)) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- ── ACCIONES DE ESTADO (solo al editar) ── --}}
            <div v-if="modoEditar" class="col-12 border-top pt-3 mt-1">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Estado actual:</small>
                        <span class="badge fs--1" :class="badgeEstado(form.estado)">
                            @{{ form.estado }}
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button v-if="form.estado === 'BORRADOR'"
                                type="button" class="btn btn-success btn-sm"
                                @click="aprobarPresupuesto">
                            <span data-feather="check" style="width:13px" class="me-1"></span>
                            Aprobar Presupuesto
                        </button>
                        <button v-if="form.estado === 'APROBADO'"
                                type="button" class="btn btn-dark btn-sm"
                                @click="cerrarPresupuesto">
                            <span data-feather="lock" style="width:13px" class="me-1"></span>
                            Cerrar Año
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </v-modal-form>

    {{-- ════════════════════════════════════════════════════════
         MODAL CLONAR
    ════════════════════════════════════════════════════════ --}}
    <v-modal-form
        v-model="mostrarModalClonar"
        id="modal-clonar"
        title="Clonar Presupuesto del Año Anterior"
        size="modal-md"
        btn-text="Clonar"
        btn-class="btn-info"
        :loading="clonando"
        @action="ejecutarClonar">

        <div class="row g-3">
            <div class="col-12">
                <div class="alert alert-soft-info py-2 mb-0 small">
                    <span data-feather="info" style="width:14px;height:14px" class="me-1"></span>
                    Esta acción copiará todos los presupuestos del año origen al año destino.
                    Los registros que ya existen en el año destino serán omitidos.
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Año origen</label>
                <select class="form-select" v-model.number="clonarForm.anio_origen">
                    <option v-for="a in aniosDisponibles" :key="a" :value="a">@{{ a }}</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Año destino</label>
                <select class="form-select" v-model.number="clonarForm.anio_destino">
                    <option v-for="a in aniosDisponibles" :key="a" :value="a">@{{ a }}</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Incremento (%)</label>
                <div class="input-group">
                    <input type="number" step="0.5" class="form-control"
                           v-model.number="clonarForm.incremento_pct"
                           placeholder="0">
                    <span class="input-group-text">%</span>
                </div>
                <small class="text-muted">
                    <strong>0</strong> = mismo monto · <strong>5</strong> = +5% · <strong>-10</strong> = -10%.
                    Los montos se multiplicarán por (1 + incremento/100).
                </small>
            </div>
        </div>
    </v-modal-form>

</div>
@endsection