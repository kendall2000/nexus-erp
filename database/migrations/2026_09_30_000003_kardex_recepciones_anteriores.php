<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las recepciones hechas con la API anterior sumaban el stock pero no escribían el
 * kardex (movimiento_inventario quedaba vacío). Aquí se crean las ENTRADAS que faltan
 * con la fecha de cada recepción. Se inserta directo en la tabla (sin el modelo) para
 * no volver a sumar el stock, que ya se había aplicado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('movimiento_inventario') || ! Schema::hasTable('detalle_recepcion')) {
            return;
        }

        $lineas = DB::table('detalle_recepcion as d')
            ->join('recepcion_mercaderia as r', 'r.id_recepcion', '=', 'd.id_recepcion')
            ->leftJoin('orden_compra as o', 'o.id_oc', '=', 'r.id_oc')
            ->whereNotExists(fn ($q) => $q->from('movimiento_inventario as m')
                ->where('m.referencia_tipo', 'COMPRA')->whereColumn('m.referencia_id', 'r.id_recepcion')
                ->whereColumn('m.id_producto', 'd.id_producto'))
            ->orderBy('d.id_detalle_rec')
            ->get(['d.id_producto', 'd.cantidad_recibida', 'd.costo_unitario', 'd.subtotal', 'r.id_recepcion', 'r.id_empresa', 'r.id_bodega',
                'r.numero_recepcion', 'r.created_by', 'r.created_at', 'o.numero_oc', 'o.moneda', 'o.created_by as oc_created_by']);

        foreach ($lineas as $l) {
            DB::table('movimiento_inventario')->insert([
                'id_empresa' => $l->id_empresa,
                'id_producto' => $l->id_producto,
                'id_bodega' => $l->id_bodega,
                'tipo_movimiento' => 'ENTRADA',
                'cantidad' => $l->cantidad_recibida,
                'costo_unitario' => $l->costo_unitario,
                'costo_total' => $l->subtotal,
                'moneda' => $l->moneda ?? 'GTQ',
                'referencia_tipo' => 'COMPRA',
                'referencia_id' => $l->id_recepcion,
                'observaciones' => "Recepción {$l->numero_recepcion} · OC {$l->numero_oc} (kardex regenerado)",
                'created_by' => $l->created_by ?? $l->oc_created_by ?? 0,
                'created_at' => $l->created_at,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('movimiento_inventario')) {
            DB::table('movimiento_inventario')->where('observaciones', 'like', '%(kardex regenerado)')->delete();
        }
    }
};
