<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Módulo «Bitácora de cambios» (auditoria_cambio): ver y exportar.
 * Idempotente: si el módulo o el permiso ya existe, no lo duplica.
 */
return new class extends Migration
{
    private const CODIGO = 'bitacora';

    public function up(): void
    {
        $idModulo = DB::table('modulo')->where('codigo', self::CODIGO)->value('id_modulo') ?? DB::table('modulo')->insertGetId([
            'codigo' => self::CODIGO, 'nombre' => 'Bitácora de cambios', 'descripcion' => 'Quién creó, cambió o eliminó cada registro',
            'icono' => 'activity', 'ruta' => '/sistema/bitacora', 'grupo' => 'Configuración',
            'orden' => (int) DB::table('modulo')->where('grupo', 'Configuración')->max('orden') + 1, 'activo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['ver' => 'Ver', 'exportar' => 'Exportar'] as $accion => $nombre) {
            DB::table('permiso')->insertOrIgnore([
                'id_modulo' => $idModulo, 'id_accion' => DB::table('accion')->where('codigo', $accion)->value('id_accion'),
                'descripcion' => "{$nombre} bitácora de cambios",
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
