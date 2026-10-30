{{-- Acciones de un país, departamento o municipio. --}}
<div class="font-sans-serif btn-reveal-trigger position-static">
    <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" aria-label="Acciones"><span class="fas fa-ellipsis-h fs--2"></span></button>
    <div class="dropdown-menu dropdown-menu-end py-2">
        <a class="dropdown-item" href="{{ route('geografia.edit', [$tipo, $id]) }}">Editar</a>
        <form method="POST" action="{{ route('geografia.estado', [$tipo, $id]) }}">
            @csrf
            @method('PATCH')
            <button class="dropdown-item" type="submit">{{ $registro->activo ? 'Desactivar' : 'Activar' }}</button>
        </form>
        <div class="dropdown-divider"></div>
        <form method="POST" action="{{ route('geografia.destroy', [$tipo, $id]) }}" onsubmit="return confirm(@js('¿Eliminar «'.$registro->nombre.'»?'))">
            @csrf
            @method('DELETE')
            <button class="dropdown-item text-danger" type="submit">Eliminar</button>
        </form>
    </div>
</div>
