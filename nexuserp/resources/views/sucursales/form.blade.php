@extends('layouts.app', ['titulo' => $sucursal->exists ? 'Editar sucursal' : 'Nueva sucursal'])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('sucursales.index') }}">Sucursales</a></li>
            <li class="breadcrumb-item active">{{ $sucursal->exists ? $sucursal->nombre : 'Nueva' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $sucursal->exists ? 'Editar sucursal' : 'Nueva sucursal' }}</h2>

    <div class="card" style="max-width: 48rem">
        <div class="card-body">
            <form method="POST" action="{{ $sucursal->exists ? route('sucursales.update', $sucursal->id_sucursal) : route('sucursales.store') }}">
                @csrf
                @if ($sucursal->exists)
                    @method('PUT')
                @endif
                <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $sucursal->nombre) }}" required maxlength="150" />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="id_pais">País</label>
                        <select class="form-select" id="id_pais" name="id_pais">
                            <option value="">—</option>
                            @foreach ($paises as $p)
                                <option value="{{ $p->id_pais }}" @selected((int) old('id_pais', $sucursal->id_pais) === $p->id_pais)>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_division">Departamento</label>
                        <select class="form-select" id="id_division" name="id_division" data-valor="{{ old('id_division', $sucursal->id_division) }}"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_municipio">Municipio</label>
                        <select class="form-select" id="id_municipio" name="id_municipio" data-valor="{{ old('id_municipio', $sucursal->id_municipio) }}"></select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="direccion">Dirección</label>
                    <textarea class="form-control" id="direccion" name="direccion" rows="2" maxlength="300">{{ old('direccion', $sucursal->direccion) }}</textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input class="form-control" id="telefono" name="telefono" value="{{ old('telefono', $sucursal->telefono) }}" maxlength="50" />
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="email">Correo</label>
                        <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $sucursal->email) }}" maxlength="100" />
                    </div>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" id="es_casa_matriz" name="es_casa_matriz" type="checkbox" value="1" @checked(old('es_casa_matriz', $sucursal->es_casa_matriz)) />
                    <label class="form-check-label" for="es_casa_matriz">Casa matriz</label>
                    <div class="form-text mt-0">Solo puede haber una: al marcar esta, la anterior deja de serlo.</div>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $sucursal->activo)) />
                    <label class="form-check-label" for="activo">Activa</label>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('sucursales.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // País → departamento → municipio, filtrando en el navegador.
        (function () {
            var divisiones = @json($divisiones);
            var municipios = @json($municipios);
            var pais = document.getElementById('id_pais');
            var division = document.getElementById('id_division');
            var municipio = document.getElementById('id_municipio');

            function llenar(select, opciones, llave, valor) {
                select.innerHTML = '';
                select.appendChild(new Option('—', ''));
                opciones.forEach(function (o) {
                    var opcion = new Option(o.nombre, o[llave]);
                    if (String(o[llave]) === String(valor)) opcion.selected = true;
                    select.appendChild(opcion);
                });
                select.disabled = opciones.length === 0;
            }
            function cargarDivisiones(valor) {
                llenar(division, divisiones.filter(function (d) { return String(d.id_pais) === pais.value; }), 'id_division', valor);
            }
            function cargarMunicipios(valor) {
                llenar(municipio, municipios.filter(function (m) { return String(m.id_division) === division.value; }), 'id_municipio', valor);
            }

            pais.addEventListener('change', function () { cargarDivisiones(''); cargarMunicipios(''); });
            division.addEventListener('change', function () { cargarMunicipios(''); });
            cargarDivisiones(division.dataset.valor);
            cargarMunicipios(municipio.dataset.valor);
        })();
    </script>
@endpush
