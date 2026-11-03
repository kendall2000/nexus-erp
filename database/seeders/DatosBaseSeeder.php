<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Catálogos globales (sin id_empresa) que necesita una base de datos nueva:
 * módulos, acciones y permisos, monedas, geografía, industrias, fuentes de prospectos
 * y la configuración del sistema (como plantilla, se edita en Configuración).
 *
 * Los datos salen de database/seeders/datos/catalogos.json, que genera
 * `php artisan esquema:exportar`. Solo llena tablas vacías: en una BD que ya
 * tiene datos no toca nada.
 *
 * BD nueva: php artisan migrate --force && php artisan db:seed --force
 */
class DatosBaseSeeder extends Seeder
{
    /** Tabla => llave primaria, en orden de dependencias. */
    public const TABLAS = [
        'moneda' => 'codigo',
        'pais' => 'id_pais',
        'division_geografica' => 'id_division',
        'municipio' => 'id_municipio',
        'accion' => 'id_accion',
        'modulo' => 'id_modulo',
        'permiso' => 'id_permiso',
        'industria' => 'id_industria',
        'fuente_lead' => 'id_fuente',
        'ConfiguracionSistema' => 'idConfig',
    ];

    public function run(): void
    {
        $ruta = database_path('seeders/datos/catalogos.json');
        if (! File::exists($ruta)) {
            throw new RuntimeException('Falta database/seeders/datos/catalogos.json: genéralo con «php artisan esquema:exportar».');
        }
        $datos = json_decode(File::get($ruta), true, flags: JSON_THROW_ON_ERROR);

        Schema::withoutForeignKeyConstraints(function () use ($datos) {
            foreach (array_keys(self::TABLAS) as $tabla) {
                $filas = $datos[$tabla] ?? [];
                if (! $filas || DB::table($tabla)->exists()) {
                    $this->command?->line("  {$tabla}: ".($filas ? 'ya tiene datos, se deja igual' : 'sin datos en el catálogo'));

                    continue;
                }
                foreach (array_chunk($filas, 500) as $lote) {
                    DB::table($tabla)->insert($lote);
                }
                $this->command?->info("  {$tabla}: ".count($filas).' filas');
            }
        });
    }
}
