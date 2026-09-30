<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de acciones ampliado (pedido del usuario, 2026-09-30) en este orden de
 * columnas: Ver · Crear · Editar · Eliminar · Exportar · Anular · Cancelar · Asignar ·
 * Configurar · Gestionar · Imprimir · Aprobar · Cobrar · Condonar · Devolver dinero ·
 * Cerrar · Reabrir (+ Procesar, que usa Nómina). Además se asignan a los módulos donde
 * tienen sentido; Configurar y Gestionar quedan listas para marcarse en «Módulos».
 * Solo agrega datos; cada permiso puede darse por rol o como extra por usuario.
 */
return new class extends Migration
{
    private const ACCIONES = [
        'ver' => ['Ver', 'Ver y listar.'],
        'crear' => ['Crear', 'Crear registros nuevos.'],
        'editar' => ['Editar', 'Modificar, activar y desactivar registros.'],
        'eliminar' => ['Eliminar', 'Eliminar registros.'],
        'exportar' => ['Exportar', 'Exportar a Excel o PDF.'],
        'anular' => ['Anular', 'Anular documentos emitidos.'],
        'cancelar' => ['Cancelar', 'Cancelar documentos.'],
        'asignar' => ['Asignar', 'Asignar a otra persona.'],
        'configurar' => ['Configurar', 'Cambiar la configuración del módulo.'],
        'gestionar' => ['Gestionar', 'Administración completa del módulo.'],
        'imprimir' => ['Imprimir', 'Imprimir documentos.'],
        'aprobar' => ['Aprobar', 'Aprobar (órdenes de compra, presupuestos…).'],
        'cobrar' => ['Cobrar', 'Registrar cobros.'],
        'condonar' => ['Condonar', 'Perdonar saldos, mora o intereses.'],
        'devolver' => ['Devolver dinero', 'Registrar devoluciones de dinero.'],
        'cerrar' => ['Cerrar', 'Cerrar (tickets, periodos…).'],
        'reabrir' => ['Reabrir', 'Reabrir lo que se cerró.'],
        'procesar' => ['Procesar', 'Procesar (nómina…).'],
    ];

    /** Orden anterior de las acciones que ya existían (para la reversa). */
    private const ORDEN_ANTERIOR = [
        'ver' => 1, 'crear' => 2, 'editar' => 3, 'eliminar' => 4, 'exportar' => 5, 'aprobar' => 6,
        'cancelar' => 7, 'anular' => 8, 'asignar' => 9, 'cerrar' => 10, 'procesar' => 11,
    ];

    /** módulo => acciones que se agregan */
    private const REPARTO = [
        'facturas' => ['imprimir', 'exportar', 'cobrar', 'condonar'],
        'pagos' => ['imprimir', 'exportar', 'devolver'],
        'presupuesto' => ['exportar', 'cerrar', 'reabrir'],
        'ordenes_compra' => ['imprimir', 'exportar'],
        'recepciones' => ['imprimir'],
        'contratos' => ['imprimir', 'cerrar', 'reabrir'],
        'tickets' => ['reabrir'],
        'nomina' => ['imprimir', 'cerrar', 'reabrir'],
        'productos' => ['exportar'],
        'proveedores' => ['exportar'],
        'bodegas' => ['exportar'],
        'categorias' => ['exportar'],
        'cuentas_contables' => ['exportar'],
        'centros_costo' => ['exportar'],
        'empleados' => ['exportar'],
        'clientes' => ['imprimir', 'asignar'],
    ];

    public function up(): void
    {
        $orden = 1;
        foreach (self::ACCIONES as $codigo => [$nombre, $descripcion]) {
            DB::table('accion')->updateOrInsert(['codigo' => $codigo], ['nombre' => $nombre, 'descripcion' => $descripcion, 'orden' => $orden++, 'activo' => true]);
        }
        $acciones = DB::table('accion')->pluck('id_accion', 'codigo');

        foreach (self::REPARTO as $codigoModulo => $nuevas) {
            $modulo = DB::table('modulo')->where('codigo', $codigoModulo)->first(['id_modulo', 'nombre']);
            if (! $modulo) {
                continue;
            }
            foreach ($nuevas as $accion) {
                $existe = DB::table('permiso')->where(['id_modulo' => $modulo->id_modulo, 'id_accion' => $acciones[$accion]])->exists();
                if (! $existe) {
                    DB::table('permiso')->insert([
                        'id_modulo' => $modulo->id_modulo, 'id_accion' => $acciones[$accion],
                        'descripcion' => self::ACCIONES[$accion][0].' '.mb_strtolower($modulo->nombre),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $acciones = DB::table('accion')->pluck('id_accion', 'codigo');

        // Permisos agregados por el reparto (y sus asignaciones).
        foreach (self::REPARTO as $codigoModulo => $nuevas) {
            $idModulo = DB::table('modulo')->where('codigo', $codigoModulo)->value('id_modulo');
            $ids = DB::table('permiso')->where('id_modulo', $idModulo)
                ->whereIn('id_accion', collect($nuevas)->map(fn ($a) => $acciones[$a] ?? 0))->pluck('id_permiso');
            DB::table('rol_permiso')->whereIn('id_permiso', $ids)->delete();
            DB::table('usuario_permiso')->whereIn('id_permiso', $ids)->delete();
            DB::table('permiso')->whereIn('id_permiso', $ids)->delete();
        }

        // Acciones nuevas que ya no usa ningún permiso.
        $nuevas = array_diff(array_keys(self::ACCIONES), array_keys(self::ORDEN_ANTERIOR));
        DB::table('accion')->whereIn('codigo', $nuevas)
            ->whereNotIn('id_accion', DB::table('permiso')->select('id_accion'))->delete();
        foreach (self::ORDEN_ANTERIOR as $codigo => $orden) {
            DB::table('accion')->where('codigo', $codigo)->update(['orden' => $orden]);
        }
    }
};
