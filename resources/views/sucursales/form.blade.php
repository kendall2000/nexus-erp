@extends('layouts.app', ['titulo' => $sucursal->exists ? 'Editar sede' : 'Nueva sede'])

@section('contenido')
    <x-page-header
        :titulo="$sucursal->exists ? 'Editar '.mb_strtolower($tipos[$sucursal->tipo] ?? 'sede') : 'Nueva sucursal o agencia'"
        icono="fas fa-store-alt"
        subtitulo="Sucursales y agencias de la empresa: ubicación, código de establecimiento SAT y casa matriz."
        :migas="[
            ['label' => 'Sucursales', 'url' => route('sucursales.index')],
            ['label' => $sucursal->exists ? $sucursal->nombre : 'Nueva'],
        ]"
    />

    <div class="card border border-200 shadow-none" style="max-width: 48rem">
        <div class="card-body">
            <form method="POST" action="{{ $sucursal->exists ? route('sucursales.update', $sucursal->id_sucursal) : route('sucursales.store') }}">
                @csrf
                @if ($sucursal->exists)
                    @method('PUT')
                @endif
                <script type="application/json" id="ubicacionDatos">@json(['divisiones' => $divisiones, 'municipios' => $municipios])</script>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            @foreach ($tipos as $codigo => $nombre)
                                <option value="{{ $codigo }}" @selected(old('tipo', $sucursal->tipo ?? 'SUCURSAL') === $codigo)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $sucursal->nombre) }}" required maxlength="150" />
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-8" id="bloquePadre">
                        <label class="form-label" for="id_sucursal_padre">Depende de la sucursal</label>
                        <select class="form-select" id="id_sucursal_padre" name="id_sucursal_padre">
                            <option value="">—</option>
                            @foreach ($padres as $p)
                                <option value="{{ $p->id_sucursal }}" @selected((int) old('id_sucursal_padre', $sucursal->id_sucursal_padre) === $p->id_sucursal)>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Una agencia es una sede más pequeña que depende de una sucursal.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="codigo_establecimiento">Código de establecimiento SAT</label>
                        <input class="form-control" id="codigo_establecimiento" name="codigo_establecimiento" type="number" min="1" max="9999"
                               value="{{ old('codigo_establecimiento', $sucursal->codigo_establecimiento) }}" />
                        <div class="form-text">El número con que la SAT registró esta sede (FEL).</div>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="id_pais">País</label>
                        <select data-choices="data-choices" class="form-select" id="id_pais" name="id_pais">
                            <option value="">—</option>
                            @foreach ($paises as $p)
                                <option value="{{ $p->id_pais }}" @selected((int) old('id_pais', $sucursal->id_pais) === $p->id_pais)>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_division">Departamento</label>
                        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_division" name="id_division" data-valor="{{ old('id_division', $sucursal->id_division) }}"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_municipio">Municipio</label>
                        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_municipio" name="id_municipio" data-valor="{{ old('id_municipio', $sucursal->id_municipio) }}"></select>
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
                    <div class="form-text mt-0">Solo puede haber una y debe ser una sucursal: al marcar esta, la anterior deja de serlo.</div>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $sucursal->activo)) />
                    <label class="form-check-label" for="activo">Activa</label>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><span class="fas fa-save me-2"></span>Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('sucursales.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ \App\Support\Sistema::recurso('assets/js/ubicacion.js') }}"></script>
    <script src="{{ \App\Support\Sistema::recurso('assets/js/sucursales.js') }}"></script>
@endpush
