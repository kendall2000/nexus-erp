<?php

namespace App\Console\Commands;

use App\Models\Finanzas\Factura;
use App\Models\Finanzas\PresupuestoAnual;
use App\Models\Core\Empresa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResincronizarPresupuesto extends Command
{
    protected $signature   = 'presupuesto:resync
                              {--empresa= : ID de empresa (opcional, default todas)}
                              {--anio= : Año a resincronizar (default año actual)}
                              {--dry-run : Solo muestra cambios sin aplicar}';

    protected $description = 'Resincroniza el ejecutado del presupuesto desde las facturas EMITIDAS reales.';

    // ════════════════════════════════════════════════════════════
    // ESTE MÉTODO ES EL QUE CAMBIAS
    // ════════════════════════════════════════════════════════════
    public function handle(): int
    {
        $idEmpresa = $this->option('empresa');
        $anio      = (int) ($this->option('anio') ?? now()->year);
        $dryRun    = $this->option('dry-run');

        $this->info("Resincronizando presupuesto año {$anio}" . ($dryRun ? ' (DRY-RUN)' : ''));

        $presupuestos = PresupuestoAnual::where('anio', $anio)
            ->when($idEmpresa, fn($q) => $q->where('id_empresa', $idEmpresa))
            ->get();

        if ($presupuestos->isEmpty()) {
            $this->warn("No se encontraron presupuestos para el año {$anio}.");
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($presupuestos->count());
        $bar->start();

        if ($dryRun) {
            foreach ($presupuestos as $pres) {
                $this->resyncUno($pres, true);
                $bar->advance();
            }
        } else {
            DB::transaction(function () use ($presupuestos, $bar) {
                foreach ($presupuestos as $pres) {
                    $this->resyncUno($pres, false);
                    $bar->advance();
                }
            });
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info('Simulación completada (no se guardaron cambios).');
        } else {
            $this->info('Resincronización aplicada correctamente.');
        }

        return self::SUCCESS;
    }

    // ════════════════════════════════════════════════════════════
    // ESTE MÉTODO NO LO TOCAS (queda igual que ya tienes)
    // ════════════════════════════════════════════════════════════
    private function resyncUno(PresupuestoAnual $pres, bool $dryRun): void
    {
        $empresa     = Empresa::find($pres->id_empresa);
        $tasaIva     = $empresa ? $empresa->tasa_iva_decimal : 0.12;
        $ivaIncluido = $empresa ? (bool) $empresa->iva_incluido_en_precio : true;

        $ejecMensual = array_fill_keys(array_values(PresupuestoAnual::MESES), 0.0);

        $facturas = Factura::with('detalles.tipoServicio')
            ->where('id_empresa', $pres->id_empresa)
            ->whereYear('fecha_emision', $pres->anio)
            ->whereNotIn('estado', ['BORRADOR', 'ANULADA'])
            ->get();

        foreach ($facturas as $factura) {
            $mes       = $factura->fecha_emision->month;
            $nombreMes = PresupuestoAnual::MESES[$mes];

            foreach ($factura->detalles as $linea) {
                $idCentro = $linea->centro_efectivo;
                $idCuenta = $linea->cuenta_efectiva;

                if ($idCentro != $pres->id_centro || $idCuenta != $pres->id_cuenta) continue;

                $subtotal  = (float) $linea->subtotal;
                $montoNeto = $this->calcularMontoNeto($subtotal, $linea->es_afecto_iva, $tasaIva, $ivaIncluido);

                $ejecMensual[$nombreMes] += $montoNeto;
            }
        }

        $totalEjec = round(array_sum($ejecMensual), 4);

        if ($dryRun) {
            $this->newLine();
            $this->line("  Presupuesto #{$pres->id_presupuesto} (centro={$pres->id_centro}, cuenta={$pres->id_cuenta}): " .
                       "actual=Q " . number_format($pres->total_ejecutado, 2) .
                       " → nuevo=Q " . number_format($totalEjec, 2));
            return;
        }

        $datos = ['total_ejecutado' => $totalEjec];
        foreach ($ejecMensual as $nombreMes => $valor) {
            $datos["eje_{$nombreMes}"] = round($valor, 4);
        }

        PresupuestoAnual::where('id_presupuesto', $pres->id_presupuesto)->update($datos);
    }

    // ════════════════════════════════════════════════════════════
    // ESTE MÉTODO NO LO TOCAS (queda igual)
    // ════════════════════════════════════════════════════════════
    private function calcularMontoNeto(
        float $subtotal,
        bool $esAfectoIva,
        float $tasaIva,
        bool $ivaIncluido
    ): float {
        if (!$esAfectoIva)      return round($subtotal, 4);
        if ($ivaIncluido)       return round($subtotal / (1 + $tasaIva), 4);
        return round($subtotal, 4);
    }
}