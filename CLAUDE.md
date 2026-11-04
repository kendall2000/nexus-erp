# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Nexus ERP: Laravel 13 (PHP 8.3+) multiempresa para compras, inventario, clientes y finanzas. Todo el código, los nombres, los mensajes y los commits van **en español**. La aplicación vive directamente en la raíz del repositorio (`nexus-erp/`). Se trabaja en la rama `dev` (`main` es la principal).

## Comandos

```bash
php artisan config:clear && php artisan test          # todas las pruebas (composer test hace lo mismo)
php artisan test --filter=FacturasTest                # un archivo de pruebas
php artisan test --filter=test_emitir_ejecuta_presupuesto_y_anular_lo_revierte   # una prueba
php artisan migrate --force                           # aplicar migraciones (ver «Base de datos»)
vendor/bin/pint                                       # formato (Laravel Pint)
php artisan serve                                     # servidor local (composer dev hace lo mismo)
```

- Correr `config:clear` antes de las pruebas: una config en caché apunta a MySQL y las pruebas deben usar SQLite en memoria (`phpunit.xml`).
- No hay build de front-end: no hay npm ni Vite. Los assets de la plantilla Phoenix se sirven tal cual desde `public/assets` y `public/vendors` (ECharts, Bootstrap, FontAwesome, Feather…). Los JS y CSS propios se enlazan con `\App\Support\Sistema::recurso('assets/js/…')`, que agrega `?v=` con la fecha del archivo para que el navegador no use los viejos tras un despliegue.
- Despliegue con Docker (`Dockerfile`, `docker-entrypoint.sh`, `docker-compose.yml`): el entrypoint cachea config, rutas, vistas y eventos, y solo migra si `RUN_MIGRATIONS=true` (el cron del scheduler, con `ENABLE_SCHEDULER=true`). No hay Docker en la máquina de desarrollo: se construye en el servidor.

## Arquitectura

**Todo es Blade renderizado en el servidor con sesión.** No hay API, tokens ni SPA: se eliminaron `/api/v1`, Sanctum y spatie/laravel-permission. No volver a introducirlos.

- **Vue (solo dos pantallas):** `vendors/vue/vue.global.prod.js` (sin compilación) se usa únicamente en las líneas del formulario de facturas (`FacturaLineas`) y de órdenes de compra (`OrdenCompraLineas`), en `public/assets/js/transaccionales-vue.js`. `public/assets/js/app-vue.js` trae además `NexusToast` (avisos) y `NexusHtml.escapar` (escapar texto antes de meterlo en HTML: usarlo siempre en JS que arme HTML). No extender Vue a otras pantallas.

