<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sucursal o agencia de cada factura, orden de compra y ticket, para reportar por sede.
 * La serie de facturación puede quedar atada a un establecimiento (FEL): las facturas de esa
 * serie son siempre de esa sede. Los documentos anteriores quedan sin sucursal.
 */
return new class extends Migration
{
    private const TABLAS = ['factura' => 'id_cliente', 'orden_compra' => 'id_proveedor', 'ticket' => 'id_cliente', 'serie_facturacion' => 'id_empresa'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla => $despues) {
            if (Schema::hasColumn($tabla, 'id_sucursal')) {
                continue;
            }
            Schema::table($tabla, function (Blueprint $t) use ($tabla, $despues) {
                $t->unsignedSmallInteger('id_sucursal')->nullable()->after($despues);
                $t->foreign('id_sucursal', "fk_{$tabla}_sucursal")->references('id_sucursal')->on('sucursal');
                $t->index(['id_empresa', 'id_sucursal'], "idx_{$tabla}_sucursal");
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLAS) as $tabla) {
            Schema::table($tabla, function (Blueprint $t) use ($tabla) {
                $t->dropForeign("fk_{$tabla}_sucursal");
                $t->dropIndex("idx_{$tabla}_sucursal");
                $t->dropColumn('id_sucursal');
            });
        }
    }
};
