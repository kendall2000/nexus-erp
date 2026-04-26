<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    protected $table      = 'municipio';
    protected $primaryKey = 'id_municipio';
    public $timestamps    = false;

    protected $fillable = [
        'id_division',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function division()
    {
        return $this->belongsTo(DivisionGeografica::class, 'id_division');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}