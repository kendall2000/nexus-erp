<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Support\ExportarCsv;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporte consolidado de las empresas a las que tiene acceso el usuario: ventas, compras,
 * cobros y cartera, y presupuesto. Cada sección incluye solo las empresas donde tiene el
 * permiso del módulo (facturas, ordenes_compra, pagos, presupuesto). Los montos se agrupan
 * por moneda: nunca se suman monedas distintas.
 */
class ConsolidadoController extends Controller
{
    /** sección => [título, módulo del permiso] */
    public const SECCIONES = [
        'ventas' => ['Ventas y facturación', 'facturas'],
        'compras' => ['Compras', 'ordenes_compra'],
        'cobros' => ['Cobros y cartera', 'pagos'],
        'presupuesto' => ['Presupuesto', 'presupuesto'],
    ];

    /** Órdenes de compra aprobadas (las que ejecutan presupuesto). */
    public const ESTADOS_OC = ['ENVIADA', 'PARCIAL', 'RECIBIDA'];

    /** Antigüedad de la cartera según los días de atraso desde el vencimiento. */
    public const TRAMOS = ['al_dia' => 'Al día', 'd30' => '1 a 30 días', 'd60' => '31 a 60 días', 'd90' => '61 a 90 días', 'mas90' => 'Más de 90 días'];

    public const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public function index(Request $request): View
    {
        $anio = $this->anio($request);

        return view('consolidado.index', [
            'anio' => $anio,
            'anios' => range((int) now()->year, (int) now()->year - 4),
            'datos' => $this->datos($request, $anio, 'ver'),
            'puedeExportar' => collect(self::SECCIONES)->contains(fn ($s) => $request->user()->puede($s[1].'.exportar')),
        ]);
    }

    /** Todo el reporte en formato largo (sección, empresa, moneda, concepto, valor) para Excel. */
    public function exportar(Request $request): StreamedResponse
    {
        $anio = $this->anio($request);
        $datos = $this->datos($request, $anio, 'exportar');
        $nombre = fn ($id) => $datos['empresas'][$id]->nombre_comercial ?? "Empresa {$id}";
        $filas = [];
        $agregar = function (string $seccion, array $tabla, array $conceptos) use (&$filas, $nombre) {
            foreach ($tabla['filas'] as $f) {
                foreach ($conceptos as $campo => $etiqueta) {
                    $filas[] = [$seccion, $f['nombre'] ?? $nombre($f['id_empresa']), $f['moneda'], $etiqueta, number_format((float) $f[$campo], 2, '.', '')];
                }
            }
        };

        if ($datos['ventas']) {
            $agregar('Ventas', $datos['ventas'], ['facturado' => 'Facturado', 'notas_credito' => 'Notas de crédito', 'neto' => 'Venta neta', 'saldo' => 'Saldo por cobrar']);
        }
        if ($datos['compras']) {
            $agregar('Compras', $datos['compras'], ['ordenes' => 'Órdenes aprobadas', 'total' => 'Total aprobado', 'recibido' => 'Total recibido']);
        }
        if ($datos['cobros']) {
            $agregar('Cobros', $datos['cobros']['cobros'], ['cobrado' => 'Cobrado']);
            $agregar('Cartera', $datos['cobros']['cartera'], self::TRAMOS + ['total' => 'Total cartera']);
        }
        if ($datos['presupuesto']) {
            $agregar('Presupuesto', $datos['presupuesto']['empresas'], ['presupuestado' => 'Presupuestado', 'ejecutado' => 'Ejecutado', 'disponible' => 'Disponible']);
        }

        return ExportarCsv::descargar("consolidado_{$anio}", ['Sección', 'Empresa', 'Moneda', 'Concepto', 'Valor'], $filas);
    }

    private function anio(Request $request): int
    {
        $anio = (int) $request->query('anio', now()->year);

        return $anio >= 2000 && $anio <= (int) now()->year + 1 ? $anio : (int) now()->year;
    }

    /** @return array<string, mixed> */
    private function datos(Request $request, int $anio, string $accion): array
    {
        $usuario = $request->user();
        $empresas = $usuario->empresasDisponibles()->keyBy('id_empresa');
        $datos = ['empresas' => $empresas];

        foreach (self::SECCIONES as $clave => [, $modulo]) {
            $ids = $empresas->keys()->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $usuario->puedeEn($id, "{$modulo}.{$accion}"))->values()->all();
            $datos[$clave] = $ids ? $this->{$clave}($ids, $anio) : null;
        }

