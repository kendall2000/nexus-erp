<?php

function mail_constructor($title, $message) {
    return $salida = '
<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ASMS</title>
		<style>
			 body{
				 font-family: Arial, sans-serif;
				 font-size: 14px;
				 color: #585858;
			 }
		</style>
  </head>
<body style="margin: 0; padding: 0;">
<br>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td>
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse;">
				<tr>		
					<td align="center" bgcolor="#F9F9F9" style="padding: 10px 0 10px 0; border: 1px solid transparent;border-radius: 4px;border-color: #EDEDED;">
						<img src="https://bpm.desarrollogt.net/CONFIG/img/logo_largo.png" width="30%">
					</td>
				</tr>
				<tr>		
					<td bgcolor="#ffffff" style="padding: 10px 30px 10px 30px;">
						
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td align="justify">
									<hr>
									<p>' . $message . '</p>
								</td>
							</tr>	
						</table>
						
					</td>
				</tr>
				<tr>		
					<td align="center">
						<table border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td align = "center">
									<a href="https://www.facebook.com/InversionesDigitalesSA/?ref=br_rs"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/22/Icon_Facebook.svg/1022px-Icon_Facebook.svg.png" alt="Twitter" width="38" height="38" style="display: block;" border="0" /></a>
								</td>
								<td align = "center">
									<a href="https://twitter.com/InversionesDig"><img src="http://www.interprint-services.co.uk/wp-content/uploads/2016/11/Icon_Twitter.svg_.png" alt="Twitter" width="38" height="38" style="display: block;" border="0" /></a>
								</td>
								<td align = "center">
									<a href="https://www.youtube.com/channel/UCuPlqaJjryJ55XBshA9GYxg"><img src="https://www.seoclerk.com/pics/want42144-1VBx9L1472503782.png" alt="Twitter" width="38" height="38" style="display: block;" border="0" /></a>
								</td>
							</tr>
						</table>
						<br>
					</td>
				</tr>
				<tr>
					<td align = "center"  bgcolor="#777777">
						<p style="font-size: 18px; font-weight: bold; color: #fff;">Copyright © BPManagement 2019. </p>
						<p style="color: #fff;">Powered by Cuantica. Soluciones de Software y Hardware S.A</p>
					</td>
				</tr>
			</table>
	
		</td> 
	</tr>
</table>
 <br>
</body>
</html>
	';
}

$subject = "Bienvenido a ASMS";
$texto = "Han generado un nuevo usuario para ti. Haz click en el link adjunto, para activar tu usuario.<br><br> ";
// $texto .= "<a href = '$absolute_url/CPVALIDA/FRMactivate.php?hashkey=$hashkey' style = 'color: #337ab7; font-weight: bold; text-decoration: none;'> Click para Activar el Usuario </a>";
$texto .= "<br><br>Que pases un feliz dia!!!";

//echo mail_constructor($subject, $texto);
?>
