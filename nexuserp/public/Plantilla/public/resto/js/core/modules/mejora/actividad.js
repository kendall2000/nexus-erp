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

function printTable() {
	contenedor = document.getElementById("result");
	plan = document.getElementById("plan");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tabla");
	http.append("plan", plan.value);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_plan.php");
	request.send(http);
	request.onreadystatechange = function () {
		// console.log(request);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log( request.responseText );
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado );
				contenedor.innerHTML = '...';
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			//tabla
			contenedor.innerHTML = resultado.tabla;
		}
	};
}

function Seleccionar(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "get");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_plan.php");
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
			if (data.tipo == 1) {
				setSelect2("periodicidad", data.periodicidad);
				cambiaTipo();
				document.getElementById("desde").value = data.desde;
				document.getElementById("hasta").value = data.hasta;
			}
			descripcion = document.getElementById("descripcion");
			descripcion.value = data.descripcion;
			AutoGrowTextArea(descripcion);
			//tabla
			var tabla = resultado.tabla;
			contenedor.innerHTML = tabla;
			$('#tabla').DataTable({
				pageLength: 50,
				responsive: true
			});
			//botones
			document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
			document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
		}
	};
}

function Grabar() {
	contenedor = document.getElementById("result");
	info = contenedor.innerHTML;
	loadingCogs(contenedor);
	//--
	plan = document.getElementById('plan');
	fini = document.getElementById('desde');
	ffin = document.getElementById('hasta');
	periodicidad = document.getElementById('periodicidad');
	dini = document.getElementById('inicio');
	dfin = document.getElementById('fin');
	descripcion = document.getElementById('descripcion');

	if (plan.value !== "" && descripcion.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar");
		http.append("fini", fini.value);
		http.append("ffin", ffin.value);
		http.append("periodicidad", periodicidad.value);
		http.append("dini", dini.value);
		http.append("dfin", dfin.value);
		http.append("plan", plan.value);
		http.append("descripcion", descripcion.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_plan.php");
		request.send(http);
		request.onreadystatechange = function () {
			// console.log(request.responseText);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					contenedor.innerHTML = info;
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				//console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
					descripcion.value = "";

					//tabla
					var tabla = resultado.data;
					contenedor.innerHTML = tabla;
					$('#tabla').DataTable({
						pageLength: 50,
						responsive: true
					});
				});
			}
		};
	} else {
		contenedor.innerHTML = info;
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
	if (fini.value === "") {
		fini.classList.add("is-invalid");
	} else {
		fini.classList.remove("is-invalid");
	}
	if (ffin.value === "") {
		ffin.classList.add("is-invalid");
	} else {
		ffin.classList.remove("is-invalid");
	}
	if (descripcion.value === "") {
		descripcion.classList.add("is-invalid");
	} else {
		descripcion.classList.remove("is-invalid");
	}
}

function Modificar() {
	codigo = document.getElementById('codigo');
	plan = document.getElementById('plan');
	fini = document.getElementById('desde');
	ffin = document.getElementById('hasta');
	periodicidad = document.getElementById('periodicidad');
	dini = document.getElementById('inicio');
	dfin = document.getElementById('fin');
	descripcion = document.getElementById('descripcion');

	if (codigo.value !== "" && plan.value !== "" && descripcion.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-modificar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar");
		http.append("codigo", codigo.value);
		http.append("plan", plan.value);
		http.append("fini", fini.value);
		http.append("ffin", ffin.value);
		http.append("periodicidad", periodicidad.value);
		http.append("dini", dini.value);
		http.append("dfin", dfin.value);
		http.append("descripcion", descripcion.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_plan.php");
		request.send(http);
		request.onreadystatechange = function () {
			// console.log(request.responseText);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				//console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
					descripcion.value = "";

					//tabla
					var tabla = resultado.data;
					contenedor.innerHTML = tabla;
					$('#tabla').DataTable({
						pageLength: 50,
						responsive: true
					});
					document.getElementById("btn-grabar").className = "btn btn-primary btn-sm";
					document.getElementById("btn-modificar").className = "btn btn-primary btn-sm hidden";
				});
			}
		};
	} else {
		contenedor.innerHTML = info;
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
	if (fini.value === "") {
		fini.classList.add("is-invalid");
	} else {
		fini.classList.remove("is-invalid");
	}
	if (ffin.value === "") {
		ffin.classList.add("is-invalid");
	} else {
		ffin.classList.remove("is-invalid");
	}
	if (descripcion.value === "") {
		descripcion.classList.add("is-invalid");
	} else {
		descripcion.classList.remove("is-invalid");
	}
}

