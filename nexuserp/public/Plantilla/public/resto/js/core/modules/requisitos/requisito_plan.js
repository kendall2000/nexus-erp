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

function SeleccionarProgramacion(codigo){		
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/requisitos/programacion.php",{codigo:codigo}, function(data){
	// Ponemos la respuesta de nuestro script en el DIV recargado
	$("#Pcontainer").html(data);
	});
	abrirModal();
}


function printTable() {
	contenedor = document.getElementById("result");
	requisito = document.getElementById("requisito");
	console.log(requisito.value);
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tabla");
	http.append("requisito", requisito.value);

	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_requisito_plan.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			//console.log( request.responseText );
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log(resultado);
				contenedor.innerHTML = '...';
				//swal("Informaci\u00F3n", resultado.message, "info");
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


function Grabar() {
	contenedor = document.getElementById("result");
	info = contenedor.innerHTML;
	loadingCogs(contenedor);
	//--
	requisito = document.getElementById('requisito');
	titulo_requisito = document.getElementById('titulo_requisito');
	fini = document.getElementById('fini');
	ffin = document.getElementById('ffin');
	tipo = document.getElementById('tipo');
	comentario = document.getElementById('comentario');
	if (requisito.value !== "" && fini.value !== "" && ffin.value !== ""  && comentario.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "planificar");
		http.append("requisito", requisito.value);
		http.append("fini", fini.value);
		http.append("ffin", ffin.value);
		// http.append("hini", hini.value);
		// http.append("hfin", hfin.value);
		http.append("mensaje", comentario.value);

		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_requisito_plan.php"); 
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request.responseText);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				//console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					contenedor.innerHTML = info;
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				//	console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
					requisito.value = "";
					titulo_requisito.value = "";
					comentario.value = "";
					//tabla
					var tabla = resultado.data;
					//console.log(tabla);
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
		swal("Ohoo!", "Debe llenar los Campos Obligatorios..", "error");
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

	if (comentario.value === "") {
		comentario.classList.add("is-invalid");
	} else {
		comentario.classList.remove("is-invalid");
	}
}


function Modificar() {
	contenedor = document.getElementById("result");
	info = contenedor.innerHTML;
	loadingCogs(contenedor);
	//--
	codigo = document.getElementById('codigo');
	requisito = document.getElementById('requisito');
	titulo_requisito = document.getElementById('titulo_requisito');
	fini = document.getElementById('fini');
	ffin = document.getElementById('ffin');
	tipo = document.getElementById('tipo');
	comentario = document.getElementById('comentario');
	if (codigo.value !== "" && requisito.value !== "" && fini.value !== "" && ffin.value !== ""  && comentario.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar_planificacion");
		http.append("codigo", codigo.value);
		http.append("requisito", requisito.value);
		http.append("fini", fini.value);
		http.append("ffin", ffin.value);
		http.append("mensaje", comentario.value);
		
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_requisito_plan.php"); 
		request.send(http);
		request.onreadystatechange = function () {
		//	console.log(request.responseText);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				//console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					contenedor.innerHTML = info;
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				//	console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					// deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
					// requisito.value = "";
					// comentario.value = "";
					// setSelect2("tipo", "");
					// //tabla
					// var tabla = resultado.data;
					// //console.log(tabla);
					// contenedor.innerHTML = tabla;
					// $('#tabla').DataTable({
					// 	pageLength: 50,
					// 	responsive: true
					// });
					window.location.reload();
				});
			}
		};
	} else {
		contenedor.innerHTML = info;
		swal("Ohoo!", "Debe llenar los Campos Obligatorios..", "error");
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
	if (comentario.value === "") {
		comentario.classList.add("is-invalid");
	} else {
		comentario.classList.remove("is-invalid");
	}
}



function cambioSituacion(codigo, situacion) {
	contenedor = document.getElementById("result");
	requisito = document.getElementById('requisito');
	fini = document.getElementById('fini');
	ffin = document.getElementById('ffin');
	tipo = document.getElementById('tipo');
	comentario = document.getElementById('comentario');
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	codigo_documento = document.getElementById('codigo-documento');
	titulo_documento = document.getElementById("titulo-documento");
	http.append("request", "situacion");
	http.append("codigo", codigo);
	http.append("situacion", situacion);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_requisito_plan.php");
	request.send(http);
	request.onreadystatechange = function () {
	 //console.log(request.responseText);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado.sql );
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value) => {
				requisito.value = "";
				comentario.value = "";
				//tabla
				// console.log(resultado.data);
				var tabla = resultado.data;
				//console.log(tabla);

				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
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
	request.open("POST", "ajax_fns_requisito_plan.php");
	request.send(http);
	request.onreadystatechange = function () {
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				return;
			}
			var data = resultado.data;
			//console.log(data);
			//set
			document.getElementById("codigo").value = data.codigo;
			requistito = document.getElementById('requisito');
			fini = document.getElementById('fini');
			ffin = document.getElementById('ffin');
			comentario = document.getElementById('comentario');
			titulo_requisito = document.getElementById('titulo_requisito');
			
			requisito.value = data.requisito;
			titulo_requisito.value = data.req_titulo;
			fini.value = data.fini;
			ffin.value = data.ffin;
			comentario.value = data.comentario;
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


function requisitos(){		
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/requisitos/requisitos_evaluacion.php", function(data){
	// Ponemos la respuesta de nuestro script en el DIV recargado
	$("#Pcontainer").html(data);
	});
	abrirModal();
}


function setRequisito(codigo, nombre) {
	document.getElementById("titulo_requisito").value = nombre;
	document.getElementById("requisito").value = codigo;
	cerrarModal();
}

