//funciones javascript y validaciones
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
	
	////////////////////////////////////////////////// Elemento ////////////////////////////////////////////////////
   
	function masElemento(tipo,nombre_contenedor){
		proceso = document.getElementById("codigo");
		contenedor = document.getElementById(nombre_contenedor);
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "nuevo");
		http.append("proceso", proceso.value);
		http.append("tipo", tipo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_elementos.php");
		request.send(http);
		request.onreadystatechange = function(){
			if(request.readyState != 4) return;
			if(request.status === 200){
				console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				//console.log(resultado.data);
				document.getElementById(nombre_contenedor).innerHTML = resultado.data;
			}	
		};
	}
   
   
   function seleccionarElemento(codigo,proceso,tipo,nombre_contenedor){
		contenedor = document.getElementById(nombre_contenedor);
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "get");
		http.append("codigo", codigo);
		http.append("proceso", proceso);
		http.append("tipo", tipo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_elementos.php");
		request.send(http);
		request.onreadystatechange = function(){
			if(request.readyState != 4) return;
			if(request.status === 200){
				console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				//console.log(resultado.data);
				document.getElementById(nombre_contenedor).innerHTML = resultado.data;
			}	
		};
   }
	
	function saveElemento(codigo,proceso,tipo,nombre_contenedor){
		titulo = document.getElementById("titulo");
		descripcion = document.getElementById("elemento");
		
		if(titulo.value !== ""){
			contenedor = document.getElementById(nombre_contenedor);
			loadingCogs(contenedor);
			var http = new FormData();
			http.append("request", "grabar");
			http.append("codigo", codigo);
			http.append("proceso", proceso);
			http.append("tipo", tipo);
			http.append("titulo", titulo.value);
			http.append("descripcion", descripcion.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_elementos.php");
			request.send(http);
			request.onreadystatechange = function(){
				//console.log(request.readyState);
				if(request.readyState != 4) return;
				if(request.status === 200){
					console.log(request.responseText);
					resultado = JSON.parse(request.responseText);
					//console.log(resultado);
					if(resultado.status !== true){
						//swal("Informaci\u00F3n", resultado.message, "info");
						return;
					}
					document.getElementById(nombre_contenedor).innerHTML = resultado.data;
					mostrarDiagrama();
				}	
			};
		}else{
			if(titulo.value === ""){
				titulo.className = "form-danger input-table";
			}else{
				titulo.className = "form-control input-table";
			}
			swal("Error","Debe llenar los campos obligatorios...","error");
		}
	}
	
   
   function quitarElemento(representante,proceso,tipo,nombre_contenedor){
		swal({
			title: "Eliminar Detalle",
			text: "\u00BFEsta seguro de quitar este registro del proceso?",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
			}
		}).then((value) => {
			switch (value) {
				case true:
					deleteElemento(representante,proceso,tipo,nombre_contenedor);
					break;
				default:
				  return;
			}
		});
	}
	
	function deleteElemento(codigo,proceso,tipo,nombre_contenedor){
		contenedor = document.getElementById(nombre_contenedor);
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "delete");
		http.append("codigo", codigo);
		http.append("proceso", proceso);
		http.append("tipo", tipo);
			var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_elementos.php");
		request.send(http);
		request.onreadystatechange = function(){
			if(request.readyState != 4) return;
			if(request.status === 200){
				console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				//console.log(resultado.alert);
				document.getElementById(nombre_contenedor).innerHTML = resultado.data;
				mostrarDiagrama();
			}	
		};
	}
	
	
	function mostrarDiagrama(){
		proceso = document.getElementById("codigo").value;
		contenedor = document.getElementById("resultElementos");
		loadingCogs(contenedor);
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/process/diagrama.php",{proceso:proceso}, function(data){
			// Ponemos la respuesta de nuestro script en el DIV recargado
			$("#resultElementos").html(data);
		});
   }
	
	function mostrarDiagramaReporte(){
		proceso = document.getElementById("codigo").value;
		contenedor = document.getElementById("resultElementos");
		loadingCogs(contenedor);
		//Realiza una peticion de contenido a la contenido.php
		$.post("../../promts/process/diagrama.php",{proceso:proceso}, function(data){
			// Ponemos la respuesta de nuestro script en el DIV recargado
			$("#resultElementos").html(data);
		});
   }
	