@extends('layouts.app', ['titulo' => $cliente->exists ? 'Editar cliente' : 'Nuevo cliente'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $cliente->{$campo}); @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
            @if ($cliente->exists)<li class="breadcrumb-item"><a href="{{ route('clientes.show', $cliente->id_cliente) }}">{{ $cliente->razon_social }}</a></li>@endif
            <li class="breadcrumb-item active">{{ $cliente->exists ? 'Editar' : 'Nuevo' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $cliente->exists ? 'Editar cliente' : 'Nuevo cliente' }}</h2>

    <form method="POST" action="{{ $cliente->exists ? route('clientes.update', $cliente->id_cliente) : route('clientes.store') }}" class="mb-9" style="max-width: 60rem">
        @csrf
        @if ($cliente->exists)
            @method('PUT')
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Datos generales</h5>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="razon_social">Razón social</label>
                        <input class="form-control @error('razon_social') is-invalid @enderror" id="razon_social" name="razon_social" value="{{ $v('razon_social') }}" required maxlength="250" />
                        @error('razon_social')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="nit">NIT</label>
                        <input class="form-control text-uppercase @error('nit') is-invalid @enderror" id="nit" name="nit" value="{{ $v('nit') }}" maxlength="25" placeholder="Ej.: 1234567-8 o CF" />
                        @error('nit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8"><label class="form-label" for="nombre_comercial">Nombre comercial</label><input class="form-control" id="nombre_comercial" name="nombre_comercial" value="{{ $v('nombre_comercial') }}" maxlength="150" /></div>
                    <div class="col-md-4">
                        <label class="form-label" for="tipo_persona">Tipo de persona</label>
                        <select class="form-select" id="tipo_persona" name="tipo_persona" required>
                            <option value="JURIDICA" @selected($v('tipo_persona') === 'JURIDICA')>Jurídica (empresa)</option>
                            <option value="NATURAL" @selected($v('tipo_persona') === 'NATURAL')>Natural (individual)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_industria">Industria</label>
                        <select class="form-select" id="id_industria" name="id_industria">
                            <option value="">—</option>
                            @foreach ($industrias as $i)<option value="{{ $i->id_industria }}" @selected((int) $v('id_industria') === $i->id_industria)>{{ $i->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="segmento">Segmento</label>
                        <select class="form-select" id="segmento" name="segmento">
                            <option value="">—</option>
                            @foreach ($segmentos as $codigo => $nombre)<option value="{{ $codigo }}" @selected($v('segmento') === $codigo)>{{ $nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="categoria">Categoría</label>
                        <select class="form-select" id="categoria" name="categoria">
                            <option value="">—</option>
                            @foreach ($categorias as $cat)<option value="{{ $cat }}" @selected($v('categoria') === $cat)>{{ $cat }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Contacto y ubicación</h5>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="email_principal">Correo</label>
                        <input class="form-control @error('email_principal') is-invalid @enderror" id="email_principal" name="email_principal" type="email" value="{{ $v('email_principal') }}" maxlength="150" />
                        @error('email_principal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3"><label class="form-label" for="telefono_principal">Teléfono</label><input class="form-control" id="telefono_principal" name="telefono_principal" value="{{ $v('telefono_principal') }}" maxlength="20" /></div>
                    <div class="col-md-4">
                        <label class="form-label" for="sitio_web">Sitio web</label>
                        <input class="form-control @error('sitio_web') is-invalid @enderror" id="sitio_web" name="sitio_web" value="{{ $v('sitio_web') }}" maxlength="200" placeholder="empresa.com" />
                        @error('sitio_web')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_pais">País</label>
                        <select class="form-select" id="id_pais" name="id_pais" required>
                            <option value="">Selecciona…</option>
                            @foreach ($paises as $p)<option value="{{ $p->id_pais }}" @selected((int) $v('id_pais') === $p->id_pais)>{{ $p->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_division">Departamento</label>
                        <select class="form-select" id="id_division" name="id_division" data-valor="{{ old('id_division', $cliente->municipio?->id_division) }}"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_municipio">Municipio</label>
                        <select class="form-select @error('id_municipio') is-invalid @enderror" id="id_municipio" name="id_municipio" data-valor="{{ $v('id_municipio') }}"></select>
                        @error('id_municipio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12"><label class="form-label" for="direccion_fiscal">Dirección fiscal</label><input class="form-control" id="direccion_fiscal" name="direccion_fiscal" value="{{ $v('direccion_fiscal') }}" maxlength="300" /></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Facturación y crédito</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="moneda_facturacion">Moneda</label>
                        <select class="form-select" id="moneda_facturacion" name="moneda_facturacion" required>
                            @foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda_facturacion') === $m->codigo)>{{ $m->codigo }} — {{ $m->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="dias_credito">Días de crédito</label>
                        <input class="form-control @error('dias_credito') is-invalid @enderror" id="dias_credito" name="dias_credito" type="number" min="0" max="255" value="{{ $v('dias_credito') }}" required />
                        @error('dias_credito')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="limite_credito">Límite de crédito</label>
                        <input class="form-control" id="limite_credito" name="limite_credito" type="number" step="0.01" min="0" value="{{ $v('limite_credito') !== null ? (float) $v('limite_credito') : '' }}" placeholder="Sin límite" />
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked($v('activo')) />
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-phoenix-secondary" href="{{ $cliente->exists ? route('clientes.show', $cliente->id_cliente) : route('clientes.index') }}">Cancelar</a>
        </div>
    </form>
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
