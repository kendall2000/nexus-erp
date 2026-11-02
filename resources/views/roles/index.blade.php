@extends('layouts.app', ['titulo' => 'Roles y Permisos'])

{{-- Estructura: apps/e-commerce/admin/products.html (buscador + tabla ordenable con list.js). --}}
@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$yo->puede('roles.crear'), $yo->puede('roles.editar'), $yo->puede('roles.eliminar')];
        $totalRoles = $roles->count();
        $rolesActivos = $roles->where('activo', true)->count();
        $totalUsuariosConRol = $roles->sum('usuarios_count');
    @endphp

    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="#!">Administración</a></li>
            <li class="breadcrumb-item active">Roles y Permisos</li>
        </ol>
    </nav>

    <div class="mb-4">
        <div class="row g-3 justify-content-between align-items-end">
            <div class="col-12 col-md">
                <h2 class="mb-1 text-bold text-1100">
                    <span class="fas fa-shield-alt text-primary me-2"></span>Perfiles de Seguridad, Roles y Accesos
                </h2>
                <p class="text-700 mb-0">Matriz de privilegios por puesto de trabajo y políticas de autenticación en dos factores (2FA).</p>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <a class="btn btn-phoenix-secondary" href="{{ route('usuarios.index') }}">
                    <span class="fas fa-users-cog me-2"></span>Ver Usuarios del Sistema
                </a>
                @if ($puedeCrear)
                    <a class="btn btn-primary" href="{{ route('roles.create') }}">
                        <span class="fas fa-plus-circle me-2"></span>Crear Nuevo Rol
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Tarjetas de métricas de seguridad Phoenix -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100 border-start border-4 border-primary shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Roles Definidos</h6>
                            <h3 class="fw-bolder mb-1 text-1000">{{ $totalRoles }}</h3>
                            <span class="badge badge-phoenix badge-phoenix-success fs--2">
                                <i class="fas fa-check me-1"></i>{{ $rolesActivos }} operativos
                            </span>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-primary-subtle text-primary fs-1">
                                <i class="fas fa-user-tag"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100 border-start border-4 border-warning shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Asignaciones Activas</h6>
                            <h3 class="fw-bolder mb-1 text-warning">{{ $totalUsuariosConRol }}</h3>
                            <span class="fs--2 text-muted">cuentas vinculadas a perfiles</span>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-warning-subtle text-warning fs-1">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-12 col-xl-4">
            <div class="card h-100 border-start border-4 border-info shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 text-uppercase fs--2 mb-1 fw-bold">Catálogo de Privilegios</h6>
                            <h3 class="fw-bolder mb-1 text-info">{{ $totalPermisos }}</h3>
                            <span class="fs--2 text-muted"><i class="fas fa-lock me-1"></i>Permisos granulares del sistema</span>
                        </div>
                        <div class="avatar avatar-m">
                            <div class="avatar-name rounded-circle bg-info-subtle text-info fs-1">
                                <i class="fas fa-key"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla con List.js y contenedor Phoenix -->
    <div class="card shadow-none border" id="tablaRoles" data-list='{"valueNames":["nombre","usuarios","estado"],"page":25,"pagination":true}'>
        <div class="card-header p-3 border-bottom bg-body-emphasis">
            <div class="row justify-content-between align-items-center g-2">
                <div class="col-12 col-md-4">
                    <div class="search-box w-100">
                        <form class="position-relative" onsubmit="return false">
                            <input class="form-control form-control-sm search-input search" type="search" placeholder="Buscar rol por nombre o función..." aria-label="Buscar" />
                            <span class="fas fa-search search-box-icon text-500"></span>
                        </form>
                    </div>
                </div>
                <div class="col-12 col-md-auto">
                    <span class="badge badge-phoenix badge-phoenix-info fs--2">
                        <i class="fas fa-info-circle me-1"></i>Los roles de Administrador tienen acceso irrestricto por diseño.
                    </span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm fs--1 mb-0 align-middle table-hover">
                <thead class="bg-body-secondary text-900">
                    <tr>
                        <th class="sort ps-3 py-2 text-uppercase text-700" scope="col" data-sort="nombre" style="min-width:240px;">Rol & Descripción</th>
                        <th class="text-uppercase text-700" scope="col" style="min-width:180px;">Cobertura de Permisos</th>
                        <th class="sort text-center text-uppercase text-700" scope="col" data-sort="usuarios">Usuarios</th>
                        <th class="text-center text-uppercase text-700" scope="col">Seguridad 2FA</th>
                        <th class="sort text-center text-uppercase text-700" scope="col" data-sort="estado">Estado</th>
                        <th class="pe-3 text-end text-uppercase text-700" scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody class="list">
                @forelse ($roles as $rol)
                    @php
                        $pct = $totalPermisos > 0 ? min(100, round(($rol->permisos_count / $totalPermisos) * 100)) : 0;
                    @endphp
                    <tr class="hover-actions-trigger position-static">
                        <td class="nombre ps-3 py-2">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-m me-3 flex-shrink-0">
                                    <div class="avatar-name rounded-circle {{ $rol->es_rol_sistema ? 'bg-primary-subtle text-primary' : 'bg-body-secondary text-700' }} fw-bold fs-0">
                                        <i class="fas {{ $rol->esAdministrador() ? 'fa-crown' : 'fa-id-badge' }}"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        <a class="fw-bold text-1100 text-decoration-none fs-0" href="{{ route('roles.edit', $rol->id_rol) }}">
                                            {{ $rol->nombre }}
                                        </a>
                                        @if ($rol->es_rol_sistema)
                                            <span class="badge badge-phoenix badge-phoenix-primary fs--2 ms-1">Sistema</span>
                                        @endif
                                    </div>
                                    @if ($rol->descripcion)
                                        <p class="text-muted fs--2 mb-0 line-clamp-1">{{ $rol->descripcion }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($rol->esAdministrador())
                                <span class="badge badge-phoenix badge-phoenix-primary fs--2">
                                    <i class="fas fa-check-double me-1"></i>Acceso Total (100%)
                                </span>
                            @else
                                <div class="d-flex justify-content-between fs--2 mb-1">
                                    <span class="fw-semi-bold text-800">{{ $rol->permisos_count }} de {{ $totalPermisos }}</span>
                                    <span class="text-muted">{{ $pct }}%</span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pct }}%"></div>
                                </div>
                            @endif
                        </td>
                        <td class="usuarios text-center">
                            <span class="badge badge-phoenix badge-phoenix-secondary fs--1 px-2 py-1">
                                <i class="fas fa-user me-1"></i>{{ $rol->usuarios_count }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if ($rol->requiere_2fa)
                                <span class="badge badge-phoenix badge-phoenix-warning px-2 py-1">
                                    <i class="fas fa-mobile-alt me-1"></i>Obligatoria
                                </span>
                            @else
                                <span class="badge badge-phoenix badge-phoenix-secondary px-2 py-1">
                                    Opcional
                                </span>
                            @endif
                        </td>
                        <td class="estado text-center">
                            @if ($rol->activo)
                                <span class="badge badge-phoenix badge-phoenix-success px-2 py-1">
                                    <i class="fas fa-check-circle me-1"></i>Activo
                                </span>
                            @else
                                <span class="badge badge-phoenix badge-phoenix-danger px-2 py-1">
                                    <i class="fas fa-ban me-1"></i>Inactivo
                                </span>
                            @endif
                        </td>
                        <td class="pe-3 text-end">
                            <div class="btn-reveal-trigger position-static">
                                <button class="btn btn-sm btn-phoenix-secondary dropdown-toggle dropdown-caret-none transition-none" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Acciones">
                                    <span class="fas fa-ellipsis-h fs--2"></span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end py-2">
                                    <a class="dropdown-item" href="{{ route('roles.edit', $rol->id_rol) }}">
                                        <i class="fas fa-pen text-primary me-2"></i>{{ $puedeEditar ? 'Editar Permisos' : 'Ver Rol' }}
                                    </a>
                                    @if ($puedeEliminar && ! $rol->esProtegido() && $rol->usuarios_count === 0)
                                        <div class="dropdown-divider"></div>
                                        <form method="POST" action="{{ route('roles.destroy', $rol->id_rol) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item text-danger" type="button"
                                                    data-confirm-phoenix="¿Eliminar definitivamente el rol {{ $rol->nombre }}? Esta acción es irreversible."
                                                    data-confirm-titulo="Eliminar Rol de Usuario"
                                                    data-confirm-boton="Sí, eliminar rol">
                                                <i class="fas fa-trash me-2"></i>Eliminar Rol
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-700 py-5">
                            <div class="avatar avatar-xl mb-3 mx-auto">
                                <div class="avatar-name rounded-circle bg-body-secondary text-500 fs-1">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                            </div>
                            <h5 class="text-800">No se encontraron roles de usuario</h5>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer p-3 border-top d-flex align-items-center justify-content-between">
            <div class="d-none d-sm-block">
                <p class="mb-0 fs--1 text-700" data-list-info="data-list-info"></p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-phoenix-secondary" data-list-pagination="prev">
                    <span class="fas fa-chevron-left me-1"></span>Anterior
                </button>
                <button class="btn btn-sm btn-phoenix-secondary" data-list-pagination="next">
                    Siguiente<span class="fas fa-chevron-right ms-1"></span>
                </button>
            </div>
        </div>
    </div>
@endsection
