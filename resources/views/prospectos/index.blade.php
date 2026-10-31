@extends('layouts.app', ['titulo' => 'Prospectos'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <x-page-header
        titulo="Prospectos"
        subtitulo="Empresas interesadas, su seguimiento y la conversión en clientes."
        icono="fas fa-user-plus"
        :breadcrumbs="['CRM' => null, 'Prospectos' => null]"
    >
        @if ($yo->puede('prospectos.crear'))
            <a class="btn btn-primary" href="{{ route('prospectos.create') }}">
                <span class="fas fa-plus me-2"></span>Nuevo prospecto
            </a>
        @endif
    </x-page-header>

    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="search-box w-100">
                    <input class="form-control search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Empresa, contacto o correo" maxlength="100" />
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="estado">
                    <option value="">Abiertos</option>
                    <option value="todos" @selected(($filtros['estado'] ?? '') === 'todos')>Todos</option>
                    @foreach ($estados as $k => [$n])
                        <option value="{{ $k }}" @selected(($filtros['estado'] ?? '') === $k)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="temperatura">
                    <option value="">Toda temperatura</option>
                    @foreach ($temperaturas as $k => [$n])
                        <option value="{{ $k }}" @selected(($filtros['temperatura'] ?? '') === $k)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="fuente">
                    <option value="">Toda fuente</option>
                    @foreach ($fuentes as $f)
                        <option value="{{ $f->id_fuente }}" @selected((int) ($filtros['fuente'] ?? 0) === $f->id_fuente)>{{ $f->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="asignado">
                    <option value="">Todo vendedor</option>
                    @foreach ($vendedores as $v)
                        <option value="{{ $v->id_empleado }}" @selected((int) ($filtros['asignado'] ?? 0) === $v->id_empleado)>{{ $v->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button class="btn btn-phoenix-secondary w-100" type="submit" title="Filtrar"><span class="fas fa-filter"></span></button>
                @if (array_filter($filtros))
                    <a class="btn btn-phoenix-danger px-2" href="{{ route('prospectos.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>
                @endif
            </div>
        </form>
    </div>

    <x-phoenix-table :paginador="$prospectos">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0 text-uppercase text-700">Empresa</th>
                    <th class="text-uppercase text-700">Contacto</th>
                    <th class="text-uppercase text-700">Temperatura</th>
                    <th class="text-uppercase text-700">Vendedor</th>
                    <th class="text-uppercase text-700">Último seguimiento</th>
                    <th class="pe-0 text-end text-uppercase text-700">Estado</th>
                </tr>
            </thead>
            <tbody class="list">
            @forelse ($prospectos as $p)
                @php
                    [$nombreEstado, $color] = $estados[$p->estado] ?? [$p->estado, 'secondary'];
                    [$nombreTemp, $colorTemp] = $temperaturas[$p->temperatura] ?? [$p->temperatura, 'secondary'];
                    $ultimo = $p->ultimoSeguimiento;
                    $iniciales = mb_strtoupper(mb_substr($p->nombre_empresa, 0, 2));
                @endphp
                <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                    <td class="ps-0 py-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-m me-3 d-none d-sm-inline-block">
                                <div class="avatar-name rounded-circle bg-soft-{{ $colorTemp }} text-{{ $colorTemp }}">
                                    <span>{{ $iniciales }}</span>
                                </div>
                            </div>
                            <div>
                                <a class="fw-bold text-1100 text-decoration-none" href="{{ route('prospectos.show', $p->id_prospecto) }}">{{ $p->nombre_empresa }}</a>
                                <span class="d-block fs--2 text-600">{{ $p->fuente?->nombre ?? 'Sin fuente' }}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="fw-semi-bold text-900">{{ $p->nombre_contacto }}</span>
                        @if ($p->email_contacto)
                            <span class="d-block fs--2 text-600">{{ $p->email_contacto }}</span>
                        @endif
                    </td>
                    <td><span class="badge badge-phoenix badge-phoenix-{{ $colorTemp }}">{{ $nombreTemp }}</span></td>
                    <td class="text-900">{{ $p->asignadoA?->nombre_completo ?? '—' }}</td>
                    <td class="fs--2 text-700">
                        {{ $ultimo ? $ultimo->fecha_hora?->format('d/m/Y').' · '.\App\Http\Controllers\ProspectoController::RESULTADOS[$ultimo->resultado] : 'Sin seguimiento' }}
                    </td>
                    <td class="pe-0 text-end">
                        <span class="badge badge-phoenix badge-phoenix-{{ $color }}">{{ $nombreEstado }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-700 py-5">
                        <span class="fas fa-user-plus fs-2 text-300 d-block mb-2"></span>
                        {{ array_filter($filtros) ? 'Ningún prospecto coincide con los filtros.' : 'No hay prospectos abiertos.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-phoenix-table>
@endsection
