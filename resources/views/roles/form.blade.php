@extends('layouts.app', ['titulo' => $rol->exists ? 'Editar rol' : 'Nuevo rol'])

{{-- Estructura: apps/e-commerce/admin/add-product.html (encabezado con acciones, columna principal y tarjeta lateral). --}}
@section('contenido')
    @php
        $esAdmin = $rol->exists && $rol->esAdministrador();
        $bloqueado = ! auth()->user()->puede($rol->exists ? 'roles.editar' : 'roles.crear') || $soloLectura;
        $marcados = array_map('intval', old('permisos', $marcados));
    @endphp

    @if ($soloLectura)
        <div class="alert alert-soft-warning fs--1" role="alert">{{ $soloLectura }}</div>
    @endif

    <form class="mb-9" method="POST" action="{{ $rol->exists ? route('roles.update', $rol->id_rol) : route('roles.store') }}">
        @csrf
        @if ($rol->exists)
            @method('PUT')
        @endif
        <fieldset @disabled($bloqueado)>
            <x-page-header
                :title="$rol->exists ? ($bloqueado ? $rol->nombre : 'Editar rol') : 'Nuevo rol'"
                subtitle="Marca lo que este rol puede hacer en cada módulo."
                :breadcrumbs="[
                    ['label' => 'Roles y permisos', 'url' => route('roles.index')],
                    ['label' => $rol->exists ? $rol->nombre : 'Nuevo rol']
                ]"
            >
                <a class="btn btn-phoenix-secondary me-2" href="{{ route('roles.index') }}">{{ $bloqueado ? 'Volver' : 'Cancelar' }}</a>
                @unless ($bloqueado)
                    <button class="btn btn-primary" type="submit"><span class="fas fa-check me-2"></span>Guardar rol</button>
                @endunless
            </x-page-header>

            <div class="row g-5">
                <div class="col-12 col-xl-8">
                    <div class="card border border-300 shadow-none mb-4">
                        <div class="card-body">
                            <h4 class="mb-3">Nombre del rol</h4>
                            <input class="form-control mb-4" id="nombre" name="nombre" type="text" maxlength="100" required
                                   value="{{ old('nombre', $rol->nombre) }}" placeholder="Ej.: Contabilidad, Bodega, Ventas" @readonly($esAdmin) />

                            <h4 class="mb-2">Permisos</h4>
                            @if ($esAdmin)
                                <div class="alert alert-soft-primary fs--1" role="alert">
                                    Este rol tiene acceso total: todos los permisos y todo el menú. No se puede limitar.
                                </div>
                            @else
                                <p class="text-700 fs--1 mb-3">
                                    «Ver» también muestra el módulo en el menú lateral.
                                    @if ($propios !== null) Solo puedes dar los permisos que tú tienes; los demás aparecen deshabilitados y se conservan como están. @endif
                                </p>
                                @include('permisos.matriz', ['matriz' => $matriz, 'marcados' => $marcados, 'propios' => $propios, 'bloqueado' => $bloqueado])
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card border border-300 shadow-none mb-3">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Detalles</h4>
                            <div class="mb-4">
                                <h5 class="mb-2 text-1000">Descripción</h5>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="300" placeholder="Qué hace este puesto">{{ old('descripcion', $rol->descripcion) }}</textarea>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" id="requiere_2fa" name="requiere_2fa" type="checkbox" value="1" @checked(old('requiere_2fa', $rol->requiere_2fa)) />
                                <label class="form-check-label text-900" for="requiere_2fa">Exigir verificación en dos pasos</label>
                            </div>
                            @unless ($esAdmin)
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $rol->activo)) />
                                    <label class="form-check-label text-900" for="activo">Rol activo</label>
                                </div>
                                <p class="text-600 fs--1 mt-2 mb-0">Si lo desactivas, sus usuarios (sin otro rol activo) no podrán entrar al sistema.</p>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </form>
@endsection
