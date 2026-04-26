<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class TicketAdjunto extends Model
{
    protected $table      = 'ticket_adjunto';
    protected $primaryKey = 'id_adjunto';
    public $timestamps    = false;

    protected $fillable = [
        'id_ticket',
        'id_comentario',
        'nombre_archivo',
        'url_archivo',
        'tipo_mime',
        'tamano_bytes',
        'subido_por',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'created_at'   => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }

    public function comentario()
    {
        return $this->belongsTo(TicketComentario::class, 'id_comentario');
    }

    public function getTamanoFormateadoAttribute(): string
    {
        $bytes = $this->tamano_bytes;
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}