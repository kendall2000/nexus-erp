@extends('layouts.app', ['titulo' => $item->exists ? 'Editar menú' : 'Nuevo en el menú'])

@section('contenido')
    @php $esOpcion = old('id_padre', $item->id_padre) !== null && old('id_padre', $item->id_padre) !== ''; @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('menu.index') }}">Gestión de menú</a></li>
            <li class="breadcrumb-item active">{{ $item->exists ? $item->nombre : 'Nuevo' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $item->exists ? 'Editar «'.$item->nombre.'»' : ($esOpcion ? 'Nueva opción' : 'Nuevo grupo') }}</h2>

    <div class="card" style="max-width: 40rem">
        <div class="card-body">
            <form method="POST" action="{{ $item->exists ? route('menu.update', $item->id_menu) : route('menu.store') }}">
                @csrf
                @if ($item->exists)
                    @method('PUT')
                @endif
                <div class="mb-3">
                    <label class="form-label" for="id_padre">Ubicación</label>
                    <select class="form-select" id="id_padre" name="id_padre">
                        <option value="">Es un grupo (título del menú)</option>
                        @foreach ($grupos as $g)
                            <option value="{{ $g->id_menu }}" @selected((int) old('id_padre', $item->id_padre) === $g->id_menu)>Opción dentro de «{{ $g->nombre }}»</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $item->nombre) }}" required maxlength="100" placeholder="Ej.: Clientes, Finanzas" />
                </div>
                <div id="campos-opcion" class="{{ $esOpcion ? '' : 'd-none' }}">
                    <div class="mb-3">
                        <label class="form-label" for="icono">Ícono</label>
                        <div class="input-group">
                            <span class="input-group-text" id="vista-icono" style="width:3rem"><span data-feather="{{ old('icono', $item->icono) ?: 'chevrons-right' }}" style="width:16px;height:16px"></span></span>
                            <input class="form-control" id="icono" name="icono" list="lista-iconos" value="{{ old('icono', $item->icono) }}" maxlength="50" placeholder="p. ej. users, package, file-text" autocomplete="off" />
                        </div>
                        <datalist id="lista-iconos">
                            @foreach ($iconos as $icono)<option value="{{ $icono }}"></option>@endforeach
                        </datalist>
                        <div class="form-text">Nombre de un ícono <a href="https://feathericons.com" target="_blank" rel="noopener">Feather</a>.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ruta">Ruta</label>
                        <input class="form-control" id="ruta" name="ruta" value="{{ old('ruta', $item->ruta) }}" maxlength="200" placeholder="/sistema/clientes" />
                        <div class="form-text">Dirección interna de la pantalla. Si la pantalla todavía no existe, lleva al inicio con un aviso.</div>
                    </div>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $item->activo)) />
                    <label class="form-check-label" for="activo">Visible en el menú</label>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('menu.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Grupo vs. opción: los grupos no llevan ícono ni ruta. Vista previa del ícono Feather.
        (function () {
            var padre = document.getElementById('id_padre');
            var campos = document.getElementById('campos-opcion');
            var icono = document.getElementById('icono');
            var vista = document.getElementById('vista-icono');
            padre.addEventListener('change', function () { campos.classList.toggle('d-none', padre.value === ''); });
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
