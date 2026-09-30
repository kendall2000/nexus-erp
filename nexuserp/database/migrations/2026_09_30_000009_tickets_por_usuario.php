<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paso 7e (Tickets): quien responde o escala es un usuario del sistema, que no siempre es un
 * empleado. Se guarda el usuario en comentarios y escalaciones (el empleado queda opcional) y se
 * agregan los permisos tickets.editar (responder, cambiar estado) y tickets.configurar (categorías y SLA).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_comentario', function (Blueprint $t) {
            $t->unsignedInteger('id_usuario')->nullable()->after('id_autor');
        });
        Schema::table('escalacion_ticket', function (Blueprint $t) {
            $t->unsignedInteger('escalado_por')->nullable()->change();
            $t->unsignedInteger('id_usuario')->nullable()->after('escalado_a');
        });

        $modulo = DB::table('modulo')->where('codigo', 'tickets')->value('id_modulo');
        foreach (['editar' => 'Responder y cambiar el estado de tickets', 'configurar' => 'Configurar categorías y SLA de tickets'] as $accion => $descripcion) {
            $idAccion = DB::table('accion')->where('codigo', $accion)->value('id_accion');
            if ($modulo && $idAccion && ! DB::table('permiso')->where(['id_modulo' => $modulo, 'id_accion' => $idAccion])->exists()) {
                DB::table('permiso')->insert(['id_modulo' => $modulo, 'id_accion' => $idAccion, 'descripcion' => $descripcion]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ticket_comentario', fn (Blueprint $t) => $t->dropColumn('id_usuario'));
        Schema::table('escalacion_ticket', fn (Blueprint $t) => $t->dropColumn('id_usuario'));
        $modulo = DB::table('modulo')->where('codigo', 'tickets')->value('id_modulo');
        $acciones = DB::table('accion')->whereIn('codigo', ['editar', 'configurar'])->pluck('id_accion');
        $permisos = DB::table('permiso')->where('id_modulo', $modulo)->whereIn('id_accion', $acciones)->pluck('id_permiso');
        DB::table('rol_permiso')->whereIn('id_permiso', $permisos)->delete();
        DB::table('usuario_permiso')->whereIn('id_permiso', $permisos)->delete();
        DB::table('permiso')->whereIn('id_permiso', $permisos)->delete();
    }
};
