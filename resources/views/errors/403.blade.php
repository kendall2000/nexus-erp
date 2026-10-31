<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head', ['titulo' => 'Acceso denegado'])
</head>
<body>
<main class="main" id="top">
    <div class="px-3">
        <div class="row min-vh-100 flex-center p-5">
            <div class="col-12 col-xl-10 col-xxl-8">
                <div class="row justify-content-center align-items-center g-5">
                    <div class="col-12 col-lg-6 text-center order-lg-1">
                        <img class="img-fluid w-lg-100 d-dark-none" src="{{ asset('assets/img/spot-illustrations/403-illustration.png') }}" alt="403" width="400" />
                        <img class="img-fluid w-md-50 w-lg-100 d-light-none" src="{{ asset('assets/img/spot-illustrations/dark_403-illustration.png') }}" alt="403" width="540" />
                    </div>
                    <div class="col-12 col-lg-6 text-center text-lg-start">
                        <img class="img-fluid mb-6 w-50 w-lg-75 d-dark-none" src="{{ asset('assets/img/spot-illustrations/403.png') }}" alt="403" />
                        <img class="img-fluid mb-6 w-50 w-lg-75 d-light-none" src="{{ asset('assets/img/spot-illustrations/dark_403.png') }}" alt="403" />
                        <h2 class="text-800 fw-bolder mb-3">¡Acceso restringido!</h2>
                        <p class="text-900 mb-5">{{ $exception->getMessage() ?: 'No tienes permisos para abrir esta pantalla. Si necesitas acceso, solicítalo al administrador del sistema.' }}</p>
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
