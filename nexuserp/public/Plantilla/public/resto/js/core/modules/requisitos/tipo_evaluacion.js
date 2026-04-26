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


function printTable() {
	contenedor = document.getElementById("result");

	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	requisito = document.getElementById('requisito');
	http.append("request", "tabla_tipo_evaluacion");
	http.append("requisito", requisito.value);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_tipo_Evaluacion.php");
	request.send(http);
	request.onreadystatechange = function () {
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				contenedor.innerHTML = '...';
				return;
			}
			var data = resultado.tabla;
			contenedor.innerHTML = data;
			$('#tabla').DataTable({
				pageLength: 50,
				responsive: true,
				dom: '<"html5buttons"B>lTfgitp',
				buttons: [
					{ extend: 'copy' },
					{ extend: 'csv' },
					{ extend: 'excel', title: 'Tabla de Actividades' },
					{ extend: 'pdf', title: 'Tabla de Actividades' },
					{
						extend: 'print',
						customize: function (win) {
							$(win.document.body).addClass('white-bg');
							$(win.document.body).css('font-size', '10px');
							$(win.document.body).find('table')
								.addClass('compact')
								.css('font-size', 'inherit');
						}, title: 'Tabla de Actividades'
					}
				]
			});
		}
	};
}

function Seleccionar(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	fecharReevaluacion = document.getElementById("fecha");
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "get");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_tipo_Evaluacion.php");
	request.send(http);
	request.onreadystatechange = function () {
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				return;
			}
			var data = resultado.data;
			document.getElementById("codigo").value = data.codigo;
			requisito = document.getElementById('requisito');
			cumple = document.getElementById('cumple');
			aspecto = document.getElementById('aspecto');
			componente = document.getElementById('componente');
			frecuencia = document.getElementById('frecuencia');
			fecharReevaluacion = document.getElementById("fecha");
			evaRequisito = document.getElementById("evarequisito");

			requisito.value = data.requisito;
			trueFalse(cumple, data.cumple)
			setSelect2("nombre", data.nombre);
			aspecto.value = data.aspecto;
			componente.value = data.componente;
			setSelect2("frecuencia", data.frecuencia);
			fecharReevaluacion.value = data.fecha;
			trueFalse(evaRequisito, data.eva_requisito);
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
function validateCheck(checkbox) {
	if (checkbox.checked) {
		checkbox.value = 1;
	} else {
		checkbox.value = 0;
	}
}
function trueFalse(checkbox, value) {
	if (value == 1) {
		checkbox.checked = true;
	} else if (value == 0) {
		checkbox.checked = false;
	}
}

