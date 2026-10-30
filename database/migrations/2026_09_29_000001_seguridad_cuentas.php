<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paso 2 de seguridad (igual que sistema-restaurante): recordar sesión,
 * verificación en dos pasos, sesiones en BD (para ver y cerrar las abiertas),
 * recuperación de contraseña, 2 pasos obligatorio por rol e historial de
 * accesos con más eventos. Solo agrega; no modifica ni borra datos.
 */
return new class extends Migration
{
    private const ACCIONES_ANTES = ['LOGIN_OK', 'LOGIN_FAIL', 'LOGOUT', 'CAMBIO_PASSWORD', 'RESET_PASSWORD'];

    private const ACCIONES_NUEVAS = ['BLOQUEO', 'DESACTIVADO', 'LOGIN_FAIL_2FA', 'SESION_CERRADA'];

    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->string('remember_token', 100)->nullable()->after('password_hash');
            $table->text('two_factor_secret')->nullable()->after('remember_token');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });

        Schema::table('rol', function (Blueprint $table) {
            $table->boolean('requiere_2fa')->default(false)->after('activo')
                ->comment('Obliga a sus usuarios a activar la verificación en dos pasos');
        });

        Schema::table('ConfiguracionSistema', function (Blueprint $table) {
            $table->unsignedSmallInteger('bloqueoMinutos')->default(15)->after('maxIntentosSesion')
                ->comment('Minutos de bloqueo tras agotar los intentos (usuario + IP)');
        });

        // Historial de accesos: más eventos y un detalle opcional.
        Schema::table('auditoria_acceso', function (Blueprint $table) {
            $table->string('detalle', 255)->nullable()->after('user_agent');
        });
        if (DB::getDriverName() === 'mysql') {
            $valores = "'".implode("','", [...self::ACCIONES_ANTES, ...self::ACCIONES_NUEVAS])."'";
            DB::statement("ALTER TABLE auditoria_acceso MODIFY accion ENUM({$valores}) NOT NULL");
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');

        if (DB::getDriverName() === 'mysql') {
            // Los eventos nuevos se guardan como LOGIN_FAIL antes de quitarlos del enum.
            DB::table('auditoria_acceso')->whereIn('accion', self::ACCIONES_NUEVAS)->update(['accion' => 'LOGIN_FAIL']);
            $valores = "'".implode("','", self::ACCIONES_ANTES)."'";
            DB::statement("ALTER TABLE auditoria_acceso MODIFY accion ENUM({$valores}) NOT NULL");
        }
        Schema::table('auditoria_acceso', fn (Blueprint $table) => $table->dropColumn('detalle'));

        Schema::table('ConfiguracionSistema', fn (Blueprint $table) => $table->dropColumn('bloqueoMinutos'));
        Schema::table('rol', fn (Blueprint $table) => $table->dropColumn('requiere_2fa'));
        Schema::table('usuario', fn (Blueprint $table) => $table->dropColumn([
            'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        ]));
    }
};
