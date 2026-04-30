@extends('layouts.app')

@section('breadcrumb', 'Configuración del Sistema')

@section('content')

<div id="config-app" v-cloak>
    
    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-900">
                <span data-feather="settings" class="me-2"></span>
                Configuración del Sistema
            </h4>
            <p class="text-700 mb-0 fs--1">
                Personaliza parámetros, apariencia, reportes y correos de NexusERP
            </p>
        </div>
        <button class="btn btn-primary px-4" :disabled="guardando" @click="guardarConfiguracion">
            <span v-if="guardando" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="fas fa-save me-2"></i>
            Guardar Cambios
        </button>
    </div>

    {{-- ── Skeleton Loader ─────────────────────────────────────── --}}
    <div v-if="cargando" class="card shadow-none border border-300">
        <div class="card-body text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3 mb-0 text-600">Cargando configuración...</p>
        </div>
    </div>

    {{-- ── Formulario con Pestañas ─────────────────────────────── --}}
    <div v-else class="card shadow-none border border-300">
        <div class="card-header border-bottom border-200 p-0">
            <ul class="nav nav-underline fs--1 px-3" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-sistema" role="tab">⚙️ Sistema y Parámetros</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-visual" role="tab">🎨 Apariencia y Login</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-imagenes" role="tab">🖼️ Imágenes y Logos</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-emails" role="tab">📧 Emails y Reportes</a></li>
            </ul>
        </div>
        
        <div class="card-body p-4">
            <div class="tab-content">
                
                {{-- PESTAÑA: SISTEMA --}}
                <div class="tab-pane fade show active" id="tab-sistema" role="tabpanel">
                    <h5 class="mb-4 text-800">Información General</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Sistema</label>
                            <input v-model="form.nombreSistema" type="text" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre de la Empresa</label>
                            <input v-model="form.nombreEmpresa" type="text" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIT</label>
                            <input v-model="form.nit" type="text" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono de Contacto</label>
                            <input v-model="form.telefono" type="text" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección Física</label>
                            <input v-model="form.direccion" type="text" class="form-control">
                        </div>
                    </div>

                    <h5 class="mb-4 text-800 border-top border-200 pt-4">Parámetros Regionales y Seguridad</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Moneda (Símbolo)</label>
                            <input v-model="form.moneda" type="text" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Moneda (Código)</label>
                            <input v-model="form.monedaCodigo" type="text" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Zona Horaria</label>
                            <input v-model="form.zonaHoraria" type="text" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Formato Fecha</label>
                            <input v-model="form.formatoFecha" type="text" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Máx Intentos Login</label>
                            <input v-model="form.maxIntentosSesion" type="number" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sesión Expira (Minutos)</label>
                            <input v-model="form.sesionExpiraMin" type="number" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Días de Mora</label>
                            <input v-model="form.diasMora" type="number" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">% de Mora</label>
                            <input v-model="form.porcentajeMora" type="number" step="0.01" class="form-control">
                        </div>
                    </div>
                </div>

                {{-- PESTAÑA: VISUAL & LOGIN --}}
                <div class="tab-pane fade" id="tab-visual" role="tabpanel">
                    <h5 class="mb-4 text-800">Colores de la Interfaz</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-2 col-6">
                            <label class="form-label">Primario</label>
                            <input v-model="form.colorPrimario" type="color" class="form-control form-control-color w-100 h-auto" style="min-height:40px;">
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label">Secundario</label>
                            <input v-model="form.colorSecundario" type="color" class="form-control form-control-color w-100 h-auto" style="min-height:40px;">
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label">Acento</label>
                            <input v-model="form.colorAccent" type="color" class="form-control form-control-color w-100 h-auto" style="min-height:40px;">
                        </div>
                    </div>

                    <h5 class="mb-4 text-800 border-top border-200 pt-4">Textos Pantalla de Login</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Título Principal</label>
                            <input v-model="form.loginTitulo" type="text" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subtítulo</label>
                            <input v-model="form.loginSubtitulo" type="text" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Mensaje de Bienvenida</label>
                            <input v-model="form.loginMensajeBienve" type="text" class="form-control">
                        </div>
                    </div>

                    <h5 class="mb-4 text-800 border-top border-200 pt-4">Footer (Pie de página)</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Texto de Copyright</label>
                            <input v-model="form.footerTexto" type="text" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Año</label>
                            <input v-model="form.footerAnio" type="text" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Versión</label>
                            <input v-model="form.footerVersion" type="text" class="form-control">
                        </div>
                    </div>
                </div>

                {{-- PESTAÑA: IMÁGENES --}}
                <div class="tab-pane fade" id="tab-imagenes" role="tabpanel">
                    <div class="alert alert-soft-info fs--1">
                        <i class="fas fa-info-circle me-2"></i> Puedes pegar directamente la URL de la imagen si ya está alojada en un servidor (ej. AWS S3) o seleccionar una imagen local (se convertirá a Base64 automáticamente).
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4" v-for="(imgField, index) in camposImagenes" :key="index">
                            <div class="card border border-200 h-100 shadow-none">
                                <div class="card-body text-center p-3">
                                    <h6 class="mb-3 text-700">@{{ imgField.label }}</h6>
                                    <div class="mb-3 rounded-2 bg-100 d-flex align-items-center justify-content-center border" style="height: 150px; overflow: hidden;">
                                        <img v-if="form[imgField.key]" :src="form[imgField.key]" style="max-height:100%; max-width:100%; object-fit:contain;">
                                        <span v-else class="text-400"><i class="fas fa-image fa-3x mb-2"></i><br>Sin imagen</span>
                                    </div>
                                    <input type="text" v-model="form[imgField.key]" class="form-control form-control-sm mb-2" placeholder="URL de la imagen...">
                                    <div class="position-relative">
                                        <input type="file" class="form-control form-control-sm" accept="image/*" @change="procesarImagen($event, imgField.key)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PESTAÑA: EMAILS Y REPORTES --}}
                <div class="tab-pane fade" id="tab-emails" role="tabpanel">
                    <h5 class="mb-4 text-800">Plantillas de Email</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Asunto: Restablecer Contraseña</label>
                            <input v-model="form.emailAsuntoReset" type="text" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Asunto: Bienvenida</label>
                            <input v-model="form.emailAsuntoBienve" type="text" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Asunto: Cobro/Cuota</label>
                            <input v-model="form.emailAsuntoCuota" type="text" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Firma de Correo (Pie)</label>
                            <textarea v-model="form.emailFirma" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection