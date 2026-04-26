<nav class="navbar navbar-top fixed-top navbar-expand" id="navbar-app">
    <div class="collapse navbar-collapse justify-content-between" id="navbarDefault">
        <div class="navbar-logo">
            <button class="btn navbar-toggler navbar-toggler-humburger-icon hover-bg-transparent"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarVerticalCollapse"
                aria-controls="navbarVerticalCollapse"
                aria-expanded="false"
                aria-label="Toggle Navigation">
                <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
            </button>
            <a class="navbar-brand me-1 me-sm-3" href="{{ url('/sistema/dashboard') }}">
                <div class="d-flex align-items-center">
                    <p class="logo-text ms-2 d-none d-sm-block">NexusERP</p>
                </div>
            </a>
        </div>

        {{-- Buscador --}}
        <div class="search-box navbar-top-search-box d-none d-lg-block" style="width:25rem;">
            <form class="position-relative">
                <input class="form-control search-input fuzzy-search rounded-pill form-control-sm"
                    type="search" placeholder="Buscar..." aria-label="Buscar" />
                <span class="fas fa-search search-box-icon"></span>
            </form>
        </div>

        <ul class="navbar-nav navbar-nav-icons flex-row">

            {{-- Toggle tema --}}
            <li class="nav-item">
                <div class="theme-control-toggle fa-icon-wait px-2">
                    <input class="form-check-input ms-0 theme-control-toggle-input"
                        type="checkbox" data-theme-control="phoenixTheme"
                        value="dark" id="themeControlToggle" />
                    <label class="mb-0 theme-control-toggle-label theme-control-toggle-light"
                        for="themeControlToggle" data-bs-toggle="tooltip"
                        data-bs-placement="left" title="Cambiar tema">
                        <span class="icon" data-feather="moon"></span>
                    </label>
                    <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark"
                        for="themeControlToggle" data-bs-toggle="tooltip"
                        data-bs-placement="left" title="Cambiar tema">
                        <span class="icon" data-feather="sun"></span>
                    </label>
                </div>
            </li>

            {{-- Notificaciones --}}
            <li class="nav-item dropdown">
                <a class="nav-link" href="#" style="min-width:2.5rem"
                    role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false" data-bs-auto-close="outside">
                    <span data-feather="bell" style="height:20px;width:20px;"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown-menu py-0 shadow border border-300 navbar-dropdown-caret">
                    <div class="card position-relative border-0">
                        <div class="card-header p-2">
                            <div class="d-flex justify-content-between">
                                <h5 class="text-black mb-0">Notificaciones</h5>
                                <button class="btn btn-link p-0 fs--1 fw-normal" type="button">
                                    Marcar todo como leído
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="scrollbar-overlay" style="height:auto;">
                                <p class="text-center text-muted py-3 fs--1">Sin notificaciones nuevas</p>
                            </div>
                        </div>
                        <div class="card-footer p-0 border-top border-0">
                            <div class="my-2 text-center fw-bold fs--2 text-100">
                                <a class="fw-bolder" href="{{ url('/sistema/notificaciones') }}">
                                    Ver historial
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </li>

            {{-- Perfil usuario --}}
            <li class="nav-item dropdown" id="perfil-app">
                <a class="nav-link lh-1 pe-0" href="#!" role="button"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-l">
                        <img class="rounded-circle"
                            :src="usuario.avatar_url || '{{ url('/') }}/Plantilla/public/assets/img/avatar/avatar.png'"
                            alt="Avatar" />
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border border-300">
                    <div class="card position-relative border-0">
                        <div class="card-body p-0">
                            <div class="text-center pt-4 pb-3">
                                <div class="avatar avatar-xl">
                                    <img class="rounded-circle"
                                        :src="usuario.avatar_url || '{{ url('/') }}/Plantilla/public/assets/img/avatar/avatar.png'"
                                        alt="Avatar" />
                                </div>
                                <h6 class="mt-2 text-black">@{{ usuario.nombre_completo }}</h6>
                                <small class="text-muted">@{{ usuario.email }}</small>
                            </div>
                        </div>
                        <div class="overflow-auto scrollbar" style="height:3rem;">
                            <ul class="nav d-flex flex-column mb-2 pb-1">
                                <li class="nav-item">
                                    <a class="nav-link px-3" href="{{ url('/sistema/perfil') }}">
                                        <span class="me-2 text-900" data-feather="user"></span>
                                        <span>Mi Perfil</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer p-0 border-top">
                            <div class="px-3 my-3">
                                <button class="btn btn-phoenix-secondary d-flex flex-center w-100"
                                    @click="cerrarSesion">
                                    <span class="me-2" data-feather="log-out"></span>
                                    Cerrar sesión
                                </button>
                            </div>
                            <div class="my-2 text-center fw-bold fs--2 text-600">
                                <span>NexusERP v1.0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>

{{-- Contenido del módulo --}}
<div class="content"> {{-- ESTE DIV SE ABRE AQUÍ, PERO SE CIERRA EN EXTRAS --}}
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="{{ url('/sistema/dashboard') }}">Inicio</a>
            </li>
            <li class="breadcrumb-item active">@yield('breadcrumb', 'Dashboard')</li>
        </ol>
    </nav>

    @yield('content')

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Vue({
            el: '#perfil-app',
            data: {
                usuario: JSON.parse(sessionStorage.getItem('nexus_usuario') || '{}')
            },
            methods: {
                async cerrarSesion() {
                    try {
                        await fetch(apiUrl + '/auth/logout', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': 'Bearer ' + nexusToken
                            }
                        });
                    } catch(e) {}
                    sessionStorage.removeItem('nexus_token');
                    sessionStorage.removeItem('nexus_usuario');
                    window.location.href = server + '/login';
                }
            }
        });
    });
    </script>
{{-- NOTA: NO HAY </div> AQUÍ A PROPÓSITO --}}