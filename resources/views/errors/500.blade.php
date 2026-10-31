<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head', ['titulo' => 'Error del servidor'])
</head>
<body>
<main class="main" id="top">
    <div class="px-3">
        <div class="row min-vh-100 flex-center p-5">
            <div class="col-12 col-xl-10 col-xxl-8">
                <div class="row justify-content-center align-items-center g-5">
                    <div class="col-12 col-lg-6 text-center order-lg-1">
                        <img class="img-fluid w-lg-100 d-light-none" src="{{ asset('assets/img/spot-illustrations/500-illustration.png') }}" alt="500" width="400" />
                        <img class="img-fluid w-md-50 w-lg-100 d-dark-none" src="{{ asset('assets/img/spot-illustrations/dark_500-illustration.png') }}" alt="500" width="540" />
                    </div>
                    <div class="col-12 col-lg-6 text-center text-lg-start">
                        <img class="img-fluid mb-6 w-50 w-lg-75 d-dark-none" src="{{ asset('assets/img/spot-illustrations/500.png') }}" alt="500" />
                        <img class="img-fluid mb-6 w-50 w-lg-75 d-light-none" src="{{ asset('assets/img/spot-illustrations/dark_500.png') }}" alt="500" />
                        <h2 class="text-800 fw-bolder mb-3">¡Error inesperado!</h2>
                        <p class="text-900 mb-5">Ocurrió un inconveniente al procesar tu solicitud. Intenta nuevamente en unos momentos.</p>
                        <a class="btn btn-lg btn-primary" href="{{ url('/sistema/dashboard') }}"><span class="fas fa-home me-2"></span>Ir al inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@include('partials.scripts')
</body>
</html>
