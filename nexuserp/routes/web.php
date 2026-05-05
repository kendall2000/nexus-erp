<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
Route::middleware('no.cache')->group(function () {
// Login
Route::get('/login', fn() => view('auth.login'))->name('login');

    // ── Sirve los JS de los módulos desde resources/views/modulos/ ──
    Route::get('/modulos-js/{modulo}/{archivo}.js', function (string $modulo, string $archivo) {
        $ruta = resource_path("views/modulos/{$modulo}/{$archivo}.js");

        if (!File::exists($ruta)) {
            abort(404);
        }

        return response(File::get($ruta), 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache');
    })->where(['modulo' => '[a-zA-Z0-9_-]+', 'archivo' => '[a-zA-Z0-9_-]+']);

    // ── Vistas del sistema ──────────────────────────────────────────
    Route::get('/sistema/dashboard',     fn() => view('modulos.dashboard.index'));
    Route::get('/sistema/usuarios',      fn() => view('modulos.usuarios.index'));
    Route::get('/sistema/roles',         fn() => view('modulos.roles.index'));
    Route::get('/sistema/configuracion', fn() => view('modulos.configuracion.index'));
    Route::get('/sistema/menu',          fn() => view('modulos.menu.index'));

    // ── Inventario ──────────────────────────────────────────────────
    Route::get('/sistema/bodegas',     fn() => view('modulos.bodegas.index'));
    Route::get('/sistema/proveedores', fn() => view('modulos.proveedores.index'));
    Route::get('/sistema/productos',   fn() => view('modulos.productos.index'));
    Route::get('/sistema/categorias',  fn() => view('modulos.categorias.index'));

    // ── Sucursales ──────────────────────────────────────────────────
    Route::get('/sistema/sucursales', fn() => view('modulos.sucursales.index'));

    //Geografia
    Route::get('/sistema/geografia', fn() => view('modulos.geografia.index'));

    //proveedor
    Route::get('/sistema/proveedores', fn() => view('modulos.proveedores.index'));

    //orden compra
    Route::get('/sistema/ordenes-compra', fn() => view('modulos.ordenes-compra.index'));

    //recepcion compra
    Route::get('/sistema/recepciones', fn() => view('modulos.recepciones.index'));

    //clientes
    Route::get('/sistema/clientes',  fn() => view('modulos.clientes.index'));

    //finzansas
    Route::get('/sistema/facturas',       fn() => view('modulos.facturas.index'));
    Route::get('/sistema/pagos',          fn() => view('modulos.pagos.index'));
    Route::get('/sistema/presupuesto',    fn() => view('modulos.presupuesto.index'));
    Route::get('/sistema/centros-costo', fn() => view('modulos.centros-costo.index'));
    Route::get('/sistema/cuentas-contables', fn() => view('modulos.cuentas-contables.index'));
    // Route::get('/sistema/cuentas-cobrar', fn() => view('modulos.cuentas-cobrar.index')); // -- no esta creado


// ── Catch-all (DEBE ir SIEMPRE al final) ────────────────────────
    Route::get('/sistema/{any}', fn() => view('modulos.dashboard.index'))
        ->where('any', '.*');
});