<?php

use App\Http\Controllers\BodegaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CentroCostoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CuentaContableController;
use App\Http\Controllers\CuentaSeguridadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeografiaController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RecepcionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SeguridadController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// Login, logout, recuperar y cambiar contraseña y verificación en dos pasos:
// las rutas las registra Fortify (config/fortify.php).

Route::redirect('/', '/sistema/dashboard');

Route::middleware('auth')->group(function () {

    // ── Seguridad de mi cuenta (cualquier usuario) ──────────────────
    Route::get('cuenta/seguridad', [CuentaSeguridadController::class, 'show'])->name('cuenta.seguridad');
    Route::delete('cuenta/sesiones', [CuentaSeguridadController::class, 'cerrarSesiones'])
        ->middleware('throttle:6,1')->name('cuenta.sesiones.cerrar');

    // ── Seguridad y accesos (solo administrador) ────────────────────
    Route::middleware('admin')->group(function () {
        Route::get('sistema/seguridad', [SeguridadController::class, 'index'])->name('seguridad.index');
        Route::put('sistema/seguridad', [SeguridadController::class, 'guardar'])->name('seguridad.guardar');
        Route::put('sistema/seguridad/roles', [SeguridadController::class, 'roles'])->name('seguridad.roles');

        // Configuración del sistema
        Route::get('sistema/configuracion', [ConfiguracionController::class, 'edit'])->name('configuracion.edit');
        Route::put('sistema/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');

        // Sucursales
        Route::prefix('sistema/sucursales')->name('sucursales.')->controller(SucursalController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('nueva', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{sucursal}/editar', 'edit')->whereNumber('sucursal')->name('edit');
            Route::put('{sucursal}', 'update')->whereNumber('sucursal')->name('update');
            Route::patch('{sucursal}/estado', 'estado')->whereNumber('sucursal')->name('estado');
            Route::delete('{sucursal}', 'destroy')->whereNumber('sucursal')->name('destroy');
        });

        // Geografía (catálogo compartido): {tipo} = pais | division | municipio
        Route::prefix('sistema/geografia')->name('geografia.')->controller(GeografiaController::class)
            ->where(['tipo' => 'pais|division|municipio', 'id' => '[0-9]+'])->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('{tipo}', 'store')->name('store');
            Route::get('{tipo}/{id}/editar', 'edit')->name('edit');
            Route::put('{tipo}/{id}', 'update')->name('update');
            Route::patch('{tipo}/{id}/estado', 'estado')->name('estado');
            Route::delete('{tipo}/{id}', 'destroy')->name('destroy');
            });

        // Módulos (menú lateral y acciones de los permisos)
        Route::redirect('sistema/menu', '/sistema/modulos');
        Route::prefix('sistema/modulos')->name('modulos.')->controller(ModuloController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('nuevo', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{modulo}/editar', 'edit')->whereNumber('modulo')->name('edit');
            Route::put('{modulo}', 'update')->whereNumber('modulo')->name('update');
            Route::patch('{modulo}/estado', 'estado')->whereNumber('modulo')->name('estado');
            Route::patch('{modulo}/mover', 'mover')->whereNumber('modulo')->name('mover');
            Route::delete('{modulo}', 'destroy')->whereNumber('modulo')->name('destroy');
        });
    });

    // ── Sirve los JS de los módulos desde resources/views/modulos/ ──
    Route::get('/modulos-js/{modulo}/{archivo}.js', function (string $modulo, string $archivo) {
        $ruta = resource_path("views/modulos/{$modulo}/{$archivo}.js");

        if (!File::exists($ruta)) {
            abort(404);
        }

        return response(File::get($ruta), 200)
            ->header('Content-Type', 'application/javascript');
    })->where(['modulo' => '[a-zA-Z0-9_-]+', 'archivo' => '[a-zA-Z0-9_-]+']);

    // ── Módulos con permisos «modulo.accion» (ver, crear, editar, eliminar) ──
    // Usuarios
    Route::prefix('sistema/usuarios')->name('usuarios.')->controller(UsuarioController::class)->group(function () {
        Route::get('/', 'index')->middleware('permiso:usuarios.ver')->name('index');
        Route::middleware('permiso:usuarios.crear')->group(function () {
            Route::get('nuevo', 'create')->name('create');
            Route::post('/', 'store')->name('store');
        });
        Route::middleware('permiso:usuarios.editar')->group(function () {
            Route::get('{usuario}/editar', 'edit')->whereNumber('usuario')->name('edit');
            Route::put('{usuario}', 'update')->whereNumber('usuario')->name('update');
            Route::put('{usuario}/permisos', 'permisos')->whereNumber('usuario')->name('permisos');
            Route::patch('{usuario}/estado', 'estado')->whereNumber('usuario')->name('estado');
            Route::delete('{usuario}/sesiones', 'sesiones')->whereNumber('usuario')->name('sesiones');
        });
        Route::delete('{usuario}', 'destroy')->whereNumber('usuario')->middleware('permiso:usuarios.eliminar')->name('destroy');
    });

    // Roles y permisos (ver = puede abrir el rol en solo lectura)
    Route::prefix('sistema/roles')->name('roles.')->controller(RolController::class)->group(function () {
        Route::get('/', 'index')->middleware('permiso:roles.ver')->name('index');
        Route::get('{rol}/editar', 'edit')->whereNumber('rol')->middleware('permiso:roles.ver')->name('edit');
        Route::get('nuevo', 'create')->middleware('permiso:roles.crear')->name('create');
        Route::post('/', 'store')->middleware('permiso:roles.crear')->name('store');
        Route::put('{rol}', 'update')->whereNumber('rol')->middleware('permiso:roles.editar')->name('update');
        Route::delete('{rol}', 'destroy')->whereNumber('rol')->middleware('permiso:roles.eliminar')->name('destroy');
    });

    // Catálogos: url => [controlador, parámetro, ruta «nuevo», ¿exporta?]. El permiso es la url con «_» (centros-costo → centros_costo.ver).
    $catalogos = [
        'bodegas' => [BodegaController::class, 'bodega', 'nueva', false],
        'categorias' => [CategoriaController::class, 'categoria', 'nueva', false],
        'productos' => [ProductoController::class, 'producto', 'nuevo', true],
        'proveedores' => [ProveedorController::class, 'proveedor', 'nuevo', true],
        'centros-costo' => [CentroCostoController::class, 'centro', 'nuevo', true],
        'cuentas-contables' => [CuentaContableController::class, 'cuenta', 'nueva', true],
    ];

    // Importar el plan de cuentas (va antes del bucle para que «importar» no choque con {cuenta}).
    Route::prefix('sistema/cuentas-contables')->name('cuentas-contables.')->controller(CuentaContableController::class)
        ->middleware(['permiso:cuentas_contables.crear', 'permiso:cuentas_contables.editar'])->group(function () {
            Route::get('plantilla', 'plantilla')->name('plantilla');
            Route::get('importar', 'importarForm')->name('importar');
            Route::post('importar', 'importarPrevia')->middleware('throttle:20,1')->name('importar.previa');
            Route::post('importar/confirmar', 'importarConfirmar')->name('importar.confirmar');
            Route::post('importar/cancelar', 'importarCancelar')->name('importar.cancelar');
        });

    foreach ($catalogos as $url => [$controlador, $parametro, $nuevo, $exporta]) {
        $modulo = str_replace('-', '_', $url);
        Route::prefix("sistema/{$url}")->name("{$url}.")->controller($controlador)->group(function () use ($modulo, $parametro, $nuevo, $exporta) {
            Route::get('/', 'index')->middleware("permiso:{$modulo}.ver")->name('index');
            if ($exporta) {
                Route::get('exportar', 'exportar')->middleware("permiso:{$modulo}.exportar")->name('exportar');
            }
            Route::get($nuevo, 'create')->middleware("permiso:{$modulo}.crear")->name('create');
            Route::post('/', 'store')->middleware("permiso:{$modulo}.crear")->name('store');
            Route::middleware("permiso:{$modulo}.editar")->group(function () use ($parametro) {
                Route::get("{{$parametro}}/editar", 'edit')->whereNumber($parametro)->name('edit');
                Route::put("{{$parametro}}", 'update')->whereNumber($parametro)->name('update');
                Route::patch("{{$parametro}}/estado", 'estado')->whereNumber($parametro)->name('estado');
            });
            Route::delete("{{$parametro}}", 'destroy')->whereNumber($parametro)->middleware("permiso:{$modulo}.eliminar")->name('destroy');
        });
    }

    // Órdenes de compra (borrador → aprobada → recibida / cancelada)
    Route::prefix('sistema/ordenes-compra')->name('ordenes-compra.')->controller(OrdenCompraController::class)->group(function () {
        Route::get('/', 'index')->middleware('permiso:ordenes_compra.ver')->name('index');
        Route::get('exportar', 'exportar')->middleware('permiso:ordenes_compra.exportar')->name('exportar');
        Route::get('nueva', 'create')->middleware('permiso:ordenes_compra.crear')->name('create');
        Route::post('/', 'store')->middleware('permiso:ordenes_compra.crear')->name('store');
        Route::get('{orden}', 'show')->whereNumber('orden')->middleware('permiso:ordenes_compra.ver')->name('show');
        Route::get('{orden}/imprimir', 'imprimir')->whereNumber('orden')->middleware('permiso:ordenes_compra.imprimir')->name('imprimir');
        Route::middleware('permiso:ordenes_compra.editar')->group(function () {
            Route::get('{orden}/editar', 'edit')->whereNumber('orden')->name('edit');
            Route::put('{orden}', 'update')->whereNumber('orden')->name('update');
            Route::delete('{orden}', 'destroy')->whereNumber('orden')->name('destroy');
        });
        Route::patch('{orden}/aprobar', 'aprobar')->whereNumber('orden')->middleware('permiso:ordenes_compra.aprobar')->name('aprobar');
        Route::patch('{orden}/cancelar', 'cancelar')->whereNumber('orden')->middleware('permiso:ordenes_compra.cancelar')->name('cancelar');
    });

    // Recepciones de mercadería (entrada al stock y al kardex; no se editan ni se anulan)
    Route::prefix('sistema/recepciones')->name('recepciones.')->controller(RecepcionController::class)->group(function () {
        Route::get('/', 'index')->middleware('permiso:recepciones.ver')->name('index');
        Route::get('nueva', 'create')->middleware('permiso:recepciones.crear')->name('create');
        Route::post('/', 'store')->middleware('permiso:recepciones.crear')->name('store');
        Route::get('{recepcion}', 'show')->whereNumber('recepcion')->middleware('permiso:recepciones.ver')->name('show');
        Route::get('{recepcion}/imprimir', 'imprimir')->whereNumber('recepcion')->middleware('permiso:recepciones.imprimir')->name('imprimir');
    });

    // Clientes (ficha con contactos y estado de cuenta)
    Route::prefix('sistema/clientes')->name('clientes.')->controller(ClienteController::class)->group(function () {
        Route::get('/', 'index')->middleware('permiso:clientes.ver')->name('index');
        Route::get('exportar', 'exportar')->middleware('permiso:clientes.exportar')->name('exportar');
        Route::get('nuevo', 'create')->middleware('permiso:clientes.crear')->name('create');
        Route::post('/', 'store')->middleware('permiso:clientes.crear')->name('store');
        Route::get('{cliente}', 'show')->whereNumber('cliente')->middleware('permiso:clientes.ver')->name('show');
        Route::get('{cliente}/imprimir', 'imprimir')->whereNumber('cliente')->middleware('permiso:clientes.imprimir')->name('imprimir');
        Route::middleware('permiso:clientes.editar')->group(function () {
            Route::get('{cliente}/editar', 'edit')->whereNumber('cliente')->name('edit');
            Route::put('{cliente}', 'update')->whereNumber('cliente')->name('update');
            Route::patch('{cliente}/estado', 'estado')->whereNumber('cliente')->name('estado');
            Route::post('{cliente}/contactos', 'guardarContacto')->whereNumber('cliente')->name('contactos.store');
            Route::put('{cliente}/contactos/{contacto}', 'guardarContacto')->whereNumber(['cliente', 'contacto'])->name('contactos.update');
            Route::delete('{cliente}/contactos/{contacto}', 'eliminarContacto')->whereNumber(['cliente', 'contacto'])->name('contactos.destroy');
        });
        Route::delete('{cliente}', 'destroy')->whereNumber('cliente')->middleware('permiso:clientes.eliminar')->name('destroy');
    });

    // ── Inicio ──────────────────────────────────────────────────────
    Route::get('/sistema/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Pantallas que todavía son Vue + API (se migran en el paso 5) ──
    // ── Finanzas ────────────────────────────────────────────────────
    Route::get('/sistema/facturas',          fn() => view('modulos.facturas.index'));
    Route::get('/sistema/pagos',             fn() => view('modulos.pagos.index'));
    Route::get('/sistema/presupuesto',       fn() => view('modulos.presupuesto.index'));

    // ── Pantallas del menú que aún no existen (DEBE ir SIEMPRE al final) ──
    Route::get('/sistema/{any}', fn () => redirect()->route('dashboard')
        ->with('aviso', 'Esa pantalla todavía no está disponible.'))
        ->where('any', '.*');
});
