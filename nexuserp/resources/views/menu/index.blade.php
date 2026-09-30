@extends('layouts.app', ['titulo' => 'Gestión de menú'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div>
            <h2 class="mb-1 text-1100">Gestión de menú</h2>
            <p class="text-700 fw-semi-bold mb-0">Grupos y opciones del menú lateral. Qué rol ve cada opción se elige en <a href="{{ route('roles.index') }}">Roles y permisos</a>.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('menu.create') }}"><span class="fas fa-plus me-2"></span>Nuevo grupo</a>
    </div>

    <div class="row g-3 mt-2 mb-9">
        @forelse ($grupos as $grupo)
            <div class="col-12 col-lg-6 col-xxl-4">
                <div class="card h-100 {{ $grupo->activo ? '' : 'border-dashed opacity-75' }}">
                    <div class="card-header d-flex align-items-center gap-2 py-2">
                        <div class="d-flex flex-column">
                            @include('menu.mover', ['m' => $grupo, 'primero' => $loop->first, 'ultimo' => $loop->last])
                        </div>
                        <h5 class="mb-0 flex-1 text-1000">
                            {{ $grupo->nombre }}
                            @unless ($grupo->activo)<span class="badge badge-phoenix badge-phoenix-secondary ms-1">Inactivo</span>@endunless
                        </h5>
                        @include('menu.acciones', ['m' => $grupo])
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($grupo->todosLosHijos as $opcion)
                                <li class="list-group-item d-flex align-items-center gap-2 py-2 {{ $opcion->activo ? '' : 'text-500' }}">
                                    <div class="d-flex flex-column">
                                        @include('menu.mover', ['m' => $opcion, 'primero' => $loop->first, 'ultimo' => $loop->last])
                                    </div>
                                    <span class="text-700"><span data-feather="{{ $opcion->icono ?: 'chevrons-right' }}" style="width:16px;height:16px"></span></span>
                                    <div class="flex-1 min-w-0">
                                        <div class="fw-semi-bold fs--1 text-truncate">
                                            {{ $opcion->nombre }}
                                            @unless ($opcion->activo)<span class="badge badge-phoenix badge-phoenix-secondary ms-1">Inactiva</span>@endunless
                                        </div>
                                        <div class="fs--2 text-600 text-truncate">
                                            {{ $opcion->ruta ?: 'Sin ruta' }} ·
                                            {{ $opcion->roles_count ? $opcion->roles_count.' '.($opcion->roles_count === 1 ? 'rol' : 'roles') : 'Todos los roles' }}
                                        </div>
                                    </div>
                                    @include('menu.acciones', ['m' => $opcion])
                                </li>
                            @empty
                                <li class="list-group-item text-600 fs--1 py-3">Sin opciones.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="card-footer py-2">
                        <a class="btn btn-link btn-sm px-0" href="{{ route('menu.create', ['grupo' => $grupo->id_menu]) }}"><span class="fas fa-plus me-1"></span>Agregar opción</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body text-center text-700 py-6">No hay grupos en el menú. Crea el primero.</div></div>
            </div>
        @endforelse
    </div>
@endsection
