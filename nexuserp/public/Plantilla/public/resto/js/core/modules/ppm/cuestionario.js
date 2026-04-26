//funciones javascript y validaciones
$(document).ready(function () {
	$(".select2").select2();
});

function atras() {
	if (document.getElementById('codigo').value != '') window.location.reload();
	else window.history.back();
}

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

function printTable(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tabla");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_programacion.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado );
				contenedor.innerHTML = '...';
				console.log( resultado.message );
				return;
			}
			//tabla
			var data = resultado.tabla;
			contenedor.innerHTML = data;
			$('#tabla').DataTable({
				pageLength: 50,
				responsive: true,
				dom: '<"html5buttons"B>lTfgitp',
				buttons: [
					{ extend: 'copy' },
					{ extend: 'csv' },
					{ extend: 'excel', title: 'Tabla de Cuestionario' },
					{ extend: 'pdf', title: 'Tabla de Cuestionario' },
					{
						extend: 'print',
						customize: function (win) {
							$(win.document.body).addClass('white-bg');
							$(win.document.body).css('font-size', '10px');
							$(win.document.body).find('table')
								.addClass('compact')
								.css('font-size', 'inherit');
						}, title: 'Tabla de Cuestionario'
					}
				]
			});
		}
	};
}

function Grabar() {
	categoria = document.getElementById("categoria");
	nombre = document.getElementById("nombre");

	if (categoria.value !== "" && nombre.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar");
		http.append("categoria", categoria.value);
		http.append("nombre", nombre.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_programacion.php");
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
				//console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		if (categoria.value === "") {
			categoria.parentNode.classList.add('has-error');
		} else {
			categoria.parentNode.classList.remove('has-error');
		}
		if (nombre.value === "") {
			nombre.classList.add("is-invalid");
		} else {
			nombre.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function Modificar() {
	codigo = document.getElementById("codigo");
	categoria = document.getElementById("categoria");
	nombre = document.getElementById('nombre');

	if (codigo.value !== "" && categoria.value !== "" && nombre.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-modificar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar");
		http.append("codigo", codigo.value);
		http.append("categoria", categoria.value);
		http.append("nombre", nombre.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_programacion.php");
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
				//console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		if (categoria.value === "") {
			categoria.parentNode.classList.add('has-error');
		} else {
			categoria.parentNode.classList.remove('has-error');
		}
		if (nombre.value === "") {
			nombre.classList.add("is-invalid");
		} else {
			nombre.classList.remove("is-invalid");
		}
	}
	swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
}

function Confirm_Delete_Cuestionario(codigo) {
	swal({
		text: "\u00BFDesea quitar a este cuestionario?, no prodr\u00E1 ser usada despu\u00E9s...",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				/////////// POST /////////
				var http = new FormData();
				http.append("request", "situacion");
				http.append("codigo", codigo);
				http.append("situacion", 0);
				var request = new XMLHttpRequest();
				request.open("POST", "ajax_fns_programacion.php");
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
						//console.log( resultado );
						swal("Excelente!", resultado.message, "success").then((value) => {
							window.location.reload();
						});
					}
				};
				break;
			default:
				return;
		}
	});
}


function seleccionarCuestionario(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "get");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_programacion.php");
	request.send(http);
	request.onreadystatechange = function () {
		console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			var data = resultado.data;
			//console.log( data );
			//set
			document.getElementById("codigo").value = data.codigo;
			document.getElementById("categoria").value = data.categoria;
			document.getElementById("nombre").value = data.nombre;

			// Renderizado
			var tabla = resultado.tabla;
			contenedor.innerHTML = tabla;
			$('#tabla').DataTable({
				pageLength: 50,
				responsive: true
			});
			$(".select2").select2();
			// Botones
			document.getElementById("nombre").focus();
			document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
			document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
		}
	};
}

////////////////////////// PREGUNTAS ////////////////////////////////////////////

function preguntas(cuestionario) {
	window.location.href = "FRMpreguntas.php?cuestionario=" + cuestionario;
}


function printTablePregunta(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tabla_pregunta");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_programacion.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado );
				contenedor.innerHTML = '...';
				console.log( resultado.message );
				return;
			}
			//tabla
			var data = resultado.tabla;
			contenedor.innerHTML = data;
			$('#tabla').DataTable({
				pageLength: 50,
				responsive: true,
				dom: '<"html5buttons"B>lTfgitp',
				buttons: [
					{ extend: 'copy' },
					{ extend: 'csv' },
					{ extend: 'excel', title: 'Tabla de Preguntas' },
					{ extend: 'pdf', title: 'Tabla de Preguntas' },
					{
						extend: 'print',
						customize: function (win) {
							$(win.document.body).addClass('white-bg');
							$(win.document.body).css('font-size', '10px');
							$(win.document.body).find('table')
								.addClass('compact')
								.css('font-size', 'inherit');
						}, title: 'Tabla de Preguntas'
					}
				]
			});
		}
	};
}

function seleccionarPregunta(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "get_pregunta");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_programacion.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			var data = resultado.data;
			//console.log( data );
			//set
			document.getElementById("codigo").value = data.codigo;
			document.getElementById("cuestionario").value = data.cuestionario;
			document.getElementById("pregunta").value = data.pregunta;

			// Renderizado
			var tabla = resultado.tabla;
			contenedor.innerHTML = tabla;
			$('#tabla').DataTable({
				pageLength: 50,
				responsive: true
			});
			$(".select2").select2();
			// Botones
			document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
			document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
		}
	};
}

function GrabarPregunta() {
	cuestionario = document.getElementById("cuestionario");
	pregunta = document.getElementById('pregunta');

	if (cuestionario.value !== "" && pregunta.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar_pregunta");
		http.append("cuestionario", cuestionario.value);
		http.append("pregunta", pregunta.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_programacion.php");
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
				//console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		if (pregunta.value === "") {
			pregunta.classList.add("is-invalid");
		} else {
			pregunta.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function ModificarPregunta() {
	codigo = document.getElementById('codigo');
	cuestionario = document.getElementById("cuestionario");
	pregunta = document.getElementById('pregunta');

	if (cuestionario.value !== "" && pregunta.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-modificar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar_pregunta");
		http.append("codigo", codigo.value);
		http.append("cuestionario", cuestionario.value);
		http.append("pregunta", pregunta.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_programacion.php");
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
				//console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		if (pregunta.value === "") {
			pregunta.classList.add("is-invalid");
		} else {
			pregunta.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function Confirm_Delete_Pregunta(codigo) {
	swal({
		text: "\u00BFDesea quitar a esta pregunta del cuestionario?, no prodr\u00E1 ser usada despu\u00E9s...",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				/////////// POST /////////
				var http = new FormData();
				http.append("request", "situacion_pregunta");
				http.append("codigo", codigo);
				http.append("situacion", 0);
				var request = new XMLHttpRequest();
				request.open("POST", "ajax_fns_programacion.php");
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
						//console.log( resultado );
						swal("Excelente!", resultado.message, "success").then((value) => {
							window.location.reload();
						});
					}
				};
				break;
			default:
				return;
		}
	});
}