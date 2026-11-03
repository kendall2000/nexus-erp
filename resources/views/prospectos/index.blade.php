@extends('layouts.app', ['titulo' => 'Gestión de Prospectos (Leads)'])

@section('contenido')
    @php $yo = auth()->user(); @endphp

    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="#!">CRM & Ventas</a></li>
            <li class="breadcrumb-item active">Prospectos Comerciales</li>
        </ol>
    </nav>

    <div class="mb-4">
        <div class="row g-3 justify-content-between align-items-end">
            <div class="col-12 col-md">
                <h2 class="mb-1 text-bold text-1100">
                    <span class="fas fa-user-plus text-primary me-2"></span>Prospectos & Oportunidades Potenciales
                </h2>
                <p class="text-700 mb-0">Embudo inicial de captación, cualificación de contactos y conversión hacia clientes activos.</p>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <a class="btn btn-phoenix-secondary" href="{{ route('oportunidades.index') }}">
                    <span class="fas fa-columns me-2"></span>Pipeline de Ventas
                </a>
                @if ($yo->puede('prospectos.crear'))
                    <a class="btn btn-primary" href="{{ route('prospectos.create') }}">
                        <span class="fas fa-plus-circle me-2"></span>Nuevo Prospecto
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Filtros y estadísticas de conversión Phoenix -->
    <div class="card shadow-none border mb-4">
        <div class="card-header p-3 border-bottom bg-body-emphasis">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <div class="search-box w-100">
                        <input class="form-control form-control-sm search-input search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Empresa, contacto o correo..." maxlength="100" />
                        <span class="fas fa-search search-box-icon text-500"></span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select form-select-sm" name="estado">
                        <option value="">Estado: Abiertos</option>
                        <option value="todos" @selected(($filtros['estado'] ?? '') === 'todos')>Todos los estados</option>
                        @foreach ($estados as $k => [$n])
                            <option value="{{ $k }}" @selected(($filtros['estado'] ?? '') === $k)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select form-select-sm" name="temperatura">
                        <option value="">Temperatura: Todas</option>
                        @foreach ($temperaturas as $k => [$n])
                            <option value="{{ $k }}" @selected(($filtros['temperatura'] ?? '') === $k)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select form-select-sm" name="fuente">
                        <option value="">Fuente: Todas</option>
                        @foreach ($fuentes as $f)
                            <option value="{{ $f->id_fuente }}" @selected((int) ($filtros['fuente'] ?? 0) === $f->id_fuente)>{{ $f->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select form-select-sm" name="asignado">
                        <option value="">Vendedor: Todos</option>
                        @foreach ($vendedores as $v)
                            <option value="{{ $v->id_empleado }}" @selected((int) ($filtros['asignado'] ?? 0) === $v->id_empleado)>{{ $v->nombre_completo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button class="btn btn-sm btn-phoenix-primary w-100" type="submit" title="Filtrar">
                        <span class="fas fa-filter"></span>
                    </button>
                    @if (array_filter($filtros))
                        <a class="btn btn-sm btn-phoenix-danger px-2" href="{{ route('prospectos.index') }}" title="Limpiar Filtros">
                            <span class="fas fa-times"></span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-sm fs--1 mb-0 align-middle table-hover">
                <thead class="bg-body-secondary text-900">
                    <tr>
                        <th class="ps-3 py-2 text-uppercase text-700">Empresa / Organización</th>
                        <th class="text-uppercase text-700">Contacto Principal</th>
                        <th class="text-center text-uppercase text-700">Temperatura</th>
                        <th class="text-uppercase text-700">Asignado a</th>
                        <th class="text-uppercase text-700">Último Seguimiento</th>
                        <th class="text-center text-uppercase text-700">Estado</th>
                        <th class="pe-3 text-end text-uppercase text-700">Acción</th>
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
                    <tr class="hover-actions-trigger position-static">
                        <td class="ps-3 py-2">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-m me-3 flex-shrink-0">
                                    <div class="avatar-name rounded-circle bg-{{ $colorTemp }}-subtle text-{{ $colorTemp }} fw-bold fs-0">
                                        <span>{{ $iniciales }}</span>
                                    </div>
                                </div>
                                <div>
                                    <a class="fw-bold text-1100 text-decoration-none fs-0" href="{{ route('prospectos.show', $p->id_prospecto) }}">
                                        {{ $p->nombre_empresa }}
                                    </a>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <span class="badge badge-phoenix badge-phoenix-secondary fs--2">
                                            <i class="fas fa-bullhorn me-1"></i>{{ $p->fuente?->nombre ?? 'Sin fuente' }}
                                        </span>
                                        @if ($p->telefono_contacto)
                                            <span class="fs--2 text-muted ms-1"><i class="fas fa-phone-alt me-1"></i>{{ $p->telefono_contacto }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semi-bold text-900">{{ $p->nombre_contacto }}</div>
                            @if ($p->email_contacto)
                                <a href="mailto:{{ $p->email_contacto }}" class="fs--2 text-600 text-decoration-none d-block">
                                    <i class="far fa-envelope me-1"></i>{{ $p->email_contacto }}
                                </a>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($p->temperatura === 'CALIENTE')
                                <span class="badge badge-phoenix badge-phoenix-danger px-2 py-1">
                                    <i class="fas fa-fire me-1"></i>{{ $nombreTemp }}
                                </span>
                            @elseif ($p->temperatura === 'TIBIO')
                                <span class="badge badge-phoenix badge-phoenix-warning px-2 py-1">
                                    <i class="fas fa-thermometer-half me-1"></i>{{ $nombreTemp }}
                                </span>
                            @else
                                <span class="badge badge-phoenix badge-phoenix-info px-2 py-1">
                                    <i class="fas fa-snowflake me-1"></i>{{ $nombreTemp }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if ($p->asignadoA)
                                <div class="d-flex align-items-center">
                                    <span class="fas fa-user-tie text-primary me-2"></span>
                                    <span class="text-900 fw-semi-bold">{{ $p->asignadoA->nombre_completo }}</span>
                                </div>
                            @else
                                <span class="badge badge-phoenix badge-phoenix-secondary">Sin asignar</span>
                            @endif
                        </td>
                        <td>
                            @if ($ultimo)
                                <div class="text-800 fw-semi-bold fs--1">
                                    <i class="far fa-clock me-1 text-primary"></i>{{ $ultimo->fecha_hora?->format('d/m/Y H:i') }}
                                </div>
                                <span class="fs--2 text-muted">
                                    {{ \App\Http\Controllers\ProspectoController::RESULTADOS[$ultimo->resultado] ?? $ultimo->resultado }}
                                </span>
                            @else
                                <span class="text-muted fs--2"><i class="fas fa-minus-circle me-1"></i>Sin interacción previa</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-phoenix badge-phoenix-{{ $color }} px-2 py-1">
                                {{ $nombreEstado }}
                            </span>
                        </td>
                        <td class="pe-3 text-end">
                            <div class="btn-reveal-trigger position-static">
                                <a class="btn btn-sm btn-phoenix-secondary px-2" href="{{ route('prospectos.show', $p->id_prospecto) }}" title="Gestionar Prospecto">
                                    <span class="fas fa-arrow-right text-primary"></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-700 py-5">
                            <div class="avatar avatar-xl mb-3 mx-auto">
                                <div class="avatar-name rounded-circle bg-body-secondary text-500 fs-1">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                            </div>
                            <h5 class="text-800">No hay prospectos comerciales</h5>
                            <p class="text-muted fs--1 mb-0">{{ array_filter($filtros) ? 'Ningún prospecto coincide con los filtros aplicados.' : 'Registra tus primeros contactos de ventas para iniciar el ciclo comercial.' }}</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($prospectos->hasPages())
            <div class="card-footer p-3 border-top">
                {{ $prospectos->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
