@extends('layouts.app', ['titulo' => 'Prospectos'])

@section('contenido')
    @php $yo = auth()->user(); @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Prospectos</h2>
            <p class="text-700 fw-semi-bold mb-0">Empresas interesadas, su seguimiento y la conversión en clientes.</p>
        </div>
        @if ($yo->puede('prospectos.crear'))
            <a class="btn btn-primary" href="{{ route('prospectos.create') }}"><span class="fas fa-plus me-2"></span>Nuevo prospecto</a>
        @endif
    </div>
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><input class="form-control form-control-sm" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Empresa, contacto o correo" maxlength="100" /></div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="estado">
                        <option value="">Abiertos</option>
                        <option value="todos" @selected(($filtros['estado'] ?? '') === 'todos')>Todos</option>
                        @foreach ($estados as $k => [$n])<option value="{{ $k }}" @selected(($filtros['estado'] ?? '') === $k)>{{ $n }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="temperatura"><option value="">Toda temperatura</option>@foreach ($temperaturas as $k => [$n])<option value="{{ $k }}" @selected(($filtros['temperatura'] ?? '') === $k)>{{ $n }}</option>@endforeach</select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="fuente"><option value="">Toda fuente</option>@foreach ($fuentes as $f)<option value="{{ $f->id_fuente }}" @selected((int) ($filtros['fuente'] ?? 0) === $f->id_fuente)>{{ $f->nombre }}</option>@endforeach</select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="asignado"><option value="">Todo vendedor</option>@foreach ($vendedores as $v)<option value="{{ $v->id_empleado }}" @selected((int) ($filtros['asignado'] ?? 0) === $v->id_empleado)>{{ $v->nombre_completo }}</option>@endforeach</select>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit" title="Filtrar"><span class="fas fa-filter"></span></button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('prospectos.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead><tr><th>Empresa</th><th>Contacto</th><th>Temperatura</th><th>Vendedor</th><th>Último seguimiento</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($prospectos as $p)
                        @php
                            [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary'];
                            [$nombreTemp, $colorTemp] = $temperaturas[$p->temperatura] ?? [$p->temperatura, 'secondary'];
                            $ultimo = $p->ultimoSeguimiento;
                        @endphp
                        <tr>
                            <td><a class="fw-semi-bold" href="{{ route('prospectos.show', $p->id_prospecto) }}">{{ $p->nombre_empresa }}</a><span class="d-block fs--2 text-600">{{ $p->fuente?->nombre }}</span></td>
                            <td>{{ $p->nombre_contacto }}<span class="d-block fs--2 text-600">{{ $p->email_contacto }}</span></td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $colorTemp }}">{{ $nombreTemp }}</span></td>
                            <td>{{ $p->asignadoA?->nombre_completo ?? '—' }}</td>
                            <td class="fs--2">{{ $ultimo ? $ultimo->fecha_hora?->format('d/m/Y').' · '.\App\Http\Controllers\ProspectoController::RESULTADOS[$ultimo->resultado] : 'Sin seguimiento' }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún prospecto coincide con los filtros.' : 'No hay prospectos abiertos.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $prospectos->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
