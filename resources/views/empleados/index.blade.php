@extends('layouts.app', ['titulo' => 'Empleados'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <x-page-header
        titulo="Empleados"
        subtitulo="Personal de la empresa, su contrato vigente y salario."
        icono="fas fa-users"
        :breadcrumbs="['Recursos Humanos' => null, 'Empleados' => null]"
    >
        @if ($yo->puede('empleados.configurar'))
            <a class="btn btn-phoenix-secondary" href="{{ route('organizacion.index') }}">
                <span class="fas fa-sitemap me-2"></span>Departamentos y cargos
            </a>
        @endif
        @if ($yo->puede('empleados.exportar'))
            <a class="btn btn-phoenix-secondary" href="{{ route('empleados.exportar', request()->query()) }}">
                <span class="fas fa-file-excel me-2"></span>Exportar
            </a>
        @endif
        @if ($yo->puede('empleados.crear'))
            <a class="btn btn-primary" href="{{ route('empleados.create') }}">
                <span class="fas fa-plus me-2"></span>Nuevo empleado
            </a>
        @endif
    </x-page-header>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre, código o documento" maxlength="100" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="departamento">
                    <option value="">Todos los departamentos</option>
                    @foreach ($departamentos as $d)
                        <option value="{{ $d->id_depto_org }}" @selected((int) ($filtros['departamento'] ?? 0) === $d->id_depto_org)>{{ $d->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="estado">
                    <option value="">Vigentes (sin bajas)</option>
                    @foreach ($estados as $codigo => [$nombre])
                        <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-phoenix-secondary w-100" type="submit">Filtrar</button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-3" href="{{ route('empleados.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$empleados">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Empleado</th>
                    <th class="text-uppercase text-700">Cargo</th>
                    <th class="text-uppercase text-700">Departamento</th>
                    <th class="text-uppercase text-700">Ingreso</th>
                    <th class="text-end text-uppercase text-700">Salario</th>
                    <th class="pe-0 text-end text-uppercase text-700">Estado</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($empleados as $e)
                @php
                    [$nombreEstado, $color] = $estados[$e->estado] ?? [$e->estado, 'secondary'];
                    $iniciales = mb_strtoupper(mb_substr($e->nombres ?: $e->nombre_completo, 0, 1).mb_substr($e->apellidos ?: '', 0, 1));
                @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static {{ $e->estado === 'BAJA' ? 'text-500' : '' }}">
                    <td class="ps-0 py-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-m me-3 d-none d-sm-inline-block">
                                <div class="avatar-name rounded-circle {{ $e->estado === 'BAJA' ? 'bg-200 text-600' : 'bg-soft-primary text-primary' }}">
                                    <span>{{ $iniciales }}</span>
                                </div>
                            </div>
                            <div>
                                <a class="fw-bold text-1100 text-decoration-none" href="{{ route('empleados.show', $e->id_empleado) }}">{{ $e->nombre_completo }}</a>
                                @if ($e->es_rotativo)<span class="badge badge-phoenix badge-phoenix-info fs--2 ms-1">Rotativo</span>@endif
                                <span class="d-block fs--2 text-600">{{ $e->codigo_empleado }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="fw-semi-bold text-900">{{ $e->cargo?->nombre ?? '—' }}</td>
                    <td class="text-800">{{ $e->departamento?->nombre ?? '—' }}</td>
                    <td class="text-nowrap text-800">{{ $e->fecha_ingreso?->format('d/m/Y') }}</td>
                    <td class="text-end text-nowrap fw-bold text-900">
                        {{ $e->contratoVigente ? $e->contratoVigente->moneda.' '.number_format((float) $e->contratoVigente->salario_base, 2) : ($e->es_rotativo && $e->tarifa_dia ? number_format((float) $e->tarifa_dia, 2).' / día' : 'Sin contrato') }}
                    </td>
                    <td class="pe-0 text-end">
                        <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-700 py-5">
                        <span class="fas fa-users fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ningún empleado coincide con los filtros.' : 'Todavía no hay empleados.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
