<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Models\Clientes\ContratoServicio;
use App\Models\CRM\Oportunidad;
use App\Models\CRM\Ticket;
use App\Models\Finanzas\Factura;
use App\Models\Finanzas\Pago;
use App\Models\Finanzas\PresupuestoAnual;
use App\Models\Inventario\Producto;
use App\Models\RRHH\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Inicio del sistema: saludo, analítica interactiva y cifras principales de la empresa del usuario. */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $idEmpresa = $usuario->id_empresa;

        $tarjetas = [
            ['titulo' => 'Clientes activos', 'icono' => 'briefcase', 'color' => 'primary', 'enlace' => $usuario->puede('clientes.ver') ? url('/sistema/clientes') : null,
                'valor' => Cliente::where('id_empresa', $idEmpresa)->where('activo', 1)->count()],
            ['titulo' => 'Empleados', 'icono' => 'users', 'color' => 'success', 'enlace' => $usuario->puede('empleados.ver') ? url('/sistema/empleados') : null,
                'valor' => Empleado::where('id_empresa', $idEmpresa)->where('estado', 'ACTIVO')->count()],
            ['titulo' => 'Contratos vigentes', 'icono' => 'file-text', 'color' => 'info', 'enlace' => $usuario->puede('contratos.ver') ? url('/sistema/contratos') : null,
                'valor' => ContratoServicio::where('id_empresa', $idEmpresa)->where('estado', 'VIGENTE')->count()],
            ['titulo' => 'Tickets abiertos', 'icono' => 'headphones', 'color' => 'warning', 'enlace' => $usuario->puede('tickets.ver') ? url('/sistema/tickets') : null,
                'valor' => Ticket::where('id_empresa', $idEmpresa)->whereNotIn('estado', ['CERRADO'])->count()],
        ];

        $facturasRecientes = $usuario->puede('facturas.ver')
            ? Factura::where('id_empresa', $idEmpresa)->with('cliente')->orderByDesc('fecha_emision')->orderByDesc('id_factura')->limit(6)->get()
            : collect();

        $ticketsRecientes = $usuario->puede('tickets.ver')
            ? Ticket::where('id_empresa', $idEmpresa)->whereNotIn('estado', ['CERRADO'])->with(['cliente', 'asignadoA'])->orderByDesc('id_ticket')->limit(6)->get()
            : collect();

        $saldoPorCobrar = (float) Factura::where('id_empresa', $idEmpresa)
            ->whereIn('estado', Cliente::ESTADOS_CON_SALDO)
            ->sum('saldo_pendiente');

        $facturadoMes = (float) Factura::where('id_empresa', $idEmpresa)
            ->whereNotIn('estado', ['BORRADOR', 'ANULADA'])
            ->whereBetween('fecha_emision', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total');

        $oportunidadesAbiertas = Oportunidad::where('id_empresa', $idEmpresa)
            ->whereHas('etapa', fn ($q) => $q->where('es_ganada', false)->where('es_perdida', false))
            ->count();

        // ── Datos para Gráficos Analíticos ECharts ────────────────────────────
        // 1. Facturación vs Cobranza del mes actual (por semanas)
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();
        $semanasLabels = ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5'];
        $facturacionSemanas = [0, 0, 0, 0, 0];
        $cobranzaSemanas = [0, 0, 0, 0, 0];

        $facturasDelMes = Factura::where('id_empresa', $idEmpresa)
            ->whereNotIn('estado', ['BORRADOR', 'ANULADA'])
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->get();

        foreach ($facturasDelMes as $f) {
            $dia = (int) $f->fecha_emision->format('j');
            $idx = min(4, (int) floor(($dia - 1) / 7));
            $facturacionSemanas[$idx] += round((float) $f->total, 2);
        }

        $pagosDelMes = Pago::where('id_empresa', $idEmpresa)
            ->where('estado', 'APLICADO')
            ->whereBetween('fecha_pago', [$inicioMes, $finMes])
            ->get();

        foreach ($pagosDelMes as $p) {
            $dia = (int) $p->fecha_pago->format('j');
            $idx = min(4, (int) floor(($dia - 1) / 7));
            $cobranzaSemanas[$idx] += round((float) $p->monto, 2);
        }

        // 2. Flujo de caja proyectado vs real (últimos 6 meses)
        $mesesFlujo = [];
        $flujoProyectado = [];
        $flujoReal = [];

        for ($i = 5; $i >= 0; $i--) {
            $mesObj = now()->subMonths($i);
            $nombreMes = ucfirst($mesObj->locale('es')->isoFormat('MMM'));
            $mesesFlujo[] = $nombreMes;
            $numMes = (int) $mesObj->format('n');
            $anioMes = (int) $mesObj->format('Y');

            $colMes = 'pre_' . PresupuestoAnual::MESES[$numMes];
            $proy = (float) PresupuestoAnual::where('id_empresa', $idEmpresa)
                ->where('anio', $anioMes)
                ->sum($colMes);

            $real = (float) Factura::where('id_empresa', $idEmpresa)
                ->whereNotIn('estado', ['BORRADOR', 'ANULADA'])
                ->whereYear('fecha_emision', $anioMes)
                ->whereMonth('fecha_emision', $numMes)
                ->sum('total');

            $flujoProyectado[] = round($proy, 2);
            $flujoReal[] = round($real, 2);
        }

        // 3. Inventario bajo stock mínimo
        $productosBajoStock = Producto::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->where('stock_minimo', '>', 0)
            ->select('id_producto', 'codigo', 'nombre', 'stock_minimo', 'unidad_medida')
            ->withSum('stocks as stock_actual', 'cantidad_actual')
            ->get()
            ->map(function ($p) {
                $actual = (float) ($p->stock_actual ?? 0);
                $minimo = (float) $p->stock_minimo;
                return [
                    'nombre' => $p->codigo ?: $p->nombre,
                    'nombre_completo' => $p->nombre,
                    'stock_actual' => $actual,
                    'stock_minimo' => $minimo,
                    'deficit' => max(0, $minimo - $actual),
                    'unidad' => $p->unidad_medida,
                ];
            })
            ->filter(fn ($p) => $p['stock_actual'] <= $p['stock_minimo'])
            ->sortByDesc('deficit')
            ->values()
            ->take(6);

        // 4. Tickets pendientes por prioridad
        $prioridadesConteo = Ticket::where('id_empresa', $idEmpresa)
            ->whereNotIn('estado', ['CERRADO'])
            ->select('prioridad', DB::raw('count(*) as total'))
            ->groupBy('prioridad')
            ->pluck('total', 'prioridad')
            ->all();

        $ticketsPrioridad = [
            'BAJA' => (int) ($prioridadesConteo['BAJA'] ?? 0),
            'MEDIA' => (int) ($prioridadesConteo['MEDIA'] ?? 0),
            'ALTA' => (int) ($prioridadesConteo['ALTA'] ?? 0),
            'CRITICA' => (int) ($prioridadesConteo['CRITICA'] ?? 0),
        ];

        return view('dashboard.index', [
            'usuario' => $usuario,
            'empresa' => $usuario->empresa,
            'tarjetas' => $tarjetas,
            'facturasRecientes' => $facturasRecientes,
            'ticketsRecientes' => $ticketsRecientes,
            'saldoPorCobrar' => $saldoPorCobrar,
            'facturadoMes' => $facturadoMes,
            'oportunidadesAbiertas' => $oportunidadesAbiertas,
            'analitica' => [
                'facturacionVsCobranza' => [
                    'semanas' => $semanasLabels,
                    'facturacion' => $facturacionSemanas,
                    'cobranza' => $cobranzaSemanas,
                ],
                'flujoCaja' => [
                    'meses' => $mesesFlujo,
                    'proyectado' => $flujoProyectado,
                    'real' => $flujoReal,
                ],
                'inventarioBajoStock' => $productosBajoStock,
                'ticketsPrioridad' => $ticketsPrioridad,
            ],
        ]);
    }
}
