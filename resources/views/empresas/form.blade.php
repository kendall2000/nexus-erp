@extends('layouts.app', ['titulo' => $empresa->exists ? 'Editar empresa' : 'Nueva empresa'])

@section('contenido')
    <x-page-header
        :titulo="$empresa->exists ? 'Editar empresa' : 'Nueva empresa'"
        icono="fas fa-city"
        :subtitulo="$empresa->exists ? 'Datos legales, fiscales y de contacto de la empresa.' : 'Se crea con su casa matriz y con los catálogos que copies de otra empresa.'"
        :migas="[
            ['label' => 'Empresas', 'url' => route('empresas.index')],
            ['label' => $empresa->exists ? $empresa->nombre_comercial : 'Nueva'],
        ]"
    />

    <form method="POST" action="{{ $empresa->exists ? route('empresas.update', $empresa->id_empresa) : route('empresas.store') }}" id="formEmpresa" enctype="multipart/form-data" style="max-width: 56rem">
        @csrf
        @if ($empresa->exists)
            @method('PUT')
        @endif
        <script type="application/json" id="ubicacionDatos">@json(['divisiones' => $divisiones, 'municipios' => $municipios])</script>

        <div class="card border border-200 shadow-none mb-4">
            <div class="card-body">
                <h5 class="mb-3">Datos legales</h5>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="nombre_comercial">Nombre comercial</label>
                        <input class="form-control" id="nombre_comercial" name="nombre_comercial" value="{{ old('nombre_comercial', $empresa->nombre_comercial) }}" required maxlength="100" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="nombre_legal">Razón social</label>
                        <input class="form-control" id="nombre_legal" name="nombre_legal" value="{{ old('nombre_legal', $empresa->nombre_legal) }}" required maxlength="200" />
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="nit">NIT</label>
                        <input class="form-control" id="nit" name="nit" value="{{ old('nit', $empresa->nit) }}" required maxlength="20" />
                        <div class="form-text">Sin guiones ni espacios.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="fecha_fundacion">Fecha de fundación</label>
                        <input class="form-control" id="fecha_fundacion" name="fecha_fundacion" type="date" value="{{ old('fecha_fundacion', $empresa->fecha_fundacion?->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card border border-200 shadow-none mb-4">
            <div class="card-body">
                <h5 class="mb-3">Ubicación y contacto</h5>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="id_pais">País</label>
                        <select data-choices="data-choices" class="form-select" id="id_pais" name="id_pais" required>
                            <option value="">—</option>
                            @foreach ($paises as $p)
                                <option value="{{ $p->id_pais }}" data-moneda="{{ $p->moneda_defecto }}" @selected((int) old('id_pais', $empresa->id_pais) === $p->id_pais)>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_division">Departamento</label>
                        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_division" name="id_division" data-valor="{{ old('id_division', $division) }}"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_municipio">Municipio</label>
                        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_municipio" name="id_municipio" data-valor="{{ old('id_municipio', $empresa->id_municipio) }}"></select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="direccion">Dirección</label>
                    <textarea class="form-control" id="direccion" name="direccion" rows="2" maxlength="300">{{ old('direccion', $empresa->direccion) }}</textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input class="form-control" id="telefono" name="telefono" value="{{ old('telefono', $empresa->telefono) }}" maxlength="20" />
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="email_corporativo">Correo corporativo</label>
                        <input class="form-control" id="email_corporativo" name="email_corporativo" type="email" value="{{ old('email_corporativo', $empresa->email_corporativo) }}" maxlength="150" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card border border-200 shadow-none mb-4">
            <div class="card-body">
                <h5 class="mb-3">Moneda e impuestos</h5>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="moneda_base">Moneda base</label>
                        @if ($monedaFija)
                            <input type="hidden" name="moneda_base" value="{{ $empresa->moneda_base }}" />
                            <input class="form-control" id="moneda_base" value="{{ $empresa->moneda_base }}" disabled />
                            <div class="form-text">No se puede cambiar: la empresa tiene {{ $monedaFija }}.</div>
                        @else
                            <select class="form-select" id="moneda_base" name="moneda_base" required>
                                @foreach ($monedas as $m)
                                    <option value="{{ $m->codigo }}" @selected(old('moneda_base', $empresa->moneda_base) === $m->codigo)>{{ $m->codigo }} · {{ $m->nombre }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="tasa_iva">Tasa de IVA (%)</label>
                        <input class="form-control" id="tasa_iva" name="tasa_iva" type="number" step="0.01" min="0" max="50" value="{{ old('tasa_iva', (float) $empresa->tasa_iva) }}" required />
                    </div>
                    <div class="col-md-5">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" id="iva_incluido_en_precio" name="iva_incluido_en_precio" type="checkbox" value="1" @checked(old('iva_incluido_en_precio', $empresa->iva_incluido_en_precio)) />
                            <label class="form-check-label" for="iva_incluido_en_precio">Los precios ya incluyen IVA</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border border-200 shadow-none mb-4">
            <div class="card-body">
                <h5 class="mb-1">Logo y datos para documentos</h5>
                <p class="text-700 fs--1 mb-3">
                    Salen en las facturas, órdenes de compra, constancias y demás documentos de esta empresa.
                    Si no tiene logo, se usa el del sistema (Configuración del sistema).
                </p>
                <div class="row g-3 mb-3 align-items-center">
                    <div class="col-md-3 text-center">
                        @if ($empresa->logo_url)
                            <img src="{{ $empresa->logo_url }}" alt="Logo de {{ $empresa->nombre_comercial }}" class="img-fluid border border-200 rounded-2 p-2" style="max-height: 80px" />
                        @else
                            <div class="border border-200 border-dashed rounded-2 p-3 text-600 fs--1">Sin logo propio</div>
                        @endif
                    </div>
                    <div class="col-md-9">
                        <label class="form-label" for="logo">{{ $empresa->logo_url ? 'Cambiar logo' : 'Logo' }}</label>
                        <input class="form-control" id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp" @disabled(! $contaboListo) />
                        <div class="form-text">
                            @if ($contaboListo)
                                JPG, PNG o WebP de hasta 4 MB; de preferencia horizontal y con fondo transparente.
                            @else
                                {{ \App\Support\Archivos::problema() }}
                            @endif
                        </div>
                        @if ($empresa->logo_url)
                            <div class="form-check mt-2 mb-0">
                                <input class="form-check-input" id="quitar_logo" name="quitar_logo" type="checkbox" value="1" />
                                <label class="form-check-label" for="quitar_logo">Quitar el logo (se usará el del sistema)</label>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="regimen_fiscal">Régimen fiscal</label>
                        <input class="form-control" id="regimen_fiscal" name="regimen_fiscal" value="{{ old('regimen_fiscal', $empresa->regimen_fiscal) }}" maxlength="150"
                               placeholder="Ej.: Sujeto a pagos trimestrales ISR" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="sitio_web">Sitio web</label>
                        <input class="form-control" id="sitio_web" name="sitio_web" type="url" value="{{ old('sitio_web', $empresa->sitio_web) }}" maxlength="150" placeholder="https://" />
                    </div>
                </div>
                <div>
                    <label class="form-label" for="leyenda_factura">Leyenda al pie de las facturas</label>
                    <textarea class="form-control" id="leyenda_factura" name="leyenda_factura" rows="2" maxlength="500"
                              placeholder="Ej.: Sujeto a retención definitiva. Gracias por su preferencia.">{{ old('leyenda_factura', $empresa->leyenda_factura) }}</textarea>
                </div>
            </div>
        </div>

        @unless ($empresa->exists)
            <div class="card border border-200 shadow-none mb-4">
                <div class="card-body">
                    <h5 class="mb-1">Arranque</h5>
                    <p class="text-700 fs--1 mb-3">
                        Se crea su casa matriz con la ubicación de arriba. Los catálogos se copian de la empresa modelo y luego se ajustan
                        en la empresa nueva. Nunca se copian clientes, empleados, documentos ni series de facturación.
                    </p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="casa_matriz">Nombre de la casa matriz</label>
                            <input class="form-control" id="casa_matriz" name="casa_matriz" value="{{ old('casa_matriz', 'Casa matriz') }}" required maxlength="150" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="id_empresa_modelo">Copiar catálogos de</label>
                            <select class="form-select" id="id_empresa_modelo" name="id_empresa_modelo">
                                @if ($hayPlantilla)
                                    <option value="{{ \App\Support\CatalogosEmpresa::PLANTILLA }}" @selected(old('id_empresa_modelo') === \App\Support\CatalogosEmpresa::PLANTILLA)>Plantilla del sistema</option>
                                @endif
                                @foreach ($modelos as $m)
                                    <option value="{{ $m->id_empresa }}" @selected((int) old('id_empresa_modelo', $modelo) === $m->id_empresa)>{{ $m->nombre_comercial }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2">
                        @foreach ($grupos as $clave => [$nombre, $ayuda])
                            <div class="col-md-6">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" id="cat_{{ $clave }}" name="catalogos[]" type="checkbox" value="{{ $clave }}"
                                           @checked(in_array($clave, old('catalogos', old('_token') ? [] : array_keys($grupos)), true)) />
                                    <label class="form-check-label" for="cat_{{ $clave }}">{{ $nombre }}</label>
                                    <div class="form-text mt-0">{{ $ayuda }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endunless

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit"><span class="fas fa-save me-2"></span>{{ $empresa->exists ? 'Guardar' : 'Crear empresa' }}</button>
            <a class="btn btn-phoenix-secondary" href="{{ route('empresas.index') }}">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ \App\Support\Sistema::recurso('assets/js/ubicacion.js') }}"></script>
    <script src="{{ \App\Support\Sistema::recurso('assets/js/empresas.js') }}"></script>
@endpush
