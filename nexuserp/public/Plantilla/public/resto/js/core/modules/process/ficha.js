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

function Submit() {
	myform = document.forms.f1;
	myform.submit();
	contenedor.innerHTML = '...';
}
/////////////// KM ///////////////////////////////////
/// recargar la tabla fichas procecso ///////////////
function asignar_tabla() {
    contenedor = document.getElementById("result");
		situacion = document.getElementById("situacion");
		tipo = document.getElementById("tipo");
    loadingCogs(contenedor);//funcion que me pita el egranaje
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "asignar_tabla");
		http.append("type", tipo.value);
		http.append("situacion", situacion.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_proceso.php");
    request.send(http);
    request.onreadystatechange = function() {
        // console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            //console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado );
                contenedor.innerHTML = '...';
                console.log(resultado.message);
                return;
            }
            //tabla
            contenedor.innerHTML = resultado.tabla;
            $('.dataTables-example').DataTable({
                pageLength: 50,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [

                ]
            });
        }
    };
}


////////////////////////////////////////////////// Ficha ////////////////////////////////////////////////////

function aperturaFicha() {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/process/nueva_ficha.php", {}, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
	setTimeout(function () {
		$(".select2").select2();
	}, 250);
}

function aperturaSubficha(codigo) {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php///
	$.post("../promts/process/nueva_subficha.php", {codigo: codigo }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		document.getElementById('padre').value = document.getElementById('proceso').value;
		document.getElementById('type').value = document.getElementById('codigo').value;
	});
	abrirModal();
}

function nuevaFicha(nivel) {
	nombre = document.getElementById("nombre");
	desde = document.getElementById("desde");
	hasta = document.getElementById("hasta");
	tipo = document.getElementById("type");

	if (nombre.value !== "" && tipo.value !== "") {
		boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "nueva_ficha");
		http.append("nombre", nombre.value);
		http.append("desde", desde.value);
		http.append("hasta", hasta.value);
		http.append("tipo", tipo.value);
		http.append("pertenece", nivel);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_proceso.php");
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
				hashkey = resultado.hashkey;
				//console.log(hashkey);
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.href = "FRMdetalle.php?hashkey=" + hashkey;
				});
			}
		};
	} else {
		if (nombre.value === "") {
			nombre.classList.add("is-invalid");
		} else {
			nombre.classList.remove("is-invalid");
		}
		if (tipo.value === "") {
			tipo.parentNode.classList.add('has-error');
		} else {
			tipo.parentNode.classList.remove('has-error');
		}
		swal("Error", "Debe llenar los campos obligatorios...", "error");
	}
}

function updateFicha(elemento, campo) {
	codigo = document.getElementById("codigo");
	var http = new FormData();
	http.append("request", "update_ficha");
	http.append("codigo", codigo.value);
	http.append("campo", campo);
	http.append("valor", elemento.value);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_proceso.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request.readyState);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			//console.log(resultado);
			if (resultado.status !== true) {
				console.log(resultado.message);
				return;
			}
			console.log(resultado.message);
		}
	};
}

function solicitarAprobacion(codigo) {
	swal({
		title: "SOLICITAR APROBACI\u00D3N",
		text: "\u00BFDesea solicitar la aprobaci\u00F3n de este proceso?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true },
		}
	}).then((value) => {
		switch (value) {
			case true:
				cambioSituacion(codigo, 2);
				break;
			default:
				return;
		}
	});
}

function aprobarFicha(codigo) {
	swal({
		title: "Solicitar Aprobaci\u00F3n",
		text: "\u00BFDesea aprobar este proceso?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true },
		}
	}).then((value) => {
		switch (value) {
			case true:
				cambioSituacion(codigo, 3);
				break;
			default:
				return;
		}
	});
}

function actualizarFicha(codigo) {
	swal({
		title: "Solicitud de Actualizacion",
		text: "\u00BFDesea solicitar una actualizacion de esta ficha de proceso?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true },
		}
	}).then((value) => {
		switch (value) {
			case true:
				cambioSituacion(codigo, 4);
				break;
			default:
				return;
		}
	});
}

function eliminarFicha(codigo) {
	swal({
		title: "Eliminar",
		text: "\u00BFDesea eliminar este proceso?, no prodr\u00E1 ser usada despu\u00E9s...",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true },
		}
	}).then((value) => {
		switch (value) {
			case true:
				cambioSituacion(codigo, 0);
				break;
			default:
				return;
		}
	});
}


function cambioSituacion(codigo, situacion) {
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "situacion_ficha");
	http.append("codigo", codigo);
	http.append("situacion", situacion);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_proceso.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado.sql );
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			situacion = parseInt(situacion);
			if (situacion == 4) {
				swal("Excelente!", "Solicitud de actualizaci\u00F3n enviada...!", "success").then((value) => { window.location.reload(); });
			}
			if (situacion == 2) {
				swal("Excelente!", "Solicitud de aprobaci\u00F3n enviada...!", "success").then((value) => { window.location.reload(); });
			} else if (situacion == 3) {
				swal("Excelente!", "El proceso fue aprobado satisfactoriamente!!", "success").then((value) => { window.location.reload(); });
			} else if (situacion == 0) {
				swal("OK", "Registro eliminado...", "success").then((value) => { window.location.reload(); });
			}
		}
	};
}

//////////////////////////////// Asignaciones ///////////////////////////////

function asignarUsuario(arrUsuario) {
	ficha = document.getElementById('ficha');

	if (ficha.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-asignar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "asignar_proceso");
		http.append("ficha", ficha.value);
		http.append("usuarios", arrUsuario);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_proceso.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log( request );
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					//console.log( resultado.sql );
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				//console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function usuariosProceso(codigo) {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/process/usuarios.php", { codigo: codigo }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}
