<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca si los días trabajados o las horas extra de un empleado en la nómina los puso alguien
 * a mano. Al reprocesar, lo calculado se vuelve a calcular (una baja, ausencia o permiso
 * registrado después sí se descuenta) y lo ajustado a mano se respeta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_nomina', function (Blueprint $t) {
            if (! Schema::hasColumn('detalle_nomina', 'dias_manual')) {
                $t->boolean('dias_manual')->default(false)->after('dias_trabajados');
            }
            if (! Schema::hasColumn('detalle_nomina', 'horas_manual')) {
                $t->boolean('horas_manual')->default(false)->after('horas_extra');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detalle_nomina', function (Blueprint $t) {
            $t->dropColumn(['dias_manual', 'horas_manual']);
        });
    }
};
