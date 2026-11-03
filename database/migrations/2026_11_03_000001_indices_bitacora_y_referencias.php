<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices que faltaban en el esquema real.
 *
 * - auditoria_cambio (bitácora): es la tabla que más crece (una fila por cada cambio)
 *   y la pantalla la lista con «where id_empresa = ? order by id_cambio desc»; sin un
 *   índice por empresa recorre la tabla completa. También la lista de tablas del
 *   filtro y el historial de un registro filtran por empresa.
 * - Columnas id_* sin índice: las usan Referencias::enUso (impedir borrar un registro
 *   en uso) y los listados que filtran por ellas.
 *
 * Cada índice se crea solo si no existe, así que se puede aplicar en cualquier BD.
 */
return new class extends Migration
{
    private const INDICES = [
        ['auditoria_cambio', 'idx_cambio_empresa', ['id_empresa', 'id_cambio']],
        ['auditoria_cambio', 'idx_cambio_empresa_tabla', ['id_empresa', 'tabla_afectada', 'id_registro']],
        ['cliente', 'idx_cliente_municipio', ['id_municipio']],
        ['sucursal', 'idx_sucursal_municipio', ['id_municipio']],
        ['sitio_trabajo', 'idx_sitio_municipio', ['id_municipio']],
        ['escalacion_ticket', 'idx_escalacion_usuario', ['id_usuario']],
        ['ticket_comentario', 'idx_comentario_usuario', ['id_usuario']],
        ['historial_salarial', 'idx_historial_cargo', ['id_cargo']],
        ['meta_venta', 'idx_meta_linea', ['id_linea']],
        ['prestacion_laboral', 'idx_prestacion_periodo', ['id_periodo']],
    ];

    public function up(): void
    {
        foreach (self::INDICES as [$tabla, $nombre, $columnas]) {
            if (Schema::hasTable($tabla) && ! Schema::hasIndex($tabla, $nombre)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->index($columnas, $nombre));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDICES as [$tabla, $nombre]) {
            if (Schema::hasTable($tabla) && Schema::hasIndex($tabla, $nombre)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->dropIndex($nombre));
            }
        }
    }
};
