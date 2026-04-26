<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id_usuario,
            'username'       => $this->username,
            'email'          => $this->email,
            'nombre_completo'=> $this->nombre_completo,
            'avatar_url'     => $this->avatar_url,
            'ultimo_login'   => $this->ultimo_login?->toDateTimeString(),
            'activo'         => $this->activo,

            // Empresa del usuario
            'empresa' => $this->whenLoaded('empresa', fn() => [
                'id'              => $this->empresa->id_empresa,
                'nombre_comercial'=> $this->empresa->nombre_comercial,
                'logo_url'        => $this->empresa->logo_url,
            ]),

            // Sucursal del usuario
            'sucursal' => $this->whenLoaded('sucursal', fn() => [
                'id'     => $this->sucursal->id_sucursal,
                'nombre' => $this->sucursal->nombre,
            ]),

            // Roles y permisos — crítico para el frontend
            'roles' => $this->whenLoaded('roles', fn() =>
                $this->roles->pluck('nombre')
            ),

            'permisos' => $this->whenLoaded('roles', fn() =>
                $this->roles->flatMap(fn($rol) =>
                    $rol->permisos->pluck('codigo')
                )->unique()->values()
            ),

            // Timestamps
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}