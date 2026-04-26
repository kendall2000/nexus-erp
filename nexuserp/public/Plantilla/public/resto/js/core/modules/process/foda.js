//funciones javascript y validaciones
function Limpiar() {
	swal({
		text: "\u00BFDesea Limpiar la p\u00E1gina?, si a\u00FAn no a grabado perdera los datos escritos...",
		icon: "info",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
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

////////////////////////////////////////////////// Foda ////////////////////////////////////////////////////

function masFoda(tipo, nombre_contenedor) {
	proceso = document.getElementById("codigo");
	contenedor = document.getElementById(nombre_contenedor);
	loadingCogs(contenedor);
	var http = new FormData();
	http.append("request", "nuevo");
	http.append("proceso", proceso.value);
	http.append("tipo", tipo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_foda.php");
	request.send(http);
	request.onreadystatechange = function () {
		if (request.readyState != 4) return;
		if (request.status === 200) {
			console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			//console.log(resultado.data);
			document.getElementById(nombre_contenedor).innerHTML = resultado.data;
		}
	};
}


function seleccionarFoda(codigo, proceso, tipo, nombre_contenedor) {
	contenedor = document.getElementById(nombre_contenedor);
	loadingCogs(contenedor);
	var http = new FormData();
	http.append("request", "get");
	http.append("codigo", codigo);
	http.append("proceso", proceso);
	http.append("tipo", tipo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_foda.php");
	request.send(http);
	request.onreadystatechange = function () {
		if (request.readyState != 4) return;
		if (request.status === 200) {
			console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			//console.log(resultado.data);
			document.getElementById(nombre_contenedor).innerHTML = resultado.data;
			document.getElementById("sistema").value = resultado.sistema;
		}
	};
}

function saveFoda(codigo, proceso, tipo, nombre_contenedor) {
	sistema = document.getElementById("sistema");
	descripcion = document.getElementById("foda");
	peso = document.getElementById("peso");

	if (sistema.value !== "" && descripcion.value !== "" && peso.value !== "") {
		contenedor = document.getElementById(nombre_contenedor);
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "grabar");
		http.append("codigo", codigo);
		http.append("proceso", proceso);
		http.append("tipo", tipo);
		http.append("sistema", sistema.value);
		http.append("descripcion", descripcion.value);
		http.append("peso", peso.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_foda.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request.readyState);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				//console.log(resultado);
				if (resultado.status !== true) {
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				document.getElementById(nombre_contenedor).innerHTML = resultado.data;
			}
		};
	} else {
		if (sistema.value === "") {
			sistema.className = "form-danger input-table";
		} else {
			sistema.className = "form-control input-table";
		}
		if (descripcion.value === "") {
			descripcion.className = "form-danger input-table";
		} else {
			descripcion.className = "form-control input-table";
		}
		if (peso.value === "") {
			peso.className = "form-danger input-table";
		} else {
			peso.className = "form-control input-table";
		}
		swal("Error", "Debe llenar los campos obligatorios...", "error");
	}
}


function quitarFoda(representante, proceso, tipo, nombre_contenedor) {
	swal({
		title: "Eliminar Detalle",
		text: "\u00BFEsta seguro de quitar este registro del FODA?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				deleteFoda(representante, proceso, tipo, nombre_contenedor);
				break;
			default:
				return;
		}
	});
}

function deleteFoda(codigo, proceso, tipo, nombre_contenedor) {
	contenedor = document.getElementById(nombre_contenedor);
	loadingCogs(contenedor);
	var http = new FormData();
	http.append("request", "delete");
	http.append("codigo", codigo);
	http.append("proceso", proceso);
	http.append("tipo", tipo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_foda.php");
	request.send(http);
	request.onreadystatechange = function () {
		if (request.readyState != 4) return;
		if (request.status === 200) {
			console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			//console.log(resultado.alert);
			document.getElementById(nombre_contenedor).innerHTML = resultado.data;
		}
	};
}
