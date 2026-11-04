<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Opción «Empresas» del menú, en Estructura Organizacional antes de Sucursales. Sin permisos:
 * es solo del administrador general (MenuLateral::SOLO_GENERAL y middleware admin:general).
 * Idempotente: si el módulo ya existe, no lo duplica.
 */
return new class extends Migration
{
    private const CODIGO = 'empresas';

    public function up(): void
    {
        if (DB::table('modulo')->where('codigo', self::CODIGO)->exists()) {
            return;
        }
        $sucursales = DB::table('modulo')->where('codigo', 'sucursales')->first(['id_modulo_padre', 'orden', 'grupo']);

        DB::table('modulo')->insert([
            'codigo' => self::CODIGO, 'nombre' => 'Empresas', 'descripcion' => 'Empresas del grupo, con su casa matriz y catálogos',
            'icono' => 'briefcase', 'ruta' => '/sistema/empresas',
            'id_modulo_padre' => $sucursales?->id_modulo_padre ?? DB::table('modulo')->where('codigo', 'cfg_organizacion')->value('id_modulo'),
            'grupo' => $sucursales?->grupo ?? 'Configuración',
            'orden' => max(0, (int) ($sucursales?->orden ?? 603) - 1), 'activo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('modulo')->where('codigo', self::CODIGO)->delete();
    }
};
