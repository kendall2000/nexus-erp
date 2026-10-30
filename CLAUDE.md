# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Nexus ERP: Laravel 13 (PHP 8.3+) multiempresa para compras, inventario, clientes y finanzas. Todo el código, los nombres, los mensajes y los commits van **en español**. El repositorio git está un nivel arriba (`C:\laragon\www\nexus-erp`); la aplicación vive en `nexuserp/`. Se trabaja en la rama `dev` (`main` es la principal).

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
- No hay build de front-end: no hay npm, Vite ni Vue. Los assets de la plantilla Phoenix se sirven tal cual desde `public/assets` y `public/vendors` (ECharts, Bootstrap, FontAwesome, Feather…).
- Despliegue con Docker (`Dockerfile`, `docker-entrypoint.sh`, `docker-compose.yml`): el entrypoint cachea config, rutas, vistas y eventos, y solo migra si `RUN_MIGRATIONS=true` (el cron del scheduler, con `ENABLE_SCHEDULER=true`). No hay Docker en la máquina de desarrollo: se construye en el servidor.

## Arquitectura

**Todo es Blade renderizado en el servidor con sesión.** No hay API, tokens ni SPA: se eliminaron Vue, `/api/v1`, Sanctum y spatie/laravel-permission. No volver a introducirlos.

- **Autenticación:** Laravel Fortify (`app/Providers/FortifyServiceProvider.php`): login por usuario o correo (`Seguridad::buscarUsuario`), recuperar y cambiar contraseña, verificación en dos pasos, sesiones en BD. Las reglas de bloqueo (`maxIntentosSesion`, `bloqueoMinutos`, `sesionExpiraMin`) se leen de la tabla `ConfiguracionSistema`. Cada evento de acceso se registra en `auditoria_acceso` vía `Seguridad::registrar` (su columna `accion` es un enum cerrado).
- **Middlewares web** (`bootstrap/app.php`): `EncabezadosSeguridad`, `PreventBackHistory`, `UsuarioActivo` (expulsa a desactivados) y `RequiereDosFactores` (si el rol exige 2FA, solo deja entrar a «Seguridad de mi cuenta»). Alias de ruta: `admin` y `permiso`.
- **Permisos:** `modulo` (cada opción del menú lateral) × `accion` (ver, crear, editar, eliminar, exportar, anular, cancelar, asignar, configurar, gestionar, imprimir, aprobar, cobrar, condonar, devolver, cerrar, reabrir, procesar) = `permiso`. Se asignan por `rol_permiso` y como extras por `usuario_permiso`. Código `modulo.accion` en minúsculas (`ordenes_compra.aprobar`). `Usuario::puede($codigo)` = acceso total o roles activos + extras.
  - Proteger cada ruta con `->middleware('permiso:modulo.accion')`; con `|` basta cualquiera (`permiso:pagos.crear|facturas.cobrar`). En Blade: `auth()->user()->puede(...)` para mostrar u ocultar botones.
  - Acceso total: roles `Rol::ACCESO_TOTAL` (Administrador, Superadmin, Super Administrador). Anti-escalada con `Usuario::puedeGestionar` y `puedeAsignarRol`.
  - Un módulo sin permisos es solo del administrador. El menú (`App\Support\MenuLateral`) muestra los módulos activos con `ver`.
  - Los códigos de módulo y acción ya existen en BD (migraciones `2026_09_30_000001` y `000002`): al crear una pantalla, usar los existentes en lugar de inventar otros.
