//funciones javascript y validaciones

////////////////////////////////////////////////// OBJETIVOS ////////////////////////////////////////////////////

function grabarObjetivo(tipo, sistema, descripcion) {
	proceso = document.getElementById("codigo").value;
	var http = new FormData();
	http.append("request", "grabar_objetivo");
	http.append("proceso", proceso);
	http.append("tipo", tipo);
	http.append("sistema", sistema);
	http.append("descripcion", descripcion);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_objetivo.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request.readyState);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			//console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			//console.log(resultado);
			if (resultado.status !== true) {
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			// console.log(resultado.message);
		}
	};
}
function aperturaObjetivo(codigo) {
	cerrar();
	//Realiza una peti//cion de contenido a la contenido.php///
	$.post("../promts/process/nuevo_objetivo.php", { codigo: codigo }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV re//cargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}

function masObjetivo(sistema) {
	proceso = document.getElementById("codigo");
	descripcion = document.getElementById("obj_desc");

	//if (codigo !== "" && descripcion.value != "") {
		var http = new FormData();
		http.append("request", "grabar_objetivo");
		http.append("proceso", proceso.value);
		http.append("sistema", sistema);
		http.append("descripcion", "");
		http.append("tipo", 1);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_objetivo.php");
		request.send(http);
		request.onreadystatechange = function () {
			if (request.readyState != 4) return;
			// console.log(request);
			if (request.status === 200) {
				// console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				//console.log(resultado.data);
				aperturaObjetivo(resultado.codigo);
			}
		};
	//}
	//if (descripcion.value === "") {
	//	descripcion.classList.add("is-invalid");
	//} else {
	//	descripcion.classList.remove("is-invalid");
//	}
}

function saveObjetivo(codigo) {
	descripcion = document.getElementById("obj_desc");
	//console.log(descripcion.value);
	if (codigo !== "" && descripcion.value != "") {
		var http = new FormData();
		http.append("request", "modificar");
		http.append("codigo", codigo);
		http.append("descripcion", descripcion.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_objetivo.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request.readyState);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				// console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				//console.log(resultado);
				if (resultado.status !== true) {
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
			}
		};
	}
	if (descripcion.value === "") {
		descripcion.classList.add("is-invalid");
	} else {
		descripcion.classList.remove("is-invalid");
	}
}

function quitarObjetivo(codigo, proceso, sistema) {
	swal({
		title: "Eliminar Objetivo",
		text: "\u00BFEsta seguro de quitar este registro del proceso?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				deleteObjetivo(codigo, proceso, sistema);
				break;
			default:
				return;
		}
	});
}


function deleteObjetivo(codigo, proceso, sistema) {

	if (codigo !== "" && proceso !== "" && sistema !== "") {
		contenedor = document.getElementById("objetivos" + sistema);
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request", "delete");
		http.append("codigo", codigo);
		http.append("proceso", proceso);
		http.append("sistema", sistema);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_objetivo.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request.readyState);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				// console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				//console.log(resultado);
				if (resultado.status !== true) {
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
				contenedor.innerHTML = resultado.data;
			}
		};
	} else {
		if (titulo.value === "") {
			titulo.className = "form-danger input-table";
		} else {
			titulo.className = "form-control input-table";
		}
		swal("Error", "Debe llenar los campos obligatorios...", "error");
	}
}

function printTable(proceso, sistema) {
	contenedor = document.getElementById("objetivos" + sistema);
	loadingCogs(contenedor);
	var http = new FormData();
	http.append("request", "tabla");
	http.append("proceso", proceso);
	http.append("sistema", sistema);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_objetivo.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request.readyState);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			//console.log(resultado);
			if (resultado.status !== true) {
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			contenedor.innerHTML = resultado.data;
		}
	};
}

/////////////////////// Complementarias ////////////////////////

function cerrarModalObjetivo(proceso, sistema) {
	cerrarModal();
	printTable(proceso, sistema);
}




function GuardarObjetivoGeneral(proceso,sistema,codigoObjetivo,objetivo,indicadorCodigo, indicadorNombre){
	
}
