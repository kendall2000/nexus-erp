<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Models\Clientes\ContratoServicio;
use App\Models\CRM\Oportunidad;
use App\Models\CRM\Ticket;
use App\Models\Finanzas\Factura;
use App\Models\RRHH\Empleado;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Inicio del sistema: saludo y cifras principales de la empresa del usuario. */
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

        return view('dashboard.index', [
            'usuario' => $usuario,
            'empresa' => $usuario->empresa,
            'tarjetas' => $tarjetas,
            'facturasRecientes' => $facturasRecientes,
            'ticketsRecientes' => $ticketsRecientes,
            'saldoPorCobrar' => $saldoPorCobrar,
            'facturadoMes' => $facturadoMes,
            'oportunidadesAbiertas' => $oportunidadesAbiertas,
        ]);
    }
}