- **Multiempresa:** cada usuario tiene `id_empresa`. Cada controlador filtra explícitamente con un `deMiEmpresa()` (`Modelo::query()->where('id_empresa', $request->user()->id_empresa)`) y valida las llaves foráneas con `Rule::exists(...)->where('id_empresa', ...)`. No hay scope global: olvidar el filtro expone datos de otra empresa.
- **Controladores** (`app/Http/Controllers`, uno por módulo): `index / create / store / show / edit / update / estado / destroy`, más acciones de flujo (`aprobar`, `emitir`, `anular`…) y a menudo `exportar` e `imprimir`. Rutas en `routes/web.php` bajo `sistema/<modulo>`; los catálogos simples se registran en el bucle `$catalogos`, donde el permiso es la URL con `_` (`centros-costo` → `centros_costo.ver`). La ruta comodín `sistema/{any}` debe quedar siempre al final.
- **Lógica de negocio compartida en `app/Support`**, no en hooks de modelos:
  - `EjecucionPresupuesto`: ejecuta el presupuesto de forma simétrica. Aprobar una orden de compra suma y cancelarla resta; emitir una factura suma y anularla resta (solo si se había emitido); la nota de crédito resta. Siempre la base sin IVA, solo en presupuestos `APROBADO`.
  - `PlanCuentas`: reglas del árbol de cuentas (padre de agrupación y del mismo tipo, sin ciclos, niveles).
  - `Referencias::enUso`: impide borrar registros en uso (el esquema no tiene todas las llaves foráneas). Usar `tabla.columna` si la columna se llama distinto.
  - `ExportarCsv`: CSV para Excel, con BOM, `;` y protección contra inyección de fórmulas.
  - `Archivos`: subida al bucket Contabo (disco `contabo`, público); en BD se guarda la URL completa.
  - `Sistema`: nombre, logo y colores desde `ConfiguracionSistema`.
  - `Seguridad` y `MatrizPermisos`: apoyo a autenticación y permisos.
- **Zona horaria:** la de Configuración (`ConfiguracionSistema.zonaHoraria`, por defecto `America/Guatemala`) la aplica `AppServiceProvider` a PHP y a la sesión de MySQL en cada petición. En pruebas, congelar la fecha con `Carbon::setTestNow()` si la lógica depende de «hoy» (asistencia y pagos no aceptan fechas futuras).
- **Ojo con `$request->validate()` + `[...]`:** los campos `nullable` enviados vacíos llegan como clave con `null`, y el operador `+` no los reemplaza. Para poner un valor por defecto sobre un campo validado, usar `$datos['campo'] ??= …` o `array_merge`, no `$datos + ['campo' => …]`.
- **Documentos con flujo:** se validan las transiciones dentro de `DB::transaction` con `lockForUpdate()` sobre el registro (y sobre la serie o factura que se consume) para evitar dobles aprobaciones, cobros o números repetidos.
  - Órdenes de compra: BORRADOR → ENVIADA (aprobada) → PARCIAL / RECIBIDA; se cancelan sin mercadería recibida.
  - Recepciones: cada línea crea una ENTRADA en `movimiento_inventario` (kardex, inmutable), cuyo hook actualiza `stock_bodega` y el costo promedio.
  - Facturas: el tipo sale de la serie y el número se toma de la serie bloqueada al crear el borrador; solo el último borrador de una serie se puede eliminar.
  - Pagos: nunca se borran; pasan a `REVERTIDO` o `DEVUELTO`. `Factura::recalcularCobro` recalcula saldo = total − pagos aplicados − condonado.
- **Vistas:** `resources/views/<modulo>/{index,form,show,imprimir}.blade.php` con `@extends('layouts.app', ['titulo' => …])` y `@section('contenido')`. JS propio con `@push('scripts')`; datos al navegador con `@json`, nunca con peticiones AJAX. Mensajes con `->with('status' | 'aviso')` o `withErrors()` (los muestra `partials/alertas`). Las vistas `imprimir` son HTML autónomo para imprimir desde el navegador. Diseño: HTML y clases de la plantilla Phoenix (Bootstrap 5, `btn-phoenix-*`, `badge-phoenix-*`).

## Base de datos

- MySQL en un servidor remoto (el `.env` local apunta a la **misma BD que usa producción**; hoy los datos son de prueba). Un cambio de esquema rompe producción hasta desplegar el código que lo acompaña: aplicar la migración, hacer commit y push, y avisar que hay que desplegar.
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
