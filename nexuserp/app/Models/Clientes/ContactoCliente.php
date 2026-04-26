<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;

class ContactoCliente extends Model
{
    protected $table      = 'contacto_cliente';
    protected $primaryKey = 'id_contacto';
    public $timestamps    = false;

    protected $fillable = [
        'id_cliente',
        'nombre',
        'cargo',
        'email',
        'telefono',
        'whatsapp',
        'es_contacto_principal',
        'recibe_facturas',
        'activo',
    ];

    protected $casts = [
        'es_contacto_principal' => 'boolean',
        'recibe_facturas'       => 'boolean',
        'activo'                => 'boolean',
        'created_at'            => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeRecibeFacturas($query)
    {
        return $query->where('recibe_facturas', true)->where('activo', true);
    }
}