<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Acción de un permiso: ver, crear, editar, eliminar… (columnas de la matriz de permisos). */
class Accion extends Model
{
    protected $table = 'accion';

    protected $primaryKey = 'id_accion';

    public $timestamps = false;

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'orden', 'activo'];

    protected $casts = ['activo' => 'boolean', 'orden' => 'integer'];

    public function permisos(): HasMany
    {
        return $this->hasMany(Permiso::class, 'id_accion');
    }
}
