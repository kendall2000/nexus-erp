<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head')
    @stack('estilos')
</head>
<body>
@php $cfg = \App\Support\Sistema::config(); @endphp
<main class="main" id="top">
    <div class="container-fluid bg-300 dark__bg-1200">
        <div class="bg-holder bg-auth-card-overlay" style="background-image:url({{ $cfg->imgFondoLogin ?: asset('assets/img/bg/37.png') }});"></div>
        <!--/.bg-holder-->

        <div class="row flex-center position-relative min-vh-100 g-0 py-5">
            <div class="col-11 col-sm-10 col-xl-8">
                <div class="card border border-200 auth-card">
                    <div class="card-body pe-md-0">
                        <div class="row align-items-center gx-0 gy-7">
                            <div class="col-auto bg-100 dark__bg-1100 rounded-3 position-relative overflow-hidden auth-title-box">
                                <div class="bg-holder" style="background-image:url({{ asset('assets/img/bg/38.png') }});"></div>
                                <!--/.bg-holder-->

                                <div class="position-relative px-4 px-lg-7 pt-7 pb-7 pb-sm-5 text-center text-md-start pb-lg-7 pb-md-7">
                                    <h3 class="mb-3 text-black dark__text-white fs-1">{{ \App\Support\Sistema::nombre() }}</h3>
                                    <p class="text-700">{{ $cfg->slogan ?: 'Gestiona clientes, operaciones, inventario, finanzas y recursos humanos desde un solo lugar.' }}</p>
                                    <ul class="list-unstyled mb-0 w-max-content w-md-auto mx-auto">
                                        <li class="d-flex align-items-center"><span class="uil uil-check-circle text-success me-2"></span><span class="text-700 fw-semi-bold">Rápida</span></li>
                                        <li class="d-flex align-items-center"><span class="uil uil-check-circle text-success me-2"></span><span class="text-700 fw-semi-bold">Simple</span></li>
                                        <li class="d-flex align-items-center"><span class="uil uil-check-circle text-success me-2"></span><span class="text-700 fw-semi-bold">Segura</span></li>
                                    </ul>
                                </div>
                                <div class="position-relative z-index--1 mb-6 d-none d-md-block text-center mt-md-15">
                                    <img class="auth-title-box-img d-dark-none" src="{{ asset('assets/img/spot-illustrations/auth.png') }}" alt="{{ \App\Support\Sistema::nombre() }}" />
                                    <img class="auth-title-box-img d-light-none" src="{{ asset('assets/img/spot-illustrations/auth-dark.png') }}" alt="{{ \App\Support\Sistema::nombre() }}" />
                                </div>
                            </div>
                            <div class="col mx-auto">
                                <div class="auth-form-box">
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
                </div>
            </div>
        </div>
    </div>
</main>
@include('partials.scripts')
@stack('scripts')
</body>
</html>
