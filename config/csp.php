<?php

/*
 * Política de seguridad de contenido (CSP), ver App\Support\PoliticaContenido.
 *  - reportar: el navegador no bloquea nada; solo avisa a /csp-reporte (storage/logs/csp.log).
 *  - aplicar:  el navegador bloquea lo que no cumpla la política.
 *  - apagado:  sin encabezado.
 */
return [
    'modo' => env('CSP_MODO', 'reportar'),
];
