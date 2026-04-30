<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexusERP — Iniciar Sesión</title>

    <script>
        const server = '{{ str_replace("http://", "https://", url("/")) }}';
        const apiUrl = server + '/api/v1';
    </script>

    <base href="{{ url('/') }}/Plantilla/public/pages/login/">

    <link rel="icon" type="image/png" href="{{ url('/') }}/Plantilla/public/assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="../../vendors/simplebar/simplebar.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <link href="../../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../../assets/css/user.min.css" rel="stylesheet" id="user-style-default">

    <script src="../../vendors/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="../../vendors/simplebar/simplebar.min.js"></script>
    <script src="../../assets/js/config.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>

    <style>
        [v-cloak] { display: none; }

        /* Skeleton loader para mientras carga la config */
        .skeleton {
            background: linear-gradient(90deg, #e8e8e8 25%, #f5f5f5 50%, #e8e8e8 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
            display: inline-block;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Color primario dinámico via CSS variable */
        :root {
            --color-primario: v-bind(config.colorPrimario);
        }
    </style>
</head>

<body>
<main class="main" id="login-app" v-cloak>
    <div class="row vh-100 g-0">

        {{-- ── Panel izquierdo — imagen dinámica ─────────────────── --}}
        <div class="col-lg-6 position-relative d-none d-lg-block">

            {{-- Fondo dinámico desde ConfiguracionSistema.imgFondoLogin --}}
            <div class="bg-holder"
                :style="{ backgroundImage: config.imgFondoLogin
                    ? 'url(' + config.imgFondoLogin + ')'
                    : 'url(../../assets/img/generic/authentication-bg.webp)' }">
            </div>

            {{-- Overlay con datos del sistema --}}
            <div class="position-absolute bottom-0 start-0 p-5 text-white" style="z-index:1; background: linear-gradient(transparent, rgba(0,0,0,0.7)); width:100%;">
                <img v-if="config.imgLogo"
                     :src="config.imgLogo"
                     alt="Logo"
                     style="max-height:50px; margin-bottom:1rem;"
                     class="d-block" />
                <h2 class="fw-bold mb-1">@{{ config.nombreSistema || 'NexusERP' }}</h2>
                <p class="mb-0 opacity-75">@{{ config.slogan || 'Gestión empresarial sin fronteras' }}</p>
                <small class="opacity-50">@{{ config.nombreEmpresa }}</small>
            </div>
        </div>

        {{-- ── Panel derecho — formulario ─────────────────────────── --}}
        <div class="col-lg-6">
            <div class="row flex-center h-100 g-0 px-4 px-sm-0">
                <div class="col col-sm-6 col-lg-7 col-xl-6">

                    {{-- Logo + Título --}}
                    <div class="text-center mb-4">
                        {{-- Logo dinámico --}}
                        <div v-if="config.imgLogo" class="mb-3">
                            <img :src="config.imgLogo"
                                 :alt="config.nombreSistema"
                                 style="max-height:60px; max-width:200px;" />
                        </div>
                        {{-- Fallback texto si no hay logo --}}
                        <div v-else class="mb-3">
                            <h3 class="fw-bold text-primary mb-0">
                                Nexus<span class="text-dark">ERP</span>
                            </h3>
                        </div>

                        {{-- Textos dinámicos --}}
                        <h4 class="text-1000 mt-2">
                            @{{ config.loginTitulo || 'Iniciar Sesión' }}
                        </h4>
                        <p class="text-700">
                            @{{ config.loginMensajeBienve || 'Ingresa tus credenciales para continuar' }}
                        </p>
                    </div>

                    {{-- Alerta de error --}}
                    <div v-if="errorMsg"
                        class="alert alert-danger alert-dismissible fade show"
                        role="alert">
                        <span data-feather="alert-circle" class="me-2"></span>
                        @{{ errorMsg }}
                        <button type="button" class="btn-close" @click="errorMsg = ''"></button>
                    </div>

                    <div class="position-relative">
                        <hr class="bg-200 mt-4 mb-4" />
                        <div class="divider-content-center">
                            @{{ config.loginSubtitulo || 'Ingresa tus credenciales' }}
                        </div>
                    </div>

                    {{-- Campo usuario --}}
                    <div class="mb-3 text-start">
                        <label class="form-label" for="login">
                            @{{ config.loginLabelUsuario || 'Usuario o correo electrónico' }}
                        </label>
                        <div class="form-icon-container">
                            <input
                                class="form-control form-icon-input"
                                id="login"
                                v-model="datos.login"
                                type="text"
                                :placeholder="config.loginPlaceholderUs || 'usuario o correo@empresa.com'"
                                autocomplete="username"
                                @keyup.enter="loginUsuario" />
                            <span class="fas fa-user text-900 fs--1 form-icon"></span>
                        </div>
                    </div>

                    {{-- Campo contraseña --}}
                    <div class="mb-3 text-start">
                        <label class="form-label" for="password">
                            @{{ config.loginLabelPassword || 'Contraseña' }}
                        </label>
                        <div class="form-icon-container">
                            <input
                                class="form-control form-icon-input"
                                id="password"
                                v-model="datos.password"
                                :type="mostrarPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                @keyup.enter="loginUsuario" />
                            <span
                                class="fs--1 form-icon cursor-pointer"
                                :class="mostrarPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"
                                @click="mostrarPassword = !mostrarPassword"
                                style="cursor:pointer;">
                            </span>
                        </div>
                    </div>

                    {{-- Recordarme + Olvidé contraseña --}}
                    <div class="row flex-between-center mb-4">
                        <div class="col-auto">
                            <div class="form-check mb-0">
                                <input class="form-check-input"
                                    id="recordarme"
                                    type="checkbox"
                                    v-model="datos.remember" />
                                <label class="form-check-label mb-0" for="recordarme">
                                    @{{ config.loginLabelRecordar || 'Mantener sesión activa' }}
                                </label>
                            </div>
                        </div>
                        <div class="col-auto">
                            <a class="fs--1 fw-semi-bold" href="#">
                                @{{ config.loginLinkOlvide || '¿Olvidaste tu contraseña?' }}
                            </a>
                        </div>
                    </div>

                    {{-- Botón login dinámico --}}
                    <button
                        type="button"
                        class="btn btn-primary w-100 mb-3"
                        @click="loginUsuario"
                        :disabled="cargando || configurandose">
                        <span v-if="cargando"
                            class="spinner-border spinner-border-sm me-2"
                            role="status"
                            aria-hidden="true">
                        </span>
                        @{{ cargando
                            ? 'Verificando...'
                            : (config.loginTextBoton || 'Ingresar al sistema') }}
                    </button>

                    {{-- Footer del formulario --}}
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            @{{ config.nombreSistema || 'NexusERP' }}
                            @{{ config.version ? 'v' + config.version : 'v1.0' }}
                            — Centroamérica
                        </small>
                    </div>

                </div>
            </div>
        </div>

    </div>
</main>

{{-- Scripts Phoenix --}}
<script src="../../vendors/popper/popper.min.js"></script>
<script src="../../vendors/bootstrap/bootstrap.min.js"></script>
<script src="../../vendors/anchorjs/anchor.min.js"></script>
<script src="../../vendors/is/is.min.js"></script>
<script src="../../vendors/fontawesome/all.min.js"></script>
<script src="../../vendors/lodash/lodash.min.js"></script>
<script src="../../vendors/feather-icons/feather.min.js"></script>
<script src="../../assets/js/phoenix.js"></script>

<script>
new Vue({
    el: '#login-app',
    data: {
        // Estado
        cargando:       false,
        configurandose: true,   // true mientras carga la config
        errorMsg:       '',
        mostrarPassword:false,

        // Configuración dinámica desde la BD
        config: {
            nombreSistema:      'NexusERP',
            nombreEmpresa:      '',
            slogan:             '',
            version:            '1.0.0',
            loginTitulo:        'Bienvenido',
            loginSubtitulo:     'Ingresa tus credenciales',
            loginTextBoton:     'Ingresar al sistema',
            loginLabelUsuario:  'Usuario o correo electrónico',
            loginPlaceholderUs: 'usuario o correo@empresa.com',
            loginLabelPassword: 'Contraseña',
            loginLabelRecordar: 'Mantener sesión activa',
            loginLinkOlvide:    '¿Olvidaste tu contraseña?',
            loginMensajeBienve: 'Ingresa tus credenciales para continuar',
            colorPrimario:      '#6366f1',
            imgLogo:            null,
            imgFondoLogin:      null,
        },

        // Credenciales
        datos: {
            login:    '',
            password: '',
            remember: false,
        },
    },

    async created() {
        await this.cargarConfiguracion();
    },

    methods: {
        // ── Carga configuración desde la API ──────────────────────
        async cargarConfiguracion() {
            try {
                const res = await fetch(apiUrl + '/configuracion/login', {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' }
                });

                if (res.ok) {
                    const json = await res.json();
                    if (json.success && json.data) {
                        // Mezcla los valores de la BD con los defaults
                        this.config = Object.assign({}, this.config, json.data);

                        // Aplica el color primario dinámicamente
                        if (json.data.colorPrimario) {
                            document.documentElement.style.setProperty(
                                '--phoenix-primary', json.data.colorPrimario
                            );
                        }

                        // Aplica favicon dinámico
                        if (json.data.imgFavicon) {
                            const favicon = document.querySelector("link[rel='icon']");
                            if (favicon) favicon.href = json.data.imgFavicon;
                        }

                        // Título del navegador
                        document.title = (json.data.nombreSistema || 'NexusERP') + ' — Iniciar Sesión';
                    }
                }
            } catch(e) {
                console.warn('No se pudo cargar la configuración, usando valores por defecto.');
            } finally {
                this.configurandose = false;
            }
        },

        // ── Login ─────────────────────────────────────────────────
        async loginUsuario() {
            if (!this.datos.login.trim() || !this.datos.password) {
                this.errorMsg = 'Por favor completa todos los campos.';
                return;
            }

            this.cargando = true;
            this.errorMsg = '';

            try {
                const res = await fetch(apiUrl + '/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        login:    this.datos.login.trim(),
                        password: this.datos.password,
                        remember: this.datos.remember,
                    })
                });

                const data = await res.json();

                if (data.success) {
                    // Guarda token y usuario en sessionStorage
                    sessionStorage.setItem('nexus_token',   data.data.token);
                    sessionStorage.setItem('nexus_usuario', JSON.stringify(data.data.usuario));

                    // Guarda config para usarla en todo el sistema
                    sessionStorage.setItem('nexus_config', JSON.stringify(this.config));

                    // Redirige al dashboard
                    window.location.href = server + '/sistema/dashboard';
                } else {
                    this.errorMsg = data.message || 'Credenciales incorrectas.';
                }

            } catch(e) {
                this.errorMsg = 'Error al conectar con el servidor. Intenta nuevamente.';
                console.error('Error login:', e);
            } finally {
                this.cargando = false;
            }
        },
    }
});
</script>
</body>
</html>