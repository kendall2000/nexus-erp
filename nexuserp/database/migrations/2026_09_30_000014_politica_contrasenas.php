<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Política de contraseñas configurable (Seguridad y accesos): largo mínimo, complejidad,
 * vencimiento y no repetir las últimas. Los valores por defecto son la regla que ya regía
 * (12 caracteres con mayúsculas, números y símbolos; sin vencimiento ni historial), así que
 * nada cambia hasta que el administrador la ajuste. A los usuarios existentes se les cuenta
 * el vencimiento desde hoy, para que nadie quede vencido al activarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ConfiguracionSistema', function (Blueprint $table) {
            $table->unsignedTinyInteger('passwordMinimo')->default(12)->after('sesionExpiraMin')->comment('Largo mínimo de la contraseña');
            $table->boolean('passwordMayusculas')->default(true)->after('passwordMinimo')->comment('Exige mayúsculas y minúsculas');
            $table->boolean('passwordNumeros')->default(true)->after('passwordMayusculas')->comment('Exige números');
            $table->boolean('passwordSimbolos')->default(true)->after('passwordNumeros')->comment('Exige símbolos');
            $table->unsignedSmallInteger('passwordVenceDias')->default(0)->after('passwordSimbolos')->comment('Días de vigencia (0 = no vence)');
            $table->unsignedTinyInteger('passwordHistorial')->default(0)->after('passwordVenceDias')->comment('Últimas contraseñas que no se pueden repetir (0 = sin control)');
        });

        Schema::table('usuario', function (Blueprint $table) {
            $table->dateTime('password_cambiado_at')->nullable()->after('password_hash');
            $table->boolean('debe_cambiar_password')->default(false)->after('password_cambiado_at')
                ->comment('El administrador puso la contraseña: debe cambiarla al entrar');
        });
        DB::table('usuario')->update(['password_cambiado_at' => now()]);

        Schema::create('historial_password', function (Blueprint $table) {
            $table->bigIncrements('id_historial');
            $table->unsignedInteger('id_usuario')->index();
            $table->string('password_hash');
            $table->dateTime('created_at')->useCurrent();
            $table->comment('Contraseñas anteriores (solo el hash) para no repetirlas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_password');
        Schema::table('usuario', fn (Blueprint $table) => $table->dropColumn(['password_cambiado_at', 'debe_cambiar_password']));
        Schema::table('ConfiguracionSistema', fn (Blueprint $table) => $table->dropColumn([
            'passwordMinimo', 'passwordMayusculas', 'passwordNumeros', 'passwordSimbolos', 'passwordVenceDias', 'passwordHistorial',
        ]));
    }
};
