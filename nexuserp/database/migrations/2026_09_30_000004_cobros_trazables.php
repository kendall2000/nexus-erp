<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cobros trazables (paso 5e): un pago ya no se borra al revertirlo ni al devolver el
 * dinero, se marca con su estado, quién, cuándo y por qué. La factura guarda lo condonado
 * aparte de lo pagado: saldo = total − pagado (pagos APLICADOS) − condonado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pago', function (Blueprint $t) {
            $t->enum('estado', ['APLICADO', 'REVERTIDO', 'DEVUELTO'])->default('APLICADO')->after('notas')->index('idx_pago_estado');
            $t->unsignedInteger('revertido_por')->nullable()->after('estado');
            $t->dateTime('fecha_reversion')->nullable()->after('revertido_por');
            $t->string('motivo_reversion', 300)->nullable()->after('fecha_reversion');
        });
        Schema::table('factura', function (Blueprint $t) {
            $t->decimal('monto_condonado', 15, 4)->default(0)->after('total_pagado');
            $t->unsignedInteger('condonado_por')->nullable()->after('monto_condonado');
            $t->dateTime('fecha_condonacion')->nullable()->after('condonado_por');
        });
    }

    public function down(): void
    {
        Schema::table('pago', function (Blueprint $t) {
            $t->dropIndex('idx_pago_estado');
            $t->dropColumn(['estado', 'revertido_por', 'fecha_reversion', 'motivo_reversion']);
        });
        Schema::table('factura', function (Blueprint $t) {
            $t->dropColumn(['monto_condonado', 'condonado_por', 'fecha_condonacion']);
        });
    }
};
