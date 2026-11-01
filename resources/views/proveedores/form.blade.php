@extends('layouts.app', ['titulo' => $proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $proveedor->{$campo}); @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
            <li class="breadcrumb-item active">{{ $proveedor->exists ? 'Editar' : 'Nuevo' }}</li>
        </ol>
    </nav>

    <form method="POST" action="{{ $proveedor->exists ? route('proveedores.update', $proveedor->id_proveedor) : route('proveedores.store') }}" class="mb-9">
        @csrf
        @if ($proveedor->exists)
            @method('PUT')
        @endif

        {{-- Encabezado idéntico a apps/e-commerce/admin/add-product.html --}}
        <div class="row g-3 flex-between-end mb-5">
            <div class="col-auto">
                <h2 class="mb-2">{{ $proveedor->exists ? 'Editar Proveedor: '.$proveedor->razon_social : 'Agregar un nuevo proveedor' }}</h2>
                <h5 class="text-700 fw-semi-bold">Configuración de datos fiscales, contacto directo y condiciones de pago</h5>
            </div>
            <div class="col-auto">
                <a class="btn btn-phoenix-secondary me-2 mb-2 mb-sm-0" href="{{ route('proveedores.index') }}">Descartar</a>
                <button class="btn btn-primary mb-2 mb-sm-0" type="submit">
                    <span class="fas fa-save me-2"></span>{{ $proveedor->exists ? 'Actualizar proveedor' : 'Guardar proveedor' }}
                </button>
            </div>
        </div>

        <div class="row g-5">
            {{-- Columna Principal Izquierda (8 columnas) --}}
            <div class="col-12 col-xl-8">
                <h4 class="mb-3">Razón Social del Proveedor</h4>
                <input class="form-control mb-5" type="text" id="razon_social" name="razon_social" value="{{ $v('razon_social') }}" placeholder="Nombre o Razón Social oficial..." required maxlength="250" />

                {{-- Pestañas verticales idénticas a Phoenix --}}
                <h4 class="mb-3">Información y Condiciones Comerciales</h4>
                <div class="row g-0 border-top border-bottom border-300 mb-5">
                    <div class="col-sm-4">
                        <div class="nav flex-sm-column border-bottom border-bottom-sm-0 border-end-sm border-300 fs--1 vertical-tab h-100 justify-content-between" role="tablist" aria-orientation="vertical">
                            <a class="nav-link border-end border-end-sm-0 border-bottom-sm border-300 text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center active" id="generalTab" data-bs-toggle="tab" data-bs-target="#generalTabContent" role="tab">
                                <span class="me-sm-2 fas fa-building"></span><span class="d-none d-sm-inline">Datos Fiscales</span>
                            </a>
                            <a class="nav-link border-end border-end-sm-0 border-bottom-sm border-300 text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center" id="contactTab" data-bs-toggle="tab" data-bs-target="#contactTabContent" role="tab">
                                <span class="me-sm-2 fas fa-address-book"></span><span class="d-none d-sm-inline">Contacto y Ubicación</span>
                            </a>
                            <a class="nav-link text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center" id="termsTab" data-bs-toggle="tab" data-bs-target="#termsTabContent" role="tab">
                                <span class="me-sm-2 fas fa-credit-card"></span><span class="d-none d-sm-inline">Condiciones de Pago</span>
                            </a>
                        </div>
                    </div>

                    <div class="col-sm-8">
                        <div class="tab-content py-3 ps-sm-4 h-100">
                            {{-- Tab 1: Datos Fiscales --}}
                            <div class="tab-pane fade show active" id="generalTabContent" role="tabpanel">
                                <h5 class="mb-3 text-1100">Datos Tributarios y Clasificación</h5>
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="nit">NIT / Identificación Fiscal</label>
                                        <input class="form-control text-uppercase" id="nit" name="nit" value="{{ $v('nit') }}" maxlength="20" placeholder="Ej: 1234567-8 o C/F" />
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="nombre_comercial">Nombre Comercial</label>
                                        <input class="form-control" id="nombre_comercial" name="nombre_comercial" value="{{ $v('nombre_comercial') }}" maxlength="150" placeholder="Nombre comercial de la marca..." />
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="tipo_proveedor">Tipo de Proveedor</label>
                                        <select class="form-select" id="tipo_proveedor" name="tipo_proveedor" required>
                                            @foreach ($tipos as $codigo => $nombre)
                                                <option value="{{ $codigo }}" @selected($v('tipo_proveedor') === $codigo)>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="id_pais">País de Origen</label>
                                        <select class="form-select" id="id_pais" name="id_pais" required>
                                            <option value="">Selecciona un país…</option>
                                            @foreach ($paises as $p)
                                                <option value="{{ $p->id_pais }}" @selected((int) $v('id_pais') === $p->id_pais)>{{ $p->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 2: Contacto y Ubicación --}}
                            <div class="tab-pane fade" id="contactTabContent" role="tabpanel">
                                <h5 class="mb-3 text-1100">Datos de Contacto Comercial y Entrega</h5>
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="contacto">Persona de Contacto</label>
                                        <input class="form-control" id="contacto" name="contacto" value="{{ $v('contacto') }}" maxlength="200" placeholder="Ejecutivo de ventas o enlace..." />
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="email">Correo Electrónico</label>
                                        <input class="form-control" id="email" name="email" type="email" value="{{ $v('email') }}" maxlength="150" placeholder="ventas@proveedor.com" />
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="telefono">Teléfono / PBX</label>
                                        <input class="form-control" id="telefono" name="telefono" value="{{ $v('telefono') }}" maxlength="20" placeholder="+502 0000-0000" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="direccion">Dirección Física</label>
                                        <input class="form-control" id="direccion" name="direccion" value="{{ $v('direccion') }}" maxlength="300" placeholder="Calle, avenida, zona o parque industrial..." />
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 3: Condiciones de Pago --}}
                            <div class="tab-pane fade" id="termsTabContent" role="tabpanel">
                                <h5 class="mb-3 text-1100">Condiciones de Crédito y Moneda</h5>
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="dias_credito">Días de Crédito Acordados</label>
                                        <input class="form-control" id="dias_credito" name="dias_credito" type="number" min="0" max="255" value="{{ $v('dias_credito') }}" required />
                                        <div class="form-text">0 = Pago de contado.</div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="moneda_pago">Moneda de Pago</label>
                                        <select class="form-select" id="moneda_pago" name="moneda_pago" required>
                                            @foreach ($monedas as $m)
                                                <option value="{{ $m->codigo }}" @selected($v('moneda_pago') === $m->codigo)>{{ $m->codigo }} — {{ $m->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Lateral Derecha (4 columnas) --}}
            <div class="col-12 col-xl-4">
                <div class="card border border-200 shadow-none">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Estado del Proveedor</h4>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked($v('activo')) />
                            <label class="form-check-label fw-semi-bold" for="activo">Proveedor Activo</label>
                            <p class="fs--2 text-600 mb-0 mt-1">Los proveedores activos están habilitados para generar órdenes de compra.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
