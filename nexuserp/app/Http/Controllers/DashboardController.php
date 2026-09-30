<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Models\Clientes\ContratoServicio;
use App\Models\CRM\Ticket;
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
            ['titulo' => 'Clientes activos', 'icono' => 'briefcase', 'color' => 'primary', 'enlace' => url('/sistema/clientes'),
                'valor' => Cliente::where('id_empresa', $idEmpresa)->where('activo', 1)->count()],
            ['titulo' => 'Empleados', 'icono' => 'users', 'color' => 'success', 'enlace' => null,
                'valor' => Empleado::where('id_empresa', $idEmpresa)->where('estado', 'ACTIVO')->count()],
            ['titulo' => 'Contratos vigentes', 'icono' => 'file-text', 'color' => 'info', 'enlace' => null,
                'valor' => ContratoServicio::where('id_empresa', $idEmpresa)->where('estado', 'VIGENTE')->count()],
            ['titulo' => 'Tickets abiertos', 'icono' => 'headphones', 'color' => 'warning', 'enlace' => null,
                'valor' => Ticket::where('id_empresa', $idEmpresa)->whereNotIn('estado', ['CERRADO'])->count()],
        ];

        return view('dashboard.index', [
            'usuario' => $usuario,
            'empresa' => $usuario->empresa,
            'tarjetas' => $tarjetas,
        ]);
    }
}
