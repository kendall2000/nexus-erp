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
				vaciarCampos();
				break;
			default:
				return;
		}
	});
}

function vaciarCampos() {
	let currentDate = new Date();
	let cDay = currentDate.getDate();
	let cMonth = currentDate.getMonth() + 1;
	let cYear = currentDate.getFullYear();
	currentDate = cDay + "/" + cMonth + "/" + cYear;
	setCampos("", currentDate, "", "", "", "", "");
}

function setCampos(codigo, fecha, entidad, tipo, resumen, objetivo) {
	//-- Input
	document.getElementById("codigo").value = codigo;
	document.getElementById("fecha").value = fecha;
	document.getElementById("entidad").value = entidad;
	//-- Select
	setSelect2("tipo", tipo);
	//-- Textarea
	document.getElementById("resumen").value = resumen;
	AutoGrowTextArea(document.getElementById("resumen"));
	document.getElementById("objetivo").value = objetivo;
	AutoGrowTextArea(document.getElementById("objetivo"));
}

/////////////////// CRUD /////////////////////

function printTable() {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tabla");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_externa.php");
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
			$('#tabla').DataTable({
				pageLength: 50,
				responsive: true
			});
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
	request.open("POST", "ajax_fns_externa.php");
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
			setCampos(data.codigo, data.fecha, data.entidad, data.tipo, data.resumen, data.objetivo);
			//tabla
			contenedor.innerHTML = resultado.tabla;
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
	fecha = document.getElementById('fecha');
	tipo = document.getElementById('tipo');
	entidad = document.getElementById('entidad');
	objetivo = document.getElementById('objetivo');
	resumen = document.getElementById('resumen');

	if (fecha.value !== "" && tipo.value !== "" && entidad.value !== "" && objetivo.value !== "" && resumen.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar");
		http.append("fecha", fecha.value);
		http.append("tipo", tipo.value);
		http.append("entidad", entidad.value);
		http.append("objetivo", objetivo.value);
		http.append("resumen", resumen.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_externa.php");
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
					vaciarCampos();

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
	if (fecha.value === "") {
		fecha.classList.add("is-invalid");
	} else {
		fecha.classList.remove("is-invalid");
	}
	if (entidad.value === "") {
		entidad.classList.add("is-invalid");
	} else {
		entidad.classList.remove("is-invalid");
	}
	if (objetivo.value === "") {
		objetivo.classList.add("is-invalid");
	} else {
		objetivo.classList.remove("is-invalid");
	}
	if (resumen.value === "") {
		resumen.classList.add("is-invalid");
	} else {
		resumen.classList.remove("is-invalid");
	}
	if (tipo.value === "") {
		tipo.classList.add("has-error");
	} else {
		tipo.classList.remove("has-error");
	}
}

function Modificar() {
	info = contenedor.innerHTML;
	codigo = document.getElementById('codigo');
	fecha = document.getElementById('fecha');
	tipo = document.getElementById('tipo');
	entidad = document.getElementById('entidad');
	objetivo = document.getElementById('objetivo');
	resumen = document.getElementById('resumen');

	if (codigo.value !== "" && fecha.value !== "" && tipo.value !== "" && entidad.value !== "" && objetivo.value !== "" && resumen.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-modificar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar");
		http.append("codigo", codigo.value);
		http.append("fecha", fecha.value);
		http.append("tipo", tipo.value);
		http.append("entidad", entidad.value);
		http.append("objetivo", objetivo.value);
		http.append("resumen", resumen.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_externa.php");
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
					vaciarCampos();

					//tabla
					contenedor.innerHTML = resultado.data;
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
	if (fecha.value === "") {
		fecha.classList.add("is-invalid");
	} else {
		fecha.classList.remove("is-invalid");
	}
	if (entidad.value === "") {
		entidad.classList.add("is-invalid");
	} else {
		entidad.classList.remove("is-invalid");
	}
	if (objetivo.value === "") {
		objetivo.classList.add("is-invalid");
	} else {
		objetivo.classList.remove("is-invalid");
	}
	if (resumen.value === "") {
		resumen.classList.add("is-invalid");
	} else {
		resumen.classList.remove("is-invalid");
	}

	if (tipo.value === "") {
		tipo.parentNode.classList.add('has-error');
	} else {
		tipo.parentNode.classList.remove('has-error');
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
	request.open("POST", "ajax_fns_externa.php");
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
				vaciarCampos();
				//tabla
				// console.log(resultado.data);
				contenedor.innerHTML = resultado.data;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
			});
		}
	};
}
