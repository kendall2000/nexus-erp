<?php
session_start();

function getMyUrl() {
    $is_local = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
    $protocol = $is_local ? 'http' : 'https';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}

$server = getMyUrl();
$is_local = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
$base_path = $is_local ? "/General" : "";

if (!isset($_SESSION["link1"]) || empty($_SESSION["link1"])) {
    $_SESSION["link1"] = "$server/sistema/Inicio/Dashboard";
}

if (isset($_SESSION['codEmpleado'])) {
    header("Location: $server$base_path/sistema/Inicio/Dashboard");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <?php echo "<script>var server = '$server';</script>"; ?>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?php echo $server . $base_path; ?>/FrontEnd/Plantilla/public/pages/login/">
    <title>GTS - Login</title>

    <link rel="apple-touch-icon" sizes="180x180" href="https://cursosprogramacion.s3.us-east-2.amazonaws.com/LogosCursos/iconoGTS.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://cursosprogramacion.s3.us-east-2.amazonaws.com/LogosCursos/iconoGTS.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://cursosprogramacion.s3.us-east-2.amazonaws.com/LogosCursos/iconoGTS.png">
    
    <script src="../../vendors/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="../../vendors/simplebar/simplebar.min.js"></script>
    <script src="../../assets/js/config.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&amp;display=swap" rel="stylesheet">
    <link href="../../vendors/simplebar/simplebar.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <link href="../../assets/css/theme-rtl.min.css" type="text/css" rel="stylesheet" id="style-rtl">
    <link href="../../assets/css/theme.min.css" type="text/css" rel="stylesheet" id="style-default">
    <link href="../../assets/css/user-rtl.min.css" type="text/css" rel="stylesheet" id="user-style-rtl">
    <link href="../../assets/css/user.min.css" type="text/css" rel="stylesheet" id="user-style-default">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        [v-cloak] { display: none; }
        .bg-holder-login {
            background-position: center;
            background-size: cover;
            transition: background-image 0.5s ease-in-out;
        }
    </style>
</head>

<body>
    <main class="main" id="login-app" v-cloak>
        <div class="row vh-100 g-0">
            <div class="col-lg-6 position-relative d-none d-lg-block">
                <div class="bg-holder bg-holder-login" :style="{ backgroundImage: 'url(' + config.imgFondoLogin + ')' }"></div>
            </div>

            <div class="col-lg-6">
                <div class="row flex-center h-100 g-0 px-4 px-sm-0">
                    <div class="col col-sm-6 col-lg-7 col-xl-6">
                        <div class="text-center mb-4">
                            <img :src="config.imgLogo" alt="Logo" width="200" class="mb-4" />
                            <h3 class="text-1000">{{ config.loginTitulo }}</h3>
                            <p class="text-700">{{ config.loginSubtitulo }}</p>
                        </div>

                        <div class="position-relative">
                            <hr class="bg-200 mt-5 mb-4" />
                            <div class="divider-content-center">Ingresar credenciales</div>
                        </div>

                        <div class="mb-3 text-start">
                            <label class="form-label" for="codEmpleado">{{ config.loginLabelUsuario }}</label>
                            <div class="form-icon-container">
                                <input class="form-control form-icon-input" 
                                    id="codEmpleado" 
                                    v-model="datos.codEmpleado" 
                                    type="text" 
                                    :placeholder="config.loginPlaceholderUs" />
                                <span class="fas fa-user text-900 fs--1 form-icon"></span>
                            </div>
                        </div>

                        <div class="mb-3 text-start">
                            <label class="form-label" for="password">{{ config.loginLabelPassword }}</label>
                            <div class="form-icon-container">
                                <input class="form-control form-icon-input" 
                                    id="password" 
                                    v-model="datos.password" 
                                    type="password" 
                                    placeholder="Password" 
                                    @keyup.enter="loginUsuario" />
                                <span class="fas fa-key text-900 fs--1 form-icon"></span>
                            </div>
                        </div>

                        <div class="row flex-between-center mb-7">
                            <div class="col-auto">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" id="basic-checkbox" type="checkbox" checked="checked" />
                                    <label class="form-check-label mb-0" for="basic-checkbox">{{ config.loginLabelRecordar }}</label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <a class="fs--1 fw-semi-bold" :href="'resetpassword?path=Vertical'">{{ config.loginLinkOlvide }}</a>
                            </div>
                        </div>

                        <button id="btnLogin" 
                                type="button" 
                                class="btn btn-primary w-100 mb-3" 
                                @click="loginUsuario"
                                :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                            {{ config.loginTextBoton }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../vendors/popper/popper.min.js"></script>
    <script src="../../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../../vendors/anchorjs/anchor.min.js"></script>
    <script src="../../vendors/is/is.min.js"></script>
    <script src="../../vendors/fontawesome/all.min.js"></script>
    <script src="../../vendors/lodash/lodash.min.js"></script>
    <script src="../../vendors/list.js/list.min.js"></script>
    <script src="../../vendors/feather-icons/feather.min.js"></script>
    <script src="../../vendors/dayjs/dayjs.min.js"></script>
    <script src="../../assets/js/phoenix.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>

    <script>
        const isLocal = window.location.hostname === 'localhost';
        const basePath = isLocal ? '/General' : '';

        new Vue({
            el: "#login-app",
            data: {
                loading: false,
                config: {}, // Aquí se guardará la respuesta de configuracionsistema
                datos: { codEmpleado: '', password: '' }
            },
            async created() {
                await this.cargarConfiguracion();
            },
            methods: {
                async cargarConfiguracion() {
                    try {
                        const response = await fetch(`${server}${basePath}/services/condominios/Configuraciones/configuracionsistema`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ accion: "R", tipo: "login" })
                        });
                        const res = await response.json();
                        if (res.status === 200 && res.data.length > 0) {
                            this.config = res.data[0];
                        }
                    } catch (error) {
                        console.error('Error cargando configuración:', error);
                    }
                },
                async loginUsuario() {
                    if (!this.datos.codEmpleado || !this.datos.password) {
                        this.showError("Por favor complete todos los campos");
                        return;
                    }

                    this.loading = true;
                    const payload = {
                        password: btoa(this.datos.password),
                        codEmpleado: btoa(this.datos.codEmpleado)
                    };

                    try {
                        const response = await fetch(`${server}${basePath}/services/condominios/Login/comprobarUsuario`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });

                        const data = await response.json();
                        this.handleResponse(data);
                    } catch (error) {
                        this.showError("Error al conectar con el servicio de autenticación");
                    } finally {
                        this.loading = false;
                    }
                },
                handleResponse(data) {
                    switch (parseInt(data.correcto)) {
                        case 1:
                            this.showError(data.mensaje);
                            this.datos.codEmpleado = '';
                            this.datos.password = '';
                            break;
                        case 2:
                            this.showError(data.mensaje);
                            this.datos.password = '';
                            break;
                        case 3:
                            this.showPasswordResetPrompt(data.mensaje, data.restpassword);
                            break;
                        case 4:
                            window.location.href = "<?php echo $_SESSION['link1']; ?>";
                            break;
                    }
                },
                showError(message) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: message,
                        confirmButtonColor: this.config.colorPrimario || '#2A7BE4'
                    });
                },
                showPasswordResetPrompt(message, restpassword) {
                    Swal.fire({
                        title: 'Password',
                        text: message,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, estoy seguro',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = server + restpassword;
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>