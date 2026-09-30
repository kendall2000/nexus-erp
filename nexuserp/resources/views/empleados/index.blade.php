@extends('layouts.app', ['titulo' => 'Empleados'])

@section('contenido')
    @php $yo = auth()->user(); @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Empleados</h2>
            <p class="text-700 fw-semi-bold mb-0">Personal de la empresa, su contrato vigente y salario.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($yo->puede('empleados.configurar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('organizacion.index') }}"><span class="fas fa-sitemap me-2"></span>Departamentos y cargos</a>
            @endif
            @if ($yo->puede('empleados.exportar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('empleados.exportar', request()->query()) }}"><span class="fas fa-file-excel me-2"></span>Exportar</a>
            @endif
            @if ($yo->puede('empleados.crear'))
                <a class="btn btn-primary" href="{{ route('empleados.create') }}"><span class="fas fa-plus me-2"></span>Nuevo empleado</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-5"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre, código o documento" maxlength="100" /></div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="departamento">
                        <option value="">Todos los departamentos</option>
                        @foreach ($departamentos as $d)<option value="{{ $d->id_depto_org }}" @selected((int) ($filtros['departamento'] ?? 0) === $d->id_depto_org)>{{ $d->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Vigentes (sin bajas)</option>
                        @foreach ($estados as $codigo => [$nombre])<option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('empleados.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Empleado</th><th>Cargo</th><th>Departamento</th><th>Ingreso</th><th class="text-end">Salario</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($empleados as $e)
                        @php [$nombreEstado, $color] = $estados[$e->estado] ?? [$e->estado, 'secondary']; @endphp
                        <tr class="{{ $e->estado === 'BAJA' ? 'text-500' : '' }}">
                            <td>
                                <a class="fw-semi-bold" href="{{ route('empleados.show', $e->id_empleado) }}">{{ $e->nombre_completo }}</a>
                                <span class="d-block fs--2 text-600">{{ $e->codigo_empleado }}</span>
                            </td>
                            <td>{{ $e->cargo?->nombre ?? '—' }}</td>
                            <td>{{ $e->departamento?->nombre ?? '—' }}</td>
                            <td class="text-nowrap">{{ $e->fecha_ingreso?->format('d/m/Y') }}</td>
                            <td class="text-end text-nowrap">{{ $e->contratoVigente ? $e->contratoVigente->moneda.' '.number_format((float) $e->contratoVigente->salario_base, 2) : 'Sin contrato' }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún empleado coincide con los filtros.' : 'Todavía no hay empleados.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $empleados->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
