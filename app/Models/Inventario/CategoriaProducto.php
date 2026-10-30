<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class CategoriaProducto extends Model
{
    protected $table      = 'categoria_producto';
    protected $primaryKey = 'id_categoria';
    public $timestamps    = false;

    protected $fillable = [
        'id_padre',
        'id_empresa',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function padre()
    {
        return $this->belongsTo(CategoriaProducto::class, 'id_padre');
    }

    public function hijos()
    {
        return $this->hasMany(CategoriaProducto::class, 'id_padre');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_categoria');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeRaices($query)
    {
        return $query->whereNull('id_padre');
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }
}