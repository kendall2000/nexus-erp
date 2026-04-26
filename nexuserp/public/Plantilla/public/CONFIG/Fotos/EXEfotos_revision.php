<?php
	
	$directorio = opendir("."); //ruta actual
	while ($archivo = readdir($directorio)){
		if (is_dir($archivo)){
			echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
		}else{
			$imagePath = $archivo;
			// Abrimos una Imagen PNG
			$mime_type = mime_content_type($imagePath);
			if($mime_type == "image/png"){
				$imagen = imagecreatefrompng($imagePath); // si es, convierte a JPG
				imagejpeg($imagen,$imagePath,100); // Creamos la Imagen JPG a partir de la PNG u otra que venga
				echo $archivo . " - <b>Convertido</b> <br>";
			}else{
				echo $archivo . " - <b> JPG (Sin conversion)</b> <br>";
			}
		}
	}
 

?>