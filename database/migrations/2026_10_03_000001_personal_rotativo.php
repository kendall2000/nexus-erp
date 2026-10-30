<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personal rotativo: empleados que cubren a otros (vacaciones, suspensiones, incapacidades…)
 * y cobran por día cubierto. El empleado se marca como rotativo con su tarifa diaria habitual
 * y cada cobertura guarda la tarifa que se pactó (se puede ajustar por cobertura). Nómina paga
 * los días cubiertos dentro del periodo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleado', function (Blueprint $table) {
            $table->boolean('es_rotativo')->default(false)->after('tipo_contrato')->comment('Cubre a otros empleados y cobra por día');
            $table->decimal('tarifa_dia', 15, 4)->nullable()->after('es_rotativo')->comment('Tarifa diaria habitual del rotativo');
        });

        Schema::create('cobertura_rotativo', function (Blueprint $table) {
            $table->increments('id_cobertura');
            $table->unsignedTinyInteger('id_empresa')->index();
            $table->unsignedInteger('id_rotativo')->index()->comment('Empleado rotativo que cubre');
            $table->unsignedInteger('id_titular')->nullable()->index()->comment('Empleado cubierto (vacío si el puesto está vacante)');
            $table->enum('motivo', ['VACACIONES', 'SUSPENSION', 'INCAPACIDAD', 'PERMISO', 'LICENCIA', 'AUSENCIA', 'VACANTE', 'OTRO']);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('paga_fines_semana')->default(true);
            $table->decimal('dias', 5, 2);
            $table->decimal('tarifa_dia', 15, 4);
            $table->decimal('total', 15, 4);
            $table->enum('estado', ['VIGENTE', 'ANULADA'])->default('VIGENTE');
            $table->string('observaciones', 300)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('anulada_por')->nullable();
            $table->timestamps();
            $table->comment('Coberturas del personal rotativo: quién cubre a quién, cuántos días y a qué tarifa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobertura_rotativo');
        Schema::table('empleado', fn (Blueprint $table) => $table->dropColumn(['es_rotativo', 'tarifa_dia']));
    }
};
