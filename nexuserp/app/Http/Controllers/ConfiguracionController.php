<?php

namespace App\Http\Controllers;

use App\Support\Archivos;
use App\Support\Seguridad;
use App\Support\Sistema;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Configuración del sistema (solo Administrador): datos generales, apariencia,
 * textos del login, imágenes (en Contabo) y correos. Las reglas de inicio de
 * sesión se editan en «Seguridad y accesos».
 *
 * Se guarda en todas las filas de ConfiguracionSistema (login y general), como
 * hacía la pantalla anterior; el sistema lee la fila «login».
 */
class ConfiguracionController extends Controller
{
    /** Campos de imagen: columna => [título, ayuda]. */
    public const IMAGENES = [
        'imgLogo' => ['Logo principal', 'Barra superior y login.'],
        'imgLogoOscuro' => ['Logo para modo oscuro', 'Opcional: se usa cuando el tema está oscuro.'],
        'imgFavicon' => ['Favicon', 'Ícono de la pestaña del navegador (cuadrado).'],
        'imgFondoLogin' => ['Fondo del login', 'Imagen de la mitad izquierda del inicio de sesión.'],
        'imgAvatarDefault' => ['Avatar por defecto', 'Para usuarios sin foto de perfil.'],
        'imgBannerDashboard' => ['Banner del dashboard', 'Reservado para el inicio.'],
        'imgLogoEmail' => ['Logo para correos', 'Reservado para las plantillas de correo.'],
        'imgFondoEmail' => ['Encabezado de correos', 'Reservado para las plantillas de correo.'],
        'imgLogoReporte' => ['Logo para reportes PDF', 'Reservado para los reportes.'],
        'imgFondoError404' => ['Imagen de error 404', 'Reservado para la página de error.'],
    ];

    public const FORMATOS_FECHA = ['d/m/Y' => '31/12/2026', 'd-m-Y' => '31-12-2026', 'm/d/Y' => '12/31/2026', 'Y-m-d' => '2026-12-31'];

    public function edit(): View
    {
        return view('configuracion.edit', [
            'config' => Sistema::config(),
            'imagenes' => self::IMAGENES,
            'formatosFecha' => self::FORMATOS_FECHA,
            'zonas' => DateTimeZone::listIdentifiers(DateTimeZone::AMERICA),
            'contaboListo' => Archivos::disponible(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(collect($request->only(['colorPrimario', 'colorSecundario', 'colorAccent']))
            ->map(fn ($c) => strtolower(trim((string) $c)))->all());

        $color = ['required', 'regex:/^#[0-9a-f]{6}$/'];
        $texto = fn (int $max) => ['nullable', 'string', 'max:'.$max];

        $datos = $request->validate([
            // Sistema
            'nombreSistema' => ['required', 'string', 'max:100'],
            'nombreEmpresa' => $texto(100),
            'slogan' => $texto(255),
            'nit' => $texto(20),
            'telefono' => $texto(20),
            'correoContacto' => ['nullable', 'email', 'max:100'],
            'direccion' => $texto(255),
            'sitioWeb' => ['nullable', 'url:https,http', 'max:100'],
            // Parámetros regionales
            'moneda' => ['required', 'string', 'max:10'],
            'monedaCodigo' => ['required', 'string', 'size:3', 'alpha'],
            'zonaHoraria' => ['required', Rule::in(DateTimeZone::listIdentifiers())],
            'formatoFecha' => ['required', Rule::in(array_keys(self::FORMATOS_FECHA))],
            'diasMora' => ['nullable', 'integer', 'min:0', 'max:365'],
            'porcentajeMora' => ['nullable', 'numeric', 'min:0', 'max:100'],
            // Apariencia
            'colorPrimario' => $color,
            'colorSecundario' => $color,
            'colorAccent' => $color,
            // Login
            'loginTitulo' => $texto(100),
            'loginSubtitulo' => $texto(150),
            'loginMensajeBienve' => $texto(255),
            'loginLabelUsuario' => $texto(50),
            'loginPlaceholderUs' => $texto(100),
            'loginLabelPassword' => $texto(50),
            'loginLabelRecordar' => $texto(50),
            'loginLinkOlvide' => $texto(50),
            'loginTextBoton' => $texto(50),
            // Pie de página
            'footerTexto' => $texto(255),
            'footerVersion' => $texto(20),
            // Correos
            'emailAsuntoReset' => $texto(150),
            'emailAsuntoBienve' => $texto(150),
            'emailAsuntoCuota' => $texto(150),
            'emailFirma' => $texto(255),
            // Imágenes
            'imagen' => ['array'],
            'imagen.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,ico', 'max:4096'], // sin SVG: puede llevar scripts
            'quitar' => ['array'],
            'quitar.*' => [Rule::in(array_keys(self::IMAGENES))],
        ], [
            'regex' => 'El color debe tener el formato #rrggbb.',
            'monedaCodigo.size' => 'El código de moneda tiene 3 letras (p. ej. GTQ, USD).',
        ], [
            'colorPrimario' => 'color primario', 'colorSecundario' => 'color secundario', 'colorAccent' => 'color de acento',
            'imagen.*' => 'imagen',
        ]);

        $cambios = collect($datos)->except(['imagen', 'quitar'])->all();
        $cambios['monedaCodigo'] = strtoupper($cambios['monedaCodigo']);
        $cambios['footerAnio'] = (int) date('Y');

        // Imágenes: quitar y/o subir a Contabo (carpeta sistema/).
        $actual = Sistema::config();
        $errores = [];
        foreach ($request->input('quitar', []) as $campo) {
            Archivos::borrar($actual->{$campo});
            $cambios[$campo] = null;
        }
        foreach (array_keys(self::IMAGENES) as $campo) {
            if (! $request->hasFile("imagen.{$campo}")) {
                continue;
            }
            try {
                $cambios[$campo] = Archivos::subir($request->file("imagen.{$campo}"), 'sistema', "imagen.{$campo}");
                Archivos::borrar($actual->{$campo});
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errores["imagen.{$campo}"] = self::IMAGENES[$campo][0].': '.$e->validator->errors()->first();
            }
        }

        DB::table('ConfiguracionSistema')->update($cambios + [
            'actualizadoPor' => $request->user()->id_usuario,
            'fechaActualizacion' => now(),
        ]);
        Seguridad::olvidar(); // la zona horaria se aplica en la siguiente petición

        $respuesta = redirect()->route('configuracion.edit', ['pestana' => $request->input('pestana')])
            ->with('status', 'Configuración guardada.');

        return $errores ? $respuesta->withErrors($errores) : $respuesta;
    }
}
