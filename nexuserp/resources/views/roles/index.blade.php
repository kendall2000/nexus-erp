@extends('layouts.app', ['titulo' => 'Roles y permisos'])

{{-- Estructura: apps/e-commerce/admin/products.html (buscador + tabla ordenable con list.js). --}}
@section('contenido')
    @php
        $yo = auth()->user();
        [$puedeCrear, $puedeEditar, $puedeEliminar] = [$yo->puede('roles.crear'), $yo->puede('roles.editar'), $yo->puede('roles.eliminar')];
    @endphp
    <div class="mb-9">
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Roles y permisos</h2>
                <p class="text-700 fw-semi-bold mb-0 mt-1">Qué puede hacer cada puesto en cada módulo. El Administrador tiene acceso a todo.</p>
            </div>
        </div>

        <div id="tablaRoles" data-list='{"valueNames":["nombre","usuarios","estado"],"page":25,"pagination":true}'>
            <div class="mb-4">
                <div class="d-flex flex-wrap gap-3">
                    <div class="search-box">
                        <form class="position-relative" onsubmit="return false">
                            <input class="form-control search-input search" type="search" placeholder="Buscar rol" aria-label="Buscar" />
                            <span class="fas fa-search search-box-icon"></span>
                        </form>
                    </div>
                    @if ($puedeCrear)
                        <div class="ms-xxl-auto">
                            <a class="btn btn-primary" href="{{ route('roles.create') }}"><span class="fas fa-plus me-2"></span>Nuevo rol</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table fs--1 mb-0">
                        <thead>
                        <tr>
                            <th class="sort white-space-nowrap align-middle ps-0" scope="col" data-sort="nombre" style="min-width:220px;">ROL</th>
                            <th class="align-middle ps-4" scope="col">PERMISOS</th>
                            <th class="sort align-middle text-end ps-4" scope="col" data-sort="usuarios">USUARIOS</th>
                            <th class="align-middle ps-4" scope="col">2 PASOS</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="estado">ESTADO</th>
                            <th class="align-middle text-end pe-0" scope="col"></th>
                        </tr>
                        </thead>
                        <tbody class="list">
                        @forelse ($roles as $rol)
                            <tr class="position-static">
                                <td class="nombre align-middle ps-0">
                                    <a class="fw-semi-bold" href="{{ route('roles.edit', $rol->id_rol) }}">{{ $rol->nombre }}</a>
                                    @if ($rol->es_rol_sistema)<span class="badge badge-phoenix badge-phoenix-secondary ms-1">Sistema</span>@endif
                                    @if ($rol->descripcion)<p class="text-600 fs--1 mb-0 line-clamp-1">{{ $rol->descripcion }}</p>@endif
                                </td>
                                <td class="align-middle ps-4 white-space-nowrap">
                                    @if ($rol->esAdministrador())
                                        <span class="badge badge-phoenix badge-phoenix-primary">Todos</span>
                                    @else
                                        <span class="fw-semi-bold text-700">{{ $rol->permisos_count }} de {{ $totalPermisos }}</span>
                                    @endif
                                </td>
                                <td class="usuarios align-middle text-end fw-bold text-700 ps-4">{{ $rol->usuarios_count }}</td>
                                <td class="align-middle ps-4">
                                    @if ($rol->requiere_2fa)
                                        <span class="badge badge-phoenix badge-phoenix-warning">Obligatoria</span>
                                    @else
                                        <span class="badge badge-phoenix badge-phoenix-secondary">Opcional</span>
                                    @endif
                                </td>
                                <td class="estado align-middle ps-4">
                                    @if ($rol->activo)
                                        <span class="badge badge-phoenix badge-phoenix-success">Activo</span>
                                    @else
                                        <span class="badge badge-phoenix badge-phoenix-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="align-middle white-space-nowrap text-end pe-0 btn-reveal-trigger">
                                    <div class="font-sans-serif btn-reveal-trigger position-static">
                                        <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent" aria-label="Acciones"><span class="fas fa-ellipsis-h fs--2"></span></button>
                                        <div class="dropdown-menu dropdown-menu-end py-2">
                                            <a class="dropdown-item" href="{{ route('roles.edit', $rol->id_rol) }}">{{ $puedeEditar ? 'Editar' : 'Ver' }}</a>
                                            @if ($puedeEliminar && ! $rol->esProtegido() && $rol->usuarios_count === 0)
                                                <div class="dropdown-divider"></div>
                                                <form method="POST" action="{{ route('roles.destroy', $rol->id_rol) }}" onsubmit="return confirm(@js('¿Eliminar el rol '.$rol->nombre.'?'))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit">Eliminar</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-700 py-4">No hay roles.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="row align-items-center justify-content-between py-2 pe-0 fs--1">
                    <div class="col-auto d-flex">
                        <p class="mb-0 d-none d-sm-block me-3 fw-semi-bold text-900" data-list-info="data-list-info"></p>
                    </div>
                    <div class="col-auto d-flex">
                        <button class="page-link" data-list-pagination="prev" aria-label="Anterior"><span class="fas fa-chevron-left"></span></button>
                        <ul class="mb-0 pagination"></ul>
                        <button class="page-link pe-0" data-list-pagination="next" aria-label="Siguiente"><span class="fas fa-chevron-right"></span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
