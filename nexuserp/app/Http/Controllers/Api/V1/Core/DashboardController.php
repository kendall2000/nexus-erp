<?php
namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Clientes\Cliente;
use App\Models\RRHH\Empleado;
use App\Models\Clientes\ContratoServicio;
use App\Models\CRM\Ticket;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function resumen(Request $request)
    {
        $idEmpresa = $request->user()->id_empresa;

        return response()->json([
            'success' => true,
            'data' => [
                'clientes'  => Cliente::where('id_empresa', $idEmpresa)->where('activo', 1)->count(),
                'empleados' => Empleado::where('id_empresa', $idEmpresa)->where('estado', 'ACTIVO')->count(),
                'contratos' => ContratoServicio::where('id_empresa', $idEmpresa)->where('estado', 'VIGENTE')->count(),
                'tickets'   => Ticket::where('id_empresa', $idEmpresa)->whereNotIn('estado', ['CERRADO'])->count(),
            ]
        ]);
    }
}