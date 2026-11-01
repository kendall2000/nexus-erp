@extends('layouts.app', ['titulo' => $cliente->exists ? 'Editar cliente' : 'Nuevo cliente'])

@section('contenido')
    @php
        $v = fn (string $campo) => old($campo, $cliente->{$campo});
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
            @if ($cliente->exists)
                <li class="breadcrumb-item"><a href="{{ route('clientes.show', $cliente->id_cliente) }}">{{ $cliente->razon_social }}</a></li>
            @endif
            <li class="breadcrumb-item active">{{ $cliente->exists ? 'Editar' : 'Nuevo' }}</li>
        </ol>
    </nav>

    <form method="POST" action="{{ $cliente->exists ? route('clientes.update', $cliente->id_cliente) : route('clientes.store') }}" class="mb-9">
        @csrf
        @if ($cliente->exists)
            @method('PUT')
        @endif

        {{-- Encabezado idéntico a apps/e-commerce/admin/add-product.html --}}
        <div class="row g-3 flex-between-end mb-5">
            <div class="col-auto">
                <h2 class="mb-2">{{ $cliente->exists ? 'Editar Cliente: '.$cliente->razon_social : 'Agregar un nuevo cliente' }}</h2>
                <h5 class="text-700 fw-semi-bold">Configuración de datos fiscales, ubicación geográfica y condiciones comerciales</h5>
            </div>
            <div class="col-auto">
                <a class="btn btn-phoenix-secondary me-2 mb-2 mb-sm-0" href="{{ $cliente->exists ? route('clientes.show', $cliente->id_cliente) : route('clientes.index') }}">Descartar</a>
                <button class="btn btn-primary mb-2 mb-sm-0" type="submit">
                    <span class="fas fa-save me-2"></span>{{ $cliente->exists ? 'Actualizar cliente' : 'Guardar cliente' }}
                </button>
            </div>
        </div>

        <div class="row g-5">
            {{-- Columna Principal Izquierda (8 columnas) --}}
            <div class="col-12 col-xl-8">
                <h4 class="mb-3">Razón Social del Cliente</h4>
                <input class="form-control mb-5 @error('razon_social') is-invalid @enderror" type="text" id="razon_social" name="razon_social" value="{{ $v('razon_social') }}" placeholder="Nombre o Razón Social formal..." required maxlength="250" />
                @error('razon_social')<div class="invalid-feedback mb-3">{{ $message }}</div>@enderror

                {{-- Pestañas verticales idénticas a Phoenix --}}
                <h4 class="mb-3">Información y Condiciones Comerciales</h4>
                <div class="row g-0 border-top border-bottom border-300 mb-5">
                    <div class="col-sm-4">
                        <div class="nav flex-sm-column border-bottom border-bottom-sm-0 border-end-sm border-300 fs--1 vertical-tab h-100 justify-content-between" role="tablist" aria-orientation="vertical">
                            <a class="nav-link border-end border-end-sm-0 border-bottom-sm border-300 text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center active" id="generalTab" data-bs-toggle="tab" data-bs-target="#generalTabContent" role="tab">
                                <span class="me-sm-2 fas fa-building"></span><span class="d-none d-sm-inline">Datos Fiscales</span>
                            </a>
                            <a class="nav-link border-end border-end-sm-0 border-bottom-sm border-300 text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center" id="locationTab" data-bs-toggle="tab" data-bs-target="#locationTabContent" role="tab">
                                <span class="me-sm-2 fas fa-map-marker-alt"></span><span class="d-none d-sm-inline">Ubicación y Dirección</span>
                            </a>
                            <a class="nav-link text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center" id="creditTab" data-bs-toggle="tab" data-bs-target="#creditTabContent" role="tab">
                                <span class="me-sm-2 fas fa-credit-card"></span><span class="d-none d-sm-inline">Crédito y Facturación</span>
                            </a>
                        </div>
                    </div>

                    <div class="col-sm-8">
                        <div class="tab-content py-3 ps-sm-4 h-100">
                            {{-- Tab 1: Datos Fiscales --}}
                            <div class="tab-pane fade show active" id="generalTabContent" role="tabpanel">
                                <h5 class="mb-3 text-1100">Identificación Tributaria y Clasificación</h5>
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="nit">NIT / Identificación Fiscal</label>
                                        <input class="form-control text-uppercase @error('nit') is-invalid @enderror" id="nit" name="nit" value="{{ $v('nit') }}" maxlength="25" placeholder="Ej.: 1234567-8 o C/F" />
                                        @error('nit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="nombre_comercial">Nombre Comercial</label>
                                        <input class="form-control" id="nombre_comercial" name="nombre_comercial" value="{{ $v('nombre_comercial') }}" maxlength="150" placeholder="Nombre comercial de la marca..." />
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="tipo_persona">Tipo de Persona</label>
                                        <select class="form-select" id="tipo_persona" name="tipo_persona" required>
                                            <option value="JURIDICA" @selected($v('tipo_persona') === 'JURIDICA')>Persona Jurídica (Empresa)</option>
                                            <option value="NATURAL" @selected($v('tipo_persona') === 'NATURAL')>Persona Natural (Individual)</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="id_industria">Industria / Sector</label>
                                        <select class="form-select" id="id_industria" name="id_industria">
                                            <option value="">Selecciona industria…</option>
                                            @foreach ($industrias as $i)
                                                <option value="{{ $i->id_industria }}" @selected((int) $v('id_industria') === $i->id_industria)>{{ $i->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="segmento">Segmento Comercial</label>
                                        <select class="form-select" id="segmento" name="segmento">
                                            <option value="">Selecciona segmento…</option>
                                            @foreach ($segmentos as $codigo => $nombre)
                                                <option value="{{ $codigo }}" @selected($v('segmento') === $codigo)>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="categoria">Categoría</label>
                                        <select class="form-select" id="categoria" name="categoria">
                                            <option value="">Selecciona categoría…</option>
                                            @foreach ($categorias as $cat)
                                                <option value="{{ $cat }}" @selected($v('categoria') === $cat)>Categoría {{ $cat }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 2: Ubicación y Dirección --}}
                            <div class="tab-pane fade" id="locationTabContent" role="tabpanel">
                                <h5 class="mb-3 text-1100">Ubicación y Domicilio Fiscal</h5>
                                <div class="row g-3">
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label" for="id_pais">País</label>
                                        <select class="form-select" id="id_pais" name="id_pais" required>
                                            @foreach ($paises as $p)
                                                <option value="{{ $p->id_pais }}" @selected((int) $v('id_pais') === $p->id_pais)>{{ $p->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label" for="id_division">Departamento / Estado</label>
                                        <select class="form-select" id="id_division" name="id_division" data-valor="{{ $v('id_division') }}"></select>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label" for="id_municipio">Municipio / Ciudad</label>
                                        <select class="form-select" id="id_municipio" name="id_municipio" data-valor="{{ $v('id_municipio') }}"></select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="direccion_fiscal">Dirección Fiscal Completa</label>
                                        <input class="form-control" id="direccion_fiscal" name="direccion_fiscal" value="{{ $v('direccion_fiscal') }}" maxlength="300" placeholder="Calle, avenida, zona, número de oficina/local..." />
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 3: Crédito y Facturación --}}
                            <div class="tab-pane fade" id="creditTabContent" role="tabpanel">
                                <h5 class="mb-3 text-1100">Condiciones de Crédito y Cobranza</h5>
                                <div class="row g-3">
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label" for="moneda_facturacion">Moneda de Facturación</label>
                                        <select class="form-select" id="moneda_facturacion" name="moneda_facturacion" required>
                                            @foreach ($monedas as $m)
                                                <option value="{{ $m->codigo }}" @selected($v('moneda_facturacion') === $m->codigo)>{{ $m->codigo }} — {{ $m->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label" for="dias_credito">Días de Crédito Otorgados</label>
                                        <input class="form-control @error('dias_credito') is-invalid @enderror" id="dias_credito" name="dias_credito" type="number" min="0" max="255" value="{{ $v('dias_credito') }}" required />
                                        @error('dias_credito')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label" for="limite_credito">Límite de Crédito (Monto)</label>
                                        <input class="form-control" id="limite_credito" name="limite_credito" type="number" step="0.01" min="0" value="{{ $v('limite_credito') !== null ? (float) $v('limite_credito') : '' }}" placeholder="Dejar vacío si no tiene límite" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Lateral Derecha (4 columnas) --}}
            <div class="col-12 col-xl-4">
                <div class="card mb-4 border border-200 shadow-none">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Contacto Principal</h4>
                        <div class="mb-3">
                            <label class="form-label" for="email_principal">Correo Electrónico</label>
                            <input class="form-control @error('email_principal') is-invalid @enderror" id="email_principal" name="email_principal" type="email" value="{{ $v('email_principal') }}" maxlength="150" placeholder="cliente@empresa.com" />
                            @error('email_principal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="telefono_principal">Teléfono</label>
                            <input class="form-control" id="telefono_principal" name="telefono_principal" value="{{ $v('telefono_principal') }}" maxlength="50" placeholder="+502 0000-0000" />
                        </div>
                    </div>
                </div>

                <div class="card border border-200 shadow-none">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Estado del Cliente</h4>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked($v('activo')) />
                            <label class="form-check-label fw-semi-bold" for="activo">Cliente Activo</label>
                            <p class="fs--2 text-600 mb-0 mt-1">Los clientes inactivos no pueden recibir nuevas ventas ni órdenes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var divisiones = @json($divisiones);
            var municipios = @json($municipios);
            var pais = document.getElementById('id_pais');
            var division = document.getElementById('id_division');
            var municipio = document.getElementById('id_municipio');

            function llenar(select, opciones, llave, valor) {
                select.innerHTML = '';
                select.appendChild(new Option('— Selecciona —', ''));
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
