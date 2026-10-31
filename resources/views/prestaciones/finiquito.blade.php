{{-- Finiquito laboral imprimible de una liquidación. --}}
@php
    $dinero = fn ($n) => number_format((float) $n, 2);
    $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $total = (float) $lineas->sum('monto_calculado');
@endphp
<x-print-layout :titulo="'Finiquito — '.$e->nombre_completo" :empresa="$empresa" :volver-url="route('prestaciones.index', ['anio' => $salida->year])">
    <x-slot:encabezado>
        <span class="badge badge-phoenix badge-phoenix-primary fs--1">FINIQUITO LABORAL</span>
        <p class="fs--1 text-700 mb-0 mt-2">{{ $periodo }}</p>
        <p class="fs--2 text-600 mb-0">Emitido el {{ now()->format('d/m/Y') }}</p>
    </x-slot:encabezado>

    <div class="row g-3 fs--1 mb-4">
        <div class="col-sm-6">
            <h6 class="text-700 text-uppercase fs--2 mb-1">Colaborador</h6>
            <p class="fw-bold text-1100 mb-0">{{ $e->nombre_completo }}</p>
            <p class="text-700 mb-0">{{ $e->codigo_empleado }}{{ $e->cargo ? ' · '.$e->cargo->nombre : '' }}{{ $e->departamento ? ' · '.$e->departamento->nombre : '' }}</p>
        </div>
        <div class="col-sm-6 text-sm-end">
            <h6 class="text-700 text-uppercase fs--2 mb-1">Relación laboral</h6>
            <p class="text-700 mb-0">Ingreso: <strong>{{ $e->fecha_ingreso?->format('d/m/Y') }}</strong></p>
            <p class="text-700 mb-0">Salida: <strong>{{ $salida->format('d/m/Y') }}</strong></p>
            @if ($e->motivo_baja)<p class="text-600 mb-0">Motivo: {{ $e->motivo_baja }}</p>@endif
        </div>
    </div>

    <table class="table table-sm fs--1 mb-4">
        <thead class="bg-200"><tr><th class="ps-2">Concepto</th><th class="text-end">Días</th><th class="text-end">Base</th><th class="text-end">Estado</th><th class="text-end pe-2">Monto</th></tr></thead>
        <tbody>
        @foreach ($lineas as $l)
            <tr>
                <td class="ps-2">{{ $tipos[$l->tipo] ?? $l->tipo }}</td>
                <td class="text-end">{{ $cantidad($l->dias_calculados) }}</td>
                <td class="text-end">{{ $dinero($l->monto_base) }}</td>
                <td class="text-end">{{ $estados[$l->estado][0] ?? $l->estado }}</td>
                <td class="text-end pe-2">{{ $dinero($l->monto_calculado) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot class="border-top border-300 fw-bold"><tr><td class="ps-2" colspan="4">Total a recibir</td><td class="text-end pe-2">{{ $dinero($total) }}</td></tr></tfoot>
    </table>

    <p class="fs--1 text-800 mb-0">
        Yo, <strong>{{ $e->nombre_completo }}</strong>, hago constar que recibí de <strong>{{ $empresa?->nombre_legal ?: ($empresa?->nombre_comercial ?: \App\Support\Sistema::nombre()) }}</strong>
        la cantidad de <strong>{{ $dinero($total) }}</strong> en concepto de las prestaciones laborales detalladas, por la relación laboral que concluyó el {{ $salida->format('d/m/Y') }},
        y declaro que no tengo reclamo pendiente por los conceptos aquí liquidados.
    </p>

    <div class="row g-5 mt-5 pt-4 text-center fs--2 text-700">
        <div class="col-6"><div class="border-top border-300 pt-1">Firma del colaborador: {{ $e->nombre_completo }}</div></div>
        <div class="col-6"><div class="border-top border-300 pt-1">Representante del empleador</div></div>
    </div>
</x-print-layout>
