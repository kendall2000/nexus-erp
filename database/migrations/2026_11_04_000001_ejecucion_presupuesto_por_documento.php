<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lo que cada documento (orden de compra o factura) sumó a cada presupuesto, con su mes.
 *
 * Al cancelar la orden o anular la factura se resta exactamente eso, aunque después el
 * presupuesto haya cambiado de estado o el producto o servicio haya cambiado de centro o
 * de cuenta. Una fila con id_presupuesto vacío marca que el documento se registró sin
 * ningún presupuesto aprobado (y por eso no hay nada que revertir).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ejecucion_presupuesto')) {
            return;
        }
        Schema::create('ejecucion_presupuesto', function (Blueprint $t) {
            $t->increments('id_ejecucion');
            $t->unsignedTinyInteger('id_empresa');
            $t->enum('documento', ['ORDEN_COMPRA', 'FACTURA']);
            $t->unsignedInteger('id_documento');
            $t->unsignedInteger('id_presupuesto')->nullable();
            $t->unsignedTinyInteger('mes')->nullable();
            $t->decimal('monto', 15, 4)->default(0);
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('revertido_at')->nullable();
            $t->index(['documento', 'id_documento'], 'idx_ejecucion_documento');
            $t->index('id_presupuesto', 'idx_ejecucion_presupuesto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejecucion_presupuesto');
    }
};
