@extends('layouts.app', ['titulo' => 'Departamentos y cargos'])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('empleados.index') }}">Empleados</a></li>
            <li class="breadcrumb-item active">Departamentos y cargos</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">Departamentos y cargos</h2>

    <div class="row g-4">
        {{-- Departamentos --}}
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Departamentos</h5>
                    @if ($errors->departamento->any())<div class="alert alert-soft-danger fs--1">{{ $errors->departamento->first() }}</div>@endif
                    @foreach ($departamentos as $d)
                        <details class="border-bottom border-200 py-2 fs--1" @if ($errors->departamento->any() && (int) old('id') === $d->id_depto_org) open @endif>
                            <summary class="d-flex justify-content-between" style="cursor: pointer">
                                <span class="{{ $d->activo ? '' : 'text-500' }}"><span class="fw-semi-bold">{{ $d->nombre }}</span>
                                    <span class="text-600">{{ $d->codigo }}{{ $d->padre ? ' · en '.$d->padre->nombre : '' }}</span></span>
                                <span class="text-700 text-nowrap">{{ $d->empleados_count }} emp. · {{ $d->cargos_count }} cargos</span>
                            </summary>
                            @include('organizacion.departamento', ['d' => $d])
                        </details>
                    @endforeach
                    <details class="mt-3" @if ($errors->departamento->any() && ! old('id')) open @endif>
                        <summary class="btn btn-phoenix-primary btn-sm"><span class="fas fa-plus me-1"></span>Nuevo departamento</summary>
                        @include('organizacion.departamento', ['d' => new \App\Models\RRHH\DepartamentoOrg(['activo' => true])])
                    </details>
                </div>
            </div>
        </div>

        {{-- Cargos --}}
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Cargos</h5>
                    @if ($errors->cargo->any())<div class="alert alert-soft-danger fs--1">{{ $errors->cargo->first() }}</div>@endif
                    @foreach ($cargos as $c)
                        <details class="border-bottom border-200 py-2 fs--1" @if ($errors->cargo->any() && (int) old('id') === $c->id_cargo) open @endif>
                            <summary class="d-flex justify-content-between" style="cursor: pointer">
                                <span class="{{ $c->activo ? '' : 'text-500' }}"><span class="fw-semi-bold">{{ $c->nombre }}</span>
                                    <span class="text-600">{{ $niveles[$c->nivel_jerarquico] ?? '' }}{{ $c->departamento ? ' · '.$c->departamento->nombre : '' }}</span></span>
                                <span class="text-700 text-nowrap">
                                    @if ($c->salario_min || $c->salario_max){{ $c->moneda }} {{ number_format((float) $c->salario_min, 0) }}–{{ number_format((float) $c->salario_max, 0) }} · @endif{{ $c->empleados_count }} emp.
                                </span>
                            </summary>
                            @include('organizacion.cargo', ['c' => $c])
                        </details>
                    @endforeach
                    <details class="mt-3" @if ($errors->cargo->any() && ! old('id')) open @endif>
                        <summary class="btn btn-phoenix-primary btn-sm"><span class="fas fa-plus me-1"></span>Nuevo cargo</summary>
                        @include('organizacion.cargo', ['c' => new \App\Models\RRHH\Cargo(['activo' => true, 'nivel_jerarquico' => 1, 'moneda' => 'GTQ'])])
                    </details>
                </div>
            </div>
        </div>
    </div>
@endsection
