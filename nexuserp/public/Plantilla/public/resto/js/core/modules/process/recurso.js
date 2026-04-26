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
	
	////////////////////////////////////////////////// Requisitos Legales ////////////////////////////////////////////////////
   
	function masRecurso(){
		proceso = document.getElementById("codigo");
		contenedor = document.getElementById("resultRecurso");
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "nuevo");
		http.append("proceso", proceso.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_recursos.php");
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
				document.getElementById("resultRecurso").innerHTML = resultado.data;
				document.getElementById("recurso").focus();
			}	
		};
	}
   
   
   function seleccionarRecurso(codigo,proceso){
		contenedor = document.getElementById("resultRecurso");
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "get");
		http.append("codigo", codigo);
		http.append("proceso", proceso);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_recursos.php");
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
				document.getElementById("resultRecurso").innerHTML = resultado.data;
				document.getElementById("clase").value = resultado.clase;
			}	
		};
   }
	
	function saveRecurso(codigo,proceso){
		clase = document.getElementById("clase");
		descripcion = document.getElementById("recurso");
		
		if(clase.value !== "" && descripcion.value !== ""){
			contenedor = document.getElementById("resultRecurso");
			loadingCogs(contenedor);
			var http = new FormData();
			http.append("request", "grabar");
			http.append("codigo", codigo);
			http.append("proceso", proceso);
			http.append("clase", clase.value);
			http.append("descripcion", descripcion.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_recursos.php");
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
					document.getElementById("resultRecurso").innerHTML = resultado.data;
				}	
			};
		}else{
			if(clase.value === ""){
				clase.className = "form-danger input-table";
			}else{
				clase.className = "form-control input-table";
			}
			if(descripcion.value === ""){
				descripcion.className = "form-danger input-table";
			}else{
				descripcion.className = "form-control input-table";
			}
			swal("Error","Debe llenar los campos obligatorios...","error");
		}
	}
	
   
   function quitarRecurso(representante,proceso){
		swal({
			title: "Eliminar Detalle",
			text: "\u00BFEsta seguro de quitar este recurso?",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
			}
		}).then((value) => {
			switch (value) {
				case true:
					deleteRecurso(representante,proceso);
					break;
				default:
				  return;
			}
		});
	}
	
	function deleteRecurso(codigo,proceso){
		contenedor = document.getElementById("resultRecurso");
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "delete");
		http.append("codigo", codigo);
		http.append("proceso", proceso);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_recursos.php");
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
				document.getElementById("resultRecurso").innerHTML = resultado.data;
			}	
		};
	}
