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
	
	function imagenRespuesta(auditoria,pregunta,ejecucion){
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/auditoria/imagenes.php",{auditoria:auditoria,pregunta:pregunta,ejecucion:ejecucion}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
   }
	
	function responderFecha(auditoria,pregunta,solucion,fila){
		fecha = document.getElementById("fecha"+fila).value;
		console.log(auditoria,pregunta,solucion,fecha);
		xajax_Responder_Fecha(auditoria,pregunta,solucion,fecha);
	}
	
	function responderTexto(auditoria,pregunta,solucion,seccion,respuesta){
		//console.log(auditoria,pregunta,solucion,respuesta);
		xajax_Responder_Solucion(auditoria,pregunta,solucion,seccion,respuesta);
	}
	
	function responderResponsable(auditoria,pregunta,solucion,responsable){
		//console.log(auditoria,pregunta,solucion,respuesta);
		xajax_Responder_Responsable(auditoria,pregunta,solucion,responsable);
	}
	
	function responderSituacion(auditoria,pregunta,solucion){
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/auditoria/status.php",{auditoria:auditoria, pregunta:pregunta, solucion:solucion}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
	}
	
	function cambiaStatus(){
		auditoria = document.getElementById("auditoria1");
		pregunta = document.getElementById("pregunta1");
		solucion = document.getElementById("ejecucion1");
		situacion = document.getElementById("status1");
		obs = document.getElementById("observaciones1");
		if(status.value !== ""){
			console.log(situacion.value);
			xajax_Responder_Situacion(auditoria.value,pregunta.value,solucion.value,situacion.value,obs.value);
		}else{
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function GrabarPlan(){
		ejecucion = document.getElementById("ejecucion");
		tratamiento = document.getElementById("tratamiento");
		nombre = document.getElementById("nombre");
		rol = document.getElementById("rol");
		obs = document.getElementById("obs");
		if(ejecucion.value !== ""){
			loadingButton(document.getElementById("btncerrar"));
			xajax_Cerrar_Plan(ejecucion.value,tratamiento.value,nombre.value,rol.value,obs.value);
		}
	}
	
	
	function FotoJs(pregunta){
		inpfile = document.getElementById("imagen");
		inpfile.click();
		document.getElementById("pregunta").value = pregunta;
	}
	
	function uploadImage() {
		myform = document.forms.f1;
		auditoria = document.getElementById("auditoria").value;
		ejecucion = document.getElementById("ejecucion").value;
		pregunta = document.getElementById("pregunta").value;
		archivo = document.getElementById("imagen");
		if(archivo.value) {
			if(archivo.value !== ""){
				exdoc = comprueba_extension(archivo.value,1);
				if(exdoc !== 1){
					swal("Alto!", "Este archivo no es extencion .jpg \u00F3 .png", "error");
					return;
				}
			}
		}else{	
			swal("Alto!", "El archivo viene vacio...", "error");
			return;
		}
		var observacion;
		loadingGif(pregunta); //coloca un gif cargando en la imagen
		
		var formData = new FormData(myform);
		formData.append("auditoria", auditoria);
		formData.append("ejecucion", ejecucion);
		formData.append("pregunta", pregunta);
		formData.append("imagen", archivo.files[0]);
		var request = new XMLHttpRequest();
		request.open("POST", "EXEcarga_foto_solucion.php");
		request.send(formData);
		request.onreadystatechange = function(){
			if(request.readyState != 4) return;
			//alert(request.status);
			if(request.status === 200){
				//alert("Status: " + request.status + " | Respuesta: " + request.responseText);
				//console.log(request.responseText);
				resultado = JSON.parse(request.responseText); 
				//alert(resultado.status + ", " + resultado.message + ", " + resultado.img);
				//console.log(resultado);
				if(resultado.status !== 1){
					swal("Error en la carga", resultado.message, "error");
					return;
				}
				var arrimagenes = resultado.img;
				var imagenes = '';
				arrimagenes.forEach(function(element) {
					//console.log(element.foto);
					imagenes+=element.foto;
				});
				document.getElementById("foto"+pregunta).innerHTML = imagenes;
			}else{
				//alert("Error: " + request.status + " " + request.responseText);
				swal("Error en la carga", "Error en la carga de la imagen", "error");
				return;
			}
		};
		
	}
	
	function deleteFotoConfirm(codigo, auditoria, pregunta, ejecucion){
		swal({
			title: "\u00BFDesea Eliminar la Foto?",
			text: "\u00BFEsta seguro(a) de eliminar esta imagen del archivo de soluci\u00F3n de hallazgos?",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,}
			}
		}).then((value) => {
			switch (value) {
				case true:
					deleteFoto(codigo, auditoria, pregunta, ejecucion);
					break;
				default:
				  return;
			}
		});
	}
	
	function deleteFoto(codigo, auditoria, pregunta, ejecucion){
		
		loadingGif(pregunta); //coloca un gif cargando en la imagen
		myform = document.forms.f1;
		var formData = new FormData(myform);
		formData.append("codigo", codigo);
		formData.append("auditoria", auditoria);
		formData.append("ejecucion", ejecucion);
		formData.append("pregunta", pregunta);
		var request = new XMLHttpRequest();
		request.open("POST", "EXEdelete_foto_solucion.php");
		request.send(formData);
		request.onreadystatechange = function(){
			if(request.readyState != 4) return;
			//alert(request.status);
			if(request.status === 200){
				//alert("Status: " + request.status + " | Respuesta: " + request.responseText);
				//console.log(request.responseText);
				resultado = JSON.parse(request.responseText); 
				//alert(resultado.status + ", " + resultado.message + ", " + resultado.img);
				//console.log(resultado);
				if(resultado.status !== 1){
					swal("Error en la transacci\u00F3n", resultado.message, "error");
					return;
				}
				var arrimagenes = resultado.img;
				var imagenes = '';
				arrimagenes.forEach(function(element) {
					//console.log(element.foto);
					imagenes+=element.foto;
				});
				document.getElementById("foto"+pregunta).innerHTML = imagenes;
			}else{
				//alert("Error: " + request.status + " " + request.responseText);
				swal("Error en la carga", "Error en la carga de la imagen", "error");
				return;
			}
		};
		cerrar();
   }
	
	function validaFoto(pregunta,file){
		if(file.value !== ""){
			exdoc = comprueba_extension(file.value,1);
			if(exdoc !== 1){
				swal("Alto!", "Este archivo no es extencion .jpg \u00F3 .png", "error");
			}else{
				uploadImage();	
			}	
		}
	}
	
	function loadingGif(pregunta){
		document.getElementById("foto"+pregunta).innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
	}
	
	function loadingButton(elemento){
		elemento.setAttribute("disabled","disabled");
		elemento.innerHTML = '<img src="../../CONFIG/img/img-loader.gif" width="15px" alt="cargando...">';
	}
	
	function menuFoto(codigo, auditoria, pregunta, ejecucion){
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/auditoria/menu_foto_solucion.php",{codigo:codigo,auditoria:auditoria,pregunta:pregunta,ejecucion:ejecucion}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#lblparrafo").html(data);
		});
		abrir();
	}
	
	function verFoto(codigo, auditoria, pregunta, ejecucion){
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/auditoria/imagen_solucion.php",{codigo:codigo,auditoria:auditoria,pregunta:pregunta,ejecucion:ejecucion}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#lblparrafo").html(data);
		});
	}
	
	function verFotoAuditoria(codigo, auditoria, pregunta, ejecucion){
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/auditoria/imagen.php",{codigo:codigo,auditoria:auditoria,pregunta:pregunta,ejecucion:ejecucion}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#lblparrafo").html(data);
		});
	}
	