<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head')
    @stack('estilos')
</head>
<body>
@php
    $usuario = auth()->user();
    $avatar = $usuario->avatar_url ?: \App\Support\Sistema::config()->imgAvatarDefault;
@endphp
<main class="main" id="top">
    <nav class="navbar navbar-vertical navbar-expand-lg">
        <script>
            var navbarStyle = window.config.config.phoenixNavbarStyle;
            if (navbarStyle && navbarStyle !== 'transparent') {
                document.querySelector('body').classList.add(`navbar-${navbarStyle}`);
            }
        </script>
        <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
            <div class="navbar-vertical-content">
                <ul class="navbar-nav flex-column" id="navbarVerticalNav">
                    @include('partials.menu')
                </ul>
            </div>
        </div>
        <div class="navbar-vertical-footer">
            <button class="btn navbar-vertical-toggle border-0 fw-semi-bold w-100 white-space-nowrap d-flex align-items-center">
                <span class="uil uil-left-arrow-to-left fs-0"></span><span class="uil uil-arrow-from-right fs-0"></span>
                <span class="navbar-vertical-footer-text ms-2">Contraer menú</span>
            </button>
        </div>
    </nav>

    <nav class="navbar navbar-top fixed-top navbar-expand" id="navbarDefault">
        <div class="collapse navbar-collapse justify-content-between">
            <div class="navbar-logo">
                <button class="btn navbar-toggler navbar-toggler-humburger-icon hover-bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse" aria-expanded="false" aria-label="Abrir menú">
                    <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
                </button>
                <a class="navbar-brand me-1 me-sm-3" href="{{ route('dashboard') }}">
                    <div class="d-flex align-items-center">
                        @include('partials.logo')
                    </div>
                </a>
            </div>
            <ul class="navbar-nav navbar-nav-icons flex-row">
                <li class="nav-item">
                    <div class="theme-control-toggle fa-icon-wait px-2">
                        <input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggle" />
                        <label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="themeControlToggle" title="Modo oscuro"><span class="icon" data-feather="moon"></span></label>
                        <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="themeControlToggle" title="Modo claro"><span class="icon" data-feather="sun"></span></label>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link lh-1 pe-0" id="navbarDropdownUser" href="#!" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <div class="avatar avatar-l">
                            @if ($avatar)
                                <img class="rounded-circle" src="{{ $avatar }}" alt="{{ $usuario->nombre_completo }}" />
                            @else
                                <div class="avatar-name rounded-circle"><span>{{ mb_strtoupper(mb_substr($usuario->nombre_completo ?: $usuario->username, 0, 1)) }}</span></div>
                            @endif
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border border-300" aria-labelledby="navbarDropdownUser">
                        <div class="card position-relative border-0">
                            <div class="card-body p-0">
                                <div class="text-center pt-4 pb-3">
                                    <h6 class="mt-2 text-1000 mb-0">{{ $usuario->nombre_completo ?: $usuario->username }}</h6>
                                    <p class="fs--1 text-700 mb-0">{{ $usuario->roles->pluck('nombre')->join(', ') ?: 'Sin rol' }}</p>
                                </div>
                            </div>
                            <ul class="nav d-flex flex-column mb-2 pb-1">
                                <li class="nav-item"><a class="nav-link px-3" href="{{ route('cuenta.seguridad') }}"><span class="me-2 text-900" data-feather="lock"></span>Seguridad de mi cuenta</a></li>
                                @if ($usuario->esAdministrador())
                                    <li class="nav-item"><a class="nav-link px-3" href="{{ route('seguridad.index') }}"><span class="me-2 text-900" data-feather="shield"></span>Seguridad y accesos</a></li>
                                @endif
                            </ul>
                            <div class="card-footer p-0 border-top">
                                <div class="px-3 my-3">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-phoenix-secondary d-flex flex-center w-100"><span class="me-2" data-feather="log-out"></span>Cerrar sesión</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="content">
        @include('partials.alertas')
        @yield('contenido')
        <footer class="footer position-absolute">
            <div class="row g-0 justify-content-between align-items-center h-100">
                <div class="col-12 col-sm-auto text-center">
                    <p class="mb-0 mt-2 mt-sm-0 text-900">{{ \App\Support\Sistema::config()->footerTexto ? \App\Support\Sistema::nombre().' · '.\App\Support\Sistema::config()->footerTexto : \App\Support\Sistema::nombre() }} &copy; {{ date('Y') }}</p>
                </div>
                <div class="col-12 col-sm-auto text-center">
                    <p class="mb-0 text-600">{{ \App\Support\Sistema::config()->footerVersion }}</p>
                </div>
            </div>
        </footer>
    </div>
</main>
@include('partials.scripts')
@stack('scripts')
</body>
</html>
