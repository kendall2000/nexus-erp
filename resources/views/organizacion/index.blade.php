@extends('layouts.app', ['titulo' => 'Departamentos y cargos'])

@section('contenido')
    <x-page-header
        title="Departamentos y cargos"
        subtitle="Estructura organizacional de la empresa y niveles salariales."
        :breadcrumbs="[
            ['label' => 'Empleados', 'url' => route('empleados.index')],
            ['label' => 'Departamentos y cargos']
        ]"
    >
        <a class="btn btn-phoenix-secondary" href="{{ route('empleados.index') }}">Volver a empleados</a>
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-stat-card etiqueta="Departamentos activos" :valor="$kpis['departamentos']" icono="fas fa-building" color="primary" :subtexto="$departamentos->count().' en total'" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card etiqueta="Cargos y puestos" :valor="$kpis['cargos']" icono="fas fa-briefcase" color="info" :subtexto="$cargos->count().' definidos'" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card etiqueta="Sin rango salarial" :valor="$kpis['sin_rango']" icono="fas fa-coins" color="warning" :subtexto="$kpis['sin_rango'] > 0 ? 'Conviene definirlos' : 'Todos tienen rango'" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card etiqueta="Salarios fuera de rango" :valor="$kpis['fuera_rango']" icono="fas fa-exclamation-triangle" color="danger" :alerta="$kpis['fuera_rango'] > 0" :subtexto="$kpis['fuera_rango'] > 0 ? 'Revisar contratos' : 'Todos en rango'" />
        </div>
    </div>

    @if ($fueraDeRango->isNotEmpty())
        <div class="alert alert-soft-warning mb-4 fs--1">
            <div class="d-flex align-items-center mb-1">
                <span class="fas fa-exclamation-triangle me-2"></span>
                <strong>Colaboradores con salario fuera del rango asignado a su puesto:</strong>
            </div>
            <ul class="mb-0 ps-3">
                @foreach ($fueraDeRango as $fe)
                    <li>
                        <a href="{{ route('empleados.show', $fe->id_empleado) }}" class="fw-semi-bold">{{ $fe->nombre_completo }}</a>:
                        {{ $fe->cargo->nombre }} (Rango: {{ $fe->cargo->moneda }} {{ number_format((float) $fe->cargo->salario_min, 2) }} – {{ number_format((float) $fe->cargo->salario_max, 2) }}) —
                        Salario actual: <strong>{{ $fe->contratoVigente->moneda }} {{ number_format((float) $fe->contratoVigente->salario_base, 2) }}</strong>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        {{-- Departamentos --}}
        <div class="col-xl-5">
            <div class="card border border-300 shadow-none h-100">
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
            <div class="card border border-300 shadow-none h-100">
                <div class="card-body">
                    <h5 class="mb-3">Cargos y puestos</h5>
                    @if ($errors->cargo->any())<div class="alert alert-soft-danger fs--1">{{ $errors->cargo->first() }}</div>@endif
                    @foreach ($cargos as $c)
                        @php $colorNivel = $colores[$c->nivel_jerarquico] ?? 'secondary'; @endphp
                        <details class="border-bottom border-200 py-2 fs--1" @if ($errors->cargo->any() && (int) old('id') === $c->id_cargo) open @endif>
                            <summary class="d-flex justify-content-between align-items-center" style="cursor: pointer">
                                <div>
                                    <span class="{{ $c->activo ? '' : 'text-500' }}">
                                        <span class="fw-semi-bold">{{ $c->nombre }}</span>
                                    </span>
                                    <span class="badge badge-phoenix badge-phoenix-{{ $colorNivel }} ms-1">{{ $niveles[$c->nivel_jerarquico] ?? '' }}</span>
                                    @if ($c->departamento)
                                        <span class="text-600 ms-1">· {{ $c->departamento->nombre }}</span>
                                    @endif
                                </div>
                                <span class="text-700 text-nowrap text-end">
                                    @if ($c->salario_min || $c->salario_max)
                                        <span class="fw-semi-bold text-900">{{ $c->moneda }} {{ number_format((float) $c->salario_min, 0) }} – {{ number_format((float) $c->salario_max, 0) }}</span> ·
                                    @else
                                        <span class="text-warning">Sin rango</span> ·
                                    @endif
                                    {{ $c->empleados_count }} colab.
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
