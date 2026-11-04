<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Opción «Resumen por sucursal» del menú, en Finanzas → Contabilidad y Control. Sin permisos
 * propios: la ve quien puede ver facturas, órdenes de compra, tickets o empleados
 * (MenuLateral::CON_PERMISOS_DE_OTROS). Idempotente.
 */
return new class extends Migration
{
    private const CODIGO = 'por_sucursal';

    public function up(): void
    {
        if (DB::table('modulo')->where('codigo', self::CODIGO)->exists()) {
            return;
        }
        $padre = DB::table('modulo')->where('codigo', 'fin_contabilidad')->first(['id_modulo', 'grupo']);

        DB::table('modulo')->insert([
            'codigo' => self::CODIGO, 'nombre' => 'Resumen por sucursal', 'descripcion' => 'Ventas, compras, tickets y personal de cada sucursal y agencia',
            'icono' => 'map', 'ruta' => '/sistema/por-sucursal', 'id_modulo_padre' => $padre?->id_modulo, 'grupo' => $padre?->grupo ?? 'Finanzas',
            'orden' => (int) DB::table('modulo')->where('id_modulo_padre', $padre?->id_modulo)->max('orden') + 1, 'activo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('modulo')->where('codigo', self::CODIGO)->delete();
    }
};
