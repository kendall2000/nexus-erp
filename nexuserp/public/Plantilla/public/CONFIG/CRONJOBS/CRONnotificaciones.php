<?php
	include_once('html_fns_cron.php');
	include_once('template_notificacion.php');
	
	$semana = date("W");
	$anio = date("Y");
	$semana = (strlen($semana) <= 1)?"0$semana":$semana;
	//$desde = date("d/m/Y", strtotime($anio."-W".$semana.'-1'));
	//$hasta = date("d/m/Y", strtotime($anio."-W".$semana.'-7'));
	$desde = date("d/m/Y");
	$hasta = date("d/m/Y");
	/// PRUEBA
	
	
	$ClsUsu = new ClsUsuario();
	$result = $ClsUsu->get_usuario('','','','','','1');
	if(is_array($result)){
		foreach($result as $row){
			$usuario = $row["usu_id"];
			$nombre = depurador_texto($row["usu_nombre"]);
			$mail = trim($row["usu_mail"]);
			//---
			$programacion = tabla_programacion($usuario, $desde, $hasta);
			if($programacion["valida"] == true){
				mail_usuario($nombre, $mail, "del $desde as $hasta", $semana, $programacion["tabla"]);
			}else{
				//echo "No tiene programacion";
			}
		}
	}
		
	
	
	
function mail_usuario($nombre_usuario,$mail,$rango_fechas,$semana,$tabla_html){
	
	//////////////////////// CREDENCIALES DE CLIENTE
	$ClsConf = new ClsConfig();
	$result = $ClsConf->get_credenciales();
	if(is_array($result)){
		foreach($result as $row){
			$cliente_nombre = utf8_decode($row['cliente_nombre']);
			$cliente_nombre_reporte = utf8_decode($row['cliente_nombre_reporte']);
		}
	}
	$cliente_nombre = depurador_texto($cliente_nombre);
	$cliente_nombre_reporte = depurador_texto($cliente_nombre_reporte);
	$url = url_origin( $_SERVER );
		
	$mailadmin = "farasi@farasi.com.gt";
    // Instancia el API KEY de Mandrill
	$mandrill = new Mandrill('kY2BCc_1JELItjyrMAB4Qg');
	/////////////_________ Correo a admin
	$subject = "Recordatorio Diario de Maintenance Planner";
	$html = mail_constructor_notificacion($nombre_usuario,$rango_fechas,$semana,$tabla_html);
	
	$to = array(
		array(
			"email" => $mail,
			"name" => $nombre_usuario,
			"type" => "to"
		)
	);
	
	try{
		$message = array(
			'subject' => $subject,
			'html' => $html,
			'from_email' => 'noreply@farasi.com.gt',
			'from_name' => 'BPManagement',
			'to' => $to
		);
		 
		//print_r($message);
		//echo "<br><br><br><br>";
		$result = $mandrill->messages->send($message);
		$validacion =  1;
	} catch(Mandrill_Error $e) { 
		//echo "<br>";
		print_r($e);
		//devuelve un mensaje de manejo de errores
		$validacion =  0;
	}         
		
	return $validacion;
}



function tabla_programacion($usuario, $desde, $hasta){
	$ClsPro = new ClsProgramacionPPM();
	$result = $ClsPro->get_programacion('','', $usuario, '', '', '', '', $desde, $hasta,'','',1);
	
	if(is_array($result)){
		$salida.= '<table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">';
		$salida.= '<thead>';
		$salida.= '<tr>';
		$salida.= '<th align = "center" width = "5%">No.</th>';
		$salida.= '<th align = "center" width = "25%">Sede</th>';
		$salida.= '<th align = "center" width = "30%">Activo</th>';
		$salida.= '<th align = "center" width = "30%">Categor&iacute;a</th>';
		$salida.= '<th align = "center" width = "10%">Fecha</th>';
		$salida.= '</tr>';
		$salida.= '</thead>';
		$salida.= '<tbody>';
		$i=1;
		foreach($result as $row){
			$salida.= '<tr>';
			//No.
			$salida.= '<td align = "center">'.$i.'</td>';
			//sede
			$sede = depurador_texto($row["sed_nombre"]);
			$salida.= '<td align = "left">'.$sede.'</td>';
			//activo
			$activo = depurador_texto($row["act_nombre"]);
			$salida.= '<td align = "left">'.$activo.'</td>';
			//categoria
			$categoria = depurador_texto($row["cat_nombre"]);
			$salida.= '<td align = "left">'.$categoria.'</td>';
			//fecha.
			$fecha = depurador_texto($row["pro_fecha"]);
			$salida.= '<td align = "center">'.$fecha.'</td>';
			//--
			$salida.= '</tr>';
			$i++;
		}
		$salida.= '</tbody>';
		$salida.= '</table>';
		$validacion = true;
	}else{
		$validacion = false;
		$salida = "";
	}
	
	$retorno = array(
		"valida" => $validacion,
		"tabla" => $salida
	);
	return $retorno;
}

//////////////////////////////////////////////////// 
//Convierte fecha de Informix a normal 
//////////////////////////////////////////////////// 
function cambia_fecha($Fecha) 
{ 
if ($Fecha<>""){ 
   $trozos = explode("-",$Fecha,3); 
   return $trozos[2]."/".$trozos[1]."/".$trozos[0]; } 
else 
   {return $Fecha;} 
} 

?>