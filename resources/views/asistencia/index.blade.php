@extends('layouts.app', ['titulo' => 'Asistencia'])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('asistencia.editar');
        $puedeAprobar = $yo->puede('asistencia.aprobar');
        $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Recursos Humanos</li>
            <li class="breadcrumb-item active">Control de Asistencia</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Encabezado con selector de fecha estilo Phoenix --}}
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-12 col-md-auto">
                <h2 class="mb-1 text-1100">Control de Asistencia & Ausencias</h2>
                <h5 class="text-700 fw-semi-bold mb-0">Registro de marcajes diarios, cálculo de horas extra y gestión de vacaciones</h5>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                <a class="btn btn-phoenix-primary" href="{{ route('rotativos.index') }}">
                    <span class="fas fa-people-arrows me-2"></span>Personal rotativo
                </a>
                <form method="GET" class="d-flex gap-2">
                    <a class="btn btn-phoenix-secondary px-3" href="{{ route('asistencia.index', ['fecha' => $fecha->copy()->subDay()->toDateString()]) }}" title="Día anterior">
                        <span class="fas fa-chevron-left"></span>
                    </a>
                    <input class="form-control" type="date" name="fecha" value="{{ $fecha->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()" />
                    @if ($fecha->lt(today()))
                        <a class="btn btn-phoenix-secondary px-3" href="{{ route('asistencia.index', ['fecha' => $fecha->copy()->addDay()->toDateString()]) }}" title="Día siguiente">
                            <span class="fas fa-chevron-right"></span>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Tarjetas KPI estilo Phoenix widgets --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border border-200">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Presentes en la Fecha</h6>
                                <h3 class="text-success mb-0 fw-bolder">{{ $kpis['presentes'] }} / {{ $empleados->count() }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">{{ $kpis['ausentes'] }} ausencias registradas</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-success-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-user-check text-success fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">De Vacaciones</h6>
                                <h3 class="text-primary mb-0 fw-bolder">{{ $kpis['vacaciones'] }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Fecha: {{ $fecha->format('d/m/Y') }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-umbrella-beach text-primary fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Solicitudes por Aprobar</h6>
                                <h3 class="{{ $kpis['pendientes'] > 0 ? 'text-warning' : 'text-1100' }} mb-0 fw-bolder">{{ $kpis['pendientes'] }}</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Permisos y descansos</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-warning-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-hourglass-half text-warning fs-1 z-index-1 ms-2"></span>
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
                                <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Pasivo Vacaciones</h6>
                                <h3 class="text-info mb-0 fw-bolder">{{ $cantidad($kpis['dias_disponibles']) }} días</h3>
                                <p class="fs--2 text-600 mb-0 mt-1">Estimado: Q {{ number_format($kpis['pasivo'], 2) }}</p>
                            </div>
                            <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                <span class="fas fa-piggy-bank text-info fs-1 z-index-1 ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pestañas de Navegación estilo Phoenix --}}
        <ul class="nav nav-underline mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $errors->solicitud->any() ? '' : 'active' }}" data-bs-toggle="tab" href="#tab-diaria" role="tab">
                    <span class="fas fa-clipboard-check me-2"></span>Marcaje Diario
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $errors->solicitud->any() ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-solicitudes" role="tab">
                    <span class="fas fa-calendar-minus me-2"></span>Solicitudes de Ausencia
                    @if ($kpis['pendientes'])
                        <span class="badge badge-phoenix badge-phoenix-warning ms-1">{{ $kpis['pendientes'] }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-saldos" role="tab">
                    <span class="fas fa-umbrella-beach me-2"></span>Saldos de Vacaciones
                </a>
            </li>
        </ul>

        <div class="tab-content">
            {{-- Tab 1: Asistencia Diaria --}}
            <div class="tab-pane fade {{ $errors->solicitud->any() ? '' : 'show active' }}" id="tab-diaria" role="tabpanel">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-white border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0 text-1100 fw-bold">
                                {{ ucfirst(['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'][$fecha->dayOfWeek]) }} {{ $fecha->format('d/m/Y') }}
                                @if ($fecha->isWeekend())
                                    <span class="badge badge-phoenix badge-phoenix-secondary fs--2 ms-2">Fin de semana</span>
                                @endif
                            </h5>
                            <p class="fs--2 text-600 mb-0">Horario oficial de entrada: {{ $horaEntrada }}</p>
                        </div>
                        @if ($puedeEditar && $empleados->isNotEmpty())
                            <button class="btn btn-phoenix-primary btn-sm" type="button" id="todos-presentes">
                                <span class="fas fa-check-double me-1"></span>Marcar vacíos como presentes
                            </button>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @if ($empleados->isEmpty())
                            <p class="text-700 fs--1 p-4 mb-0">No hay empleados activos en esta fecha.</p>
                        @else
                            <form method="POST" action="{{ route('asistencia.guardar') }}">
                                @csrf
                                <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}" />
                                <div class="table-responsive scrollbar">
                                    <table class="table table-sm fs--1 mb-0 align-middle">
                                        <thead class="bg-100">
                                            <tr>
                                                <th class="ps-4 text-700">COLABORADOR</th>
                                                <th class="text-700" style="width: 170px;">ESTADO ASISTENCIA</th>
                                                <th class="text-700" style="width: 130px;">ENTRADA</th>
                                                <th class="text-700" style="width: 130px;">SALIDA</th>
                                                <th class="text-700" style="width: 120px;">H. EXTRA</th>
                                                <th class="pe-4 text-700">OBSERVACIONES</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list">
                                            @foreach ($empleados as $e)
                                                @php
                                                    $as = $asistencias->get($e->id_empleado);
                                                    $bloqueado = $as?->origen === 'SOLICITUD';
                                                @endphp
                                                <tr class="hover-actions-trigger position-static">
                                                    <td class="ps-4 py-3">
                                                        <a class="fw-bold text-1100 text-hover-primary" href="{{ route('empleados.show', $e->id_empleado) }}">
                                                            {{ $e->nombre_completo }}
                                                        </a>
                                                        <span class="d-block fs--2 text-600">{{ $e->codigo_empleado }} · {{ $e->cargo?->nombre ?? 'Sin cargo' }}</span>
                                                    </td>
                                                    <td>
                                                        @if ($bloqueado)
                                                            <span class="badge badge-phoenix badge-phoenix-info fs--2">{{ $as->estado }}</span>
                                                            <span class="fs--2 text-600 d-block mt-1">Por solicitud aprobada</span>
                                                        @else
                                                            <select class="form-select form-select-sm" name="filas[{{ $e->id_empleado }}][estado]">
                                                                <option value="">Sin registro</option>
                                                                @foreach ($estadosAsistencia as $cod => [$nom])
                                                                    <option value="{{ $cod }}" @selected($as?->estado === $cod)>{{ $nom }}</option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($bloqueado)
                                                            <span class="text-600">—</span>
                                                        @else
                                                            <input class="form-control form-control-sm" type="time" name="filas[{{ $e->id_empleado }}][hora_entrada]" value="{{ $as?->hora_entrada ? \Illuminate\Support\Carbon::parse($as->hora_entrada)->format('H:i') : '' }}" />
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($bloqueado)
                                                            <span class="text-600">—</span>
                                                        @else
                                                            <input class="form-control form-control-sm" type="time" name="filas[{{ $e->id_empleado }}][hora_salida]" value="{{ $as?->hora_salida ? \Illuminate\Support\Carbon::parse($as->hora_salida)->format('H:i') : '' }}" />
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($bloqueado)
                                                            <span class="text-600">—</span>
                                                        @else
                                                            <input class="form-control form-control-sm" type="number" step="0.5" min="0" max="24" name="filas[{{ $e->id_empleado }}][horas_extra]" value="{{ $as && (float) $as->horas_extra ? (float) $as->horas_extra : '' }}" placeholder="0" />
                                                        @endif
                                                    </td>
                                                    <td class="pe-4">
                                                        @if ($bloqueado)
                                                            <span class="text-600 fs--2">{{ $as->observaciones }}</span>
                                                        @else
                                                            <input class="form-control form-control-sm" name="filas[{{ $e->id_empleado }}][observaciones]" value="{{ $as?->observaciones }}" maxlength="500" placeholder="Motivo o retardo..." />
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($puedeEditar)
                                    <div class="p-3 bg-white border-top border-200 d-flex justify-content-end">
                                        <button class="btn btn-primary" type="submit">
                                            <span class="fas fa-save me-2"></span>Guardar marcajes del día
                                        </button>
                                    </div>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tab 2: Solicitudes de Ausencia --}}
            <div class="tab-pane fade {{ $errors->solicitud->any() ? 'show active' : '' }}" id="tab-solicitudes" role="tabpanel">
                <div class="card border border-200 shadow-none mb-4">
                    <div class="card-header bg-white border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-1100 fw-bold">Solicitudes de Permisos y Vacaciones</h5>
                            <p class="mb-0 fs--2 text-600">Gestión de ausencias justificadas y autorizaciones</p>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive scrollbar">
                            <table class="table table-sm fs--1 mb-0 align-middle">
                                <thead class="bg-100">
                                    <tr>
                                        <th class="ps-3 text-700">COLABORADOR</th>
                                        <th class="text-700">TIPO</th>
                                        <th class="text-700">RANGO DE FECHAS</th>
                                        <th class="text-end text-700">DÍAS</th>
                                        <th class="text-end pe-3 text-700">ESTADO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($solicitudes as $s)
                                        <tr>
                                            <td class="ps-3 py-3 fw-semi-bold text-1000">{{ $s->empleado?->nombre_completo }}</td>
                                            <td><span class="badge badge-tag">{{ $s->tipo }}</span></td>
                                            <td class="text-700">{{ $s->fecha_inicio?->format('d/m/Y') }} — {{ $s->fecha_fin?->format('d/m/Y') }}</td>
                                            <td class="text-end fw-bold text-900">{{ (float) $s->dias }}</td>
                                            <td class="text-end pe-3">
                                                <span class="badge badge-phoenix fs--2 badge-phoenix-{{ ['PENDIENTE' => 'warning', 'APROBADO' => 'success', 'RECHAZADO' => 'danger', 'CANCELADO' => 'secondary'][$s->estado] ?? 'secondary' }}">
                                                    {{ ucfirst(strtolower($s->estado)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-600 py-4">No hay solicitudes recientes registradas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 3: Saldos de Vacaciones --}}
            <div class="tab-pane fade" id="tab-saldos" role="tabpanel">
                <div class="card border border-200 shadow-none">
                    <div class="card-header bg-white border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-1100 fw-bold">Saldos de Vacaciones al {{ today()->format('d/m/Y') }}</h5>
                            <p class="mb-0 fs--2 text-600">15 días hábiles por año laborado (Art. 130 Código de Trabajo)</p>
                        </div>
                        <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('asistencia.vacaciones.exportar') }}">
                            <span class="fas fa-file-excel me-1"></span>Exportar saldos
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive scrollbar">
                            <table class="table table-sm fs--1 mb-0 align-middle">
                                <thead class="bg-100">
                                    <tr>
                                        <th class="ps-4 text-700">COLABORADOR</th>
                                        <th class="text-700">FECHA INGRESO</th>
                                        <th class="text-end text-700">DEVENGADO</th>
                                        <th class="text-end text-700">GOZADOS</th>
                                        <th class="text-end text-700">DISPONIBLE</th>
                                        <th class="text-end pe-4 text-700">VALOR ESTIMADO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($saldos as $fila)
                                        @php $sv = $fila['saldo']; $emp = $fila['empleado']; @endphp
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <a class="fw-bold text-1100 text-hover-primary" href="{{ route('empleados.show', $emp->id_empleado) }}">
                                                    {{ $emp->nombre_completo }}
                                                </a>
                                                <span class="d-block fs--2 text-600">{{ $emp->codigo_empleado }}</span>
                                            </td>
                                            <td class="text-700">{{ $emp->fecha_ingreso?->format('d/m/Y') }}</td>
                                            <td class="text-end text-900">{{ $cantidad($sv['devengado']) }}</td>
                                            <td class="text-end text-900">{{ $cantidad($sv['gozados']) }}</td>
                                            <td class="text-end fw-bold {{ $sv['disponible'] >= 15 ? 'text-danger' : 'text-success' }}">
                                                {{ $cantidad($sv['disponible']) }} días
                                            </td>
                                            <td class="text-end pe-4 fw-bold text-1000">Q {{ number_format($sv['valor_estimado'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-600 py-4">No hay empleados vigentes.</td>
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

@push('scripts')
    <script>
        (function () {
            var boton = document.getElementById('todos-presentes');
            if (!boton) return;
            boton.addEventListener('click', function () {
                document.querySelectorAll('select[name$="[estado]"]').forEach(function (s) {
                    if (!s.value) s.value = 'PRESENTE';
                });
            });
        })();
    </script>
@endpush
