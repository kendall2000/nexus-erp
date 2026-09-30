<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Paso 7c: «empleados.configurar» administra la organización (departamentos y cargos). */
return new class extends Migration
{
    public function up(): void
    {
        $modulo = DB::table('modulo')->where('codigo', 'empleados')->value('id_modulo');
        $accion = DB::table('accion')->where('codigo', 'configurar')->value('id_accion');
        if ($modulo && $accion && ! DB::table('permiso')->where(['id_modulo' => $modulo, 'id_accion' => $accion])->exists()) {
            DB::table('permiso')->insert(['id_modulo' => $modulo, 'id_accion' => $accion, 'descripcion' => 'Configurar departamentos y cargos']);
        }
    }

    public function down(): void
    {
        $modulo = DB::table('modulo')->where('codigo', 'empleados')->value('id_modulo');
        $accion = DB::table('accion')->where('codigo', 'configurar')->value('id_accion');
        $permiso = DB::table('permiso')->where(['id_modulo' => $modulo, 'id_accion' => $accion])->value('id_permiso');
        if ($permiso) {
            DB::table('rol_permiso')->where('id_permiso', $permiso)->delete();
            DB::table('usuario_permiso')->where('id_permiso', $permiso)->delete();
            DB::table('permiso')->where('id_permiso', $permiso)->delete();
        }
    }
};
