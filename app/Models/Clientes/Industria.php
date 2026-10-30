<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;

class Industria extends Model
{
    protected $table      = 'industria';
    protected $primaryKey = 'id_industria';
    public $timestamps    = false;

    protected $fillable = [
        'nombre',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'id_industria');
    }

    public function prospectos()
    {
        return $this->hasMany(\App\Models\CRM\Prospecto::class, 'id_industria');
    }
}