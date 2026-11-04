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
    $gruposMenu = \App\Support\MenuLateral::para($usuario);
    $extraerItems = function ($items, $grupoNombre) use (&$extraerItems) {
        $resultado = collect();
        foreach ($items as $item) {
            $hijos = $item['hijos'] ?? collect();
            if ($hijos->isNotEmpty()) {
                $resultado = $resultado->concat($extraerItems($hijos, $item['nombre'] ?? $grupoNombre));
            } elseif (!empty($item['ruta'])) {
                $resultado->push([
                    'nombre' => $item['nombre'],
                    'grupo' => $grupoNombre,
                    'icono' => $item['icono'] ?? 'circle',
                    'ruta' => $item['ruta']
                ]);
            }
        }
        return $resultado;
    };
    $itemsBusqueda = $gruposMenu->flatMap(fn ($g) => $extraerItems($g['items'], $g['nombre']));
    try {
        $notificaciones = \App\Models\Core\Notificacion::porUsuario($usuario->id_usuario)->recientes(8)->get();
        $noLeidasCount = \App\Models\Core\Notificacion::porUsuario($usuario->id_usuario)->noLeidas()->count();
    } catch (\Throwable) {
        $notificaciones = collect();
        $noLeidasCount = 0;
    }
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

            <div class="search-box navbar-top-search-box d-none d-lg-block" data-list='{"valueNames":["title","category"]}' style="width:25rem;">
                <form class="position-relative" data-bs-toggle="search" data-bs-display="static" onsubmit="return false;">
                    <input class="form-control search-input fuzzy-search rounded-pill form-control-sm" type="search" placeholder="Ir a pantalla o módulo…" aria-label="Buscar módulo" />
                    <span class="fas fa-search search-box-icon"></span>
                </form>
                <div class="btn-close position-absolute end-0 top-50 translate-middle cursor-pointer shadow-none" data-bs-dismiss="search">
                    <button class="btn btn-link btn-close-falcon p-0" aria-label="Cerrar"></button>
                </div>
                <div class="dropdown-menu border border-300 font-base start-0 py-0 overflow-hidden w-100">
                    <div class="scrollbar-overlay" style="max-height: 24rem;">
                        <div class="list pb-2">
                            <h6 class="dropdown-header text-1000 fs--1 border-bottom border-200 py-2 lh-sm">Accesos rápidos del sistema</h6>
                            <div class="py-1">
                                <a class="dropdown-item py-2" href="{{ route('dashboard') }}">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2 text-primary" data-feather="home" style="width:16px;height:16px;"></span>
                                        <div class="fw-normal text-1000 title">Inicio / Dashboard</div>
                                        <span class="ms-auto fs--2 text-500 category">General</span>
                                    </div>
                                </a>
                                @foreach ($itemsBusqueda as $acc)
                                    <a class="dropdown-item py-2" href="{{ url($acc['ruta']) }}">
                                        <div class="d-flex align-items-center">
                                            <span class="me-2 text-700" data-feather="{{ $acc['icono'] }}" style="width:16px;height:16px;"></span>
                                            <div class="fw-normal text-1000 title">{{ $acc['nombre'] }}</div>
                                            <span class="ms-auto fs--2 text-500 category">{{ $acc['grupo'] }}</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="text-center pb-3">
                            <p class="fallback fw-bold fs-0 d-none mb-0">Sin coincidencias.</p>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="navbar-nav navbar-nav-icons flex-row align-items-center">
                {{-- Empresa en la que se trabaja (solo si tiene acceso a más de una) --}}
                @php $empresasUsuario = $usuario->empresasDisponibles(); @endphp
                @if ($empresasUsuario->count() > 1)
                    @php $empresaActual = $empresasUsuario->firstWhere('id_empresa', $usuario->id_empresa); @endphp
                    <li class="nav-item dropdown me-2">
                        <a class="btn btn-phoenix-secondary btn-sm d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Cambiar de empresa">
                            <span class="me-2" data-feather="briefcase" style="height:16px;width:16px;"></span>
                            <span class="d-none d-md-inline text-truncate" style="max-width: 12rem;">{{ $empresaActual?->nombre_comercial ?? 'Empresa' }}</span>
                            <span class="fas fa-chevron-down ms-2 fs--2"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-2 shadow border border-300" style="min-width: 16rem;">
                            <h6 class="dropdown-header text-700">Trabajar en</h6>
                            @foreach ($empresasUsuario as $emp)
                                <form method="POST" action="{{ route('empresa-activa.cambiar') }}">
                                    @csrf
                                    <input type="hidden" name="id_empresa" value="{{ $emp->id_empresa }}" />
                                    <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center {{ (int) $emp->id_empresa === (int) $usuario->id_empresa ? 'active' : '' }}">
                                        <span>{{ $emp->nombre_comercial }}</span>
                                        @if ((int) $emp->id_empresa === $usuario->idEmpresaPrincipal())
                                            <span class="badge badge-phoenix badge-phoenix-secondary fs--2 ms-2">Principal</span>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                            @if (Route::has('consolidado.index') && collect(['facturas.ver', 'ordenes_compra.ver', 'pagos.ver', 'presupuesto.ver'])->contains(fn ($p) => $usuario->puede($p)))
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('consolidado.index') }}"><span class="fas fa-layer-group me-2 text-600"></span>Reporte consolidado</a>
                            @endif
                        </div>
                    </li>
                @endif
                <li class="nav-item">
                    <div class="theme-control-toggle fa-icon-wait px-2">
                        <input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggle" />
                        <label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="left" title="Modo oscuro"><span class="icon" data-feather="moon"></span></label>
                        <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="left" title="Modo claro"><span class="icon" data-feather="sun"></span></label>
                    </div>
                </li>

                {{-- Campana de notificaciones estilo Phoenix --}}
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" style="min-width: 2.5rem" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bs-auto-close="outside">
                        <span data-feather="bell" style="height:20px;width:20px;"></span>
                        @if ($noLeidasCount > 0)
                            <span class="position-absolute top-0 end-0 badge rounded-pill bg-danger fs--2 p-1" style="min-width: 1.1rem; transform: translate(-10%, 25%);">{{ $noLeidasCount > 9 ? '9+' : $noLeidasCount }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown-menu py-0 shadow border border-300 navbar-dropdown-caret" id="navbarDropdownNotfication">
                        <div class="card position-relative border-0">
                            <div class="card-header p-3 border-bottom border-200">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="text-black dark__text-white mb-0">Notificaciones</h5>
                                    @if ($noLeidasCount > 0)
                                        <span class="badge badge-phoenix badge-phoenix-primary">{{ $noLeidasCount }} nuevas</span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="scrollbar-overlay" style="max-height: 22rem;">
                                    @forelse ($notificaciones as $n)
                                        <a class="d-block text-decoration-none px-3 py-3 border-300 notification-card position-relative {{ $n->leida ? 'read' : 'unread' }} border-bottom" href="{{ $n->url_destino ? url($n->url_destino) : '#!' }}">
                                            <div class="d-flex align-items-start">
                                                <div class="avatar avatar-m me-3 flex-shrink-0">
                                                    <div class="avatar-name rounded-circle bg-soft-primary text-primary">
                                                        <span class="fas fa-bell fs--1"></span>
                                                    </div>
                                                </div>
                                                <div class="flex-1">
                                                    <h6 class="fs--1 text-black dark__text-white mb-1">{{ $n->titulo }}</h6>
                                                    <p class="fs--1 text-1000 mb-1 fw-normal">{{ $n->mensaje }}</p>
                                                    <p class="text-600 fs--2 mb-0"><span class="me-1 fas fa-clock"></span>{{ $n->created_at?->diffForHumans() }}</p>
                                                </div>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="text-center py-5 px-3">
                                            <span class="text-500 mb-2 d-inline-block" data-feather="bell-off" style="width:28px;height:28px;"></span>
                                            <p class="text-700 fs--1 mb-0">No tienes notificaciones pendientes.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                {{-- Menú rápido de 9 puntos (Nine Dots) estilo Phoenix --}}
                <li class="nav-item dropdown">
                    <a class="nav-link" id="navbarDropdownNindeDots" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" data-bs-auto-close="outside" aria-expanded="false" title="Accesos directos">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="2" cy="2" r="2" fill="currentColor"></circle>
                            <circle cx="2" cy="8" r="2" fill="currentColor"></circle>
                            <circle cx="2" cy="14" r="2" fill="currentColor"></circle>
                            <circle cx="8" cy="8" r="2" fill="currentColor"></circle>
                            <circle cx="8" cy="14" r="2" fill="currentColor"></circle>
                            <circle cx="14" cy="8" r="2" fill="currentColor"></circle>
                            <circle cx="14" cy="14" r="2" fill="currentColor"></circle>
                            <circle cx="8" cy="2" r="2" fill="currentColor"></circle>
                            <circle cx="14" cy="2" r="2" fill="currentColor"></circle>
                        </svg>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-nide-dots shadow border border-300" aria-labelledby="navbarDropdownNindeDots">
                        <div class="card bg-white dark__bg-1100 position-relative border-0">
                            <div class="card-body pt-3 px-3 pb-2 overflow-auto scrollbar" style="max-height: 20rem; min-width: 18rem;">
                                <div class="row text-center align-items-center gx-0 gy-0">
                                    <div class="col-4">
                                        <a class="d-block hover-bg-200 p-2 rounded-3 text-center text-decoration-none mb-2" href="{{ route('dashboard') }}">
                                            <span class="d-inline-flex flex-center rounded-circle bg-soft-primary text-primary" style="width:2.5rem;height:2.5rem;"><span data-feather="pie-chart" style="width:18px;height:18px;"></span></span>
                                            <p class="mb-0 text-1000 text-truncate fs--2 mt-1">Dashboard</p>
                                        </a>
                                    </div>
                                    @if ($usuario->puede('clientes.ver'))
                                        <div class="col-4">
                                            <a class="d-block hover-bg-200 p-2 rounded-3 text-center text-decoration-none mb-2" href="{{ route('clientes.index') }}">
                                                <span class="d-inline-flex flex-center rounded-circle bg-soft-info text-info" style="width:2.5rem;height:2.5rem;"><span data-feather="briefcase" style="width:18px;height:18px;"></span></span>
                                                <p class="mb-0 text-1000 text-truncate fs--2 mt-1">Clientes</p>
                                            </a>
                                        </div>
                                    @endif
                                    @if ($usuario->puede('facturas.ver'))
                                        <div class="col-4">
                                            <a class="d-block hover-bg-200 p-2 rounded-3 text-center text-decoration-none mb-2" href="{{ route('facturas.index') }}">
                                                <span class="d-inline-flex flex-center rounded-circle bg-soft-success text-success" style="width:2.5rem;height:2.5rem;"><span data-feather="file-text" style="width:18px;height:18px;"></span></span>
                                                <p class="mb-0 text-1000 text-truncate fs--2 mt-1">Facturas</p>
                                            </a>
                                        </div>
                                    @endif
                                    @if ($usuario->puede('oportunidades.ver'))
                                        <div class="col-4">
                                            <a class="d-block hover-bg-200 p-2 rounded-3 text-center text-decoration-none mb-2" href="{{ route('oportunidades.index') }}">
                                                <span class="d-inline-flex flex-center rounded-circle bg-soft-warning text-warning" style="width:2.5rem;height:2.5rem;"><span data-feather="trending-up" style="width:18px;height:18px;"></span></span>
                                                <p class="mb-0 text-1000 text-truncate fs--2 mt-1">Embudo</p>
                                            </a>
                                        </div>
                                    @endif
                                    @if ($usuario->puede('tickets.ver'))
                                        <div class="col-4">
                                            <a class="d-block hover-bg-200 p-2 rounded-3 text-center text-decoration-none mb-2" href="{{ route('tickets.index') }}">
                                                <span class="d-inline-flex flex-center rounded-circle bg-soft-danger text-danger" style="width:2.5rem;height:2.5rem;"><span data-feather="headphones" style="width:18px;height:18px;"></span></span>
                                                <p class="mb-0 text-1000 text-truncate fs--2 mt-1">Tickets</p>
                                            </a>
                                        </div>
                                    @endif
                                    @if ($usuario->puede('productos.ver'))
                                        <div class="col-4">
                                            <a class="d-block hover-bg-200 p-2 rounded-3 text-center text-decoration-none mb-2" href="{{ route('productos.index') }}">
                                                <span class="d-inline-flex flex-center rounded-circle bg-soft-secondary text-secondary" style="width:2.5rem;height:2.5rem;"><span data-feather="package" style="width:18px;height:18px;"></span></span>
                                                <p class="mb-0 text-1000 text-truncate fs--2 mt-1">Productos</p>
                                            </a>
                                        </div>
                                    @endif
                                    @if ($usuario->puede('empleados.ver'))
                                        <div class="col-4">
                                            <a class="d-block hover-bg-200 p-2 rounded-3 text-center text-decoration-none mb-2" href="{{ route('empleados.index') }}">
                                                <span class="d-inline-flex flex-center rounded-circle bg-soft-primary text-primary" style="width:2.5rem;height:2.5rem;"><span data-feather="users" style="width:18px;height:18px;"></span></span>
                                                <p class="mb-0 text-1000 text-truncate fs--2 mt-1">RRHH</p>
                                            </a>
                                        </div>
                                    @endif
                                    @if ($usuario->puede('nomina.ver'))
                                        <div class="col-4">
                                            <a class="d-block hover-bg-200 p-2 rounded-3 text-center text-decoration-none mb-2" href="{{ route('nomina.index') }}">
                                                <span class="d-inline-flex flex-center rounded-circle bg-soft-success text-success" style="width:2.5rem;height:2.5rem;"><span data-feather="dollar-sign" style="width:18px;height:18px;"></span></span>
                                                <p class="mb-0 text-1000 text-truncate fs--2 mt-1">Nómina</p>
                                            </a>
                                        </div>
                                    @endif
                                    @if ($usuario->esAdministrador())
                                        <div class="col-4">
                                            <a class="d-block hover-bg-200 p-2 rounded-3 text-center text-decoration-none mb-2" href="{{ route('configuracion.edit') }}">
                                                <span class="d-inline-flex flex-center rounded-circle bg-soft-info text-info" style="width:2.5rem;height:2.5rem;"><span data-feather="settings" style="width:18px;height:18px;"></span></span>
                                                <p class="mb-0 text-1000 text-truncate fs--2 mt-1">Ajustes</p>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                {{-- Perfil de usuario estilo Phoenix --}}
                <li class="nav-item dropdown">
                    <a class="nav-link lh-1 pe-0" id="navbarDropdownUser" href="#!" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <div class="avatar avatar-l status-online">
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
                                    <div class="avatar avatar-xl status-online mb-2">
                                        @if ($avatar)
                                            <img class="rounded-circle" src="{{ $avatar }}" alt="{{ $usuario->nombre_completo }}" />
                                        @else
                                            <div class="avatar-name rounded-circle"><span>{{ mb_strtoupper(mb_substr($usuario->nombre_completo ?: $usuario->username, 0, 1)) }}</span></div>
                                        @endif
                                    </div>
                                    <h6 class="mt-2 text-1000 mb-0">{{ $usuario->nombre_completo ?: $usuario->username }}</h6>
                                    <p class="fs--1 text-700 mb-0">{{ $usuario->roles->where('id_empresa', $usuario->id_empresa)->pluck('nombre')->join(', ') ?: 'Sin rol' }}</p>
                                </div>
                            </div>
                            <div class="overflow-auto scrollbar" style="max-height: 10rem;">
                                <ul class="nav d-flex flex-column mb-2 pb-1">
                                    <li class="nav-item"><a class="nav-link px-3" href="{{ route('dashboard') }}"><span class="me-2 text-900" data-feather="pie-chart"></span><span>Inicio / Dashboard</span></a></li>
                                    <li class="nav-item"><a class="nav-link px-3" href="{{ route('cuenta.seguridad') }}"><span class="me-2 text-900" data-feather="lock"></span><span>Seguridad de mi cuenta</span></a></li>
                                    @if ($usuario->esAdministrador())
                                        <li class="nav-item"><a class="nav-link px-3" href="{{ route('seguridad.index') }}"><span class="me-2 text-900" data-feather="shield"></span><span>Seguridad y accesos</span></a></li>
                                        <li class="nav-item"><a class="nav-link px-3" href="{{ route('configuracion.edit') }}"><span class="me-2 text-900" data-feather="settings"></span><span>Configuración del sistema</span></a></li>
                                    @endif
                                </ul>
                            </div>
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

    <script>
        var navbarTopStyle = window.config.config.phoenixNavbarTopStyle;
        var navbarTop = document.querySelector('.navbar-top');
        if (navbarTop && navbarTopStyle === 'darker') {
            navbarTop.classList.add('navbar-darker');
        }
        var navbarVerticalStyle = window.config.config.phoenixNavbarVerticalStyle;
        var navbarVertical = document.querySelector('.navbar-vertical');
        if (navbarVertical && navbarVerticalStyle === 'darker') {
            navbarVertical.classList.add('navbar-darker');
        }
    </script>

    <div class="content">
        @include('partials.alertas')
        @yield('contenido')
        <div class="toast-container position-fixed bottom-0 end-0 p-3" id="nexus-toast-container" style="z-index: 1095;"></div>

        {{-- Drawer Offcanvas Lateral para Historial Rápido de Auditoría (Bitácora) --}}
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAuditoria" aria-labelledby="offcanvasAuditoriaLabel" style="width: 480px;">
            <div class="offcanvas-header border-bottom border-200 bg-body-emphasis py-3 px-4">
                <div class="d-flex align-items-center">
                    <span class="fas fa-history text-primary me-2 fs-0"></span>
                    <div>
                        <h5 class="offcanvas-title mb-0 text-1000" id="offcanvasAuditoriaLabel">Historial de Auditoría</h5>
                        <p class="fs--2 text-600 mb-0" id="auditoriaSubtitulo">Cargando bitácora...</p>
                    </div>
                </div>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-4" id="auditoriaCuerpo">
                <div class="text-center py-5" id="auditoriaCargando">
                    <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                    <p class="text-600 fs--1 mb-0">Consultando registros inmutables...</p>
                </div>
                <div id="auditoriaContenido" class="d-none"></div>
            </div>
        </div>

        {{-- Modal de Confirmación Universal Phoenix para Acciones Críticas/Destructivas --}}
        <div class="modal fade" id="modalConfirmacionPhoenix" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border border-200 shadow-lg">
                    <div class="modal-header border-bottom border-200 py-3 px-4 bg-body-emphasis">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-exclamation-triangle text-danger me-2 fs-0" id="confirmModalIcono"></span>
                            <h5 class="modal-title mb-0 text-1000" id="confirmModalTitulo">Confirmar acción</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-800 fs-0 mb-0" id="confirmModalMensaje">¿Estás seguro de continuar con esta operación?</p>
                        <div class="alert alert-soft-warning fs--2 mt-3 mb-0" id="confirmModalAlerta">
                            <span class="fas fa-info-circle me-1"></span>Esta acción quedará registrada en la bitácora de auditoría.
                        </div>
                    </div>
                    <div class="modal-footer border-top border-200 py-2 px-4 bg-body-emphasis">
                        <button type="button" class="btn btn-phoenix-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger btn-sm" id="confirmModalBotonEjecutar">
                            <span class="fas fa-check me-1"></span>Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>

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
