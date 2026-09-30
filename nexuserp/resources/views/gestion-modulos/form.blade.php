@extends('layouts.app', ['titulo' => $modulo->exists ? 'Editar módulo' : 'Nuevo módulo'])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('modulos.index') }}">Módulos</a></li>
            <li class="breadcrumb-item active">{{ $modulo->exists ? $modulo->nombre : 'Nuevo' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $modulo->exists ? 'Editar «'.$modulo->nombre.'»' : 'Nuevo módulo' }}</h2>

    <form method="POST" action="{{ $modulo->exists ? route('modulos.update', $modulo->id_modulo) : route('modulos.store') }}" class="mb-9">
        @csrf
        @if ($modulo->exists)
            @method('PUT')
        @endif
        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="mb-3">Datos y menú</h4>
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label" for="nombre">Nombre</label>
                                <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $modulo->nombre) }}" required maxlength="100" placeholder="Ej.: Bodegas" />
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="codigo">Código</label>
                                @if ($modulo->exists)
                                    <input class="form-control" id="codigo" value="{{ $modulo->codigo }}" readonly />
                                    <div class="form-text">No se cambia: lo usan los permisos de las pantallas.</div>
                                @else
                                    <input class="form-control" id="codigo" name="codigo" value="{{ old('codigo') }}" required maxlength="50" placeholder="bodegas" />
                                    <div class="form-text">Minúsculas y guion bajo. Los permisos serán «código.acción».</div>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="descripcion">Descripción</label>
                            <input class="form-control" id="descripcion" name="descripcion" value="{{ old('descripcion', $modulo->descripcion) }}" maxlength="255" />
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="grupo">Grupo del menú</label>
                                <input class="form-control" id="grupo" name="grupo" list="lista-grupos" value="{{ old('grupo', $modulo->grupo) }}" maxlength="60" placeholder="Ej.: Inventario" />
                                <datalist id="lista-grupos">@foreach ($grupos as $g)<option value="{{ $g }}"></option>@endforeach</datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="id_modulo_padre">Dentro de (submenú)</label>
                                <select class="form-select" id="id_modulo_padre" name="id_modulo_padre">
                                    <option value="">Ninguno (opción de primer nivel)</option>
                                    @foreach ($padres as $p)
                                        <option value="{{ $p->id_modulo }}" @selected((int) old('id_modulo_padre', $modulo->id_modulo_padre) === $p->id_modulo)>{{ $p->grupo ? $p->grupo.' › ' : '' }}{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="icono">Ícono</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="vista-icono" style="width:3rem"><span data-feather="{{ old('icono', $modulo->icono) ?: 'chevrons-right' }}" style="width:16px;height:16px"></span></span>
                                    <input class="form-control" id="icono" name="icono" value="{{ old('icono', $modulo->icono) }}" maxlength="50" placeholder="package" autocomplete="off" />
                                </div>
                                <div class="form-text">Nombre de un ícono <a href="https://feathericons.com" target="_blank" rel="noopener">Feather</a>.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="ruta">Ruta</label>
                                <input class="form-control" id="ruta" name="ruta" value="{{ old('ruta', $modulo->ruta) }}" maxlength="150" placeholder="/sistema/bodegas" />
                                <div class="form-text">Vacía = no aparece en el menú (solo permisos).</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $modulo->activo)) />
                            <label class="form-check-label" for="activo">Activo</label>
                            <div class="form-text mt-0">Inactivo: no aparece en el menú y sus permisos dejan de valer.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="mb-1">Acciones</h4>
                        <p class="text-700 fs--1">Cada acción marcada es un permiso que se asigna en Roles o como extra a un usuario. Sin acciones, solo el Administrador ve el módulo.</p>
                        @php $marcadas = array_map('intval', old('acciones', $marcadas)); @endphp
                        @foreach ($acciones as $a)
                            <div class="form-check mb-2">
                                <input class="form-check-input" id="accion-{{ $a->id_accion }}" name="acciones[]" type="checkbox" value="{{ $a->id_accion }}" @checked(in_array($a->id_accion, $marcadas, true)) />
                                <label class="form-check-label" for="accion-{{ $a->id_accion }}">
                                    <span class="fw-semi-bold">{{ $a->nombre }}</span> <code class="fs--2">{{ $a->codigo }}</code>
                                    <span class="d-block fs--2 text-600">{{ $a->descripcion }}</span>
                                </label>
                            </div>
                        @endforeach
                        @if ($modulo->exists)
                            <div class="alert alert-soft-warning fs--2 mb-0 mt-3">Quitar una acción elimina ese permiso de todos los roles y usuarios que lo tengan.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-phoenix-secondary" href="{{ route('modulos.index') }}">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Vista previa del ícono Feather.
        (function () {
            var icono = document.getElementById('icono');
            var vista = document.getElementById('vista-icono');
            icono.addEventListener('input', function () {
                var nombre = icono.value.trim().toLowerCase();
                vista.innerHTML = '';
                var span = document.createElement('span');
                span.setAttribute('data-feather', feather.icons[nombre] ? nombre : 'help-circle');
                span.style.width = '16px'; span.style.height = '16px';
                vista.appendChild(span);
                feather.replace();
            });
        })();
    </script>
@endpush
