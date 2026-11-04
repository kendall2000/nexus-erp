<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Models\Core\Sucursal;
use App\Support\ExportarCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Resumen por sucursal y agencia de la empresa activa: ventas, compras, tickets y personal en un
 * periodo. Cada sección aparece solo con el permiso de su módulo; los montos se separan por
 * moneda. Cada agencia es su propia fila (no se suma en la de su sucursal), y los documentos
 * anteriores a que se registrara la sede salen en «Sin sucursal».
 */
class ReporteSucursalesController extends Controller
{
    /** sección => [título, permiso] */
    public const SECCIONES = [
        'ventas' => ['Ventas', 'facturas'],
        'compras' => ['Compras', 'ordenes_compra'],
        'tickets' => ['Tickets', 'tickets'],
        'personal' => ['Personal', 'empleados'],
    ];

    /** Órdenes aprobadas (las que ejecutan presupuesto). */
    private const OC_APROBADAS = ['ENVIADA', 'PARCIAL', 'RECIBIDA'];

    public function index(Request $request): View
    {
        [$desde, $hasta] = $this->periodo($request);

        return view('reporte-sucursales.index', [
            'desde' => $desde, 'hasta' => $hasta,
            'datos' => $this->datos($request, $desde, $hasta, 'ver'),
            'puedeExportar' => collect(self::SECCIONES)->contains(fn ($s) => $request->user()->puede($s[1].'.exportar')),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->periodo($request);
        $datos = $this->datos($request, $desde, $hasta, 'exportar');
        $filas = [];
        foreach ($datos['sedes'] as $clave => $sede) {
            foreach ($datos['ventas'][$clave] ?? [] as $moneda => $v) {
                $filas[] = ['Ventas', $sede['nombre'], $moneda, $v['documentos'], $this->monto($v['facturado']), $this->monto($v['notas_credito']), $this->monto($v['neto']), $this->monto($v['saldo'])];
            }
            foreach ($datos['compras'][$clave] ?? [] as $moneda => $c) {
                $filas[] = ['Compras', $sede['nombre'], $moneda, $c['ordenes'], $this->monto($c['total']), '', '', ''];
            }
            if ($t = $datos['tickets'][$clave] ?? null) {
                $filas[] = ['Tickets', $sede['nombre'], '', $t['abiertos'], $t['nuevos'], $t['resueltos'], $t['vencidos'], ''];
            }
            if (isset($datos['personal'][$clave])) {
                $filas[] = ['Personal', $sede['nombre'], '', $datos['personal'][$clave], '', '', '', ''];
            }
        }

        return ExportarCsv::descargar("por_sucursal_{$desde}_{$hasta}",
            ['Sección', 'Sucursal', 'Moneda', 'Documentos / abiertos / empleados', 'Facturado / total / nuevos', 'Notas de crédito / resueltos', 'Venta neta / vencidos', 'Saldo por cobrar'], $filas);
    }

    /** @return array{0: string, 1: string} desde y hasta (Y-m-d); por defecto del 1 de enero a hoy */
    private function periodo(Request $request): array
    {
        $fecha = function (mixed $valor, Carbon $defecto): Carbon {
            try {
                return is_string($valor) && $valor !== '' ? Carbon::parse($valor) : $defecto;
            } catch (\Throwable) {
                return $defecto;
            }
        };
        $desde = $fecha($request->query('desde'), today()->startOfYear());
        $hasta = $fecha($request->query('hasta'), today());

        return $desde->gt($hasta) ? [$hasta->toDateString(), $desde->toDateString()] : [$desde->toDateString(), $hasta->toDateString()];
    }

    /** @return array<string, mixed> */
    private function datos(Request $request, string $desde, string $hasta, string $accion): array
    {
        $usuario = $request->user();
        $idEmpresa = (int) $usuario->id_empresa;
        $datos = ['empresa' => DB::table('empresa')->where('id_empresa', $idEmpresa)->value('nombre_comercial')];
        foreach (self::SECCIONES as $clave => [, $modulo]) {
            $datos[$clave] = $usuario->puede("{$modulo}.{$accion}") ? $this->{$clave}($idEmpresa, $desde, $hasta) : null;
        }

        // Filas: todas las sedes (activas o con datos), en árbol, y «Sin sucursal» si algo cayó ahí.
        $conDatos = collect(array_keys(self::SECCIONES))->flatMap(fn ($s) => array_keys($datos[$s] ?? []))->unique();
        $sedes = Sucursal::enArbol(Sucursal::query()->where('id_empresa', $idEmpresa)->with('padre')->orderByDesc('es_casa_matriz')->orderBy('nombre')->get()
            ->filter(fn ($s) => $s->activo || $conDatos->contains((string) $s->id_sucursal))->values());
        $datos['sedes'] = $sedes->mapWithKeys(fn ($s) => [(string) $s->id_sucursal => [
            'nombre' => $s->nombre, 'agencia' => $s->esAgencia(), 'padre' => $s->padre?->nombre, 'codigo' => $s->codigo_establecimiento, 'activo' => $s->activo,
        ]])->all();
        if ($conDatos->contains('')) {
            $datos['sedes'][''] = ['nombre' => 'Sin sucursal', 'agencia' => false, 'padre' => null, 'codigo' => null, 'activo' => true];
        }

        return $datos;
    }

    // ── Secciones: [clave de sede => …]; la clave es el id como texto, o '' sin sucursal ────

    /** Facturas emitidas en el periodo (sin borradores ni anuladas) por moneda, y saldo por cobrar a hoy. */
    private function ventas(int $idEmpresa, string $desde, string $hasta): array
    {
        $r = [];
        $fila = function ($sede, $moneda) use (&$r) {
            $r[(string) $sede][$moneda] ??= ['documentos' => 0, 'facturado' => 0.0, 'notas_credito' => 0.0, 'neto' => 0.0, 'saldo' => 0.0];
        };
        DB::table('factura')->where('id_empresa', $idEmpresa)->whereNotIn('estado', ['BORRADOR', 'ANULADA'])->whereBetween('fecha_emision', [$desde, $hasta])
            ->groupBy('id_sucursal', 'moneda', 'tipo')->selectRaw('id_sucursal, moneda, tipo, COUNT(*) as n, SUM(total) as total')->get()
            ->each(function ($f) use ($fila, &$r) {
                $fila($f->id_sucursal, $f->moneda);
                $v = &$r[(string) $f->id_sucursal][$f->moneda];
                $esNota = $f->tipo === 'NOTA_CREDITO';
                $v['documentos'] += (int) $f->n;
                $v[$esNota ? 'notas_credito' : 'facturado'] += (float) $f->total;
                $v['neto'] += ($esNota ? -1 : 1) * (float) $f->total;
            });
        DB::table('factura')->where('id_empresa', $idEmpresa)->whereIn('estado', Cliente::ESTADOS_CON_SALDO)
            ->groupBy('id_sucursal', 'moneda')->selectRaw('id_sucursal, moneda, SUM(saldo_pendiente) as saldo')->get()
            ->each(function ($s) use ($fila, &$r) {
                $fila($s->id_sucursal, $s->moneda);
                $r[(string) $s->id_sucursal][$s->moneda]['saldo'] += (float) $s->saldo;
            });

        return $this->ordenarMonedas($r);
    }

    /** Órdenes de compra aprobadas emitidas en el periodo, por moneda. */
    private function compras(int $idEmpresa, string $desde, string $hasta): array
    {
        $r = [];
        DB::table('orden_compra')->where('id_empresa', $idEmpresa)->whereIn('estado', self::OC_APROBADAS)->whereBetween('fecha_emision', [$desde, $hasta])
            ->groupBy('id_sucursal', 'moneda')->selectRaw('id_sucursal, moneda, COUNT(*) as n, SUM(total) as total')->get()
            ->each(function ($o) use (&$r) {
                $r[(string) $o->id_sucursal][$o->moneda] = ['ordenes' => (int) $o->n, 'total' => (float) $o->total];
            });

        return $this->ordenarMonedas($r);
    }

    /** Abiertos y vencidos hoy; nuevos y resueltos en el periodo. */
    private function tickets(int $idEmpresa, string $desde, string $hasta): array
    {
        $activos = "'".implode("','", TicketController::ACTIVOS)."'";
        $r = [];
        DB::table('ticket')->where('id_empresa', $idEmpresa)->groupBy('id_sucursal')
            ->selectRaw("id_sucursal,
                SUM(CASE WHEN estado IN ({$activos}) THEN 1 ELSE 0 END) as abiertos,
                SUM(CASE WHEN estado IN ({$activos}) AND fecha_limite_resolucion < ? THEN 1 ELSE 0 END) as vencidos,
                SUM(CASE WHEN fecha_apertura BETWEEN ? AND ? THEN 1 ELSE 0 END) as nuevos,
                SUM(CASE WHEN fecha_resolucion BETWEEN ? AND ? THEN 1 ELSE 0 END) as resueltos",
                [now(), "{$desde} 00:00:00", "{$hasta} 23:59:59", "{$desde} 00:00:00", "{$hasta} 23:59:59"])
            ->get()->each(function ($t) use (&$r) {
                $fila = ['abiertos' => (int) $t->abiertos, 'vencidos' => (int) $t->vencidos, 'nuevos' => (int) $t->nuevos, 'resueltos' => (int) $t->resueltos];
                if (array_sum($fila) > 0) {
                    $r[(string) $t->id_sucursal] = $fila;
                }
            });

        return $r;
    }

    /** Empleados en planilla hoy (sin bajas ni inactivos). */
    private function personal(int $idEmpresa, string $desde, string $hasta): array
    {
        return DB::table('empleado')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')->whereNotIn('estado', ['BAJA', 'INACTIVO'])
            ->groupBy('id_sucursal')->selectRaw('id_sucursal, COUNT(*) as n')->get()
            ->mapWithKeys(fn ($e) => [(string) $e->id_sucursal => (int) $e->n])->all();
    }

    private function ordenarMonedas(array $r): array
    {
        foreach ($r as &$monedas) {
            ksort($monedas);
        }

        return $r;
    }

    private function monto(float $n): string
    {
        return number_format($n, 2, '.', '');
    }

    /** @return Collection<string, array<string, float>> totales por moneda de una sección de montos */
    public static function totales(?array $seccion, array $campos): Collection
    {
        $t = [];
        foreach ($seccion ?? [] as $monedas) {
            foreach ($monedas as $moneda => $v) {
                foreach ($campos as $c) {
                    $t[$moneda][$c] = ($t[$moneda][$c] ?? 0) + $v[$c];
                }
            }
        }
        ksort($t);

        return collect($t);
    }
}
