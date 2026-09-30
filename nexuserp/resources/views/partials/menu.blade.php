{{-- Menú lateral: módulos (tabla «modulo») que el usuario puede ver, agrupados. Estructura: navbar-vertical de Phoenix. --}}
@use('App\Support\MenuLateral')
@php
    $usuario = auth()->user();
    $grupos = MenuLateral::para($usuario)->all();

    // Opciones fijas: el inicio para todos y la cuenta al final.
    array_unshift($grupos, ['nombre' => 'General', 'items' => collect([['nombre' => 'Inicio', 'icono' => 'home', 'ruta' => route('dashboard', absolute: false), 'hijos' => collect()]])]);
    $cuenta = [['nombre' => 'Seguridad de mi cuenta', 'icono' => 'lock', 'ruta' => route('cuenta.seguridad', absolute: false), 'hijos' => collect()]];
    if ($usuario->esAdministrador()) {
        $cuenta[] = ['nombre' => 'Seguridad y accesos', 'icono' => 'shield', 'ruta' => route('seguridad.index', absolute: false), 'hijos' => collect()];
    }
    $grupos[] = ['nombre' => 'Mi cuenta', 'items' => collect($cuenta)];
@endphp

@foreach ($grupos as $g => $grupo)
    <li class="nav-item">
        <p class="navbar-vertical-label">{{ $grupo['nombre'] }}</p>
        <hr class="navbar-vertical-line" />
        @foreach ($grupo['items'] as $i => $item)
            <div class="nav-item-wrapper">
                @if ($item['hijos']->isNotEmpty())
                    @php
                        $id = 'nv-'.$g.'-'.$i;
                        $abierto = $item['hijos']->contains(fn ($h) => MenuLateral::activa($h['ruta']));
                    @endphp
                    <a class="nav-link dropdown-indicator label-1 {{ $abierto ? '' : 'collapsed' }}" href="#{{ $id }}" role="button" data-bs-toggle="collapse" aria-expanded="{{ $abierto ? 'true' : 'false' }}" aria-controls="{{ $id }}">
                        <div class="d-flex align-items-center">
                            <div class="dropdown-indicator-icon"><span class="fas fa-caret-right"></span></div>
                            <span class="nav-link-icon"><span data-feather="{{ $item['icono'] }}"></span></span>
                            <span class="nav-link-text">{{ $item['nombre'] }}</span>
                        </div>
                    </a>
                    <div class="parent-wrapper label-1">
                        <ul class="nav collapse parent {{ $abierto ? 'show' : '' }}" data-bs-parent="#navbarVerticalCollapse" id="{{ $id }}">
                            <li class="collapsed-nav-item-title d-none">{{ $item['nombre'] }}</li>
                            @foreach ($item['hijos'] as $hijo)
                                <li class="nav-item">
                                    <a class="nav-link {{ MenuLateral::activa($hijo['ruta']) ? 'active' : '' }}" href="{{ url($hijo['ruta']) }}">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">{{ $hijo['nombre'] }}</span></div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <a class="nav-link label-1 {{ MenuLateral::activa($item['ruta']) ? 'active' : '' }}" href="{{ url($item['ruta']) }}" role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span data-feather="{{ $item['icono'] }}"></span></span>
                            <span class="nav-link-text-wrapper"><span class="nav-link-text">{{ $item['nombre'] }}</span></span>
                        </div>
                    </a>
                @endif
            </div>
        @endforeach
    </li>
@endforeach
