{{-- Menú lateral multinivel: módulos (tabla «modulo») con soporte Padre -> Hijo -> Nieto en Phoenix navbar-vertical. --}}
@use('App\Support\MenuLateral')
@php
    $usuario = auth()->user();
    $grupos = MenuLateral::para($usuario)->all();

    // Opciones fijas: el inicio para todos y la cuenta al final.
    array_unshift($grupos, [
        'nombre' => 'General',
        'items' => collect([['id_modulo' => 'home', 'nombre' => 'Inicio', 'icono' => 'home', 'ruta' => route('dashboard', absolute: false), 'hijos' => collect()]])
    ]);

    $cuenta = [['id_modulo' => 'seg-cuenta', 'nombre' => 'Seguridad de mi cuenta', 'icono' => 'lock', 'ruta' => route('cuenta.seguridad', absolute: false), 'hijos' => collect()]];
    if ($usuario->esAdministrador()) {
        $cuenta[] = ['id_modulo' => 'seg-accesos', 'nombre' => 'Seguridad y accesos', 'icono' => 'shield', 'ruta' => route('seguridad.index', absolute: false), 'hijos' => collect()];
    }
    $grupos[] = ['nombre' => 'Mi cuenta', 'items' => collect($cuenta)];
@endphp

@foreach ($grupos as $g => $grupo)
    <li class="nav-item">
        <p class="navbar-vertical-label">{{ $grupo['nombre'] }}</p>
        <hr class="navbar-vertical-line" />
        @foreach ($grupo['items'] as $i => $item)
            @php
                $tieneHijos = !empty($item['hijos']) && $item['hijos']->isNotEmpty();
                $idPadre = 'nv-'.$g.'-'.$i;
                $abiertoPadre = $tieneHijos && MenuLateral::tieneActivo($item);
            @endphp

            <div class="nav-item-wrapper">
                @if ($tieneHijos)
                    {{-- Nivel 1 (Padre con submenú) --}}
                    <a class="nav-link dropdown-indicator label-1 {{ $abiertoPadre ? '' : 'collapsed' }}"
                       href="#{{ $idPadre }}" role="button" data-bs-toggle="collapse"
                       aria-expanded="{{ $abiertoPadre ? 'true' : 'false' }}" aria-controls="{{ $idPadre }}">
                        <div class="d-flex align-items-center">
                            <div class="dropdown-indicator-icon"><span class="fas fa-caret-right"></span></div>
                            <span class="nav-link-icon"><span data-feather="{{ $item['icono'] }}"></span></span>
                            <span class="nav-link-text">{{ $item['nombre'] }}</span>
                        </div>
                    </a>
                    <div class="parent-wrapper label-1">
                        <ul class="nav collapse parent {{ $abiertoPadre ? 'show' : '' }}" data-bs-parent="#navbarVerticalCollapse" id="{{ $idPadre }}">
                            <li class="collapsed-nav-item-title d-none">{{ $item['nombre'] }}</li>

                            {{-- Nivel 2 (Hijos) --}}
                            @foreach ($item['hijos'] as $h => $hijo)
                                @php
                                    $tieneNietos = !empty($hijo['hijos']) && $hijo['hijos']->isNotEmpty();
                                    $idHijo = 'nv-'.$g.'-'.$i.'-'.$h;
                                    $abiertoHijo = $tieneNietos && MenuLateral::tieneActivo($hijo);
                                @endphp

                                @if ($tieneNietos)
                                    {{-- Hijo con submenú (Nieto) --}}
                                    <li class="nav-item">
                                        <a class="nav-link dropdown-indicator {{ $abiertoHijo ? '' : 'collapsed' }}"
                                           href="#{{ $idHijo }}" data-bs-toggle="collapse"
                                           aria-expanded="{{ $abiertoHijo ? 'true' : 'false' }}" aria-controls="{{ $idHijo }}">
                                            <div class="d-flex align-items-center">
                                                <div class="dropdown-indicator-icon"><span class="fas fa-caret-right"></span></div>
                                                <span class="nav-link-text">{{ $hijo['nombre'] }}</span>
                                            </div>
                                        </a>
                                        <div class="parent-wrapper">
                                            <ul class="nav collapse parent {{ $abiertoHijo ? 'show' : '' }}" data-bs-parent="#{{ $idPadre }}" id="{{ $idHijo }}">
                                                {{-- Nivel 3 (Nietos) --}}
                                                @foreach ($hijo['hijos'] as $nieto)
                                                    <li class="nav-item">
                                                        <a class="nav-link {{ MenuLateral::activa($nieto['ruta']) ? 'active' : '' }}" href="{{ url($nieto['ruta']) }}">
                                                            <div class="d-flex align-items-center">
                                                                <span class="nav-link-text">{{ $nieto['nombre'] }}</span>
                                                            </div>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </li>
                                @else
                                    {{-- Hijo simple (enlace directo) --}}
                                    <li class="nav-item">
                                        <a class="nav-link {{ MenuLateral::activa($hijo['ruta']) ? 'active' : '' }}" href="{{ url($hijo['ruta']) }}">
                                            <div class="d-flex align-items-center">
                                                <span class="nav-link-text">{{ $hijo['nombre'] }}</span>
                                            </div>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @else
                    {{-- Nivel 1 simple sin submenú --}}
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
