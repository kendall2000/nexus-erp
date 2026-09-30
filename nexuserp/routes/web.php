<?php

use App\Http\Controllers\CuentaSeguridadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SeguridadController;
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

    // ── Usuarios ────────────────────────────────────────────────────
    Route::prefix('sistema/usuarios')->name('usuarios.')->controller(UsuarioController::class)->group(function () {
        Route::get('/', 'index')->middleware('permiso:CONFIG.USUARIOS.VER')->name('index');
        Route::middleware('permiso:CONFIG.USUARIOS.CREAR')->group(function () {
            Route::get('nuevo', 'create')->name('create');
            Route::post('/', 'store')->name('store');
        });
        Route::middleware('permiso:CONFIG.USUARIOS.EDITAR')->group(function () {
            Route::get('{usuario}/editar', 'edit')->whereNumber('usuario')->name('edit');
            Route::put('{usuario}', 'update')->whereNumber('usuario')->name('update');
            Route::patch('{usuario}/estado', 'estado')->whereNumber('usuario')->name('estado');
            Route::delete('{usuario}/sesiones', 'sesiones')->whereNumber('usuario')->name('sesiones');
            Route::delete('{usuario}', 'destroy')->whereNumber('usuario')->name('destroy');
        });
    });

    // ── Roles y permisos ────────────────────────────────────────────
    Route::prefix('sistema/roles')->name('roles.')->controller(RolController::class)->group(function () {
        Route::get('/', 'index')->middleware('permiso:CONFIG.ROLES.VER')->name('index');
        Route::get('{rol}/editar', 'edit')->whereNumber('rol')->middleware('permiso:CONFIG.ROLES.VER')->name('edit');
        Route::middleware('permiso:CONFIG.ROLES.GESTIONAR')->group(function () {
            Route::get('nuevo', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::put('{rol}', 'update')->whereNumber('rol')->name('update');
            Route::delete('{rol}', 'destroy')->whereNumber('rol')->name('destroy');
        });
    });

    // ── Vistas del sistema ──────────────────────────────────────────
    Route::get('/sistema/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/sistema/configuracion', fn() => view('modulos.configuracion.index'));
    Route::get('/sistema/menu',          fn() => view('modulos.menu.index'));

    // ── Inventario ──────────────────────────────────────────────────
    Route::get('/sistema/bodegas',        fn() => view('modulos.bodegas.index'));
    Route::get('/sistema/proveedores',    fn() => view('modulos.proveedores.index'));
    Route::get('/sistema/productos',      fn() => view('modulos.productos.index'));
    Route::get('/sistema/categorias',     fn() => view('modulos.categorias.index'));
    Route::get('/sistema/ordenes-compra', fn() => view('modulos.ordenes-compra.index'));
    Route::get('/sistema/recepciones',    fn() => view('modulos.recepciones.index'));

    // ── Sucursales y geografía ──────────────────────────────────────
    Route::get('/sistema/sucursales', fn() => view('modulos.sucursales.index'));
    Route::get('/sistema/geografia',  fn() => view('modulos.geografia.index'));

    // ── Clientes ────────────────────────────────────────────────────
    Route::get('/sistema/clientes', fn() => view('modulos.clientes.index'));

    // ── Finanzas ────────────────────────────────────────────────────
    Route::get('/sistema/facturas',          fn() => view('modulos.facturas.index'));
    Route::get('/sistema/pagos',             fn() => view('modulos.pagos.index'));
    Route::get('/sistema/presupuesto',       fn() => view('modulos.presupuesto.index'));
    Route::get('/sistema/centros-costo',     fn() => view('modulos.centros-costo.index'));
    Route::get('/sistema/cuentas-contables', fn() => view('modulos.cuentas-contables.index'));

    // ── Pantallas del menú que aún no existen (DEBE ir SIEMPRE al final) ──
    Route::get('/sistema/{any}', fn () => redirect()->route('dashboard')
        ->with('aviso', 'Esa pantalla todavía no está disponible.'))
        ->where('any', '.*');
});
