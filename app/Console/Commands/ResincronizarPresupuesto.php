<?php

namespace App\Console\Commands;

use App\Models\Finanzas\Factura;
use App\Models\Finanzas\PresupuestoAnual;
use App\Models\Inventario\OrdenCompra;
use App\Support\EjecucionPresupuesto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rehace el ejecutado del presupuesto con las mismas reglas que EjecucionPresupuesto.
 *
 *  1. Documentos vigentes del año (órdenes aprobadas, facturas emitidas) que no están en
 *     ejecucion_presupuesto, porque se aprobaron antes de que existiera: se anotan en los
 *     presupuestos aprobados o cerrados de su partida (no se sabe cómo estaba el presupuesto
 *     ese día; un borrador nunca ejecuta).
 *  2. Documentos cancelados o anulados que siguen anotados: se marcan como revertidos.
 *  3. eje_* y total_ejecutado de cada presupuesto = suma de lo anotado y no revertido.
 */
class ResincronizarPresupuesto extends Command
{
    protected $signature = 'presupuesto:resync
                              {--empresa= : ID de empresa (opcional, default todas)}
                              {--anio= : Año a resincronizar (default año actual)}
                              {--dry-run : Solo muestra cambios sin aplicar}';

    protected $description = 'Resincroniza el ejecutado del presupuesto desde las facturas emitidas y las órdenes de compra aprobadas.';

    private const ORDENES_VIGENTES = ['ENVIADA', 'PARCIAL', 'RECIBIDA'];

    private const FACTURAS_SIN_EJECUCION = ['BORRADOR', 'ANULADA'];

    public function handle(): int
    {
        $idEmpresa = $this->option('empresa') ? (int) $this->option('empresa') : null;
        $anio = (int) ($this->option('anio') ?? now()->year);
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Resincronizando presupuesto año {$anio}".($dryRun ? ' (DRY-RUN)' : ''));

        $antes = $this->presupuestos($anio, $idEmpresa)->keyBy('id_presupuesto');
        if ($antes->isEmpty()) {
            $this->warn("No se encontraron presupuestos para el año {$anio}.");

            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            [$anotados, $revertidos] = $this->sincronizarDocumentos($anio, $idEmpresa);
            $this->recalcular($antes->keys()->all());
            $despues = $this->presupuestos($anio, $idEmpresa);
            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->line("  Documentos anotados: {$anotados} · marcados como revertidos: {$revertidos}");
        foreach ($despues as $p) {
            $actual = (float) $antes[$p->id_presupuesto]->total_ejecutado;
            if (abs($actual - (float) $p->total_ejecutado) >= 0.005 || $this->output->isVerbose()) {
                $this->line("  Presupuesto #{$p->id_presupuesto} (centro={$p->id_centro}, cuenta={$p->id_cuenta}): actual=".
                    number_format($actual, 2).' → nuevo='.number_format((float) $p->total_ejecutado, 2));
            }
        }
        $this->info($dryRun ? 'Simulación completada (no se guardaron cambios).' : 'Resincronización aplicada correctamente.');

        return self::SUCCESS;
    }

    /** @return array{0: int, 1: int} [documentos anotados, documentos marcados como revertidos] */
    private function sincronizarDocumentos(int $anio, ?int $idEmpresa): array
    {
        $anotados = 0;
        $estados = [PresupuestoAnual::ESTADO_APROBADO, PresupuestoAnual::ESTADO_CERRADO];

        $ordenes = OrdenCompra::query()->with('detalles.producto')->whereYear('fecha_emision', $anio)
            ->when($idEmpresa, fn ($q) => $q->where('id_empresa', $idEmpresa))->get();
        foreach ($ordenes as $oc) {
            if (in_array($oc->estado, self::ORDENES_VIGENTES, true) && ! $this->anotado(EjecucionPresupuesto::ORDEN, $oc->id_oc)) {
                EjecucionPresupuesto::registrar(EjecucionPresupuesto::ORDEN, $oc->id_oc, $oc->id_empresa, $oc->fecha_emision, EjecucionPresupuesto::partidasOrden($oc), $estados);
                $anotados++;
            }
        }

        $facturas = Factura::query()->with('detalles.tipoServicio')->whereYear('fecha_emision', $anio)
            ->when($idEmpresa, fn ($q) => $q->where('id_empresa', $idEmpresa))->get();
        foreach ($facturas as $f) {
            if (! in_array($f->estado, self::FACTURAS_SIN_EJECUCION, true) && ! $this->anotado(EjecucionPresupuesto::FACTURA, $f->id_factura)) {
                EjecucionPresupuesto::registrar(EjecucionPresupuesto::FACTURA, $f->id_factura, $f->id_empresa, $f->fecha_emision, EjecucionPresupuesto::partidasFactura($f), $estados);
                $anotados++;
            }
        }

        $sinEjecucion = fn (string $documento, array $ids) => DB::table('ejecucion_presupuesto')
            ->where('documento', $documento)->whereIn('id_documento', $ids)->whereNull('revertido_at')->update(['revertido_at' => now()]);
        $revertidos = $sinEjecucion(EjecucionPresupuesto::ORDEN, $ordenes->whereNotIn('estado', self::ORDENES_VIGENTES)->pluck('id_oc')->all())
            + $sinEjecucion(EjecucionPresupuesto::FACTURA, $facturas->whereIn('estado', self::FACTURAS_SIN_EJECUCION)->pluck('id_factura')->all());

        return [$anotados, $revertidos];
    }

    private function anotado(string $documento, int $id): bool
    {
        return DB::table('ejecucion_presupuesto')->where('documento', $documento)->where('id_documento', $id)->exists();
    }

    /** eje_* y total_ejecutado = lo anotado y no revertido de cada presupuesto. */
    private function recalcular(array $ids): void
    {
        $sumas = DB::table('ejecucion_presupuesto')->whereIn('id_presupuesto', $ids)->whereNull('revertido_at')
            ->groupBy('id_presupuesto', 'mes')->selectRaw('id_presupuesto, mes, SUM(monto) AS monto')->get()
            ->groupBy('id_presupuesto');

        foreach ($ids as $id) {
            $datos = ['total_ejecutado' => 0.0];
            foreach (PresupuestoAnual::MESES as $mes => $nombre) {
                $monto = round((float) ($sumas[$id] ?? collect())->firstWhere('mes', $mes)?->monto, 4);
                $datos["eje_{$nombre}"] = $monto;
                $datos['total_ejecutado'] += $monto;
            }
            $datos['total_ejecutado'] = round($datos['total_ejecutado'], 4);
            DB::table('presupuesto_anual')->where('id_presupuesto', $id)->update($datos);
        }
    }

    private function presupuestos(int $anio, ?int $idEmpresa)
    {
        return PresupuestoAnual::query()->where('anio', $anio)
            ->when($idEmpresa, fn ($q) => $q->where('id_empresa', $idEmpresa))
            ->orderBy('id_presupuesto')->get();
    }
}
