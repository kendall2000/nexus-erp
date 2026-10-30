<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class DivisionGeografica extends Model
{
    protected $table      = 'division_geografica';
    protected $primaryKey = 'id_division';
    public $timestamps    = false;

    protected $fillable = ['id_pais', 'nombre', 'tipo', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'id_pais');
    }

    public function municipios()
    {
        return $this->hasMany(Municipio::class, 'id_division');
    }
}