function Deshabilitar(codigo) {
	swal({
		text: "\u00BFDesea quitar este registro del listado?, no prodr\u00E1 ser usado despu\u00E9s...",
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
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "situacion");
	http.append("codigo", codigo);
	http.append("situacion", situacion);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_plan.php");
	request.send(http);
	request.onreadystatechange = function () {
		// console.log(request.responseText);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado.sql );
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value) => {
				descripcion.value = "";

				//tabla
				// console.log(resultado.data);
				var tabla = resultado.data;
				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
			});
		}
	};
}

function update(codigo, elemento, campo) {
	var http = new FormData();
	http.append("request", "update");
	http.append("codigo", codigo);
	http.append("campo", campo);
	http.append("valor", elemento.value);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_plan.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request.readyState);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			//console.log(resultado);
			if (resultado.status !== true) {
				console.log(resultado.message);
				return;
			}
			// console.log(resultado.message);
		}
	};
}

/////////////////// Extras //////////////////////////////
function cambiaTipo() {
	rangos = document.getElementById("rangos");
	tipo = document.getElementById("periodicidad");
	switch (tipo.value) {
		case "W":
			rangos.classList.remove("hidden");
			inicio.innerHTML = "";
			fin.innerHTML = "";
			// --
			var option = document.createElement("option");
			var option2 = document.createElement("option");
			option.value = 1;
			option.innerHTML = "Lunes";
			option2.value = 1;
			option2.innerHTML = "Lunes";
			$('#inicio').append(option).trigger('change');
			$('#fin').append(option2).trigger('change');
			// --
			var option = document.createElement("option");
			var option2 = document.createElement("option");
			option.value = 2;
			option.innerHTML = "Martes";
			option2.value = 2;
			option2.innerHTML = "Martes";
			$('#inicio').append(option).trigger('change');
			$('#fin').append(option2).trigger('change');
			// --
			var option = document.createElement("option");
			var option2 = document.createElement("option");
			option.value = 3;
			option.innerHTML = "Miercoles";
			option2.value = 3;
			option2.innerHTML = "Miercoles";
			$('#inicio').append(option).trigger('change');
			$('#fin').append(option2).trigger('change');
			// --
			var option = document.createElement("option");
			var option2 = document.createElement("option");
			option.value = 4;
			option.innerHTML = "Jueves";
			option2.value = 4;
			option2.innerHTML = "Jueves";
			$('#inicio').append(option).trigger('change');
			$('#fin').append(option2).trigger('change');
			// --
			var option = document.createElement("option");
			var option2 = document.createElement("option");
			option.value = 5;
			option.innerHTML = "Viernes";
			option2.value = 5;
			option2.innerHTML = "Viernes";
			$('#inicio').append(option).trigger('change');
			$('#fin').append(option2).trigger('change');
			// --
			var option = document.createElement("option");
			var option2 = document.createElement("option");
			option.value = 6;
			option.innerHTML = "Sabado";
			option2.value = 6;
			option2.innerHTML = "Sabado";
			$('#inicio').append(option).trigger('change');
			$('#fin').append(option2).trigger('change');
			// --
			var option = document.createElement("option");
			var option2 = document.createElement("option");
			option.value = 7;
			option.innerHTML = "Domingo";
			option2.value = 7;
			option2.innerHTML = "Domingo";
			$('#inicio').append(option).trigger('change');
			$('#fin').append(option2).trigger('change');
			break;
		case "M":
			rangos.classList.remove("hidden");
			inicio.innerHTML = "";
			fin.innerHTML = "";
			for (i = 1; i <= 31; i++) {
				var option = document.createElement("option");
				var option2 = document.createElement("option");
				option.value = i;
				option.innerHTML = "D\u00EDa " + i;
				option2.value = i;
				option2.innerHTML = "D\u00EDa " + i;
				$('#inicio').append(option).trigger('change');
				$('#fin').append(option2).trigger('change');
			}
			break;
		default:
			rangos.classList.add("hidden");
			inicio.innerHTML = "";
			fin.innerHTML = "";
			break;
	}
	$(".select2").select2();
}

function verProgramacion(codigo) {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/mejora/programacion.php", { codigo: codigo }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}

function verEjecucion(codigo) {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/mejora/ejecucion.php", { codigo: codigo }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}

function verEvaluacion(codigo) {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/mejora/evaluacion.php", { codigo: codigo }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}