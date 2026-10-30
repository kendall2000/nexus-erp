<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Historial de accesos: evento DESBLOQUEO (el administrador quita un bloqueo por
 * intentos fallidos desde «Seguridad y accesos»). Solo agrega un valor al enum.
 */
return new class extends Migration
{
    private const ACCIONES = ['LOGIN_OK', 'LOGIN_FAIL', 'LOGOUT', 'CAMBIO_PASSWORD', 'RESET_PASSWORD', 'BLOQUEO', 'DESACTIVADO', 'LOGIN_FAIL_2FA', 'SESION_CERRADA'];

    public function up(): void
    {
        $this->enum([...self::ACCIONES, 'DESBLOQUEO']);
    }

    public function down(): void
    {
        DB::table('auditoria_acceso')->where('accion', 'DESBLOQUEO')->update(['accion' => 'SESION_CERRADA']);
        $this->enum(self::ACCIONES);
    }

    private function enum(array $valores): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE auditoria_acceso MODIFY accion ENUM('".implode("','", $valores)."') NOT NULL");
        }
    }
};
