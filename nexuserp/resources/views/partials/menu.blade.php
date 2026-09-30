{{-- Menú lateral: grupos e ítems de la tabla «menu» (Gestión de menú) según los roles del usuario. --}}
@use('App\Support\MenuLateral')
@php
    $usuario = auth()->user();
    $grupos = MenuLateral::para($usuario)->all();

    // Opciones fijas de la cuenta (no dependen de Gestión de menú).
    $cuenta = [['nombre' => 'Seguridad de mi cuenta', 'icono' => 'lock', 'ruta' => route('cuenta.seguridad', absolute: false)]];
    if ($usuario->esAdministrador()) {
        $cuenta[] = ['nombre' => 'Seguridad y accesos', 'icono' => 'shield', 'ruta' => route('seguridad.index', absolute: false)];
    }
    $grupos[] = ['nombre' => 'Mi cuenta', 'items' => $cuenta];
@endphp

@foreach ($grupos as $grupo)
    <li class="nav-item">
        <p class="navbar-vertical-label">{{ $grupo['nombre'] }}</p>
        <hr class="navbar-vertical-line" />
        @foreach ($grupo['items'] as $i)
            <div class="nav-item-wrapper">
                <a class="nav-link label-1 {{ MenuLateral::activa($i['ruta']) ? 'active' : '' }}" href="{{ $i['ruta'] === '#' ? '#' : url($i['ruta']) }}" role="button">
                    <div class="d-flex align-items-center">
                        <span class="nav-link-icon"><span data-feather="{{ $i['icono'] }}"></span></span>
                        <span class="nav-link-text-wrapper"><span class="nav-link-text">{{ $i['nombre'] }}</span></span>
                    </div>
                </a>
            </div>
        @endforeach
    </li>
@endforeach
