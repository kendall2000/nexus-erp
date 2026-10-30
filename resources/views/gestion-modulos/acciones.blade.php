{{-- Menú de acciones de un grupo u opción. --}}
<div class="font-sans-serif btn-reveal-trigger position-static">
    <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" aria-label="Acciones"><span class="fas fa-ellipsis-h fs--2"></span></button>
    <div class="dropdown-menu dropdown-menu-end py-2">
        <a class="dropdown-item" href="{{ route('modulos.edit', $m->id_modulo) }}">Editar</a>
        <form method="POST" action="{{ route('modulos.estado', $m->id_modulo) }}">
            @csrf
            @method('PATCH')
            <button class="dropdown-item" type="submit">{{ $m->activo ? 'Desactivar' : 'Activar' }}</button>
        </form>
        <div class="dropdown-divider"></div>
        <form method="POST" action="{{ route('modulos.destroy', $m->id_modulo) }}" onsubmit="return confirm(@js('¿Eliminar el módulo «'.$m->nombre.'»? Se borran sus permisos y las asignaciones a roles y usuarios.'))">
            @csrf
            @method('DELETE')
            <button class="dropdown-item text-danger" type="submit">Eliminar</button>
        </form>
    </div>
</div>
