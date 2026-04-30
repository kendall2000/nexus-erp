<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ConfiguracionSistemaController extends Controller
{
    /**
     * Obtiene la configuración actual del sistema
     */
    public function index()
    {
        try {
            // Obtenemos todos los registros de configuración (login, general, etc.)
            $configuraciones = DB::table('ConfiguracionSistema')->get();

            // Transformamos los múltiples registros en un solo objeto clave-valor
            // Si en un futuro fusionas todo en una sola fila, esto seguirá funcionando.
            $config = [];
            foreach ($configuraciones as $row) {
                foreach ((array) $row as $key => $value) {
                    if (!is_null($value)) {
                        $config[$key] = $value;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data'    => $config
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza la configuración
     */
    public function update(Request $request)
    {
        try {
            $data = $request->all();
            $idUsuario = $request->user()->idUsuario ?? null; // Si usas autenticación

            // Datos a actualizar (excluimos campos que no se tocan directamente)
            $updateData = collect($data)->except(['idConfig', 'tipo', 'fechaCreacion', 'fechaActualizacion'])->toArray();
            $updateData['actualizadoPor'] = $idUsuario;
            $updateData['fechaActualizacion'] = now();

            // Actualizamos todos los registros (si usas fila por 'tipo', puedes adaptarlo aquí)
            // Por simplicidad, actualizamos toda la tabla con los valores enviados.
            DB::table('ConfiguracionSistema')->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Configuración guardada exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }
}