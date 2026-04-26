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
   
	function masPuntoNorma(){
		proceso = document.getElementById("codigo");
		contenedor = document.getElementById("resultPuntoNorma");
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "nuevo");
		http.append("proceso", proceso.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_norma.php");
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
				document.getElementById("resultPuntoNorma").innerHTML = resultado.data;
				document.getElementById("norma").focus();
			}	
		};
	}
   
   
   function seleccionarPuntoNorma(codigo,proceso){
		contenedor = document.getElementById("resultPuntoNorma");
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "get");
		http.append("codigo", codigo);
		http.append("proceso", proceso);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_norma.php");
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
				document.getElementById("resultPuntoNorma").innerHTML = resultado.data;
			}	
		};
   }
	
	function savePuntoNorma(codigo,proceso){
		descripcion = document.getElementById("norma");
		
		if(descripcion.value !== ""){
			contenedor = document.getElementById("resultPuntoNorma");
			loadingCogs(contenedor);
			var http = new FormData();
			http.append("request", "grabar");
			http.append("codigo", codigo);
			http.append("proceso", proceso);
			http.append("descripcion", descripcion.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_norma.php");
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
					document.getElementById("resultPuntoNorma").innerHTML = resultado.data;
				}	
			};
		}else{
			if(descripcion.value === ""){
				descripcion.className = "form-danger input-table";
			}else{
				descripcion.className = "form-control input-table";
			}
			swal("Error","Debe llenar los campos obligatorios...","error");
		}
	}
	
   
   function quitarPuntoNorma(representante,proceso){
		swal({
			title: "Eliminar Detalle",
			text: "\u00BFEsta seguro de quitar este punto de norma?",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
			}
		}).then((value) => {
			switch (value) {
				case true:
					deletePuntoNorma(representante,proceso);
					break;
				default:
				  return;
			}
		});
	}
	
	function deletePuntoNorma(codigo,proceso){
		contenedor = document.getElementById("resultPuntoNorma");
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "delete");
		http.append("codigo", codigo);
		http.append("proceso", proceso);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_norma.php");
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
				document.getElementById("resultPuntoNorma").innerHTML = resultado.data;
			}	
		};
	}
