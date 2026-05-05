<?php

namespace App\Http\Controllers\Api\V1\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Finanzas\Cliente;
use App\Models\Core\Pais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $clientes = Cliente::with('pais')
            ->where('id_empresa', $idEmpresa)
            ->orderBy('razon_social')
            ->get()
            ->map(fn($c) => [
                'id_cliente'       => $c->id_cliente,
                'razon_social'     => $c->razon_social,
                'nombre_comercial' => $c->nombre_comercial ?? '—',
                'nit'              => $c->nit ?? '—',
                'pais'             => $c->pais?->nombre ?? '—',
                'tipo_persona'     => $c->tipo_persona,
                'segmento'         => $c->segmento ?? '—',
                'categoria'        => $c->categoria ?? '—',
                'telefono_principal'=> $c->telefono_principal ?? '—',
                'email_principal'  => $c->email_principal ?? '—',
                'dias_credito'     => $c->dias_credito,
                'moneda_facturacion'=> $c->moneda_facturacion,
                'activo'           => $c->activo ? 'Activo' : 'Inactivo',
            ]);

        return response()->json(['success' => true, 'data' => $clientes]);
    }

    public function catalogos(): JsonResponse
    {
        $paises     = Pais::where('activo', true)->orderBy('nombre')->get(['id_pais as id', 'nombre as name']);
        $industrias = DB::table('industria')->orderBy('nombre')->get(['id_industria as id', 'nombre as name']);
        $monedas    = ['GTQ', 'USD', 'EUR', 'HNL', 'NIO', 'CRC'];
        $tipos      = ['JURIDICA', 'NATURAL'];
        $segmentos  = ['GRANDE', 'MEDIANA', 'PEQUENA', 'GOBIERNO', 'ONG'];
        $categorias = ['A', 'B', 'C'];

        return response()->json([
            'success' => true,
            'data'    => compact('paises', 'industrias', 'monedas', 'tipos', 'segmentos', 'categorias'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'id_pais'           => 'required|exists:pais,id_pais',
            'razon_social'      => 'required|string|max:250',
            'nombre_comercial'  => 'nullable|string|max:150',
            'nit'               => [
                'nullable', 'string', 'max:20',
                Rule::unique('cliente', 'nit')
                    ->where('id_empresa', $idEmpresa)
                    ->whereNull('deleted_at'),
            ],
            'tipo_persona'      => 'required|in:JURIDICA,NATURAL',
            'email_principal'   => 'nullable|email|max:150',
            'telefono_principal'=> 'nullable|string|max:20',
            'sitio_web'         => 'nullable|string|max:200',
            'direccion_fiscal'  => 'nullable|string|max:300',
            'segmento'          => 'nullable|in:GRANDE,MEDIANA,PEQUENA,GOBIERNO,ONG',
            'categoria'         => 'nullable|in:A,B,C',
            'moneda_facturacion'=> 'required|string|size:3',
            'dias_credito'      => 'required|integer|min:0|max:255',
            'limite_credito'    => 'nullable|numeric|min:0',
        ]);

        $cliente = Cliente::create(array_merge(
            $request->all(),
            ['id_empresa' => $idEmpresa, 'created_by' => $request->user()->id_usuario]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado correctamente.',
            'data'    => ['id_cliente' => $cliente->id_cliente],
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $cliente = Cliente::where('id_empresa', $request->user()->id_empresa)
                          ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $cliente]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $cliente   = Cliente::where('id_empresa', $idEmpresa)->findOrFail($id);

        $request->validate([
            'id_pais'           => 'required|exists:pais,id_pais',
            'razon_social'      => 'required|string|max:250',
            'nombre_comercial'  => 'nullable|string|max:150',
            'nit'               => [
                'nullable', 'string', 'max:20',
                Rule::unique('cliente', 'nit')
                    ->where('id_empresa', $idEmpresa)
                    ->whereNull('deleted_at')
                    ->ignore($id, 'id_cliente'),
            ],
            'tipo_persona'      => 'required|in:JURIDICA,NATURAL',
            'email_principal'   => 'nullable|email|max:150',
            'telefono_principal'=> 'nullable|string|max:20',
            'sitio_web'         => 'nullable|string|max:200',
            'direccion_fiscal'  => 'nullable|string|max:300',
            'segmento'          => 'nullable|in:GRANDE,MEDIANA,PEQUENA,GOBIERNO,ONG',
            'categoria'         => 'nullable|in:A,B,C',
            'moneda_facturacion'=> 'required|string|size:3',
            'dias_credito'      => 'required|integer|min:0|max:255',
            'limite_credito'    => 'nullable|numeric|min:0',
        ]);

        $cliente->update(array_merge(
            $request->all(),
            ['updated_by' => $request->user()->id_usuario]
        ));

        return response()->json(['success' => true, 'message' => 'Cliente actualizado correctamente.']);
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $cliente = Cliente::where('id_empresa', $request->user()->id_empresa)->findOrFail($id);
        $cliente->update(['activo' => !$cliente->activo]);

        return response()->json([
            'success' => true,
            'message' => $cliente->activo ? 'Cliente activado.' : 'Cliente desactivado.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $cliente = Cliente::where('id_empresa', $request->user()->id_empresa)->findOrFail($id);

        if ($cliente->facturas()->whereNotIn('estado', ['ANULADA'])->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un cliente con facturas activas.',
            ], 422);
        }

        $cliente->delete();

        return response()->json(['success' => true, 'message' => 'Cliente eliminado correctamente.']);
    }
}