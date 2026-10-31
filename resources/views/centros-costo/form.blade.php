@extends('layouts.app', ['titulo' => $centro->exists ? 'Editar centro de costo' : 'Nuevo centro de costo'])

@section('contenido')
    <x-page-header
        :titulo="$centro->exists ? 'Editar centro de costo' : 'Nuevo centro de costo'"
        icono="fas fa-sitemap"
        subtitulo="Agrupa ingresos, costos y gastos por unidad operativa o departamento."
        :migas="[
            ['label' => 'Centros de costo', 'url' => route('centros-costo.index')],
            ['label' => $centro->exists ? $centro->codigo : 'Nuevo'],
        ]"
    />

    <div class="card border border-200 shadow-none" style="max-width: 40rem">
        <div class="card-body">
            <form method="POST" action="{{ $centro->exists ? route('centros-costo.update', $centro->id_centro) : route('centros-costo.store') }}">
                @csrf
                @if ($centro->exists)
                    @method('PUT')
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="codigo">Código</label>
                        <input class="form-control text-uppercase" id="codigo" name="codigo" value="{{ old('codigo', $centro->codigo) }}" required maxlength="20" placeholder="Ej.: ADM-01" />
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $centro->nombre) }}" required maxlength="150" placeholder="Ej.: Administración central" />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="2" maxlength="300">{{ old('descripcion', $centro->descripcion) }}</textarea>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $centro->activo)) />
                    <label class="form-check-label" for="activo">Activo</label>
                    <div class="form-text mt-0">Un centro inactivo no aparece para nuevas órdenes ni presupuestos.</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><span class="fas fa-save me-2"></span>Guardar</button>
                    <a class="btn btn-phoenix-secondary" href="{{ route('centros-costo.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
