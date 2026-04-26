<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class FuenteLead extends Model
{
    protected $table      = 'fuente_lead';
    protected $primaryKey = 'id_fuente';
    public $timestamps    = false;

    protected $fillable = ['nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function prospectos()
    {
        return $this->hasMany(Prospecto::class, 'id_fuente');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}