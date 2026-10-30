<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class CategoriaTicket extends Model
{
    protected $table      = 'categoria_ticket';
    protected $primaryKey = 'id_categoria';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'nombre',
        'descripcion',
        'prioridad_default',
        'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Core\Empresa::class, 'id_empresa');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_categoria');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}