- **Autenticación:** Laravel Fortify (`app/Providers/FortifyServiceProvider.php`): login por usuario o correo (`Seguridad::buscarUsuario`), recuperar y cambiar contraseña, verificación en dos pasos, sesiones en BD. Las reglas de bloqueo (`maxIntentosSesion`, `bloqueoMinutos`, `sesionExpiraMin`) se leen de la tabla `ConfiguracionSistema`. Cada evento de acceso se registra en `auditoria_acceso` vía `Seguridad::registrar` (su columna `accion` es un enum cerrado).
- **Middlewares web** (`bootstrap/app.php`), en orden: `EncabezadosSeguridad` (encabezados y CSP), `PreventBackHistory`, `LimitarRecuperacion` (recuperar contraseña por IP y por correo), `UsuarioActivo` (expulsa a desactivados), `EmpresaActiva` (ver «Multiempresa»; antes de los permisos), `ContrasenaVencida` y `RequiereDosFactores` (si el rol exige 2FA, solo deja entrar a «Seguridad de mi cuenta»). Alias de ruta: `admin`, `admin:general` y `permiso`.
- **CSP** (`App\Support\PoliticaContenido`, `config/csp.php`): `CSP_MODO` = `reportar` (por defecto: el navegador solo avisa a `POST /csp-reporte`, que anota en `storage/logs/csp.log`), `aplicar` o `apagado`. Solo permite recursos propios, Google Fonts, unicons e imágenes de Contabo; todavía con `'unsafe-inline'` (hay `<script>` y `on…=` en las vistas) y `'unsafe-eval'` (Vue). No cargar scripts ni estilos de otros dominios ni hacer `fetch` fuera del sistema sin agregarlos a la política. Al escribir JS nuevo, preferir un archivo propio o un `<script>` en `@push('scripts')` y evitar `onclick=`/`onsubmit=` en el HTML (la fase 2 quitará `'unsafe-inline'`).
- **Permisos:** `modulo` (cada opción del menú lateral) × `accion` (ver, crear, editar, eliminar, exportar, anular, cancelar, asignar, configurar, gestionar, imprimir, aprobar, cobrar, condonar, devolver, cerrar, reabrir, procesar) = `permiso`. Se asignan por `rol_permiso` y como extras por `usuario_permiso`. Código `modulo.accion` en minúsculas (`ordenes_compra.aprobar`). `Usuario::puede($codigo)` = acceso total, o los roles activos **de la empresa activa** + los extras (los extras solo cuentan en su empresa principal).
  - Proteger cada ruta con `->middleware('permiso:modulo.accion')`; con `|` basta cualquiera (`permiso:pagos.crear|facturas.cobrar`). En Blade: `auth()->user()->puede(...)` para mostrar u ocultar botones.
  - Acceso total: roles `Rol::ACCESO_TOTAL` (Administrador, Superadmin, Super Administrador), comparados con `Rol::normalizarNombre` (sin acentos ni espacios). Anti-escalada con `Usuario::puedeGestionar` y `puedeAsignarRol`.
  - **Administrador general** (`Usuario::esAdministradorGeneral`, middleware `admin:general`): acceso total en su empresa principal. Solo él cambia lo que comparten todas las empresas: configuración del sistema (`ConfiguracionSistema` es una sola tabla para todas), reglas de sesión y de contraseñas, módulos, geografía, las **empresas** (`sistema/empresas`: se crean con su casa matriz y con los catálogos copiados de una empresa modelo por `App\Support\CatalogosEmpresa`; no se eliminan, se desactivan) y los «Accesos a otras empresas» de los usuarios. `MenuLateral::SOLO_GENERAL` oculta esas pantallas a los demás. Quien tiene acceso total solo en otra empresa (`admin`) administra esa empresa.
  - Un módulo sin permisos es solo del administrador. El menú (`App\Support\MenuLateral`) muestra los módulos activos con `ver`.
  - Los códigos de módulo y acción ya existen en BD (migraciones `2026_09_30_000001` y `000002`): al crear una pantalla, usar los existentes en lugar de inventar otros.
