<?php

use App\Http\Controllers\ConsolidadoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaActivaController;
use App\Http\Controllers\ReporteCspController;
use App\Http\Controllers\ReporteSucursalesController;
use App\Http\Middleware\ContrasenaVencida;
use App\Http\Middleware\EmpresaActiva;
use App\Http\Middleware\RequiereDosFactores;
use App\Http\Middleware\UsuarioActivo;
use Illuminate\Support\Facades\Route;

// Login, logout, recuperar y cambiar contraseña y verificación en dos pasos:
// las rutas las registra Fortify (config/fortify.php).

Route::redirect('/', '/sistema/dashboard');

// Avisos de la CSP que manda el navegador (con o sin sesión; sin las redirecciones de la cuenta).
Route::post('/csp-reporte', [ReporteCspController::class, 'guardar'])
    ->withoutMiddleware([UsuarioActivo::class, EmpresaActiva::class, ContrasenaVencida::class, RequiereDosFactores::class])
    ->middleware('throttle:60,1')->name('csp.reporte');

Route::middleware('auth')->group(function () {

    // ── Módulos por dominio de negocio ──────────────────────────────
    require __DIR__.'/web/core.php';
    require __DIR__.'/web/catalogos.php';
    require __DIR__.'/web/inventario.php';
    require __DIR__.'/web/comercial.php';
    require __DIR__.'/web/finanzas.php';
    require __DIR__.'/web/rrhh.php';

    // ── Inicio ──────────────────────────────────────────────────────
    Route::get('/sistema/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Cambiar de empresa (cada usuario, entre las empresas a las que tiene acceso)
    Route::post('/sistema/empresa-activa', [EmpresaActivaController::class, 'cambiar'])->name('empresa-activa.cambiar');

    // Reporte consolidado de todas sus empresas (cada sección filtra por el permiso de su módulo en cada empresa)
    Route::get('/sistema/consolidado', [ConsolidadoController::class, 'index'])
        ->middleware('permiso:facturas.ver|ordenes_compra.ver|pagos.ver|presupuesto.ver')->name('consolidado.index');
    Route::get('/sistema/consolidado/exportar', [ConsolidadoController::class, 'exportar'])
        ->middleware('permiso:facturas.exportar|ordenes_compra.exportar|pagos.exportar|presupuesto.exportar')->name('consolidado.exportar');

    // Resumen por sucursal y agencia de la empresa activa (cada sección con el permiso de su módulo)
    Route::get('/sistema/por-sucursal', [ReporteSucursalesController::class, 'index'])
        ->middleware('permiso:facturas.ver|ordenes_compra.ver|tickets.ver|empleados.ver')->name('por-sucursal.index');
    Route::get('/sistema/por-sucursal/exportar', [ReporteSucursalesController::class, 'exportar'])
        ->middleware('permiso:facturas.exportar|ordenes_compra.exportar|tickets.exportar|empleados.exportar')->name('por-sucursal.exportar');

    // ── Pantallas del menú que aún no existen (DEBE ir SIEMPRE al final) ──
    Route::get('/sistema/{any}', fn () => redirect()->route('dashboard')
        ->with('aviso', 'Esa pantalla todavía no está disponible.'))
        ->where('any', '.*');
});
