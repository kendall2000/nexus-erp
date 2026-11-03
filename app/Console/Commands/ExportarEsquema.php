<?php

namespace App\Console\Commands;

use Database\Seeders\DatosBaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Exporta el esquema real de la base de datos a database/schema/mysql-schema.sql.
 *
 * Una base de datos nueva (p. ej. la de una organización nueva) se crea con
 * `php artisan migrate`: Laravel carga primero este archivo y luego aplica las
 * migraciones posteriores. Volver a ejecutar este comando después de cada
 * migración que cambie tablas, para que el archivo no quede desactualizado.
 *
 * Sobre lo que genera `schema:dump` se hacen dos limpiezas para que el archivo
 * cargue tanto en MySQL como en MariaDB y en cualquier servidor:
 *  - Se quita la primera línea «enable the sandbox mode» de mariadb-dump 11,
 *    que el cliente de MySQL no entiende.
 *  - Las vistas pierden su DEFINER (root@localhost no existe en otro servidor)
 *    y pasan a SQL SECURITY INVOKER: se ejecutan con los permisos de quien consulta.
 */
class ExportarEsquema extends Command
{
    protected $signature = 'esquema:exportar';

    protected $description = 'Exporta el esquema real de la BD a database/schema (portable entre MySQL y MariaDB).';

    public function handle(): int
    {
        if ($this->call('schema:dump') !== self::SUCCESS) {
            return self::FAILURE;
        }

        $ruta = database_path('schema/mysql-schema.sql');
        if (! File::exists($ruta)) {
            $this->error("No se generó {$ruta}.");

            return self::FAILURE;
        }

        $sql = File::get($ruta);
        $sql = preg_replace('/\A\/\*M!999999\\\\- enable the sandbox mode \*\/\s*\R/', '', $sql);
        $sql = preg_replace('/\/\*!50013 DEFINER=`[^`]*`@`[^`]*` SQL SECURITY DEFINER \*\//', '/*!50013 SQL SECURITY INVOKER */', $sql);
        File::put($ruta, $sql);

        $restantes = substr_count($sql, 'DEFINER=');
        if ($restantes > 0) {
            $this->warn("Quedaron {$restantes} DEFINER sin limpiar (¿triggers o procedimientos nuevos?). Revísalos.");
        }

        $this->info('Esquema exportado y limpiado: '.str_replace(base_path().'/', '', $ruta));

        $this->exportarCatalogos();

        return self::SUCCESS;
    }

    /**
     * El esquema solo trae la estructura. Una BD nueva también necesita los catálogos
     * globales (sin id_empresa): módulos, acciones y permisos (sin ellos nadie ve el menú),
     * monedas, geografía, industrias, fuentes y la configuración del sistema como plantilla.
     * Las migraciones que insertaban módulos y permisos ya figuran como aplicadas en el
     * esquema, así que no vuelven a correr: estos datos los carga DatosBaseSeeder.
     */
    private function exportarCatalogos(): void
    {
        $datos = [];
        foreach (DatosBaseSeeder::TABLAS as $tabla => $llave) {
            $datos[$tabla] = DB::table($tabla)->orderBy($llave)->get()->map(fn ($fila) => (array) $fila)->all();
        }

        $ruta = database_path('seeders/datos/catalogos.json');
        File::ensureDirectoryExists(dirname($ruta));
        File::put($ruta, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        $resumen = collect($datos)->map(fn ($filas, $tabla) => $tabla.' '.count($filas))->implode(', ');
        $this->info('Catálogos exportados: '.str_replace(base_path().'/', '', $ruta)." ({$resumen})");
    }
}
