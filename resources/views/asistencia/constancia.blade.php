{{-- Constancia imprimible de vacaciones o permiso aprobado. --}}
@php
    $e = $s->empleado;
    $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $esVacaciones = $s->tipo === 'VACACIONES';
@endphp
<x-print-layout :titulo="'Constancia #'.$s->id_solicitud" :empresa="$empresa" :volver-url="route('asistencia.index')">
    <x-slot:encabezado>
        <span class="badge badge-phoenix badge-phoenix-primary fs--1">{{ $esVacaciones ? 'CONSTANCIA DE VACACIONES' : 'CONSTANCIA DE PERMISO' }}</span>
        <p class="fs--1 text-700 mb-0 mt-2">Solicitud #{{ $s->id_solicitud }}</p>
        <p class="fs--2 text-600 mb-0">Emitida el {{ now()->format('d/m/Y') }}</p>
    </x-slot:encabezado>

    <div class="row g-3 fs--1 mb-4">
        <div class="col-sm-6">
            <h6 class="text-700 text-uppercase fs--2 mb-1">Colaborador</h6>
            <p class="fw-bold text-1100 mb-0">{{ $e?->nombre_completo }}</p>
            <p class="text-700 mb-0">{{ $e?->codigo_empleado }}{{ $e?->cargo ? ' · '.$e->cargo->nombre : '' }}{{ $e?->departamento ? ' · '.$e->departamento->nombre : '' }}</p>
            <p class="text-600 mb-0">Ingreso: {{ $e?->fecha_ingreso?->format('d/m/Y') }}</p>
        </div>
        <div class="col-sm-6 text-sm-end">
            <h6 class="text-700 text-uppercase fs--2 mb-1">Ausencia</h6>
            <p class="fw-bold text-1100 mb-0">{{ $tipos[$s->tipo] ?? $s->tipo }}</p>
            <p class="text-700 mb-0">Del {{ $s->fecha_inicio?->format('d/m/Y') }} al {{ $s->fecha_fin?->format('d/m/Y') }}</p>
            <p class="text-700 mb-0">{{ $s->dias_habiles }} días hábiles</p>
            <p class="text-600 mb-0">Estado: {{ ucfirst(strtolower($s->estado)) }}{{ $s->aprobadoPor ? ' por '.$s->aprobadoPor->nombre_completo : '' }}{{ $s->fecha_aprobacion ? ' el '.$s->fecha_aprobacion->format('d/m/Y') : '' }}</p>
        </div>
    </div>

    @if ($s->motivo)
        <p class="fs--1 mb-4"><span class="fw-semi-bold">Motivo:</span> {{ $s->motivo }}</p>
    @endif

    @if ($saldo)
        <table class="table table-sm fs--1 mb-4">
            <thead class="bg-200"><tr><th class="ps-2">Saldo de vacaciones</th><th class="text-end pe-2">Días</th></tr></thead>
            <tbody>
                <tr><td class="ps-2">Devengado a la fecha</td><td class="text-end pe-2">{{ $cantidad($saldo['devengado']) }}</td></tr>
                <tr><td class="ps-2">Gozados (incluye esta solicitud)</td><td class="text-end pe-2">{{ $cantidad($saldo['gozados']) }}</td></tr>
                <tr class="fw-bold"><td class="ps-2">Disponible</td><td class="text-end pe-2">{{ $cantidad($saldo['disponible']) }}</td></tr>
            </tbody>
        </table>
    @endif

    <div class="row g-5 mt-5 pt-4 text-center fs--2 text-700">
        <div class="col-6"><div class="border-top border-300 pt-1">Firma del colaborador: {{ $e?->nombre_completo }}</div></div>
        <div class="col-6"><div class="border-top border-300 pt-1">Recursos humanos / Jefe inmediato</div></div>
    </div>
</x-print-layout>
