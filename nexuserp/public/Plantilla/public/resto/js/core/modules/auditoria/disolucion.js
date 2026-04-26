//funciones javascript y validaciones
	$(document).ready(function() {
		$(".select2").select2();
	});
	
	function Submit(){
		myform = document.forms.f1;
		myform.submit();
	}
	
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
	
	function responderPonderacion(valor){
		document.getElementById('respuestaX').value = valor;
		return;
	}
	
	function disolverHallazgo(){
		auditoria = document.getElementById("auditoriaX");
		ejecucion = document.getElementById("ejecucionX");
		pregunta = document.getElementById("preguntaX");
		seccion = document.getElementById("seccionX");
		justificacion = document.getElementById("justificacionX");
		if(justificacion.value !== ""){
			//----
			tipo = document.getElementById("tipoX");
			peso = document.getElementById('pesoX');
			respuesta = document.getElementById('respuestaX');
			observacion = document.getElementById("observacionX");
			//valida si aplica o no aplica
			//console.log(auditoria.value,pregunta.value,ejecucion.value,seccion.value,tipo.value,peso.value,respuesta.value,observacion.value,justificacion.value);
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			/////////// POST /////////
			var http = new FormData();
			http.append("request","disolverHallazgo");
			http.append("auditoria", auditoria.value);
			http.append("pregunta", pregunta.value);
			http.append("ejecucion", ejecucion.value);
			http.append("seccion", seccion.value);
			http.append("tipo", tipo.value);
			http.append("peso", peso.value);
			http.append("respuesta", respuesta.value);
			http.append("observacion", observacion.value);
			http.append("justificacion", justificacion.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_aprobar.php");
			request.send(http);
			request.onreadystatechange = function(){
			   //console.log( request );
			   if(request.readyState != 4) return;
			   if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
					if(resultado.status !== true){
						swal("Error", resultado.message , "error").then((value) => {
							console.log( value );
							deloadingBtn(boton,'<i class="fas fa-save"></i> Grabar');
						});
						return;
					}
					//console.log( resultado );
					swal("Excelente!", resultado.message, "success").then((value) => {
						window.location.reload();
					});
				}
			};     
			
		}else{
			if(justificacion.value === ""){
				justificacion.classList.add("is-invalid");
			}else{
				justificacion.classList.remove("is-invalid");
			}
			swal("Alto!", "Debe justificar la disoluci\u00F3n del hallazgo...", "error");
		}
	}


	function seleccionarHallazgo(auditoria, pregunta, ejecucion){
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/auditoria/disolucion.php",{auditoria:auditoria,pregunta:pregunta,ejecucion:ejecucion}, function(data){
		// Ponemos la resultado de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
	}
	
        function seleccionarHallazgoClasificar(auditoria, pregunta, ejecucion){
		//Realiza una peticion de contenido a contenido.php
		$.post("../promts/auditoria/clasificacion.php",{auditoria:auditoria,pregunta:pregunta,ejecucion:ejecucion}, function(data){
		// Ponemos el resultado de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
	}

	function clasificarHallazgo(){
		auditoria = document.getElementById("auditoriaX");
		ejecucion = document.getElementById("ejecucionX");
		pregunta = document.getElementById("preguntaX");
                clasificacion = document.getElementById("clasificacionX");
		motivo = document.getElementById("motivoX");
                
		if(motivo.value !== ""){
			//----		
			//valida si aplica o no aplica
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			/////////// POST /////////
			var http = new FormData();
			http.append("request","clasificarHallazgo");
			http.append("auditoria", auditoria.value);
			http.append("pregunta", pregunta.value);
			http.append("ejecucion", ejecucion.value);
                        http.append("clasificacion", clasificacion.value);
			http.append("motivo", motivo.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_aprobar.php");
			request.send(http);
			request.onreadystatechange = function(){
			   //console.log( request );
			   if(request.readyState != 4) return;
			   if(request.status === 200){
				console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
					if(resultado.status !== true){
						swal("Error", resultado.message , "error").then((value) => {
							console.log( value );
							deloadingBtn(boton,'<i class="fas fa-save"></i> Grabar');
						});
						return;
					}
					//console.log( resultado );
					swal("Excelente!", resultado.message, "success").then((value) => {
						window.location.reload();
					});
				}
			};     
			
		}else{
			if(motivo.value === ""){
				motivo.classList.add("is-invalid");
			}else{
				motivo.classList.remove("is-invalid");
			}
			swal("Alto!", "Debe justificar la clasificaci\u00F3n del hallazgo...", "error");
		}
	}