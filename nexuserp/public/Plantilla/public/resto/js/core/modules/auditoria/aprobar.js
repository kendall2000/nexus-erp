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
	
	
	function cambioSituacion(ejecucion,situacion,obs){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","situacion");
		http.append("ejecucion",ejecucion);
		http.append("situacion",situacion);
		http.append("obs",obs);
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
					swal("Error", resultado.message , "error");
					return;
				}
				if(situacion == 4 || situacion == 5){
					swal("Excelente!", "Cambio de situaci\u00F3n realizado satisfactoriamente...", "success").then((value)=>{ console.log( value ); window.location.href="FRMaprobaciones.php"; });
				}else{
					swal("Excelente!", "Cambio de situaci\u00F3n realizado satisfactoriamente...", "success").then((value)=>{ console.log( value ); window.location.reload(); });
				}
			}
		};     
	}
	
	
	function aprobarAuditoria(){
		ejecucion = document.getElementById("ejecucion").value;
		observaciones = document.getElementById("observaciones").value;
		swal({
			title: "\u00BFAPROBAR?",
			text: "\u00BFEsta seguro(a) de aprobar este formulario de auditor\u00EDa?",
			icon: "info",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,}
			}
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(ejecucion,4,observaciones);
					break;
				default:
				  return;
			}
		});
	}
	
	function rechazarAuditoria(){
		ejecucion = document.getElementById("ejecucion").value;
		observaciones = document.getElementById("observaciones").value;
		swal({
			title: "\u00BFSOLICITAR CORRECI\u00D3N?",
			text: "\u00BFEsta seguro(a) de rechazar esta revisi\u00F3n para solicitar una correcci\u00F3n?, el auditor encargado deber\u00E1 realizar las correcciones indicadas en esta revisi\u00F3n...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,}
			}
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(ejecucion,5,observaciones);
					break;
				default:
				  return;
			}
		});
	}
	
	function revisionRespuesta(pregunta,resultado){
		auditoria = document.getElementById("auditoria");
		ejecucion = document.getElementById("ejecucion");
		observacion = document.getElementById("observacion"+pregunta);
		inpresultado = document.getElementById("resultado"+pregunta).value = resultado;
		////botones
		limpiaBotones(pregunta);
		btnCases(pregunta,resultado);
		observacion.disabled = false;
		//console.log(auditoria.value,pregunta,ejecucion.value,resultado,observacion.value);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","responderAprobacion");
		http.append("auditoria", auditoria.value);
		http.append("pregunta", pregunta);
		http.append("ejecucion", ejecucion.value);
		http.append("resultado", resultado);
		http.append("observacion", observacion.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_aprobar.php");
		request.send(http);
		request.onreadystatechange = function(){
		   //console.log( request );
		   if(request.readyState != 4) return;
		   if(request.status === 200){
				result = JSON.parse(request.responseText);
				if(result.status !== true){
					console.log( result.sql );
					swal("Error", result.message , "error").then((value) => {
						console.log( value );
					});
					return;
				}
				console.log( result.message );
				return;
			}
		};     
	}
	
	function observacionRespuesta(pregunta,observacion){
		auditoria = document.getElementById("auditoria");
		ejecucion = document.getElementById("ejecucion");
		resultado = document.getElementById("resultado"+pregunta);
		//console.log(auditoria.value,pregunta,ejecucion.value,resultado.value,observacion);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","responderAprobacion");
		http.append("auditoria", auditoria.value);
		http.append("pregunta", pregunta);
		http.append("ejecucion", ejecucion.value);
		http.append("resultado", resultado.value);
		http.append("observacion", observacion);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_aprobar.php");
		request.send(http);
		request.onreadystatechange = function(){
		   //console.log( request );
		   if(request.readyState != 4) return;
		   if(request.status === 200){
			result = JSON.parse(request.responseText);
				if(result.status !== true){
					console.log( result.sql );
					swal("Error", result.message , "error").then((value) => {
						console.log( value );
					});
					return;
				}
				console.log( result.message );
				return;
			}
		};     
	}


	function imagenRespuesta(auditoria,pregunta,ejecucion){
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/auditoria/imagenes.php",{auditoria:auditoria,pregunta:pregunta,ejecucion:ejecucion}, function(data){
		// Ponemos la resultado de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
   }

	function verFotoAuditoria(codigo, auditoria, pregunta, ejecucion){
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/auditoria/imagen.php",{codigo:codigo,auditoria:auditoria,pregunta:pregunta,ejecucion:ejecucion}, function(data){
		// Ponemos la resultado de nuestro script en el DIV recargado
		$("#lblparrafo").html(data);
		});
	}
	
	
	/////// Funciones utilitarias que manejan el color de botones
	function btnCases(pregunta,resultado){
		let btnclase;
		resultado = parseInt(resultado);
		switch(resultado){
			case 1: btnclase = "btn btn-success btn-block"; break;
			case 2: btnclase = "btn btn-warning btn-block"; break;
			case 3: btnclase = "btn btn-danger btn-block"; break;
			case 4: btnclase = "btn btn-primary btn-block"; break;
			default: btnclase = "btn btn-white btn-block"; break;
		}
		boton = document.getElementById("btn_"+resultado+"_"+pregunta);
		//console.log( boton );
		boton.className = btnclase+" active";
	}
	
	function limpiaBotones(pregunta){
		boton1 = document.getElementById("btn_1_"+pregunta);
		boton2 = document.getElementById("btn_2_"+pregunta);
		boton3 = document.getElementById("btn_3_"+pregunta);
		boton4 = document.getElementById("btn_4_"+pregunta);
		
		boton1.className = "btn btn-success btn-block";
		boton2.className = "btn btn-warning btn-block";
		boton3.className = "btn btn-danger btn-block";
		boton4.className = "btn btn-primary btn-block";
	}
	
	