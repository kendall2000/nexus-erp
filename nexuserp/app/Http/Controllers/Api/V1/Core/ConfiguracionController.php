<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\ConfiguracionSistema;
use Illuminate\Http\JsonResponse;

class ConfiguracionController extends Controller
{
    public function login(): JsonResponse
    {
        $config = ConfiguracionSistema::obtenerLogin();
        return response()->json([
            'success' => true,
            'data'    => $config,
        ]);
    }

    public function general(): JsonResponse
    {
        $config = ConfiguracionSistema::obtenerGeneral();
        return response()->json([
            'success' => true,
            'data'    => $config,
        ]);
    }
}