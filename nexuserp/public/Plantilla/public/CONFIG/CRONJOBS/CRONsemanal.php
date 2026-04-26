<?php
	include_once('html_fns_cron.php');
	include_once('template_checklist.php');
	
	$semana = date("W");
	$anio = date("Y");
	$semana = (strlen($semana) <= 1)?"0$semana":$semana;
	$desde = date("d/m/Y", strtotime($anio."-W".$semana.'-1'));
	$hasta = date("d/m/Y", strtotime($anio."-W".$semana.'-7'));
	$desde = "01/01/2019";
	/// RECORRE USUARIOS
	
	$ClsUsu = new ClsUsuario();
	$result = $ClsUsu->get_usuario('','','','','','1');
	if(is_array($result)){
		foreach($result as $row){
			$usuario = $row["usu_id"];
			$nombre = depurador_texto($row["usu_nombre"]);
			$mail = trim($row["usu_mail"]);
			//---
			$usuario = 1;
			$mail = "soporte@plani-go.com";
			$result2 = $ClsUsu->get_usuario_sede('',$usuario,'','',1);
			if(is_array($result2)) {
				$cont_sede = 1;
				foreach ($result2 as $row2){
					$sedes_IN.= $row2['sed_codigo'].",";
					$cont_sede++;
				}
				$cont_sede--; //quita la ultima vuelta
				$sedes_IN = substr($sedes_IN, 0, -1); // quita la ultima coma
			}
			$result2 = $ClsUsu->get_usuario_categoria('',$usuario,'','',1);
			if(is_array($result2)) {
				$cont_categoria = 1;
				foreach ($result2 as $row2){
					$categorias_IN.= $row2['cat_codigo'].",";
					$cont_categoria++;
				}
				$cont_categoria--; //quita la ultima vuelta
				$categorias_IN = substr($categorias_IN, 0, -1); // quita la ultima coma
			}
			
			$programacion = tabla_semanal($sedes_IN, $categorias_IN, $desde, $hasta);
			if($programacion["valida"] == true){
				//echo $programacion["tabla"];
				mail_usuario($nombre, $mail, "del $desde as $hasta", $semana, $programacion["tabla"]);
			}else{
				//echo "No tiene programacion";
			}
			break;
		}
	}
		
	
	
	
function mail_usuario($nombre_usuario,$mail,$rango_fechas,$semana,$tabla_html){
	
	//////////////////////// CREDENCIALES DE COLEGIO
	$archivo = "../../CONFIG/credenciales.txt";
	$texto = file_get_contents($archivo);
	$texto = explode("|",$texto);
	$cliente_codigo = trim($texto[1]);
	$cliente_nombre = trim($texto[3]);
	$cliente_nombre_reporte = depurador_texto(trim($texto[5]));
	$url = url_origin( $_SERVER );
		
	$mailadmin = "soporte@plani-go.com";
    // Instancia el API KEY de Mandrill
	$mandrill = new Mandrill('Wv2bSyaK5LgZ4c7uBp-n1g');
	/////////////_________ Correo a admin
	$subject = "Resumen Semanal de Check List";
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
			'from_email' => 'noreply@planigo.app',
			'from_name' => 'Planigo',
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



function tabla_semanal($sedes_IN, $categorias_IN, $desde, $hasta){
	$ClsRev = new ClsRevision();
	$result = $ClsRev->get_resultados('','','',$sedes_IN,'','', $categorias_IN = '',$desde,$hasta,'','');
	
	if(is_array($result)){
		$salida.= '<table border="1" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">';
		$salida.= '<thead>';
		$salida.= '<tr>';
		$salida.= '<th align = "center" width = "10px">No.</th>';
		$salida.= '<th align = "center" width = "50px">Revisi&oacute;n</th>';
		$salida.= '<th align = "left" width = "150px">Lista</th>';
		$salida.= '<th align = "left" width = "150px">Categor&iacute;a</th>';
		$salida.= '<th align = "center" width = "120px">Fecha</th>';
		$salida.= '<th align = "left" width = "150px">Pregunta</th>';
		$salida.= '<th align = "center" width = "150px">Respuesta</th>';
		$salida.= '<th align = "justify" width = "200px">Observaciones en Revisi&oacute;n</th>';
		$salida.= '</tr>';
		$salida.= '</thead>';
		$salida.= '<tbody>';
		$i=1;
		foreach($result as $row){
			$salida.= '<tr>';
			//codigo
			$salida.= '<td align = "center" >'.$i.'. </td>';
			//nombre
			$codigo = Agrega_Ceros($row["rev_codigo"]);
			$salida.= '<td align = "center"># '.$codigo.'</td>';
			//lista
			$lista = depurador_texto($row["list_nombre"]);
			$salida.= '<td align = "left">'.$lista.'</td>';
			//categoria
			$categoria = depurador_texto($row["cat_nombre"]);
			$salida.= '<td align = "left">'.$categoria.'</td>';
			//fecha
			$fecha = cambia_fechaHora($row["resp_fecha_registro"]);
			$salida.= '<td align = "center">'.$fecha.'</td>';
			//pregunta
			$pregunta = depurador_texto($row["pre_pregunta"]);
			$salida.= '<td align = "left">'.$pregunta.'</td>';
			//respuesta
			$resp = trim($row["resp_respuesta"]);
			if($resp == 1){
				$respuesta = '<span style="color:blue">SI</span>';
			}else if($resp == 2){
				$respuesta = '<strong style="color:red">NO</strong>';
			}else{
				$respuesta = '<strong style="color:gray">No aplica</strong>';
			}
			$salida.= '<td align = "center">'.$respuesta.'</td>';
			//observaciones
			$obs = depurador_texto($row["rev_observaciones"]);
			$salida.= '<td align = "justify">'.$obs.'</td>';
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
function cambia_fechaHora($Fecha) { 
if ($Fecha<>""){ 
   $trozos=explode("-",$Fecha); 
   $trozos2=explode(" ",$trozos[2]);
   $fecha = $trozos2[0]."/".$trozos[1]."/".$trozos[0]; 
   $hora = $trozos2[1];
   return "$fecha $hora";
}else 
   {return $Fecha;} 
} 

?>