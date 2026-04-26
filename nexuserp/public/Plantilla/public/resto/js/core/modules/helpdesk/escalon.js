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
	
	////////////////////////////////////////////////// Escalones ////////////////////////////////////////////////////
   
	function masEscalones(categoria){
		var divCollapse = document.getElementById("accordion");
		loadingCogs(divCollapse); //coloca un gif cargando en la imagen
		var formData = new FormData();
		formData.append("request", "nuevoEscalon");
		formData.append("categoria", categoria);
		var request = new XMLHttpRequest();
		request.open("POST", "API_escalones.php");
		request.send(formData);
		request.onreadystatechange = function(){
			//console.log(request.readyState);
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				//console.log(resultado.data);
				document.getElementById("accordion").innerHTML = resultado.data;
			}	
		};
	}
   
   
   function buscaEscalones(categoria,escalon){
		var divCollapse = document.getElementById("accordion");
		loadingCogs(divCollapse); //coloca un gif cargando en la imagen
		var formData = new FormData();
		formData.append("request", "buscaEscalon");
		formData.append("categoria", categoria);
		formData.append("escalon", escalon);
		var request = new XMLHttpRequest();
		request.open("POST", "API_escalones.php");
		request.send(formData);
		request.onreadystatechange = function(){
			//console.log(request.readyState);
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				//console.log(resultado.data);
				document.getElementById("accordion").innerHTML = resultado.data;
			}	
		};
   }
	
	function saveEscalon(categoria,escalon){
		nombre = document.getElementById("nombre");
		posicion = document.getElementById("posicion");
		
		if(nombre.value !== "" && posicion.value !== ""){
			var divCollapse = document.getElementById("accordion");
			loadingCogs(divCollapse); //coloca un gif cargando en la imagen
			var formData = new FormData();
			formData.append("request", "updateEscalon");
			formData.append("categoria", categoria);
			formData.append("escalon", escalon);
			formData.append("nombre", nombre.value);
			formData.append("posicion", posicion.value);
			var request = new XMLHttpRequest();
			request.open("POST", "API_escalones.php");
			request.send(formData);
			request.onreadystatechange = function(){
				//console.log(request.readyState);
				if(request.readyState != 4) return;
				if(request.status === 200){
					resultado = JSON.parse(request.responseText);
					//console.log(resultado);
					if(resultado.status !== true){
						//swal("Informaci\u00F3n", resultado.message, "info");
						return;
					}
					document.getElementById("accordion").innerHTML = resultado.data;
				}	
			};
		}else{
			if(nombre.value === ""){
				nombre.classList.add("is-invalid");
			}else{
				nombre.classList.remove("is-invalid");
			}
			if(posicion.value === ""){
				posicion.classList.add("is-invalid");
			}else{
				posicion.classList.remove("is-invalid");
			}
			swal("Error","Debe llenar los campos obligatorios...","error");
		}
	}
	
   
   function confirmDeleteEscalon(categoria,escalon){
		swal({
			title: "Eliminar Escalon",
			text: "\u00BFEsta seguro de eliminar este registro?, perdera los registros anclados a el...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
			}
		}).then((value) => {
			switch (value) {
				case true:
					deleteEscalon(categoria,escalon);
					break;
				default:
				  return;
			}
		});
	}
	
	function deleteEscalon(categoria,escalon){
		var divCollapse = document.getElementById("accordion");
		loadingCogs(divCollapse); //coloca un gif cargando en la imagen
		var formData = new FormData();
		formData.append("request", "deleteEscalon");
		formData.append("categoria", categoria);
		formData.append("escalon", escalon);
		var request = new XMLHttpRequest();
		request.open("POST", "API_escalones.php");
		request.send(formData);
		request.onreadystatechange = function(){
			//console.log(request.readyState);
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				//console.log(resultado.alert);
				document.getElementById("accordion").innerHTML = resultado.data;
			}	
		};
	}
   
	
	////////////////////////////////////////////////// Detalle de Escalones ////////////////////////////////////////////////////
   
	function masDetalle(categoria,escalon){
		var divCollapse = document.getElementById("collapse"+escalon);
		loadingCogs(divCollapse); //coloca un gif cargando en la imagen
		var formData = new FormData();
		formData.append("request", "nuevoDetalle");
		formData.append("categoria", categoria);
		formData.append("escalon", escalon);
		var request = new XMLHttpRequest();
		request.open("POST", "API_escalones.php");
		request.send(formData);
		request.onreadystatechange = function(){
			console.log(request.readyState);
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				//console.log(resultado.data);
				document.getElementById("collapse"+escalon).innerHTML = resultado.data;
			}	
		};
	}
   
   
   function buscaDetalle(categoria,escalon,detalle){
		var divCollapse = document.getElementById("collapse"+escalon);
		loadingCogs(divCollapse); //coloca un gif cargando en la imagen
		var formData = new FormData();
		formData.append("request", "buscaDetalle");
		formData.append("categoria", categoria);
		formData.append("escalon", escalon);
		formData.append("detalle", detalle);
		var request = new XMLHttpRequest();
		request.open("POST", "API_escalones.php");
		request.send(formData);
		request.onreadystatechange = function(){
			//console.log(request.readyState);
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				//console.log(resultado.data);
				document.getElementById("collapse"+escalon).innerHTML = resultado.data;
			}	
		};
   }
	
	
	function saveDetalle(categoria,escalon,detalle){
		nombre = document.getElementById("detNombre");
		mail = document.getElementById("detMail");
		
		if(nombre.value !== "" && mail.value !== ""){
			var divCollapse = document.getElementById("collapse"+escalon);
			loadingCogs(divCollapse); //coloca un gif cargando en la imagen
			var formData = new FormData();
			formData.append("request", "updateDetalle");
			formData.append("categoria", categoria);
			formData.append("escalon", escalon);
			formData.append("detalle", detalle);
			formData.append("nombre", nombre.value);
			formData.append("mail", mail.value);
			var request = new XMLHttpRequest();
			request.open("POST", "API_escalones.php");
			request.send(formData);
			request.onreadystatechange = function(){
				//console.log(request.readyState);
				if(request.readyState != 4) return;
				if(request.status === 200){
					resultado = JSON.parse(request.responseText);
					//console.log(resultado);
					if(resultado.status !== true){
						//swal("Informaci\u00F3n", resultado.message, "info");
						return;
					}
					document.getElementById("collapse"+escalon).innerHTML = resultado.data;
				}	
			};
		}else{
			if(nombre.value === ""){
				nombre.classList.add("is-invalid");
			}else{
				nombre.classList.remove("is-invalid");
			}
			if(mail.value === ""){
				mail.classList.add("is-invalid");
			}else{
				mail.classList.remove("is-invalid");
			}
			swal("Error","Debe llenar los campos obligatorios...","error");
		}
	}
	
   
   function confirmDeleteDetalle(categoria,escalon,detalle){
		swal({
			title: "Eliminar Notoficacion",
			text: "\u00BFEsta seguro de eliminar este registro?, perdera los registros anclados a el...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
			}
		}).then((value) => {
			switch (value) {
				case true:
					deleteDetalle(categoria,escalon,detalle);
					break;
				default:
				  return;
			}
		});
	}
	
	
	function deleteDetalle(categoria,escalon,detalle){
		var divCollapse = document.getElementById("collapse"+escalon);
		loadingCogs(divCollapse); //coloca un gif cargando en la imagen
		var formData = new FormData();
		formData.append("request", "deleteDetalle");
		formData.append("categoria", categoria);
		formData.append("escalon", escalon);
		formData.append("detalle", detalle);
		var request = new XMLHttpRequest();
		request.open("POST", "API_escalones.php");
		request.send(formData);
		request.onreadystatechange = function(){
			//console.log(request.readyState);
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				//console.log(resultado.alert);
				document.getElementById("collapse"+escalon).innerHTML = resultado.data;
			}	
		};
	}
