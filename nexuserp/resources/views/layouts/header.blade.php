<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NexusERP</title>
    <base href="/Plantilla/public/">
    <!-- <base href="{{ url('/') }}/Plantilla/public/"> -->

    <script>
        const server     = window.location.protocol + '//' + window.location.host;
        const basePath   = '';
        const servidor   = server + basePath;
        const apiUrl     = server + '/api/v1';
        const nexusToken = sessionStorage.getItem('nexus_token') || '';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>

    <link rel="icon" type="image/png" href="{{ url('/') }}/Plantilla/public/assets/img/favicon.png">

    {{-- Phoenix CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vue-select@3.20.2/dist/vue-select.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">

    <link href="vendors/flatpickr/flatpickr.min.css" rel="stylesheet">
    <link href="vendors/simplebar/simplebar.min.css" rel="stylesheet">
    <link href="vendors/choices/choices.min.css" rel="stylesheet">
    <link href="vendors/leaflet/leaflet.css" rel="stylesheet">
    <link href="assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    <link href="assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="https://cdn.datatables.net/v/bs5/jq-3.7.0/jszip-3.10.1/dt-1.13.8/b-2.4.2/b-html5-2.4.2/sl-1.7.0/datatables.min.css" rel="stylesheet">

    {{-- Scripts base --}}
    <script src="vendors/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="vendors/simplebar/simplebar.min.js"></script>
    <script src="vendors/dayjs/dayjs.min.js"></script>
    <script src="assets/js/config.js"></script>

    <script>
        // RTL/LTR automático
        // const phoenixIsRTL = window.config?.config?.phoenixIsRTL;
        // if (phoenixIsRTL) {
        //     document.getElementById('style-default').disabled    = true;
        //     document.getElementById('user-style-default').disabled = true;
        //     document.documentElement.setAttribute('dir', 'rtl');
        // } else {
        //     document.getElementById('style-rtl').disabled      = true;
        //     document.getElementById('user-style-rtl').disabled = true;
        // }
        if (!window.location.pathname.includes('/login')) {
            document.documentElement.classList.add('nexus-loading');
        }
    </script>

    <style>
        /* Anti-FOUC: oculta el body mientras valida sesión */
        html.nexus-loading body {
            visibility: hidden !important;
            opacity: 0;
        }
        html body {
            visibility: visible;
            opacity: 1;
            transition: opacity 0.15s ease-in;
        }

        /* ✅ FIX: Footer sticky correcto */
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1 0 auto;
            padding-bottom: 4rem; /* Espacio reservado para el footer */
        }
        .footer {
            flex-shrink: 0;
            position: relative !important; /* ⚠️ Quitamos absolute */
            margin-top: auto;
            padding: 1rem 0;
        }

        .table { width: 100% !important; }
        [v-cloak] { display: none; }
    </style>

    @stack('styles')
</head>
<body>
<main class="main" id="top">