function Grabar() {
	contenedor = document.getElementById("result");
	info = contenedor.innerHTML;
	loadingCogs(contenedor);
	//--
	requisito = document.getElementById('requisito');
	nombre = document.getElementById('nombre');
	cumple = document.getElementById('cumple');
	validateCheck(cumple);
	aspecto = document.getElementById('aspecto');
	componente = document.getElementById('componente');
	frecuencia = document.getElementById('frecuencia');
	fecharReevaluacion = document.getElementById("fecha");
	evaRequisito = document.getElementById("evarequisito");
	validateCheck(evaRequisito);
	if (requisito.value !== "" && nombre.value !== "" && cumple.value !== "" && aspecto.value !== "" && componente.value !== "" && frecuencia.value !== "" && fecharReevaluacion.value !== "" && evaRequisito.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar_tipo_evaluacion");
		http.append("requisito", requisito.value);
		http.append("nombre", nombre.value);
		http.append("cumple", cumple.value);
		http.append("aspecto", aspecto.value);
		http.append("componente", componente.value);
		http.append("frecuencia", frecuencia.value);
		http.append("fecha", fecharReevaluacion.value);
		http.append("evaRequisito", evaRequisito.value);

		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_tipo_Evaluacion.php");
		request.send(http);
		request.onreadystatechange = function () {
			if (request.readyState != 4) return;
			if (request.status === 200) {
				console.log(request.responseText);
				resultado = JSON.parse(request.responseText);

				if (resultado.status !== true) {
					contenedor.innerHTML = info;
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				swal("Excelente!", resultado.message, "success").then((value) => {
					deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
					nombre.value = "";
					aspecto.value = "";
					cumple.checked = false;
					setSelect2("frecuencia", "");
					componente.value = "";
					fecharReevaluacion.value = "";
					evaRequisito.checked = false;
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
	if (nombre.value === "") {
		nombre.classList.add("is-invalid");
	} else {
		nombre.classList.remove("is-invalid");
	}
	if (aspecto.value === "") {
		aspecto.classList.add("is-invalid");
	} else {
		aspecto.classList.remove("is-invalid");
	}
	if (componente.value === "") {
		componente.classList.add("is-invalid");
	} else {
		componente.classList.remove("is-invalid");
	}
	if (frecuencia.value === "") {
		frecuencia.parentNode.classList.add('has-error');
	} else {
		frecuencia.parentNode.classList.remove('has-error');
	}
	if (fecharReevaluacion.value === "") {
		fecharReevaluacion.classList.add("is-invalid");
	} else {
		fecharReevaluacion.classList.remove("is-invalid");
	}
}

function Modificar() {
	contenedor = document.getElementById("result");
	info = contenedor.innerHTML;
	loadingCogs(contenedor);
	//--
	codigo = document.getElementById('codigo');
	requisito = document.getElementById('requisito');
	nombre = document.getElementById('nombre');
	cumple = document.getElementById('cumple');
	validateCheck(cumple);
	aspecto = document.getElementById('aspecto');
	componente = document.getElementById('componente');
	frecuencia = document.getElementById('frecuencia');
	fecharReevaluacion = document.getElementById("fecha");
	console.log(fecharReevaluacion.value);
	evaRequisito = document.getElementById("evarequisito");
	validateCheck(evaRequisito);
	if (codigo.value !== "" && requisito.value !== "" && nombre.value !== "" && cumple.value !== "" && aspecto.value !== "" && componente.value !== "" && frecuencia.value !== "" && fecharReevaluacion.value !== "" && evaRequisito.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-modificar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar_tipo_evaluacion");
		http.append("codigo", codigo.value);
		http.append("requisito", requisito.value);
		http.append("nombre", nombre.value);
		http.append("cumple", cumple.value);
		http.append("aspecto", aspecto.value);
		http.append("componente", componente.value);
		http.append("frecuencia", frecuencia.value);
		http.append("fecha", fecharReevaluacion.value);
		http.append("evaRequisito", evaRequisito.value);

		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_tipo_Evaluacion.php");
		request.send(http);
		request.onreadystatechange = function () {
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				swal("Excelente!", resultado.message, "success").then((value) => {
					deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
					nombre.value = "";
					aspecto.value = "";
					cumple.checked = false;
					setSelect2("frecuencia", "");
					componente.value = "";
					fecharReevaluacion.value = "";
					evaRequisito.checked = false;
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
	if (nombre.value === "") {
		nombre.classList.add("is-invalid");
	} else {
		nombre.classList.remove("is-invalid");
	}	
	if (aspecto.value === "") {
		aspecto.classList.add("is-invalid");
	} else {
		aspecto.classList.remove("is-invalid");
	}
	if (componente.value === "") {
		componente.classList.add("is-invalid");
	} else {
		componente.classList.remove("is-invalid");
	}
	if (frecuencia.value === "") {
		frecuencia.parentNode.classList.add('has-error');
	} else {
		frecuencia.parentNode.classList.remove('has-error');
	}
	if (fecharReevaluacion.value === "") {
		fecharReevaluacion.classList.add("is-invalid");
	} else {
		fecharReevaluacion.classList.remove("is-invalid");
	}
}

function cambioSituacion(codigo, situacion) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	requisito = document.getElementById('requisito');
	http.append("request", "situacion");
	http.append("codigo", codigo);
	http.append("requisito", requisito.value);
	http.append("situacion", situacion);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_tipo_Evaluacion.php");
	request.send(http);
	request.onreadystatechange = function () {
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				return;
			}
			swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value) => {
				cumple.value = "";
				requisito.value = "";
				setSelect2("nombre", "");
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

