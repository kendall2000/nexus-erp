<?php

use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\RecepcionController;
use Illuminate\Support\Facades\Route;

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

// Kardex: movimientos de inventario (no se editan ni se borran)
Route::prefix('sistema/movimientos')->name('movimientos.')->controller(MovimientoController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:movimientos.ver')->name('index');
    Route::get('nuevo', 'create')->middleware('permiso:movimientos.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:movimientos.crear')->name('store');
});

// Recepciones de mercadería (entrada al stock y al kardex; no se editan ni se anulan)
Route::prefix('sistema/recepciones')->name('recepciones.')->controller(RecepcionController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:recepciones.ver')->name('index');
    Route::get('nueva', 'create')->middleware('permiso:recepciones.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:recepciones.crear')->name('store');
    Route::get('{recepcion}', 'show')->whereNumber('recepcion')->middleware('permiso:recepciones.ver')->name('show');
    Route::get('{recepcion}/imprimir', 'imprimir')->whereNumber('recepcion')->middleware('permiso:recepciones.imprimir')->name('imprimir');
});
