{{-- Flechas para subir / bajar un lugar dentro de su grupo. --}}
@foreach (['subir' => ['up', $primero], 'bajar' => ['down', $ultimo]] as $direccion => [$flecha, $deshabilitado])
    <form method="POST" action="{{ route('modulos.mover', $m->id_modulo) }}" class="lh-1">
        @csrf
        @method('PATCH')
        <input type="hidden" name="direccion" value="{{ $direccion }}" />
        <button class="btn btn-link p-0 lh-1 text-600" type="submit" aria-label="{{ ucfirst($direccion) }}" @disabled($deshabilitado)><span class="fas fa-chevron-{{ $flecha }} fs--2"></span></button>
    </form>
@endforeach
