<?php 
require_once("../../ROOT/Clases/ClsTicket.php");
require_once("../../ROOT/Clases/ClsRevision.php");
require_once("../../ROOT/Clases/ClsEscalon.php");
require_once("../../ROOT/Clases/ClsProgramacionPPM.php");
require_once("../../ROOT/Clases/ClsUsuario.php");
require_once("../../ROOT/Clases/ClsConfig.php");
require_once("../constructor.php"); //--correos
require_once("../recursos/mandrill/src/Mandrill.php"); //--correos


function Agrega_Ceros($dato){
    $len = strlen($dato);
	switch($len){
		case 1: $dato = "000$dato"; break;
		case 2: $dato = "00$dato"; break;
		case 3: $dato = "0$dato"; break;
	}
	return $dato;
}

//////////////////////////////////////////////////// 
//quita caracteres de español
//////////////////////////////////////////////////// 
function depurador_texto($texto) {
	$texto = trim($texto);
	$texto = str_replace("á","a",$texto);
	$texto = str_replace("é","e",$texto);
	$texto = str_replace("í","i",$texto);
	$texto = str_replace("ó","o",$texto);
	$texto = str_replace("ú","u",$texto);
	$texto = str_replace("Á","A",$texto);
	$texto = str_replace("É","E",$texto);
	$texto = str_replace("Í","I",$texto);
	$texto = str_replace("Ú","U",$texto);
	$texto = str_replace("ñ","n",$texto);
	$texto = str_replace("Ñ","N",$texto);
     //--
	$texto = str_replace("Ä","A",$texto);
	$texto = str_replace("Ë","E",$texto);
	$texto = str_replace("Ï","I",$texto);
	$texto = str_replace("Ö","O",$texto);
	$texto = str_replace("Ü","U",$texto);
	$texto = str_replace("ä","a",$texto);
	$texto = str_replace("ë","e",$texto);
	$texto = str_replace("ï","i",$texto);
	$texto = str_replace("ö","o",$texto);
	$texto = str_replace("ü","u",$texto);
	
   return $texto;
} 
//////////////////////////////////////////////////// 
// URL DEL SERVIDOR
//////////////////////////////////////////////////// 
function url_origin( $s, $use_forwarded_host = false ){
    $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
    $sp       = strtolower( $s['SERVER_PROTOCOL'] );
    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
    $port     = $s['SERVER_PORT'];
    $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
    $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
    $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}

function full_url( $s, $use_forwarded_host = false ){
    return url_origin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
}


?>