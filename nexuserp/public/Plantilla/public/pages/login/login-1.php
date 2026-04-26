<?php
session_start();
function getMyUrl()
{
  $protocol = (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) ? 'https://' : 'http://';
  $server = $_SERVER['SERVER_NAME'];
  $port = $_SERVER['SERVER_PORT'] ? ':' . $_SERVER['SERVER_PORT'] : '';
  return $protocol . $server . $port;
}
$server = getMyUrl();
if (!isset($_SESSION["link1"]) || strcasecmp($_SESSION["link1"], "") == 0)
  $_SESSION["link1"] = "$server/General/sistema/Inicio/Dashboard";

if (isset($_SESSION['codEmpleado'])) {
 header("location:$server/General/sistema/Inicio/Dashboard");
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <?php echo "<script>var server = '$server';</script>"; ?>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?php echo $server; ?>/General/FrontEnd/Plantilla/public/pages/login/">
  <!-- ===============================================-->
  <!--    Document Title-->
  <!-- ===============================================-->
  <title>Login</title>
  <!-- ===============================================-->
  <!--    Favicons-->
  <!-- ===============================================-->
  <link rel="apple-touch-icon" sizes="180x180" href="../../assets/img/favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../assets/img/favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../../assets/img/favicons/favicon-16x16.png">
  <link rel="shortcut icon" type="image/x-icon" href="../../assets/img/favicons/favicon.ico">
  <link rel="manifest" href="../../assets/img/favicons/manifest.json">
  <meta name="msapplication-TileImage" content="../../assets/img/favicons/mstile-150x150.png">
  <meta name="theme-color" content="#ffffff">
  <script src="../../vendors/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="../../vendors/simplebar/simplebar.min.js"></script>
  <script src="../../assets/js/config.js"></script>
  <!-- ===============================================-->
  <!--    Stylesheets-->
  <!-- ===============================================-->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&amp;display=swap"
    rel="stylesheet">
  <link href="../../vendors/simplebar/simplebar.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
  <link href="../../assets/css/theme-rtl.min.css" type="text/css" rel="stylesheet" id="style-rtl">
  <link href="../../assets/css/theme.min.css" type="text/css" rel="stylesheet" id="style-default">
  <link href="../../assets/css/user-rtl.min.css" type="text/css" rel="stylesheet" id="user-style-rtl">
  <link href="../../assets/css/user.min.css" type="text/css" rel="stylesheet" id="user-style-default">

  <script>
    var phoenixIsRTL = window.config.config.phoenixIsRTL;
    if (phoenixIsRTL) {
      var linkDefault = document.getElementById('style-default');
      var userLinkDefault = document.getElementById('user-style-default');
      linkDefault.setAttribute('disabled', true);
      userLinkDefault.setAttribute('disabled', true);
      document.querySelector('html').setAttribute('dir', 'rtl');
    } else {
      var linkRTL = document.getElementById('style-rtl');
      var userLinkRTL = document.getElementById('user-style-rtl');
      linkRTL.setAttribute('disabled', true);
      userLinkRTL.setAttribute('disabled', true);
    }
  </script>
</head>

<body>
  <main class="form-signin" id="top">
    <form method="POST">
      <div class="container">
        <div class="row flex-center min-vh-100 py-5">
          <div class="col-sm-10 col-md-8 col-lg-5 col-xl-5 col-xxl-3"><a
              class="d-flex flex-center text-decoration-none mb-4" href="FrontEnd/Planilla/public/index.html">
              <div class="d-flex align-items-center fw-bolder fs-5 d-inline-block"><img
                  src="../../assets/img/icons/logo.png" alt="phoenix" width="58" />
              </div>
            </a>
            <div class="text-center mb-7">
              <h3 class="text-1000">Inicia Sesion</h3>
              <p class="text-700">Accede a tu Cuenta</p>
            </div>
            <div class="position-relative">
              <hr class="bg-200 mt-5 mb-4" />
              <div class="divider-content-center">Ingresar credenciales</div>
            </div>
            <div id="login-form">
              <div class="mb-3 text-start">
                <label class="form-label" for="email">Correo Electronico/Usuario</label>
                <div class="form-icon-container">
                  <input class="form-control form-icon-input" id="codEmpleado"
                    name='codEmpleado' type="text" placeholder="name@example.com" />
                  <span class="fas fa-user text-900 fs--1 form-icon"></span>
                </div>
              </div>
              <div class="mb-3 text-start">
                <label class="form-label" for="password">Password</label>
                <div class="form-icon-container">
                  <input class="form-control form-icon-input" name='password' id="password"
                    type="password" placeholder="Password" /><span class="fas fa-key text-900 fs--1 form-icon"></span>
                </div>
              </div>
              <div class="row flex-between-center mb-7">
<!--                <div class="col-auto">-->
<!--                  <div class="form-check mb-0">-->
<!--                    <input class="form-check-input" id="basic-checkbox" type="checkbox" checked="checked" />-->
<!--                    <label class="form-check-label mb-0" for="basic-checkbox">Remember me</label>-->
<!--                  </div>-->
<!--                </div>-->
                <div class="col-auto"><a class="fs--1 fw-semi-bold"
                    href="<?php echo $server ;?>/General/resetpassword?path=Vertical">Olvidé mi contraseña</a></div>
              </div>
              <button id="btnLogin" type="button" class="btn btn-primary w-100 mb-3" style="width: 100%" onclick="loginUsuario()">Iniciar Sesion</button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </main>
  <script src="../../vendors/popper/popper.min.js"></script>
  <script src="../../vendors/bootstrap/bootstrap.min.js"></script>
  <script src="../../vendors/anchorjs/anchor.min.js"></script>
  <script src="../../vendors/is/is.min.js"></script>
  <script src="../../vendors/fontawesome/all.min.js"></script>
  <script src="../../vendors/lodash/lodash.min.js"></script>
  <script src="https://polyfill.io/v3/polyfill.min.js?features=window.scroll"></script>
  <script src="../../vendors/list.js/list.min.js"></script>
  <script src="../../vendors/feather-icons/feather.min.js"></script>
  <script src="../../vendors/dayjs/dayjs.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../assets/js/phoenix.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>
<!--  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/crypto-js.js"></script>-->

  <script>
    var vApp = new Vue({
      el: "#login-form",
      data: {
        users: [],
        datos: { codEmpleado: '', password: '', nombreEmpleado: '', apellidoEmpleado: '' }

      }
      ,
      async created() {
        // cargarEmpresas();\
      }
    })

    var input = document.getElementById("password");
    input.addEventListener("keypress", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        document.getElementById("btnLogin").click();
      }
    });

    async function loginUsuario() {
        const passwordField = document.getElementById('password');
        const codEmpleadoField = document.getElementById('codEmpleado');

        const encodedPassword = btoa(passwordField.value);
        const encodedCodEmpleado = btoa(codEmpleadoField.value);

        const payload = {
            password: encodedPassword,
            codEmpleado: encodedCodEmpleado
        };

        try {
            const response = await fetch(`${server}/General/services/Quickdealivery/Login/comprobarUsuario`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            handleResponse(data);
        } catch (error) {
            console.error('Error during fetch:', error);
            // Additional error handling can be implemented here
        }
    }

    function handleResponse(data) {
        switch (data.correcto) {
            case 1:
                showError(data.mensaje);
                clearFields();
                break;
            case 2:
                showError(data.mensaje);
                document.getElementById('password').value = '';
                break;
            case 3:
                showPasswordResetPrompt(data.mensaje, data.restpassword);
                break;
            case 4:
                window.location.href = "<?php echo $_SESSION["link1"]; ?>";
                break;
            default:
                console.warn('Unexpected response:', data);
        }
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: message,
        });
    }

    function clearFields() {
        document.getElementById('codEmpleado').value = '';
        document.getElementById('password').value = '';
    }

    function showPasswordResetPrompt(message, restpassword) {
        Swal.fire({
            title: 'Password',
            text: message,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, estoy seguro',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirige para restablecer la contraseña
                window.location.href = server + restpassword;
            }
        });
    }

    //async function login() {
    //  const response = await fetch(server + "/General/services/Quickdealivery/Login/comprobarLogin", {
    //    method: 'POST',
    //    body: JSON.stringify(vApp._data.datos)
    //  });
    //  const data = await response.json();
    //  //comprobar que login sea correcto y redirigir
    //  if (data.correcto === 1) {
    //   window.location.href = '<?php //echo $_SESSION["link1"]; ?>//';
    //
    //  } else {
    //    swal({
    //      icon: 'error',
    //      title: 'Oops...',
    //      text: 'Tu contraseña o usuario son incorrectos!',
    //    });
    //    $('#username').val('')
    //    $('#password').val('')
    //  }
    //
    //}
  </script>

</body>

</html>