- **Multiempresa:** cada organización tiene su propio despliegue y BD, con varias empresas dentro. La columna `usuario.id_empresa` es su **empresa principal**; puede trabajar además en las empresas donde tiene un rol activo (`rol.id_empresa`), y con acceso total en la principal, en todas (`Usuario::empresasDisponibles`). La empresa en la que trabaja se elige en la barra superior y vive en la sesión (`empresa_activa`): el middleware `EmpresaActiva` la aplica en memoria con `Usuario::usarEmpresa`, así que `$request->user()->id_empresa` ya es la **empresa activa** (no se guarda en la BD; `idEmpresaPrincipal()` da la columna). `puedeEn($idEmpresa, $permiso)` consulta permisos en otra empresa sin cambiar la sesión (reporte consolidado, `sistema/consolidado`).
  - **Datos de cada empresa en sus documentos:** logo (`empresa.logo_url`), régimen fiscal, sitio web y leyenda de factura se editan en `sistema/empresas`. `x-print-layout` y el PDF de órdenes de compra usan los de la empresa y, si faltan, los del consorcio (`Empresa::logoDocumentos()`). El logo y los colores del sistema siguen siendo del consorcio.
  - **Sucursales y agencias** (`sucursal.tipo` = `SUCURSAL` | `AGENCIA`): una agencia depende de una sucursal activa (`id_sucursal_padre`); la casa matriz es siempre una sucursal. `codigo_establecimiento` es el número de la SAT (FEL), único por empresa. Para los selects, `Sucursal::paraSelect($idEmpresa)` (cada sucursal seguida de sus agencias) y `etiqueta()`.
  - **Sede de los documentos:** facturas, órdenes de compra y tickets llevan `id_sucursal` (los anteriores quedan vacíos: «Sin sucursal»). `App\Support\Sedes` da la regla de validación, la sede propuesta (la del usuario o la casa matriz) y el filtro `?sucursal=` (un id o `sin`); vistas parciales `partials/campo-sede` y `partials/filtro-sede`. Una serie atada a un establecimiento (`serie_facturacion.id_sucursal`) fija la sede de sus facturas; la orden de compra sin sede toma la de su bodega. Resumen por sede en `sistema/por-sucursal` (`ReporteSucursalesController`; en el menú por `MenuLateral::CON_PERMISOS_DE_OTROS`).
  - Cada controlador filtra explícitamente con un `deMiEmpresa()` (`Modelo::query()->where('id_empresa', $request->user()->id_empresa)`) y valida las llaves foráneas con `Rule::exists(...)->where('id_empresa', ...)`. No hay scope global: olvidar el filtro expone datos de otra empresa.
- **Controladores** (`app/Http/Controllers`, uno por módulo): `index / create / store / show / edit / update / estado / destroy`, más acciones de flujo (`aprobar`, `emitir`, `anular`…) y a menudo `exportar` e `imprimir`. Rutas en `routes/web.php` bajo `sistema/<modulo>`; los catálogos simples se registran en el bucle `$catalogos`, donde el permiso es la URL con `_` (`centros-costo` → `centros_costo.ver`). La ruta comodín `sistema/{any}` debe quedar siempre al final.
- **Lógica de negocio compartida en `app/Support`**, no en hooks de modelos:
  - `EjecucionPresupuesto`: ejecuta el presupuesto de forma simétrica. Aprobar una orden de compra suma y cancelarla resta; emitir una factura suma y anularla resta (solo si se había emitido); la nota de crédito resta. Siempre la base sin IVA, solo en presupuestos `APROBADO`. Cada documento queda anotado en `ejecucion_presupuesto`, y revertirlo resta exactamente lo anotado (no lo recalcula); `presupuesto:resync` rehace el ejecutado con las mismas reglas.
  - `PlanCuentas`: reglas del árbol de cuentas (padre de agrupación y del mismo tipo, sin ciclos, niveles).
  - `Referencias::enUso`: impide borrar registros en uso (el esquema no tiene todas las llaves foráneas). Usar `tabla.columna` si la columna se llama distinto.
  - `ExportarCsv`: CSV para Excel, con BOM, `;` y protección contra inyección de fórmulas.
  - `Archivos`: subida a Contabo. Públicos (disco `contabo`: logos, fotos de perfil) con `subir()`, guardando la URL completa. **Privados** (disco `contabo_privado`, `CONTABO_BUCKET_PRIVADO`: expediente de empleados y cualquier documento personal o sensible) con `subirPrivado()`, guardando solo la ruta; se muestran con `Archivos::respuesta()` desde una ruta protegida por permiso y empresa (ej. `empleados.documentos.show`). Nunca subir documentos sensibles al disco público.
  - `Sistema`: nombre, logo y colores desde `ConfiguracionSistema`; `recurso()` para enlazar JS y CSS propios con versión.
  - `Seguridad` y `MatrizPermisos`: apoyo a autenticación y permisos.
