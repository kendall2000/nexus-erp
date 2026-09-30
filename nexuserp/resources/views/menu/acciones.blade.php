{{-- Menú de acciones de un grupo u opción. --}}
<div class="font-sans-serif btn-reveal-trigger position-static">
    <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" aria-label="Acciones"><span class="fas fa-ellipsis-h fs--2"></span></button>
    <div class="dropdown-menu dropdown-menu-end py-2">
        <a class="dropdown-item" href="{{ route('menu.edit', $m->id_menu) }}">Editar</a>
        <form method="POST" action="{{ route('menu.estado', $m->id_menu) }}">
            @csrf
            @method('PATCH')
            <button class="dropdown-item" type="submit">{{ $m->activo ? 'Desactivar' : 'Activar' }}</button>
        </form>
        <div class="dropdown-divider"></div>
        <form method="POST" action="{{ route('menu.destroy', $m->id_menu) }}" onsubmit="return confirm(@js('¿Eliminar «'.$m->nombre.'» del menú?'))">
            @csrf
            @method('DELETE')
            <button class="dropdown-item text-danger" type="submit">Eliminar</button>
        </form>
    </div>
</div>
