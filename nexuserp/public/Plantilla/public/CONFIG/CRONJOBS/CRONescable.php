<?php
	include_once('html_fns_cron.php');
	
	$fecha = date("Y-m-d");
	$fecha = strtotime($fecha);
    $fecha = strtotime("+1 day", $fecha);
    $fecha = date('d/m/Y', $fecha);
	
	
	$ClsTic = new ClsTicket();
	$result = $ClsTic->get_ticket_asignado('','','','','','','','','',1);
	if(is_array($result)){
		foreach($result as $row){
			//codigo
			$codigo = trim($row["tic_codigo"]);
			$ticket = Agrega_Ceros($row["tic_codigo"]);
			//incidente
			$incidente = depurador_texto(trim($row["inc_nombre"]));
			//status
			$status = depurador_texto(trim($row["sta_nombre"]));
			//prioridad
			$prioridad = depurador_texto(trim($row["pri_nombre"]));
			$escalon = trim($row["tic_escalon"]);
			//categoria
			$categoria = trim($row["cat_codigo"]);
			//descripcion
			$descripcion = trim($row["tic_descripcion"]);
			$descripcion = nl2br($descripcion);
			$descripcion = depurador_texto($descripcion);
			//ubicacion
			$sede = depurador_texto(trim($row["sed_nombre"]));
			$sector = depurador_texto(trim($row["sec_nombre"]));
			$area = depurador_texto(trim($row["are_nombre"]));
			///////////////////////
			///////////////////////
			///////////////////////
			$fechor = trim($row["tic_fecha_registro"]);
			$tiempo = trim($row["pri_solucion"]);
			/////// resta tiempos
			$date1 = new DateTime($fechor);
			$date2 = new DateTime(date("Y-m-d H:i:i"));
			$interval = $date1->diff($date2);
			$intervalo = $interval->format("%H:%I:%S");
			//tiempos
			$tiempo1 = strtotime ( $tiempo );
			$tiempo2 = strtotime ( $intervalo );
			//echo "$intervalo > $tiempo <br>";
			if($intervalo > $tiempo){
				//Notificaciones del Escalon
				$ClsEsc = new ClsEscalon();
				$result = $ClsEsc->get_detalle_escalon('','',$categoria,$escalon);
				$i = 0;
				$arrnombres = "";
				if(is_array($result)){
					foreach($result as $row){
						$arrnombres.="-".depurador_texto(trim($row["not_nombre"]))."<br>";
						$arrcorreos["email"] = trim($row["not_mail"]);
						$arrcorreos["name"] = "";
						$arrcorreos["type"] = "to";
						$to[$i] = $arrcorreos;
						$i++;
					}
					$arrcorreos["email"] = "soporte@plani-go.com";
					$arrcorreos["name"] = "";
					$arrcorreos["type"] = "to";
					//$to[$i] = $arrcorreos;
				}
				///// Envio de Correo
				//echo "mail_usuario('$ticket', '$sede', '$sector', '$area', '$incidente', '$descripcion', '$arrcorreos', '$arrnombres');<br>";
				mail_usuario($ticket, $sede, $sector, $area, $incidente, $descripcion, $to, $arrnombres);
				///// Cambiar de Escalon el ticket
				$escalon++;
				$sql = $ClsTic->cambia_escalon_ticket($codigo,$escalon);
				$rs = $ClsTic->exec_sql($sql);
				if($rs == 1){
					/// Actualizado
				}else{
					/// Error al actualizar
				}
			}
		}
	}	
	
function mail_usuario($ticket, $sede, $sector, $area, $incidente, $descripcion, $to, $arrnombres){
	
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
	$subject = "Ticket Vencido";
	$texto = "Estimado Usuario,<br><br>Se ha vencido el ticket numero # $ticket en la $sede, $sector, $area.<br><br>El problema es el siguiente:<br><strong>$incidente</strong> <br>$descripcion <br><br>";
	$texto.= "Se esta notificando a los usuarios siguientes:<br>";
	$texto.= $arrnombres;
	$texto.= "<br><br>Gracias y saludos,<br><br>HelpDesk";
	
	$html = mail_constructor($subject,$texto); 
	
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
	
?>