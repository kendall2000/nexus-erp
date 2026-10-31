<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CuentaSeguridadController;
use App\Http\Controllers\GeografiaController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SeguridadController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// ── Seguridad de mi cuenta (cualquier usuario) ──────────────────
Route::get('cuenta/seguridad', [CuentaSeguridadController::class, 'show'])->name('cuenta.seguridad');
Route::delete('cuenta/sesiones', [CuentaSeguridadController::class, 'cerrarSesiones'])
    ->middleware('throttle:6,1')->name('cuenta.sesiones.cerrar');

// ── Seguridad y accesos (solo administrador) ────────────────────
Route::middleware('admin')->group(function () {
    Route::get('sistema/seguridad', [SeguridadController::class, 'index'])->name('seguridad.index');
    Route::put('sistema/seguridad', [SeguridadController::class, 'guardar'])->name('seguridad.guardar');
    Route::put('sistema/seguridad/roles', [SeguridadController::class, 'roles'])->name('seguridad.roles');
    Route::put('sistema/seguridad/contrasenas', [SeguridadController::class, 'contrasenas'])->name('seguridad.contrasenas');
    Route::post('sistema/seguridad/desbloquear', [SeguridadController::class, 'desbloquear'])->name('seguridad.desbloquear');
    Route::get('sistema/seguridad/exportar', [SeguridadController::class, 'exportar'])->name('seguridad.exportar');

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

// ── Usuarios, Roles y Bitácora ──────────────────────────────────
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

Route::prefix('sistema/roles')->name('roles.')->controller(RolController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:roles.ver')->name('index');
    Route::get('{rol}/editar', 'edit')->whereNumber('rol')->middleware('permiso:roles.ver')->name('edit');
    Route::get('nuevo', 'create')->middleware('permiso:roles.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:roles.crear')->name('store');
    Route::put('{rol}', 'update')->whereNumber('rol')->middleware('permiso:roles.editar')->name('update');
    Route::delete('{rol}', 'destroy')->whereNumber('rol')->middleware('permiso:roles.eliminar')->name('destroy');
});

Route::prefix('sistema/bitacora')->name('bitacora.')->controller(BitacoraController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:bitacora.ver')->name('index');
    Route::get('exportar', 'exportar')->middleware('permiso:bitacora.exportar')->name('exportar');
    Route::get('{cambio}', 'show')->whereNumber('cambio')->middleware('permiso:bitacora.ver')->name('show');
});
