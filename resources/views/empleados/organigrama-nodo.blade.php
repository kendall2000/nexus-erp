{{-- Nodo del organigrama (recursivo). --}}
@php
    $emp = $nodo['empleado'];
    $iniciales = mb_strtoupper(mb_substr($emp->primer_nombre ?? '', 0, 1).mb_substr($emp->primer_apellido ?? '', 0, 1));
@endphp
<li class="org-nodo">
    <a class="org-tarjeta text-decoration-none d-inline-flex align-items-center gap-2" href="{{ route('empleados.show', $emp->id_empleado) }}">
        <span class="avatar avatar-m"><span class="avatar-name rounded-circle bg-soft-primary text-primary"><span>{{ $iniciales }}</span></span></span>
        <span class="text-start">
            <span class="d-block fw-bold text-1100 fs--1">{{ $emp->nombre_completo }}</span>
            <span class="d-block fs--2 text-700">{{ $emp->cargo?->nombre ?? 'Sin cargo' }}</span>
            @if ($emp->departamento)<span class="d-block fs--2 text-500">{{ $emp->departamento->nombre }}</span>@endif
        </span>
        @if ($nodo['hijos'])<span class="badge badge-phoenix badge-phoenix-secondary ms-1" title="Reportes directos">{{ count($nodo['hijos']) }}</span>@endif
    </a>
    @if ($nodo['hijos'])
        <ul>
            @foreach ($nodo['hijos'] as $hijo)
                @include('empleados.organigrama-nodo', ['nodo' => $hijo])
            @endforeach
        </ul>
    @endif
</li>