- **Zona horaria:** la de Configuración (`ConfiguracionSistema.zonaHoraria`, por defecto `America/Guatemala`) la aplica `AppServiceProvider` a PHP y a la sesión de MySQL en cada petición. En pruebas, congelar la fecha con `Carbon::setTestNow()` si la lógica depende de «hoy» (asistencia y pagos no aceptan fechas futuras).
- **Ojo con `$request->validate()` + `[...]`:** los campos `nullable` enviados vacíos llegan como clave con `null`, y el operador `+` no los reemplaza. Para poner un valor por defecto sobre un campo validado, usar `$datos['campo'] ??= …` o `array_merge`, no `$datos + ['campo' => …]`.
- **Documentos con flujo:** se validan las transiciones dentro de `DB::transaction` con `lockForUpdate()` sobre el registro (y sobre la serie o factura que se consume) para evitar dobles aprobaciones, cobros o números repetidos.
  - Órdenes de compra: BORRADOR → ENVIADA (aprobada) → PARCIAL / RECIBIDA; se cancelan sin mercadería recibida.
  - Recepciones: cada línea crea una ENTRADA en `movimiento_inventario` (kardex, inmutable), cuyo hook actualiza `stock_bodega` y el costo promedio.
  - Facturas: el tipo sale de la serie y el número se toma de la serie bloqueada al crear el borrador; solo el último borrador de una serie se puede eliminar.
  - Pagos: nunca se borran; pasan a `REVERTIDO` o `DEVUELTO`. `Factura::recalcularCobro` recalcula saldo = total − pagos aplicados − condonado.
- **Vistas:** `resources/views/<modulo>/{index,form,show,imprimir}.blade.php` con `@extends('layouts.app', ['titulo' => …])` y `@section('contenido')`. JS propio con `@push('scripts')`; datos al navegador con `@json`, nunca con peticiones AJAX. Mensajes con `->with('status' | 'aviso')` o `withErrors()` (los muestra `partials/alertas`). Las vistas `imprimir` son HTML autónomo para imprimir desde el navegador. Diseño: HTML y clases de la plantilla Phoenix (Bootstrap 5, `btn-phoenix-*`, `badge-phoenix-*`).

## Instalación de un cliente nuevo

Cada cliente (venta o renta) tiene su propia instalación en el servidor: contenedor, `.env`, BD y carpeta de Contabo propios. No hay varios consorcios en una misma BD. **La guía completa (paso a paso, `.env` y `docker-compose` por cliente, actualización y respaldos) está en `docs/instalacion-clientes/`**: mantenerla al día si cambia el proceso.

```bash
php artisan nexus:exportar-plantilla --empresa=1   # en la instalación modelo: catálogos → database/seeders/datos/plantilla-empresa.json
php artisan migrate --force && php artisan db:seed --force   # en la BD vacía del cliente: esquema + catálogos del sistema
php artisan nexus:instalar --empresa="Acme" --nit=1234567 --correo=admin@acme.com --consorcio="Grupo Acme"
```

- `nexus:instalar` solo corre en una BD sin empresas: crea la primera empresa con su casa matriz y los catálogos de la plantilla (`CatalogosEmpresa::desdePlantilla`), el usuario **administrador del sistema** (siempre con el rol `Administrador` en la empresa principal: ve y administra todas las empresas; si no queda como `esAdministradorGeneral()`, no instala nada; debe cambiar la contraseña al entrar; si no se da `--password`, la genera y la muestra una vez) y pone el nombre del consorcio en `ConfiguracionSistema`.
- La imagen trae `default-mysql-client`: `migrate` en una BD vacía carga `database/schema/mysql-schema.sql` con él.
- La plantilla lleva solo definiciones (conceptos de nómina, roles con permisos por código `modulo.accion`, organigrama, cuentas, líneas y servicios, embudo, SLA, categorías, plantillas de comunicación); va en el repositorio. La pantalla de Empresas también la ofrece como «Plantilla del sistema».
- **Archivos:** en producción `CONTABO_CARPETA` es obligatoria (`Archivos::problema()` bloquea la subida y el instalador no corre sin ella). La instalación original, que ya guardaba en la raíz del bucket, usa `CONTABO_USAR_RAIZ=true` (agregarle carpeta rompería sus documentos privados guardados como rutas).

