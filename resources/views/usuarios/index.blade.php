@extends('layouts.app', ['titulo' => 'Usuarios'])

@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeEditar, $puedeEliminar] = [$yo->puede('usuarios.editar'), $yo->puede('usuarios.eliminar')];
        $totalUsuarios = $usuarios->total();
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Seguridad & Sistema</li>
            <li class="breadcrumb-item active">Usuarios</li>
        </ol>
    </nav>

    <div class="mb-9">
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Control de Acceso & Cuentas de Usuario</h2>
            </div>
        </div>

        {{-- Tarjetas KPI de Resumen de Seguridad --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Total Usuarios</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ number_format($totalUsuarios) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-users text-primary fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Usuarios Activos</h6>
                                <h3 class="text-success mb-0 fw-bolder">{{ number_format($usuarios->where('activo', true)->count()) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-success-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-user-check text-success fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Con 2 Factores (2FA)</h6>
                                <h3 class="text-info mb-0 fw-bolder">{{ number_format($usuarios->whereNotNull('two_factor_confirmed_at')->count()) }}</h3>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-shield-alt text-info fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="usersTable">
            {{-- Toolbar idéntico a Phoenix --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('usuarios.index') }}" class="row g-3">
                    <div class="col-auto">
                        <div class="search-box">
                            <div class="position-relative">
                                <input class="form-control search-input search" type="search" name="buscar" value="{{ $buscar }}" placeholder="Nombre, usuario o correo..." aria-label="Search" />
                                <span class="fas fa-search search-box-icon"></span>
                            </div>
                        </div>
                    </div>
                    @if ($buscar !== '')
                        <div class="col-auto">
                            <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('usuarios.index') }}" title="Limpiar filtro">
                                <span class="fas fa-times me-1"></span>Limpiar
                            </a>
                        </div>
                    @endif
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a class="btn btn-phoenix-secondary" href="{{ route('roles.index') }}">
                            <span class="fas fa-user-tag me-2"></span>Roles & Permisos
                        </a>
                        @if ($yo->puede('usuarios.crear'))
                            <a class="btn btn-primary" href="{{ route('usuarios.create') }}">
                                <span class="fas fa-plus me-2"></span>Nuevo usuario
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Tabla con estructura visual exacta de Phoenix --}}
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="white-space-nowrap fs--1 align-middle ps-0" style="max-width:20px; width:18px;">
                                    <div class="form-check mb-0 fs-0">
                                        <input class="form-check-input" id="checkbox-bulk-users-select" type="checkbox" />
                                    </div>
                                </th>
                                <th class="sort align-middle ps-3" scope="col" style="min-width:260px;">USUARIO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:160px;">USERNAME</th>
                                <th class="sort align-middle ps-4" scope="col" style="min-width:220px;">CORREO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:180px;">ROL ASIGNADO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:160px;">SUCURSAL</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:110px;">2FA</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:160px;">ÚLTIMO ACCESO</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:120px;">ESTADO</th>
                                <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody class="list" id="users-table-body">
                            @forelse ($usuarios as $u)
                                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                                    <td class="fs--1 align-middle ps-0">
                                        <div class="form-check mb-0 fs-0">
                                            <input class="form-check-input" type="checkbox" />
                                        </div>
                                    </td>
                                    <td class="customer align-middle white-space-nowrap ps-3 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-m me-3">
                                                @if ($u->avatar_url)
                                                    <img class="rounded-circle" src="{{ $u->avatar_url }}" alt="" />
                                                @else
                                                    <div class="avatar-name rounded-circle bg-soft-primary text-primary fw-bold">
                                                        <span>{{ mb_strtoupper(mb_substr($u->nombre_completo ?: $u->username, 0, 1)) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-1100 fw-bold">{{ $u->nombre_completo }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 text-900 fw-semi-bold">
                                        {{ $u->username }}
                                    </td>
                                    <td class="email align-middle white-space-nowrap ps-4 text-700">
                                        {{ $u->email }}
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        <span class="badge badge-tag">{{ $u->roles->pluck('nombre')->join(', ') ?: 'Sin rol' }}</span>
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 text-800">
                                        {{ $u->sucursal?->nombre ?? '—' }}
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4">
                                        @if ($u->two_factor_confirmed_at)
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-success"><span class="fas fa-check me-1"></span>Activo</span>
                                        @else
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-secondary">No</span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap ps-4 text-700 fs--1">
                                        {{ $u->ultimo_login?->format('d/m/Y H:i') ?? 'Nunca' }}
                                    </td>
                                    <td class="payment_status align-middle white-space-nowrap ps-4">
                                        @if ($u->activo)
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-success"><span class="badge-label">Activo</span></span>
                                        @else
                                            <span class="badge badge-phoenix fs--2 badge-phoenix-danger"><span class="badge-label">Inactivo</span></span>
                                        @endif
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                        <div class="font-sans-serif btn-reveal-trigger position-static">
                                            <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                                <span class="fas fa-ellipsis-h fs--2"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end py-2">
                                                @if ($puedeEditar && $yo->puedeGestionar($u))
                                                    <a class="dropdown-item" href="{{ route('usuarios.edit', $u->id_usuario) }}">
                                                        <span class="fas fa-edit me-2"></span>Editar cuenta
                                                    </a>
                                                    @if ($u->id_usuario !== auth()->id())
                                                        <form method="POST" action="{{ route('usuarios.estado', $u->id_usuario) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="dropdown-item" type="submit">
                                                                <span class="fas {{ $u->activo ? 'fa-ban text-warning' : 'fa-check text-success' }} me-2"></span>
                                                                {{ $u->activo ? 'Desactivar acceso' : 'Activar acceso' }}
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('usuarios.sesiones', $u->id_usuario) }}" onsubmit="return confirm(@js('¿Cerrar todas las sesiones de '.$u->username.'?'))">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="dropdown-item text-warning" type="submit">
                                                                <span class="fas fa-sign-out-alt me-2"></span>Cerrar sesiones
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                                @if ($puedeEliminar && $u->id_usuario !== auth()->id() && $yo->puedeGestionar($u))
                                                    <div class="dropdown-divider"></div>
                                                    <form method="POST" action="{{ route('usuarios.destroy', $u->id_usuario) }}" onsubmit="return confirm(@js('¿Eliminar al usuario '.$u->username.'?'))">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit">
                                                            <span class="fas fa-trash me-2"></span>Eliminar
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-700 py-5">
                                        <span class="fas fa-user-shield fs-2 text-300 d-block mb-2"></span>
                                        {{ $buscar !== '' ? 'Ningún usuario coincide con la búsqueda.' : 'No hay usuarios registrados.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer de paginación --}}
                <div class="row align-items-center justify-content-between py-2 pe-0 fs--1 border-top border-200">
                    <div class="col-auto d-flex">
                        <p class="mb-0 d-none d-sm-block me-3 fw-semi-bold text-900">
                            Mostrando {{ $usuarios->firstItem() ?? 0 }} a {{ $usuarios->lastItem() ?? 0 }}
                            <span class="text-600">de</span> {{ $usuarios->total() }} cuentas
                        </p>
                    </div>
                    <div class="col-auto d-flex">
                        {{ $usuarios->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
