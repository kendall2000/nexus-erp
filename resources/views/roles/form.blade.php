@extends('layouts.app', ['titulo' => $rol->exists ? 'Editar rol: '.$rol->nombre : 'Nuevo rol'])

@section('contenido')
    @php
        $esAdmin = $rol->exists && $rol->esAdministrador();
        $bloqueado = ! auth()->user()->puede($rol->exists ? 'roles.editar' : 'roles.crear') || $soloLectura;
        $marcados = array_map('intval', old('permisos', $marcados));
    @endphp

    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles y Permisos</a></li>
            <li class="breadcrumb-item active">{{ $rol->exists ? ($bloqueado ? $rol->nombre : 'Editar: '.$rol->nombre) : 'Nuevo Rol' }}</li>
        </ol>
    </nav>

    @if ($soloLectura)
        <div class="alert alert-subtle-warning fs--1 d-flex align-items-center mb-4" role="alert">
            <span class="fas fa-exclamation-triangle me-2 fs-0"></span>
            <div>{{ $soloLectura }}</div>
        </div>
    @endif

    <div class="mb-4">
        <div class="row g-3 justify-content-between align-items-end">
            <div class="col-12 col-md">
                <h2 class="mb-1 text-bold text-1100">
                    <span class="fas fa-user-shield text-primary me-2"></span>{{ $rol->exists ? ($bloqueado ? 'Detalle de Perfil: '.$rol->nombre : 'Modificar Privilegios del Rol') : 'Configurar Nuevo Rol de Seguridad' }}
                </h2>
                <p class="text-700 mb-0">Define la granularidad de acceso módulo por módulo para los colaboradores adscritos a este perfil.</p>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <a class="btn btn-phoenix-secondary" href="{{ route('roles.index') }}">
                    <span class="fas fa-arrow-left me-2"></span>{{ $bloqueado ? 'Volver al Listado' : 'Cancelar' }}
                </a>
                @unless ($bloqueado)
                    <button class="btn btn-primary" type="submit" form="formRol">
                        <span class="fas fa-save me-2"></span>Guardar Perfil
                    </button>
                @endunless
            </div>
        </div>
    </div>

    <form id="formRol" class="mb-9" method="POST" action="{{ $rol->exists ? route('roles.update', $rol->id_rol) : route('roles.store') }}">
        @csrf
        @if ($rol->exists)
            @method('PUT')
        @endif

        <fieldset @disabled($bloqueado)>
            <div class="row g-4">
                <!-- Columna Principal: Matriz de Permisos -->
                <div class="col-12 col-xl-8">
                    <div class="card shadow-none border mb-4">
                        <div class="card-header bg-body-emphasis p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-1100 fs-0">
                                <i class="fas fa-th text-primary me-2"></i>Matriz de Privilegios por Módulo
                            </h5>
                            @unless ($esAdmin || $bloqueado)
                                <span class="badge badge-phoenix badge-phoenix-info fs--2">
                                    <i class="fas fa-mouse-pointer me-1"></i>«Ver» habilita el acceso en el menú lateral
                                </span>
                            @endunless
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold" for="nombre">Nombre Identificador del Rol <span class="text-danger">*</span></label>
                                <input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" type="text" maxlength="100" required
                                       value="{{ old('nombre', $rol->nombre) }}" placeholder="Ej.: Analista Contable, Supervisor de Bodega, Asesor Comercial..." @readonly($esAdmin) />
                                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if ($esAdmin)
                                <div class="alert alert-subtle-primary fs--1 d-flex align-items-center mb-0" role="alert">
                                    <i class="fas fa-crown text-primary fs-1 me-3"></i>
                                    <div>
                                        <strong>Acceso Irrestricto de Administrador:</strong>
                                        <p class="mb-0">Este rol goza de todos los privilegios sobre cada controlador y pantalla del sistema de forma automática.</p>
                                    </div>
                                </div>
                            @else
                                @if ($propios !== null)
                                    <div class="alert alert-subtle-warning py-2 fs--2 mb-3">
                                        <i class="fas fa-info-circle me-1"></i>Como usuario con privilegios restringidos, solo puedes conceder los permisos que tu propia cuenta posee.
                                    </div>
                                @endif
                                @include('permisos.matriz', ['matriz' => $matriz, 'marcados' => $marcados, 'propios' => $propios, 'bloqueado' => $bloqueado])
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Columna Lateral: Configuración y Políticas de Acceso -->
                <div class="col-12 col-xl-4">
                    <div class="card shadow-none border mb-4">
                        <div class="card-header bg-body-emphasis p-3 border-bottom">
                            <h5 class="mb-0 text-1100 fs-0"><i class="fas fa-sliders-h text-primary me-2"></i>Políticas de Seguridad</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold" for="descripcion">Descripción de Responsabilidades</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" maxlength="300" placeholder="Describe brevemente qué funciones desempeña este perfil y por qué se le conceden estos permisos...">{{ old('descripcion', $rol->descripcion) }}</textarea>
                            </div>

                            <div class="card border border-200 bg-body-tertiary p-3 mb-4">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" id="requiere_2fa" name="requiere_2fa" type="checkbox" value="1" @checked(old('requiere_2fa', $rol->requiere_2fa)) />
                                    <label class="form-check-label text-1000 fw-bold" for="requiere_2fa">
                                        <i class="fas fa-mobile-alt text-warning me-1"></i>Exigir Verificación 2FA
                                    </label>
                                </div>
                                <p class="text-muted fs--2 mt-2 mb-0">Obliga a los usuarios con este rol a tener configurada una app autenticadora (Google Auth o TOTP) para iniciar sesión.</p>
                            </div>

                            @unless ($esAdmin)
                                <div class="card border border-200 bg-body-tertiary p-3 mb-0">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $rol->activo ?? true)) />
                                        <label class="form-check-label text-1000 fw-bold" for="activo">
                                            <i class="fas fa-toggle-on text-success me-1"></i>Rol Habilitado
                                        </label>
                                    </div>
                                    <p class="text-muted fs--2 mt-2 mb-0">Al desactivar este rol, cualquier usuario que dependa exclusivamente de él perderá el acceso al ERP.</p>
                                </div>
                            @endunless
                        </div>
                    </div>

                    @unless ($bloqueado)
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary py-2" type="submit">
                                <i class="fas fa-check-circle me-2"></i>{{ $rol->exists ? 'Aplicar Cambios al Rol' : 'Crear y Habilitar Rol' }}
                            </button>
                        </div>
                    @endunless
                </div>
            </div>
        </fieldset>
    </form>
@endsection
