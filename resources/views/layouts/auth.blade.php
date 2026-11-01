<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head')
    @stack('estilos')
</head>
<body>
@php $cfg = \App\Support\Sistema::config(); @endphp
<main class="main" id="top">
    <div class="row vh-100 g-0">
        {{-- Columna izquierda con fotografía / banner split --}}
        <div class="col-lg-6 position-relative d-none d-lg-block">
            <div class="bg-holder" style="background-image:url({{ $cfg->imgFondoLogin ?: asset('assets/img/bg/30.png') }}); background-size: cover; background-position: center;"></div>
            <div class="position-relative h-100 d-flex flex-column justify-content-between p-5 p-xl-7" style="background: linear-gradient(180deg, rgba(15, 23, 42, 0.4) 0%, rgba(15, 23, 42, 0.75) 100%);">
                <div>
                    <span class="badge badge-phoenix fs--2 badge-phoenix-primary px-3 py-2 rounded-pill mb-3">
                        <span class="fas fa-shield-alt me-1"></span> Portal Seguro
                    </span>
                    <h2 class="text-white fw-bolder display-6 mb-2">{{ \App\Support\Sistema::nombre() }}</h2>
                    <p class="text-300 fs-0 max-w-md">{{ $cfg->slogan ?: 'Gestiona clientes, operaciones, inventario, finanzas y recursos humanos desde un solo lugar.' }}</p>
                </div>
                <div>
                    <ul class="list-unstyled text-300 mb-0 d-flex gap-4">
                        <li class="d-flex align-items-center"><span class="fas fa-check-circle text-success me-2"></span>Alta disponibilidad</li>
                        <li class="d-flex align-items-center"><span class="fas fa-check-circle text-success me-2"></span>Acceso seguro</li>
                        <li class="d-flex align-items-center"><span class="fas fa-check-circle text-success me-2"></span>Tiempo real</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Columna derecha con el formulario --}}
        <div class="col-lg-6 d-flex flex-center bg-white dark__bg-1100">
            <div class="row flex-center h-100 g-0 px-4 px-sm-0 w-100">
                <div class="col col-sm-8 col-lg-9 col-xl-7 py-5">
                    <div class="text-center mb-4">
                        <a class="d-flex flex-center text-decoration-none mb-4" href="{{ url('/') }}">
                            <div class="d-flex align-items-center fw-bolder fs-5 d-inline-block">
                                @include('partials.logo', ['alto' => 56, 'claseTexto' => 'mb-0'])
                            </div>
                        </a>
                    </div>
                    @yield('contenido')
                </div>
            </div>
        </div>
    </div>
</main>
@include('partials.scripts')
@stack('scripts')
</body>
</html>
