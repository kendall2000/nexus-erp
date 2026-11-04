<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos propios de cada empresa para sus documentos (facturas, órdenes de compra, constancias…),
 * separados de los del consorcio (ConfiguracionSistema): el logo ya existía en logo_url; se agregan
 * el régimen fiscal, el sitio web y la leyenda al pie de las facturas.
 */
return new class extends Migration
{
    private const COLUMNAS = ['regimen_fiscal', 'sitio_web', 'leyenda_factura'];

    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $t) {
            if (! Schema::hasColumn('empresa', 'regimen_fiscal')) {
                $t->string('regimen_fiscal', 150)->nullable()->after('iva_incluido_en_precio');
            }
            if (! Schema::hasColumn('empresa', 'sitio_web')) {
                $t->string('sitio_web', 150)->nullable()->after('email_corporativo');
            }
            if (! Schema::hasColumn('empresa', 'leyenda_factura')) {
                $t->string('leyenda_factura', 500)->nullable()->after('logo_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $t) {
            $t->dropColumn(self::COLUMNAS);
        });
    }
};
