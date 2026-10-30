<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Paso 7a: el módulo «movimientos» ya tiene pantalla (Kardex) y aparece en el menú. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('modulo')->where('codigo', 'movimientos')->update(['nombre' => 'Kardex', 'ruta' => '/sistema/movimientos', 'icono' => 'repeat']);
    }

    public function down(): void
    {
        DB::table('modulo')->where('codigo', 'movimientos')->update(['nombre' => 'Movimientos de inventario', 'ruta' => null]);
    }
};
