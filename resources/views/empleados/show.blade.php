@extends('layouts.app', ['titulo' => $e->nombre_completo])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('empleados.editar');
        [$nombreEstado, $color] = $estados[$e->estado] ?? [$e->estado, 'secondary'];
        $cv = $e->contratoVigente;
        $dinero = fn ($n) => number_format((float) $n, 2);
        $deBaja = $e->estado === 'BAJA';
        $iniciales = mb_strtoupper(mb_substr($e->nombres ?: $e->nombre_completo, 0, 1).mb_substr($e->apellidos ?: '', 0, 1));
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('empleados.index') }}">Empleados</a></li>
            <li class="breadcrumb-item active">{{ $e->nombre_completo }}</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Encabezado idéntico a apps/social/profile.html --}}
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-4xl">
                    <div class="avatar-name rounded-circle {{ $deBaja ? 'bg-200 text-600' : 'bg-primary-100 text-primary-600' }} fs-2 fw-bolder">
                        <span>{{ $iniciales }}</span>
                    </div>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="mb-0 text-1100">{{ $e->nombre_completo }}</h2>
                        <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1">{{ $nombreEstado }}</span>
                    </div>
                    <p class="text-700 fs--1 mb-0 mt-1">
                        Código: <strong class="text-900">{{ $e->codigo_empleado }}</strong> · {{ $e->cargo?->nombre ?? 'Sin cargo asignado' }}
                        @if ($e->departamento)
                            · <span class="badge badge-tag">{{ $e->departamento->nombre }}</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($puedeEditar && !$deBaja)
                    <a class="btn btn-phoenix-primary" href="{{ route('empleados.edit', $e->id_empleado) }}">
                        <span class="fas fa-edit me-2"></span>Editar ficha
                    </a>
                @endif
                @if ($puedeEditar && $deBaja)
                    <form method="POST" action="{{ route('empleados.reactivar', $e->id_empleado) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-success" type="submit"><span class="fas fa-check me-2"></span>Reactivar empleado</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Tarjetas KPI de Estado Laboral estilo Phoenix --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Salario Base Actual</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ $cv ? $cv->moneda.' '.$dinero($cv->salario_base) : 'Sin contrato' }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">{{ $cv ? 'Contrato: '.$cv->numero_contrato : 'Pendiente de registrar' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-money-check-alt text-primary fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Tiempo en Empresa</h6>
                                <h3 class="text-info mb-0 fw-bolder">{{ $e->antiguedad_anios }} años</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Ingreso: {{ $e->fecha_ingreso?->format('d/m/Y') ?? '—' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-calendar-check text-info fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Vacaciones Disponibles</h6>
                                <h3 class="text-warning mb-0 fw-bolder">{{ $vacaciones ? rtrim(rtrim(number_format($vacaciones['disponible'], 2), '0'), '.').' días' : '—' }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">{{ $vacaciones ? 'Gozados: '.rtrim(rtrim(number_format($vacaciones['gozados'], 2), '0'), '.') : 'De baja' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-warning-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-umbrella-beach text-warning fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Documentos Digitales</h6>
                                <h3 class="text-success mb-0 fw-bolder">{{ $documentos->count() }} subidos</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Sucursal: {{ $e->sucursal?->nombre ?? 'Principal' }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-success-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-folder-open text-success fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5">
            {{-- Columna Lateral: Información Personal y Contacto (4 columnas) --}}
            <div class="col-12 col-xl-4">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3 text-1100">Información del Colaborador</h5>
                        <div class="border-top border-200 pt-3">
                            <div class="mb-3">
                                <h6 class="text-700 fs--2 text-uppercase mb-1">DPI / Documento</h6>
                                <p class="text-900 fs--1 mb-0 fw-semi-bold">{{ $e->dpi ?: 'No registrado' }}</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-700 fs--2 text-uppercase mb-1">NIT / Afiliación IGSS</h6>
                                <p class="text-900 fs--1 mb-0">NIT: {{ $e->nit ?: '—' }} · IGSS: {{ $e->numero_afiliacion_igss ?: '—' }}</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-700 fs--2 text-uppercase mb-1">Correo Institucional / Personal</h6>
                                <p class="text-900 fs--1 mb-0">{{ $e->email_corporativo ?: $e->email_personal ?: 'Sin correo' }}</p>
                            </div>
                            <div>
                                <h6 class="text-700 fs--2 text-uppercase mb-1">Teléfono</h6>
                                <p class="text-900 fs--1 mb-0">{{ $e->telefono ?: 'Sin teléfono' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Cadena de mando / Jefe --}}
                @if ($cadena->isNotEmpty())
                    <div class="card border border-200 shadow-none mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3 text-1100"><span class="fas fa-sitemap text-primary me-2"></span>Línea de Mando</h5>
                            @foreach ($cadena as $jefe)
                                <div class="d-flex align-items-center gap-2 py-2 border-bottom border-200">
                                    <div class="avatar avatar-m">
                                        <div class="avatar-name rounded-circle bg-soft-primary text-primary fs--1">
                                            <span>{{ mb_strtoupper(mb_substr($jefe->nombres ?: $jefe->nombre_completo, 0, 1)) }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <a class="fw-bold text-1100 fs--1" href="{{ route('empleados.show', $jefe->id_empleado) }}">{{ $jefe->nombre_completo }}</a>
                                        <span class="d-block fs--2 text-600">{{ $jefe->cargo?->nombre ?? 'Jefe Inmediato' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Columna Principal: Documentos y Expediente (8 columnas) --}}
            <div class="col-12 col-xl-8">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-white border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-1100 fw-bold">Expediente Digital & Documentos Adjuntos</h5>
                            <p class="mb-0 fs--2 text-600">Contratos, constancias, certificaciones y DPI digitalizado</p>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive scrollbar">
                            <table class="table table-sm fs--1 mb-0 align-middle">
                                <thead class="bg-100">
                                    <tr>
                                        <th class="ps-4 text-700">DOCUMENTO</th>
                                        <th class="text-700">TIPO</th>
                                        <th class="text-700">FECHA EMISIÓN</th>
                                        <th class="text-700">VENCE</th>
                                        <th class="text-end pe-4 text-700">ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($documentos as $d)
                                        @php $vence = $d->fecha_vencimiento ? (int) round(today()->diffInDays($d->fecha_vencimiento, false)) : null; @endphp
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <a class="fw-bold text-primary" href="{{ $d->url_archivo }}" target="_blank" rel="noopener noreferrer">
                                                    {{ $d->nombre }}
                                                </a>
                                                @if ($d->observaciones)
                                                    <span class="d-block fs--2 text-600">{{ $d->observaciones }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-tag">{{ $tiposDocumento[$d->tipo_documento] ?? $d->tipo_documento }}</span>
                                            </td>
                                            <td class="text-700">{{ $d->fecha_emision?->format('d/m/Y') ?? '—' }}</td>
                                            <td>
                                                @if ($vence !== null && $vence < 0)
                                                    <span class="badge badge-phoenix badge-phoenix-danger fs--2">Vencido</span>
                                                @elseif ($vence !== null && $vence <= 30)
                                                    <span class="badge badge-phoenix badge-phoenix-warning fs--2">Vence en {{ $vence }}d</span>
                                                @else
                                                    <span class="text-700">{{ $d->fecha_vencimiento?->format('d/m/Y') ?? 'Sin vencimiento' }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                <a class="btn btn-phoenix-secondary btn-sm" href="{{ $d->url_archivo }}" target="_blank">
                                                    <span class="fas fa-download me-1"></span>Ver
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-600 py-4">No hay documentos cargados en el expediente.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
