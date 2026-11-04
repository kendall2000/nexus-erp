{{-- Nombre de una sede en el resumen por sucursal. --}}
@if ($sede['agencia'])<span class="fas fa-level-up-alt fa-rotate-90 text-500 me-2"></span>@endif
<span class="fw-semi-bold text-1000">{{ $sede['nombre'] }}</span>
@if ($sede['codigo'])<span class="fs--2 text-600 ms-1">Est. {{ $sede['codigo'] }}</span>@endif
@if ($sede['agencia'] && $sede['padre'])<span class="d-block fs--2 text-600">Agencia de {{ $sede['padre'] }}</span>@endif
@unless ($sede['activo'])<span class="badge badge-phoenix badge-phoenix-secondary ms-1">Inactiva</span>@endunless