## Base de datos

- En desarrollo, el `.env` apunta a una BD **local** (MariaDB de lerd, `nexus_erp`); producción tiene su propia BD. Toda migración nueva hay que aplicarla en producción al desplegar: avisarlo al hacer commit y push. Cuidado: el bucket de Contabo (si hay credenciales en el `.env`) sí es el real; no correr comandos que muevan o borren archivos de ahí sin que el usuario lo pida.
- **El esquema principal no está en migraciones:** las tablas de negocio se crearon fuera de Laravel. Las migraciones de `database/migrations` solo registran cambios hechos desde este proyecto (seguridad de cuentas, permisos, kardex, cobros, limpieza). Consultar el esquema real con `SHOW COLUMNS FROM <tabla>` (vía `php artisan tinker`) antes de tocar una tabla.
- **Pruebas:** como no hay migraciones del esquema, `tests/Concerns/EsquemaNexus.php` crea en SQLite las tablas mínimas que usan las pruebas y ofrece `crearUsuario()`, `permiso()` y `darPermisos()`. Al usar una tabla o columna nueva, hay que agregarla ahí. Hay un archivo de pruebas por módulo en `tests/Feature/`; las clases que redefinen `setUp` llaman a `$this->crearEsquema()` a mano.
- **Modelos** en `app/Models/{Core,Inventario,Clientes,Finanzas,CRM,RRHH}` con `$table` y `$primaryKey` explícitos. Muchas tablas no tienen `created_at`/`updated_at` (`$timestamps = false`). `Usuario` usa `password_hash` como contraseña.

## Nomenclatura

- **Tablas:** español, singular, `snake_case` (`orden_compra`, `detalle_factura`, `stock_bodega`, `presupuesto_anual`). Excepción heredada: `ConfiguracionSistema`, con columnas en camelCase (`maxIntentosSesion`, `imgLogo`).
- **Llaves:** primaria `id_<entidad>` (`id_factura`, `id_oc`, `id_linea`, `id_detalle_rec`). Las foráneas usan el mismo nombre que la primaria a la que apuntan (`id_empresa`, `id_cliente`), salvo roles con nombre propio (`id_centro_default`, `id_cuenta_gasto`, `created_by`, `aprobado_por`, `anulada_por`).
- **Estados:** enums en MAYÚSCULAS (`BORRADOR`, `APROBADO`, `ENVIADA`, `ANULADA`), expuestos como constantes `ESTADOS = ['CODIGO' => ['Etiqueta', 'color-badge']]` en el controlador o el modelo.
- **Montos** `decimal(15,4)`, redondeados con `round(..., 4)`; se muestran con `number_format(..., 2)`. Moneda con código de 3 letras de la tabla `moneda` (`GTQ` por defecto).
- **Código:** clases, métodos y variables en español (`recalcularTotales`, `deMiEmpresa`, `filtrados`, `validar`). Rutas con nombre `<modulo>.<accion>` (`facturas.emitir`) y URLs `sistema/<modulo>` con guiones y participios en español (`nueva`, `editar`, `exportar`, `imprimir`).
- **Migraciones:** `AAAA_MM_DD_00000N_descripcion_en_espanol.php`.
- **Commits:** en español, estilo `tipo(ámbito): descripción` (`feat(finanzas): …`, `fix(docker): …`, `chore(limpieza): …`).

## Reglas del proyecto

- Nunca abrir ni copiar `public/Plantilla` (está en `.gitignore`; trae código de otro sistema con credenciales). Para diseñar, copiar el HTML de la plantilla a Blade y sus assets a `public/assets` o `public/vendors`.
- El sistema de referencia para diseño y seguridad es `C:\laragon\www\sistema-restaurante`; el de la estructura de permisos, `sistema-inventario`.
