<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paso 6 (limpieza): ya no hay API ni tokens (Sanctum) y los permisos son los de Nexus
 * (modulo × accion = permiso, rol_permiso, usuario_permiso), no los de spatie/laravel-permission.
 * Se quitan sus tablas (las de spatie estaban vacías; los tokens eran de la API vieja) y
 * los registros de sus migraciones, cuyos archivos se eliminaron con los paquetes.
 * Irreversible a propósito: los paquetes ya no están instalados.
 */
return new class extends Migration
{
    private const TABLAS = ['role_has_permissions', 'model_has_roles', 'model_has_permissions', 'roles', 'permissions', 'personal_access_tokens'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::dropIfExists($tabla);
        }
        DB::table('migrations')->whereIn('migration', [
            '2026_04_26_032556_create_personal_access_tokens_table',
            '2026_04_26_032559_create_permission_tables',
        ])->delete();
    }

    public function down(): void
    {
        // Sin vuelta atrás: Sanctum y spatie ya no forman parte del proyecto.
    }
};