        return $datos;
    }

    // ── Secciones ────────────────────────────────────────────────────────

    /** Facturado del año (sin borradores ni anuladas), notas de crédito, venta neta por mes y saldo por cobrar hoy. */
    private function ventas(array $ids, int $anio): array
    {
        $filas = [];
        $fila = function ($e, $m) use (&$filas) {
            return $filas["{$e}|{$m}"] ??= ['id_empresa' => (int) $e, 'moneda' => $m, 'facturado' => 0.0, 'notas_credito' => 0.0, 'neto' => 0.0, 'saldo' => 0.0, 'meses' => array_fill(0, 12, 0.0)];
        };

        DB::table('factura')->whereIn('id_empresa', $ids)->whereNotIn('estado', ['BORRADOR', 'ANULADA'])
            ->whereBetween('fecha_emision', ["{$anio}-01-01", "{$anio}-12-31"])
            ->orderBy('id_factura')->get(['id_empresa', 'moneda', 'tipo', 'total', 'fecha_emision'])
            ->each(function ($f) use ($fila, &$filas) {
                $fila($f->id_empresa, $f->moneda);
                $r = &$filas["{$f->id_empresa}|{$f->moneda}"];
                $monto = (float) $f->total;
                $signo = $f->tipo === 'NOTA_CREDITO' ? -1 : 1;
                $r[$signo < 0 ? 'notas_credito' : 'facturado'] += $monto;
                $r['neto'] += $signo * $monto;
                $r['meses'][(int) Carbon::parse($f->fecha_emision)->month - 1] += $signo * $monto;
            });

        DB::table('factura')->whereIn('id_empresa', $ids)->whereIn('estado', Cliente::ESTADOS_CON_SALDO)
            ->groupBy('id_empresa', 'moneda')->selectRaw('id_empresa, moneda, SUM(saldo_pendiente) as saldo')->get()
            ->each(function ($s) use ($fila, &$filas) {
                $fila($s->id_empresa, $s->moneda);
                $filas["{$s->id_empresa}|{$s->moneda}"]['saldo'] += (float) $s->saldo;
            });

        return $this->tabla($filas, ['facturado', 'notas_credito', 'neto', 'saldo']);
    }

    /** Órdenes de compra aprobadas del año: cantidad, total, lo ya recibido y el total por mes. */
    private function compras(array $ids, int $anio): array
    {
        $filas = [];
        DB::table('orden_compra')->whereIn('id_empresa', $ids)->whereIn('estado', self::ESTADOS_OC)
            ->whereBetween('fecha_emision', ["{$anio}-01-01", "{$anio}-12-31"])
            ->orderBy('id_oc')->get(['id_empresa', 'moneda', 'estado', 'total', 'fecha_emision'])
            ->each(function ($o) use (&$filas) {
                $r = &$filas["{$o->id_empresa}|{$o->moneda}"];
                $r ??= ['id_empresa' => (int) $o->id_empresa, 'moneda' => $o->moneda, 'ordenes' => 0, 'total' => 0.0, 'recibido' => 0.0, 'meses' => array_fill(0, 12, 0.0)];
                $r['ordenes']++;
                $r['total'] += (float) $o->total;
                $r['recibido'] += $o->estado === 'RECIBIDA' ? (float) $o->total : 0;
                $r['meses'][(int) Carbon::parse($o->fecha_emision)->month - 1] += (float) $o->total;
            });

        return $this->tabla($filas, ['ordenes', 'total', 'recibido']);
    }

    /** Cobros aplicados del año por mes y antigüedad de la cartera pendiente hoy. */
    private function cobros(array $ids, int $anio): array
    {
        $cobros = [];
        DB::table('pago')->whereIn('id_empresa', $ids)->where('estado', 'APLICADO')
            ->whereBetween('fecha_pago', ["{$anio}-01-01", "{$anio}-12-31"])
            ->orderBy('id_pago')->get(['id_empresa', 'moneda', 'monto', 'fecha_pago'])
            ->each(function ($p) use (&$cobros) {
                $r = &$cobros["{$p->id_empresa}|{$p->moneda}"];
                $r ??= ['id_empresa' => (int) $p->id_empresa, 'moneda' => $p->moneda, 'cobrado' => 0.0, 'meses' => array_fill(0, 12, 0.0)];
                $r['cobrado'] += (float) $p->monto;
                $r['meses'][(int) Carbon::parse($p->fecha_pago)->month - 1] += (float) $p->monto;
            });

        $cartera = [];
        $hoy = today();
        DB::table('factura')->whereIn('id_empresa', $ids)->whereIn('estado', Cliente::ESTADOS_CON_SALDO)->where('saldo_pendiente', '>', 0)
            ->orderBy('id_factura')->get(['id_empresa', 'moneda', 'saldo_pendiente', 'fecha_vencimiento'])
            ->each(function ($f) use (&$cartera, $hoy) {
                $r = &$cartera["{$f->id_empresa}|{$f->moneda}"];
                $r ??= ['id_empresa' => (int) $f->id_empresa, 'moneda' => $f->moneda, ...array_fill_keys(array_keys(self::TRAMOS), 0.0), 'total' => 0.0];
                $atraso = $f->fecha_vencimiento ? (int) Carbon::parse($f->fecha_vencimiento)->startOfDay()->diffInDays($hoy, false) : 0;
                $tramo = match (true) {
                    $atraso <= 0 => 'al_dia',
                    $atraso <= 30 => 'd30',
                    $atraso <= 60 => 'd60',
                    $atraso <= 90 => 'd90',
                    default => 'mas90',
                };
                $r[$tramo] += (float) $f->saldo_pendiente;
                $r['total'] += (float) $f->saldo_pendiente;
            });

        return [
            'cobros' => $this->tabla($cobros, ['cobrado']),
            'cartera' => $this->tabla($cartera, [...array_keys(self::TRAMOS), 'total']),
        ];
    }

    /** Presupuestos aprobados o cerrados del año: por empresa y por centro de costo. */
    private function presupuesto(array $ids, int $anio): array
    {
        $lineas = DB::table('presupuesto_anual as p')
            ->leftJoin('centro_costo as c', 'c.id_centro', '=', 'p.id_centro')
            ->whereIn('p.id_empresa', $ids)->where('p.anio', $anio)->whereIn('p.estado', ['APROBADO', 'CERRADO'])
            ->groupBy('p.id_empresa', 'p.moneda', 'p.id_centro', 'c.nombre')
            ->selectRaw('p.id_empresa, p.moneda, p.id_centro, c.nombre as centro, SUM(p.total_presupuestado) as presupuestado, SUM(p.total_ejecutado) as ejecutado')
            ->orderBy('p.id_empresa')->orderBy('c.nombre')->get();

        $empresas = [];
        $centros = [];
        foreach ($lineas as $l) {
            $r = &$empresas["{$l->id_empresa}|{$l->moneda}"];
            $r ??= ['id_empresa' => (int) $l->id_empresa, 'moneda' => $l->moneda, 'presupuestado' => 0.0, 'ejecutado' => 0.0, 'disponible' => 0.0];
            $r['presupuestado'] += (float) $l->presupuestado;
            $r['ejecutado'] += (float) $l->ejecutado;
            $r['disponible'] = $r['presupuestado'] - $r['ejecutado'];
            unset($r);
            $centros[] = ['id_empresa' => (int) $l->id_empresa, 'moneda' => $l->moneda, 'nombre' => $l->centro ?? 'Sin centro',
                'presupuestado' => (float) $l->presupuestado, 'ejecutado' => (float) $l->ejecutado, 'disponible' => (float) $l->presupuestado - (float) $l->ejecutado];
        }

        return [
            'empresas' => $this->tabla($empresas, ['presupuestado', 'ejecutado', 'disponible']),
            'centros' => $centros,
        ];
    }

    /**
     * Filas ordenadas por empresa y moneda, redondeadas, más el total por moneda.
     *
     * @return array{filas: list<array>, totales: array<string, array>}
     */
    private function tabla(array $filas, array $campos): array
    {
        $filas = array_values($filas);
        usort($filas, fn ($a, $b) => [$a['id_empresa'], $a['moneda']] <=> [$b['id_empresa'], $b['moneda']]);
        $totales = [];
        foreach ($filas as &$f) {
            $t = &$totales[$f['moneda']];
            $t ??= ['moneda' => $f['moneda'], ...array_fill_keys($campos, 0.0)] + (isset($f['meses']) ? ['meses' => array_fill(0, 12, 0.0)] : []);
            foreach ($campos as $c) {
                $f[$c] = round($f[$c], 4);
                $t[$c] += $f[$c];
            }
            foreach ($f['meses'] ?? [] as $i => $v) {
                $f['meses'][$i] = round($v, 4);
                $t['meses'][$i] += $f['meses'][$i];
            }
            unset($t);
        }
        unset($f);
        ksort($totales);

        return ['filas' => $filas, 'totales' => $totales];
    }
}
