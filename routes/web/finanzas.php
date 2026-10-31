<?php

use App\Http\Controllers\FacturaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PresupuestoController;
use Illuminate\Support\Facades\Route;

// Presupuesto anual (borrador → aprobado → cerrado; reabrir vuelve a aprobado)
Route::prefix('sistema/presupuesto')->name('presupuesto.')->controller(PresupuestoController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:presupuesto.ver')->name('index');
    Route::get('exportar', 'exportar')->middleware('permiso:presupuesto.exportar')->name('exportar');
    Route::middleware('permiso:presupuesto.crear')->group(function () {
        Route::get('nuevo', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('clonar', 'clonar')->name('clonar');
    });
    Route::get('{presupuesto}', 'show')->whereNumber('presupuesto')->middleware('permiso:presupuesto.ver')->name('show');
    Route::middleware('permiso:presupuesto.editar')->group(function () {
        Route::get('{presupuesto}/editar', 'edit')->whereNumber('presupuesto')->name('edit');
        Route::put('{presupuesto}', 'update')->whereNumber('presupuesto')->name('update');
        Route::delete('{presupuesto}', 'destroy')->whereNumber('presupuesto')->name('destroy');
    });
    Route::patch('{presupuesto}/aprobar', 'aprobar')->whereNumber('presupuesto')->middleware('permiso:presupuesto.aprobar')->name('aprobar');
    Route::patch('{presupuesto}/cerrar', 'cerrar')->whereNumber('presupuesto')->middleware('permiso:presupuesto.cerrar')->name('cerrar');
    Route::patch('{presupuesto}/reabrir', 'reabrir')->whereNumber('presupuesto')->middleware('permiso:presupuesto.reabrir')->name('reabrir');
});

// Facturas (borrador → emitida → enviada → pagada; anular sin pagos)
Route::prefix('sistema/facturas')->name('facturas.')->controller(FacturaController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:facturas.ver')->name('index');
    Route::get('exportar', 'exportar')->middleware('permiso:facturas.exportar')->name('exportar');
    Route::get('nueva', 'create')->middleware('permiso:facturas.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:facturas.crear')->name('store');
    Route::get('{factura}', 'show')->whereNumber('factura')->middleware('permiso:facturas.ver')->name('show');
    Route::get('{factura}/imprimir', 'imprimir')->whereNumber('factura')->middleware('permiso:facturas.imprimir')->name('imprimir');
    Route::middleware('permiso:facturas.editar')->group(function () {
        Route::get('{factura}/editar', 'edit')->whereNumber('factura')->name('edit');
        Route::put('{factura}', 'update')->whereNumber('factura')->name('update');
        Route::delete('{factura}', 'destroy')->whereNumber('factura')->name('destroy');
        Route::patch('{factura}/emitir', 'emitir')->whereNumber('factura')->name('emitir');
        Route::patch('{factura}/enviar', 'enviar')->whereNumber('factura')->name('enviar');
    });
    Route::patch('{factura}/anular', 'anular')->whereNumber('factura')->middleware('permiso:facturas.anular')->name('anular');
    Route::patch('{factura}/condonar', 'condonar')->whereNumber('factura')->middleware('permiso:facturas.condonar')->name('condonar');
});

// Pagos (cobros): no se borran, se revierten o se devuelven
Route::prefix('sistema/pagos')->name('pagos.')->controller(PagoController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:pagos.ver')->name('index');
    Route::get('exportar', 'exportar')->middleware('permiso:pagos.exportar')->name('exportar');
    Route::middleware('permiso:pagos.crear|facturas.cobrar')->group(function () {
        Route::get('nuevo', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::patch('{pago}/acreditar', 'acreditar')->whereNumber('pago')->name('acreditar');
    });
    Route::get('{pago}', 'show')->whereNumber('pago')->middleware('permiso:pagos.ver')->name('show');
    Route::get('{pago}/imprimir', 'imprimir')->whereNumber('pago')->middleware('permiso:pagos.imprimir')->name('imprimir');
    Route::patch('{pago}/revertir', 'revertir')->whereNumber('pago')->middleware('permiso:pagos.eliminar')->name('revertir');
    Route::patch('{pago}/devolver', 'devolver')->whereNumber('pago')->middleware('permiso:pagos.devolver')->name('devolver');
});
