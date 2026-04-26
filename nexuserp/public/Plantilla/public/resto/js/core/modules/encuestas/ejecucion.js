//funciones javascript y validaciones
	$(document).ready(function() {
		$(".select2").select2();
	});
	
	function Limpiar(){
		swal({
			text: "\u00BFDesea Limpiar la p\u00E1gina?, si a\u00FAn no a grabado perdera los datos escritos...",
			icon: "info",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
			}
		}).then((value) => {
			switch (value) {
				case true:
					window.location.reload();
					break;
				default:
				  return;
			}
		});
	}
	
	function Submit(){
		myform = document.forms.f1;
		myform.submit();
	}
	
	function responderPonderacion(encuesta,pregunta,ejecucion,seccion,tipo,respuesta){
		var peso = document.getElementById('peso'+pregunta).value;
		document.getElementById('respuesta'+pregunta).value = respuesta;
		//console.log(encuesta,pregunta,ejecucion,seccion,tipo,peso,aplicacion,respuesta);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","responderPonderacion");
		http.append("encuesta", encuesta);
		http.append("pregunta", pregunta);
		http.append("ejecucion", ejecucion);
		http.append("seccion", seccion);
		http.append("tipo", tipo);
		http.append("peso", peso);
		http.append("respuesta", respuesta);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ejecutar.php");
		request.send(http);
		request.onreadystatechange = function(){
		   //console.log( request );
		   if(request.readyState != 4) return;
		   if(request.status === 200){
			resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//console.log( resultado.sql );
					swal("Error", resultado.message , "error").then((value) => {
						console.log( value );
					});
					return;
				}
				console.log( resultado.message );
				return;
			}
		};     
	}
	
	function responderTexto(encuesta,pregunta,ejecucion,seccion,observacion){
		//console.log(encuesta,pregunta,ejecucion,observacion);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","responderTexto");
		http.append("encuesta", encuesta);
		http.append("pregunta", pregunta);
		http.append("ejecucion", ejecucion);
		http.append("seccion", seccion);
		http.append("observacion", observacion);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ejecutar.php");
		request.send(http);
		request.onreadystatechange = function(){
		   //console.log( request );
		   if(request.readyState != 4) return;
		   if(request.status === 200){
			resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//console.log( resultado.sql );
					swal("Error", resultado.message , "error").then((value) => {
						console.log( value );
					});
					return;
				}
				//console.log( resultado.sql );
				console.log( resultado.message );
				return;
			}
		};     
	}
	
	function ejecucionCampos(campo,valor){
		ejecucion = document.getElementById("ejecucion");
		console.log(ejecucion);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","update_campos");
		http.append("ejecucion", ejecucion.value);
		http.append("campo", campo);
		http.append("valor", valor);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ejecutar.php");
		request.send(http);
		request.onreadystatechange = function(){
		   //console.log( request );
		   if(request.readyState != 4) return;
		   if(request.status === 200){
			resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//console.log( resultado.sql );
					swal("Error", resultado.message , "error").then((value) => {
						console.log( value );
					});
					return;
				}
				console.log( resultado.message );
				return;
			}
		};     
	}
	
	function cerrarEjecucion(){
		ejecucion = document.getElementById("ejecucion");
		url = document.getElementById("url");
		/////////// POST /////////
		var boton = document.getElementById("btn-cerrar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request","cerrar");
		http.append("ejecucion", ejecucion.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ejecutar.php");
		request.send(http);
		request.onreadystatechange = function(){
		   //console.log( request );
		   if(request.readyState != 4) return;
		   if(request.status === 200){
			resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//console.log( resultado.sql );
					swal("Error", resultado.message , "error").then((value) => {
						console.log( value );
						deloadingBtn(boton,'<i class="fa fa-folder"></i> Cerrar');
					});
					return;
				}
				//console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					console.log( value );
					deloadingBtn(boton,'<i class="fa fa-folder"></i> Cerrar');
					window.location.href = url.value;
				});
			}
		};     
	}
	