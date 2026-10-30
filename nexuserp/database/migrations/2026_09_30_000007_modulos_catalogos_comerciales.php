<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Paso 7b: módulos para los catálogos comerciales, que antes solo se editaban en la base.
 * Idempotente: si el módulo o el permiso ya existe, no lo duplica.
 */
return new class extends Migration
{
    /** código => [grupo, nombre, ícono, ruta, acciones] */
    private const MODULOS = [
        'lineas_negocio' => ['CRM', 'Líneas de negocio', 'layers', '/sistema/lineas-negocio', ['ver', 'crear', 'editar', 'eliminar']],
        'tipos_servicio' => ['CRM', 'Tipos de servicio', 'list', '/sistema/tipos-servicio', ['ver', 'crear', 'editar', 'eliminar', 'exportar']],
        'series_facturacion' => ['Finanzas', 'Series de facturación', 'hash', '/sistema/series-facturacion', ['ver', 'crear', 'editar', 'eliminar']],
    ];

    public function up(): void
    {
        $acciones = DB::table('accion')->pluck('id_accion', 'codigo');
        foreach (self::MODULOS as $codigo => [$grupo, $nombre, $icono, $ruta, $accionesModulo]) {
            $idModulo = DB::table('modulo')->where('codigo', $codigo)->value('id_modulo') ?? DB::table('modulo')->insertGetId([
                'codigo' => $codigo, 'nombre' => $nombre, 'icono' => $icono, 'ruta' => $ruta, 'grupo' => $grupo,
                'orden' => (int) DB::table('modulo')->where('grupo', $grupo)->max('orden') + 1, 'activo' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($accionesModulo as $accion) {
                DB::table('permiso')->insertOrIgnore([
                    'id_modulo' => $idModulo, 'id_accion' => $acciones[$accion],
                    'descripcion' => DB::table('accion')->where('codigo', $accion)->value('nombre').' '.mb_strtolower($nombre),
                ]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('modulo')->whereIn('codigo', array_keys(self::MODULOS))->pluck('id_modulo');
        $permisos = DB::table('permiso')->whereIn('id_modulo', $ids)->pluck('id_permiso');
        DB::table('rol_permiso')->whereIn('id_permiso', $permisos)->delete();
        DB::table('usuario_permiso')->whereIn('id_permiso', $permisos)->delete();
        DB::table('permiso')->whereIn('id_permiso', $permisos)->delete();
        DB::table('modulo')->whereIn('id_modulo', $ids)->delete();
    }
};
