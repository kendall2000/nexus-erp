//funciones javascript y validaciones
$(document).ready(function () {
	$(".select2").select2();
});

function tablaEjecuciones (){
	content = '<div class="card">';
	content += '  <div class = "card-header">';
	content += '  </div>';

	content += '  <div class = "card-body">';
	content += '  </div>';
	content += '</div>';
	document.getElementById("result").innerHTML = content;
}
/*
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
}

function seleccionarProgramacion(hashkey) {
	window.location.href = "FRMejecutar.php?hashkey=" + hashkey;
}

function verProgramacion(hashkey) {
	window.location.href = "FRMrevision.php?hashkey=" + hashkey;
}

function FirmaJs(codigo) {
	window.location.href = "FRMfirma.php?codigo=" + codigo;
}


function update(elemento, campo) {
	codigo = document.getElementById("codigo");
	var http = new FormData();
	http.append("request", "update");
	http.append("codigo", codigo.value);
	http.append("campo", campo);
	http.append("valor", elemento.value);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_ejecucion.php");
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

function responder(programacion, cuestionario, pregunta, respuesta) {
	/////////// POST ////////////
	var http = new FormData();
	http.append("request", "responder_pregunta");
	http.append("programacion", programacion);
	http.append("cuestionario", cuestionario);
	http.append("pregunta", pregunta);
	http.append("respuesta", respuesta);

	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_ejecucion.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				swal("Error", resultado.message, "error").then((value) => {
				});
				return;
			}

		}
	};
}

function enEspera() {
	codigo = document.getElementById("codigo");
	observacion = document.getElementById("obs");
	if (codigo.value != "") {
		/////////// POST ////////////
		var http = new FormData();
		http.append("request", "situacion_programacion");
		http.append("codigo", codigo.value);
		http.append("situacion", 2);
		http.append("observacion", observacion.value);

		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ejecucion.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					swal("Error", resultado.message, "error").then((value) => {
						window.location.reload();
					});
					return;
				}
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.href = "FRMejecucion.php";
				});
			}
		};
	}
}

function enProceso() {
	codigo = document.getElementById("codigo");
	observacion = document.getElementById("obs");
	if (codigo.value != "") {
		/////////// POST ////////////
		var http = new FormData();
		http.append("request", "situacion_programacion");
		http.append("codigo", codigo.value);
		http.append("situacion", 3);
		http.append("observacion", observacion.value);

		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ejecucion.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					swal("Error", resultado.message, "error").then((value) => {
						window.location.reload();
					});
					return;
				}
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.href = "FRMejecucion.php";
				});
			}
		};
	}
}

function cerrarProgramacion() {
	codigo = document.getElementById("codigo");
	observacion = document.getElementById("observacion");
	if (codigo.value != "") {
		swal({
			text: "\u00BFDesea cerrar esta actividad de mantenimiento programada?, no prodr\u00E1 ser modificada despu\u00E9s...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true, },
			}
		}).then((value) => {
			switch (value) {
				case true:
					codigo = document.getElementById("codigo");
					observacion = document.getElementById("obs");
					if (codigo.value != "") {
						/////////// POST ////////////
						var http = new FormData();
						http.append("request", "situacion_programacion");
						http.append("codigo", codigo.value);
						http.append("situacion", 4);
						http.append("observacion", observacion.value);

						var request = new XMLHttpRequest();
						request.open("POST", "ajax_fns_ejecucion.php");
						request.send(http);
						request.onreadystatechange = function () {
							//console.log(request);
							if (request.readyState != 4) return;
							if (request.status === 200) {
								resultado = JSON.parse(request.responseText);
								if (resultado.status !== true) {
									swal("Error", resultado.message, "error").then((value) => {
										window.location.reload();
									});
									return;
								}
								swal("Excelente!", resultado.message, "success").then((value) => {
									window.location.href = "FRMejecucion.php";
								});
							}
						};
					}
					break;
				default:
					return;
			}
		});
	}
}

//////////////////////////////////////////////////////////////////

function FotoJs(codigo, posicion) {
	inpfile = document.getElementById("imagen");
	inpfile.click();
	document.getElementById("codigo").value = codigo;
	document.getElementById("posicion").value = posicion;
}

async function uploadImage() {
	activo = document.getElementById("codigo");
	posicion = document.getElementById("posicion");
	loadingGif(posicion.value); //coloca un gif cargando en la imagen
	//--
	var arrpromises = new Array();
	if (activo.value !== "") {
		//-- IMAGENES --
		archivo = document.getElementById("imagen");
		if (archivo.files.length > 0) {
			valida = comprueba_extension(archivo.files[0].name,1);
			if (valida !== 1) {
				swal("Ohoo!", "La extension de esta imagen no es valida....", "error").then((value) => {
					console.log(value);
				});
				return;
			}
			arrpromises[0] = await new Promise((resolve, reject) => {
				/////////// POST /////////
				let httpImagen = new FormData();
				httpImagen.append("nombre", archivo.files[0].name);
				httpImagen.append("activo", activo.value);
				httpImagen.append("posicion", posicion.value);
				httpImagen.append("imagen", archivo.files[0]);

				let requestImagen = new XMLHttpRequest();
				requestImagen.open("POST", "ajax_cargar_imagen.php");
				requestImagen.onload = () => {
					if (requestImagen.status >= 200 && requestImagen.status < 300) {
						console.log(requestImagen);
						devuelve = JSON.parse(requestImagen.response);
						if (devuelve.status === true) {
							resolve(devuelve.message);
						} else {
							reject(devuelve.message);
						}
					} else {
						//console.log( JSON.parse(requestImagen.response) );
						reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
					}
				};
				requestImagen.onerror = () => reject(requestImagen.statusText);
				requestImagen.send(httpImagen);
			}).catch(e => {
				console.log(e);
			});

		}

		await Promise.all(arrpromises).then(values => {
			//console.log(values);
			swal("Excelente!", "imagen subida satisfactoriamente...", "success").then((value) => {
				window.location.reload();
			});
		}, reason => {
			//console.log(reason);
			swal("Error", "Error en la trasaccion ...", "error").then((value) => {
				cerrar();
			});
		});

	} else {
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function deleteFotoConfirm(codigo, posicion) {
	swal({
		title: "\u00BFDesea Eliminar la Foto?",
		text: "\u00BFEsta seguro(a) de eliminar esta foto?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, }
		}
	}).then((value) => {
		switch (value) {
			case true:
				deleteFoto(codigo, posicion);
				break;
			default:
				return;
		}
	});
}

function deleteFoto(codigo, posicion) {

	loadingGif(posicion); //coloca un gif cargando en la imagen
	myform = document.forms.f1;
	var formData = new FormData(myform);
	formData.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "EXEdelete_foto.php");
	request.send(formData);
	request.onreadystatechange = function () {
		if (request.readyState != 4) return;
		//alert(request.status);
		if (request.status === 200) {
			//alert("Status: " + request.status + " | Respuesta: " + request.responseText);
			//console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			//alert(resultado.status + ", " + resultado.message + ", " + resultado.img);
			//console.log(resultado);
			if (resultado.status !== 1) {
				swal("Error en la transacci\u00F3n", resultado.message, "error");
				return;
			}
			var arrimagenes = resultado.img;
			var imagenes = '';
			arrimagenes.forEach(function (element) {
				//console.log(element.foto);
				imagenes += element.foto;
			});
			document.getElementById("foto" + posicion).innerHTML = imagenes;
		} else {
			//alert("Error: " + request.status + " " + request.responseText);
			swal("Error en la carga", "Error en la carga de la imagen", "error");
			return;
		}
	};
	cerrar();
}

function loadingGif(posicion) {
	document.getElementById("foto" + posicion).innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
}

function loadingButton(elemento) {
	elemento.setAttribute("disabled", "disabled");
	elemento.innerHTML = '<img src="../../CONFIG/img/img-loader.gif" width="15px" alt="cargando...">';
}

function ejecutarPresupuesto(programacion) {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/ppm/presupuesto.php", { codigo: programacion }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}

function grabarPresupuesto() {
	codigo = document.getElementById('codigo1');
	presupuesto = document.getElementById("presupuesto2");
	observacion = document.getElementById('obs1');
	///
	if (presupuesto.value !== "") {
	    var boton = document.getElementById("btn-presupuesto");
		loadingBtn(boton);
		/////////// POST ////////////
		var http = new FormData();
		http.append("request", "presupuesto");
		http.append("codigo", codigo.value);
		http.append("presupuesto", presupuesto.value);
		http.append("observacion", observacion.value);

		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ejecucion.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					swal("Error", resultado.message, "error").then((value) => {
						deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
					});
					return;
				}
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		if (presupuesto.value === "") {
			presupuesto.classList.add("is-invalid");
		} else {
			presupuesto.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

*/