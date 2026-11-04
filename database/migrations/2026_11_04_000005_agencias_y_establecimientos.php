<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agencias como tipo de sucursal: una agencia depende de una sucursal de la misma empresa
 * (id_sucursal_padre). Cada sede puede llevar su código de establecimiento de la SAT (FEL),
 * único dentro de la empresa. Las sedes que ya existen quedan como SUCURSAL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sucursal', function (Blueprint $t) {
            if (! Schema::hasColumn('sucursal', 'tipo')) {
                $t->enum('tipo', ['SUCURSAL', 'AGENCIA'])->default('SUCURSAL')->after('id_empresa');
            }
            if (! Schema::hasColumn('sucursal', 'id_sucursal_padre')) {
                $t->unsignedSmallInteger('id_sucursal_padre')->nullable()->after('tipo')
                    ->comment('Solo agencias: la sucursal de la que dependen');
                $t->foreign('id_sucursal_padre', 'fk_sucursal_padre')->references('id_sucursal')->on('sucursal');
            }
            if (! Schema::hasColumn('sucursal', 'codigo_establecimiento')) {
                $t->unsignedSmallInteger('codigo_establecimiento')->nullable()->after('nombre')
                    ->comment('Número de establecimiento registrado en la SAT (FEL)');
                $t->unique(['id_empresa', 'codigo_establecimiento'], 'uq_sucursal_establecimiento');
            }
        });
    }

    public function down(): void
    {
        DB::table('sucursal')->where('tipo', 'AGENCIA')->update(['id_sucursal_padre' => null]);
        Schema::table('sucursal', function (Blueprint $t) {
            $t->dropForeign('fk_sucursal_padre');
            $t->dropUnique('uq_sucursal_establecimiento');
            $t->dropColumn(['tipo', 'id_sucursal_padre', 'codigo_establecimiento']);
        });
    }
};
