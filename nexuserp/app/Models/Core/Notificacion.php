<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table      = 'notificacion';
    protected $primaryKey = 'id_notificacion';
    public $timestamps    = false;

    protected $fillable = [
        'id_usuario',
        'tipo',
        'titulo',
        'mensaje',
        'url_destino',
        'leida',
        'fecha_leida',
    ];

    protected $casts = [
        'leida'      => 'boolean',
        'fecha_leida'=> 'datetime',
        'created_at' => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function marcarLeida(): void
    {
        $this->update([
            'leida'       => true,
            'fecha_leida' => now(),
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopePorUsuario($query, $idUsuario)
    {
        return $query->where('id_usuario', $idUsuario);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeRecientes($query, int $limite = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limite);
    }
}