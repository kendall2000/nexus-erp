{{-- Un módulo en la lista: orden, ícono, nombre, código, ruta y acciones. --}}
@php $acciones = $m->permisos->sortBy(fn ($p) => $p->accion?->orden); @endphp
<li class="list-group-item d-flex align-items-center gap-2 py-2 {{ $m->activo ? '' : 'text-500' }}" style="{{ $nivel ? 'padding-left: 2.5rem' : '' }}">
    <div class="d-flex flex-column">
        @include('gestion-modulos.mover', ['m' => $m, 'primero' => $primero, 'ultimo' => $ultimo])
    </div>
    <span class="text-700"><span data-feather="{{ $m->icono ?: 'chevrons-right' }}" style="width:16px;height:16px"></span></span>
    <div class="flex-1 min-w-0">
        <div class="fw-semi-bold fs--1 text-truncate">
            {{ $m->nombre }}
            <code class="fs--2 ms-1">{{ $m->codigo }}</code>
            @unless ($m->activo)<span class="badge badge-phoenix badge-phoenix-secondary ms-1">Inactivo</span>@endunless
            @if ($m->hijos_count)<span class="badge badge-phoenix badge-phoenix-info ms-1">Submenú</span>@endif
        </div>
        <div class="fs--2 text-600 text-truncate">
            {{ $m->ruta ?: 'Sin ruta (no aparece en el menú)' }} ·
            @forelse ($acciones as $p)
                <span class="badge badge-tag me-1" title="{{ $asignaciones[$p->id_permiso] ?? 0 }} roles">{{ $p->accion?->codigo }}</span>
            @empty
                <span class="badge badge-phoenix badge-phoenix-warning">Solo Administrador</span>
            @endforelse
        </div>
    </div>
    @include('gestion-modulos.acciones', ['m' => $m])
</li>
