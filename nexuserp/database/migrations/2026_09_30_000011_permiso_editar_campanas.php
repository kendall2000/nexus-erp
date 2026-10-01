<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Paso 7h: «campanas.editar» (cambiar datos y estado de campañas y sus contactos). */
return new class extends Migration
{
    public function up(): void
    {
        $modulo = DB::table('modulo')->where('codigo', 'campanas')->value('id_modulo');
        $accion = DB::table('accion')->where('codigo', 'editar')->value('id_accion');
        if ($modulo && $accion && ! DB::table('permiso')->where(['id_modulo' => $modulo, 'id_accion' => $accion])->exists()) {
            DB::table('permiso')->insert(['id_modulo' => $modulo, 'id_accion' => $accion, 'descripcion' => 'Editar campañas']);
        }
    }

    public function down(): void
    {
        $modulo = DB::table('modulo')->where('codigo', 'campanas')->value('id_modulo');
        $accion = DB::table('accion')->where('codigo', 'editar')->value('id_accion');
        $permiso = DB::table('permiso')->where(['id_modulo' => $modulo, 'id_accion' => $accion])->value('id_permiso');
        if ($permiso) {
            DB::table('rol_permiso')->where('id_permiso', $permiso)->delete();
            DB::table('usuario_permiso')->where('id_permiso', $permiso)->delete();
            DB::table('permiso')->where('id_permiso', $permiso)->delete();
        }
    }
};
