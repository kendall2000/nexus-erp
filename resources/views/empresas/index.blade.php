@extends('layouts.app', ['titulo' => 'Empresas'])

@section('contenido')
    <x-page-header
        titulo="Empresas"
        icono="fas fa-city"
        :breadcrumbs="['Sistema' => null, 'Empresas' => null]"
    >
        <a class="btn btn-phoenix-secondary" href="{{ route('sucursales.index') }}">
            <span class="fas fa-building me-2"></span>Sucursales
        </a>
        <a class="btn btn-primary" href="{{ route('empresas.create') }}">
            <span class="fas fa-plus me-2"></span>Nueva empresa
        </a>
    </x-page-header>

    <p class="text-700 fw-semi-bold mb-4">
        Empresas del grupo. Cada una lleva su propia operación, catálogos y sucursales; las sucursales de cada empresa se administran
        con esa empresa elegida en la barra superior. Las empresas no se eliminan: se desactivan.
    </p>

    <x-phoenix-table>
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Empresa</th>
                    <th class="text-uppercase text-700">NIT</th>
                    <th class="text-uppercase text-700">País</th>
                    <th class="text-uppercase text-700">Moneda e IVA</th>
                    <th class="text-end text-uppercase text-700">Sucursales</th>
                    <th class="text-end text-uppercase text-700">Usuarios</th>
                    <th class="text-uppercase text-700">Estado</th>
                    <th class="pe-0 text-end text-uppercase text-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($empresas as $e)
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2">
                        <span class="fw-bold text-1000">{{ $e->nombre_comercial }}</span>
                        @if ($e->id_empresa === $principal)<span class="badge badge-phoenix badge-phoenix-primary ms-1">Tu empresa principal</span>@endif
                        @if ($e->id_empresa === $activa && $activa !== $principal)<span class="badge badge-phoenix badge-phoenix-info ms-1">Trabajando en ella</span>@endif
                        <p class="text-600 fs--2 mb-0 line-clamp-1">{{ $e->nombre_legal }}</p>
                    </td>
                    <td class="text-800">{{ $e->nit }}</td>
                    <td class="text-800">{{ $e->pais?->nombre ?? '—' }}</td>
                    <td class="text-800">
                        {{ $e->moneda_base }} · IVA {{ rtrim(rtrim(number_format((float) $e->tasa_iva, 2), '0'), '.') }}%
                        <div class="text-600 fs--2">{{ $e->iva_incluido_en_precio ? 'Precios con IVA incluido' : 'IVA se suma al precio' }}</div>
                    </td>
                    <td class="text-end fw-bold text-900">{{ $e->sucursales_count }}</td>
                    <td class="text-end fw-bold text-900" title="Usuarios cuya empresa principal es esta">{{ $e->usuarios_count }}</td>
                    <td>
                        @if ($e->activo)
                            <span class="badge badge-phoenix badge-phoenix-success">Activa</span>
                        @else
                            <span class="badge badge-phoenix badge-phoenix-danger">Inactiva</span>
                        @endif
                    </td>
                    <td class="pe-0 text-end text-nowrap">
                        <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('empresas.edit', $e->id_empresa) }}">Editar</a>
                        @unless ($e->id_empresa === $principal)
                            <form method="POST" action="{{ route('empresas.estado', $e->id_empresa) }}" class="d-inline"
                                  data-confirmar="{{ $e->activo ? '¿Desactivar '.$e->nombre_comercial.'? Nadie podrá trabajar en ella hasta que se vuelva a activar.' : '' }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-phoenix-{{ $e->activo ? 'danger' : 'success' }} btn-sm" type="submit">{{ $e->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-700 py-5">No hay empresas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection

@push('scripts')
    <script src="{{ \App\Support\Sistema::recurso('assets/js/empresas.js') }}"></script>
@endpush
