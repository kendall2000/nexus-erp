<?php

namespace App\Console\Commands;

use App\Models\RRHH\EmpleadoDocumento;
use App\Support\Archivos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Pasa al disco privado los documentos del expediente que se subieron como públicos (antes
 * de que existiera «contabo_privado»): copia cada archivo a «expedientes/{empleado}/», guarda
 * la nueva ruta en la BD y borra el público. Después de esto, sus URLs dejan de funcionar.
 */
class DocumentosPrivados extends Command
{
    protected $signature = 'empleados:documentos-privados
                              {--dry-run : Solo muestra qué documentos se moverían}';

    protected $description = 'Mueve los documentos del expediente de empleados del bucket público al disco privado.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if (! $dryRun && ! (Archivos::disponible() && Archivos::disponible(Archivos::DISCO_PRIVADO))) {
            $this->error('Faltan las credenciales de Contabo en el .env.');

            return self::FAILURE;
        }

        $publico = Storage::disk(Archivos::DISCO);
        $privado = Storage::disk(Archivos::DISCO_PRIVADO);
        $movidos = $omitidos = $errores = 0;

        $documentos = EmpleadoDocumento::query()->where('url_archivo', 'like', 'http%')->orderBy('id_doc')->get();
        $this->info(($dryRun ? '[Simulación] ' : '').$documentos->count().' documento(s) con enlace público.');

        foreach ($documentos as $d) {
            $origen = Archivos::rutaPublica($d->url_archivo);
            if (! $origen) {
                $this->warn("  #{$d->id_doc} «{$d->nombre}»: la URL no es del bucket del sistema; se deja igual.");
                $omitidos++;

                continue;
            }
            $destino = 'expedientes/'.$d->id_empleado.'/'.basename($origen);
            $this->line("  #{$d->id_doc} «{$d->nombre}»: {$origen} → {$destino}");
            if ($dryRun) {
                continue;
            }

            try {
                if (! $publico->exists($origen)) {
                    $this->warn('    El archivo ya no existe en el bucket público; se deja igual.');
                    $omitidos++;

                    continue;
                }
                $privado->writeStream($destino, $publico->readStream($origen), ['visibility' => 'private']);
                $d->update(['url_archivo' => $destino]);
                $publico->delete($origen);
                $movidos++;
            } catch (Throwable $e) {
                report($e);
                $this->error('    No se pudo mover: '.$e->getMessage());
                $errores++;
            }
        }

        if (! $dryRun) {
            $this->info("Movidos: {$movidos} · omitidos: {$omitidos} · con error: {$errores}.");
        }

        return $errores ? self::FAILURE : self::SUCCESS;
    }
}
