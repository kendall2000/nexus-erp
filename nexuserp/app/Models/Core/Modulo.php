<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Módulo del sistema = opción del menú lateral y «modulo» del código de permiso
 * («bodegas.crear»). Estructura de sistema-inventario.
 */
class Modulo extends Model
{
    protected $table = 'modulo';

    protected $primaryKey = 'id_modulo';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'icono', 'ruta', 'orden', 'id_modulo_padre', 'grupo', 'activo'];

    protected $casts = ['activo' => 'boolean', 'orden' => 'integer'];

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_modulo_padre');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'id_modulo_padre')->orderBy('orden')->orderBy('id_modulo');
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(Permiso::class, 'id_modulo');
    }

    /** Sin acciones = pantalla solo del Administrador. */
    public function soloAdministrador(): bool
    {
        return ! $this->permisos()->exists();
    }
}
