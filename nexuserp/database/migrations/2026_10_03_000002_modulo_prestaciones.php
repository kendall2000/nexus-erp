<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Módulo «Prestaciones» (prestacion_laboral): aguinaldo, bono 14 y liquidaciones.
 * Permisos: ver, procesar (calcular, pagar, eliminar cálculos), aprobar y exportar.
 * Idempotente: si el módulo o el permiso ya existe, no lo duplica.
 */
return new class extends Migration
{
    private const CODIGO = 'prestaciones';

    public function up(): void
    {
        $idModulo = DB::table('modulo')->where('codigo', self::CODIGO)->value('id_modulo') ?? DB::table('modulo')->insertGetId([
            'codigo' => self::CODIGO, 'nombre' => 'Prestaciones', 'descripcion' => 'Aguinaldo, bono 14 y liquidaciones',
            'icono' => 'gift', 'ruta' => '/sistema/prestaciones', 'grupo' => 'Recursos humanos',
            'orden' => (int) DB::table('modulo')->where('grupo', 'Recursos humanos')->max('orden') + 1, 'activo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['ver' => 'Ver', 'procesar' => 'Calcular y pagar', 'aprobar' => 'Aprobar', 'exportar' => 'Exportar'] as $accion => $nombre) {
            DB::table('permiso')->insertOrIgnore([
                'id_modulo' => $idModulo, 'id_accion' => DB::table('accion')->where('codigo', $accion)->value('id_accion'),
                'descripcion' => "{$nombre} prestaciones",
            ]);
        }
    }

    public function down(): void
    {
        $id = DB::table('modulo')->where('codigo', self::CODIGO)->value('id_modulo');
        $permisos = DB::table('permiso')->where('id_modulo', $id)->pluck('id_permiso');
        DB::table('rol_permiso')->whereIn('id_permiso', $permisos)->delete();
        DB::table('usuario_permiso')->whereIn('id_permiso', $permisos)->delete();
        DB::table('permiso')->whereIn('id_permiso', $permisos)->delete();
        DB::table('modulo')->where('id_modulo', $id)->delete();
    }
};
