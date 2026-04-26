<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    protected $table      = 'ConfiguracionSistema';
    protected $primaryKey = 'idConfig';
    public $timestamps    = false;

    protected $fillable = [
        'tipo', 'nombreSistema', 'nombreEmpresa',
        'loginTitulo', 'loginSubtitulo', 'loginTextBoton',
        'loginLabelUsuario', 'loginPlaceholderUs',
        'loginLabelPassword', 'loginLabelRecordar',
        'colorPrimario', 'colorSecundario', 'colorAccent',
        'imgLogo', 'imgFondoLogin', 'imgAvatarDefault',
        'zonaHoraria', 'moneda', 'monedaCodigo', 'idioma',
        'maxIntentosSesion', 'sesionExpiraMin', 'estado',
    ];

    public static function obtenerLogin(): ?self
    {
        return self::where('tipo', 'login')->where('estado', 1)->first();
    }

    public static function obtenerGeneral(): ?self
    {
        return self::where('tipo', 'general')->where('estado', 1)->first();
    }
}