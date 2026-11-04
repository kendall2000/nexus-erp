<?php

namespace App\Console\Commands;

use App\Support\CatalogosEmpresa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Guarda los catálogos de una empresa modelo como plantilla del sistema
 * (database/seeders/datos/plantilla-empresa.json): conceptos de nómina, roles y permisos,
 * departamentos y cargos, cuentas y centros de costo, líneas y tipos de servicio, embudo,
 * SLA y categorías de tickets, categorías de productos y plantillas de comunicación.
 *
 * El instalador (nexus:instalar) la usa para la primera empresa de un cliente nuevo, y la
 * pantalla de Empresas la ofrece como «Plantilla del sistema». Va en el repositorio: revisar
 * que la empresa modelo no tenga nada propio de un cliente antes de exportarla.
 */
class ExportarPlantillaEmpresa extends Command
{
    protected $signature = 'nexus:exportar-plantilla {--empresa=1 : Id de la empresa modelo}';

    protected $description = 'Exporta los catálogos de una empresa modelo como plantilla para empresas e instalaciones nuevas.';

    public function handle(): int
    {
        $id = (int) $this->option('empresa');
        $nombre = DB::table('empresa')->where('id_empresa', $id)->value('nombre_comercial');
        if ($nombre === null) {
            $this->error("No existe la empresa {$id}.");

            return self::FAILURE;
        }

        $datos = ['_origen' => ['empresa' => $nombre, 'exportada' => now()->toDateTimeString()]] + CatalogosEmpresa::leer($id);
        File::ensureDirectoryExists(dirname(CatalogosEmpresa::rutaPlantilla()));
        File::put(CatalogosEmpresa::rutaPlantilla(), json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        $this->info("Plantilla exportada desde «{$nombre}»: ".str_replace(base_path().'/', '', CatalogosEmpresa::rutaPlantilla()));
        $this->table(['Tabla', 'Filas'], collect($datos)->except(['_origen', 'permisos_rol'])->map(fn ($filas, $tabla) => [$tabla, count($filas)])->values()
            ->push(['permisos de roles', collect($datos['permisos_rol'])->flatten()->count()])->all());
        $this->warn('Revisa que no lleve nada propio de un cliente: va en el repositorio y se copia a cada instalación nueva.');

        return self::SUCCESS;
    }
}
