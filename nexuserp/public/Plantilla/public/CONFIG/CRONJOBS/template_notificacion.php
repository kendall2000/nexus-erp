<?php


function mail_constructor_notificacion($nombre_usuario,$rango_fechas,$semana,$tabla_html){
	$rul = url( $_SERVER );
	return $salida = '
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>BPManagement</title>
	<link href="https://fonts.googleapis.com/css?family=Raleway&display=swap" rel="stylesheet"> 
	<style>
		body{
			font-family: "Raleway", sans-serif;
			font-size: 14px;
			color: #585858;
		}
		
		.content {
			padding: 0 30px 30px;
			margin-top: 40px;
		}
		
		.card {
			border-radius: 12px;
			box-shadow: 0 6px 10px -4px rgba(0, 0, 0, 0.15);
			background-color: #FFFFFF;
			color: #252422;
			margin-bottom: 20px;
			position: relative;
			border: 0 none;
			transition: box-shadow 200ms ease, -webkit-transform 300ms cubic-bezier(0.34, 2, 0.6, 1);
			transition: transform 300ms cubic-bezier(0.34, 2, 0.6, 1), box-shadow 200ms ease;
			transition: transform 300ms cubic-bezier(0.34, 2, 0.6, 1), box-shadow 200ms ease, -webkit-transform 300ms cubic-bezier(0.34, 2, 0.6, 1);
		}
		
		.card .card-body {
			padding: 15px 15px 10px 15px;
			flex: 1 1 auto;
		}
		
		.row {
			display: -ms-flexbox;
			display: flex;
			-ms-flex-wrap: wrap;
			flex-wrap: wrap;
		}
		
		.col-md-12 {
			-ms-flex: 0 0 100%;
			flex: 0 0 100%;
			max-width: 100%;
		}
		
		.text-center{
		    text-align: center !important;
		}
		
	</style>
</head>
<body class="content">
	
	<div class="card">
		<div class="card-body">
		    <div class="row">
				<div class="col-md-12 text-center">
					<img src="https://demo.bpm.gt/CONFIG/img/logo_largo.png" width="200px" />
				</div>
			</div>
			<br>
			<div class="row">
				<div class="col-md-12">
					Hola '.$nombre_usuario.', <br>
					Este es un recordatorio de las tareas de mantenimiento programadas para la semana No. '.$semana.', entre el '.$rango_fechas.':
				</div>
			</div>
			<br>
			<hr>
			<br>
			<div class="row">
				<div class="col-md-12">
					'.$tabla_html.'
				</div>
			</div>
			<br>
		</div>
		<div class="card-footer text-center">
		    <strong>BPManagement</strong>
		</div>
		<br>
	</div>
	
</body>
</html>
	';
}

?>