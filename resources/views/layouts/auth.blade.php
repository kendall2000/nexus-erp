<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head')
</head>
<body>
<main class="main" id="top">
    <div class="row vh-100 g-0">
        <div class="col-lg-6 position-relative d-none d-lg-block">
            <div class="bg-holder" style="background-image:url({{ \App\Support\Sistema::config()->imgFondoLogin ?: asset('assets/img/bg/30.png') }}); background-size: cover; background-position: center;"></div>
            @if (\App\Support\Sistema::config()->slogan)
                <div class="position-absolute bottom-0 start-0 w-100 p-5 text-white" style="z-index: 1; background: linear-gradient(transparent, rgba(0,0,0,.65));">
                    <h2 class="fw-bold text-white mb-1">{{ \App\Support\Sistema::nombre() }}</h2>
                    <p class="mb-0 opacity-75">{{ \App\Support\Sistema::config()->slogan }}</p>
                </div>
            @endif
        </div>
        <div class="col-lg-6">
            <div class="row flex-center h-100 g-0 px-4 px-sm-0">
                <div class="col col-sm-6 col-lg-7 col-xl-6">
                    <a class="d-flex flex-center text-decoration-none mb-4" href="{{ url('/') }}">
                        <div class="d-flex align-items-center fw-bolder fs-5 d-inline-block">
                            @include('partials.logo', ['alto' => 56, 'claseTexto' => 'mb-0'])
                        </div>
                    </a>
                    @yield('contenido')
                </div>
            </div>
        </div>
    </div>
</main>
@include('partials.scripts')
</body>
</html>
