/////////////////// buscar Documento /////////////////
function setDocumento(codigo, nombre) {
	document.getElementById("titulo-documento").value = nombre;
	document.getElementById("codigo-documento").value = codigo;
	cerrarModal();
}

function documentos() {
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/requisitos/documentos.php", function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}
/////////////////// buscar Documento /////////////////
function setRequisito(codigo, nombre) {
	// document.getElementById("titulo-requisito").value = nombre;
	// document.getElementById("codigo-requisito").value = codigo;
	cerrarModal();
	window.location.href = 'FRMrequisito_procesos.php?requisito=' + codigo + '&titulo=' + nombre;
}

function requisitos() {
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/requisitos/requisitos.php", function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}
function detalles(codigo){
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/requisitos/evaluacion_informacion.php",{codigo:codigo}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
}

function evaluacionRequisito(codigo){
	window.location.href = "FRMrequisito_plan.php?requisito=" + codigo;
}

function verProgramacion(hashkey) {
	window.location.href = "FRMrevision.php?hashkey=" + hashkey;
}

//////////////////////////////// Asignaciones ///////////////////////////////
function asignar_requisito_proceso(arrProceso) {
	requisito = document.getElementById('codigo-requisito');

	if (requisito.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-asignar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "asignar_requisito_proceso");
		http.append("requisito", requisito.value);
		http.append("usuarios", arrProceso);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_requisitos.php");
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

function asignarUsuario(arrUsuario) {
	requisito = document.getElementById('codigo-requisito');

	if (requisito.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-asignar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "asignar_requisito");
		http.append("requisito", requisito.value);
		http.append("usuarios", arrUsuario);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_requisitos.php");
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


function refresh(){
	document.getElementById('titulo-requisito').value = '';
	document.getElementById('codigo-requisito').value = '';

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
	http.append("request", "tabla_requisitos");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_requisitos.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request);
		if (request.readyState != 4) return;
		if (request.status === 200) {
		//	console.log( request.responseText );
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
			// $('#tabla').DataTable({
			// 	pageLength: 50,
			// 	responsive: true,
			// 	dom: '<"html5buttons"B>lTfgitp',
			// 	buttons: [
			// 		{ extend: 'copy' },
			// 		{ extend: 'csv' },
			// 		{ extend: 'excel', title: 'Tabla de Actividades' },
			// 		{ extend: 'pdf', title: 'Tabla de Actividades' },
			// 		{
			// 			extend: 'print',
			// 			customize: function (win) {
			// 				$(win.document.body).addClass('white-bg');
			// 				$(win.document.body).css('font-size', '10px');
			// 				$(win.document.body).find('table')
			// 					.addClass('compact')
			// 					.css('font-size', 'inherit');
			// 			}, title: 'Tabla de Actividades'
			// 		}
			// 	]
			// });
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
	request.open("POST", "ajax_fns_requisitos.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				return;
			}
			var data = resultado.data;
			console.log(data);
			//set
			document.getElementById("codigo").value = data.codigo;
			// requisito = document.getElementById('requisito');
			comentario = document.getElementById('comentario');
			nomenclatura = document.getElementById('nomenclatura');
			codigo_documento = document.getElementById('codigo-documento');
			titulo_documento = document.getElementById('titulo-documento');

			descripcion = document.getElementById('descripcion');
			soporte = document.getElementById('soporte');

			//	console.log(sistema);
			// requisito.value = data.requisito;
			$('#requisito').val(data.requisito).trigger('change');
			comentario.value = data.comentario;
			nomenclatura.value = data.nomenclatura;
			descripcion.value = data.descripcion;
			codigo_documento.value = data.codigo_documento;
			titulo_documento.value = data.titulo_documento;
			soporte.value = data.documento_soporte;
			//AutoGrowTextArea(descripcion);
			//botones
			console.log('Cambio de botones - inicio');
			document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
			document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
			console.log('Cambio de botones - fin');
			//tabla
			var tabla = resultado.tabla;
			contenedor.innerHTML = tabla;
			// $('#tabla').DataTable({
			// 	pageLength: 50,
			// 	responsive: true
			// });
			nomenclatura.focus();
		}
	};
}

function Grabar() {
	contenedor = document.getElementById("result");
	info = contenedor.innerHTML;
	loadingCogs(contenedor);
	//--
	requisito = document.getElementById('requisito');
	comentario = document.getElementById('comentario');
	nomenclatura = document.getElementById('nomenclatura');
	descripcion = document.getElementById('descripcion');
	soporte = document.getElementById('soporte');
	codigo_documento = document.getElementById('codigo-documento');
	titulo_documento = document.getElementById("titulo-documento");
	//console.log(requisito.value, comentario.value);
	if (nomenclatura.value !== "" && codigo_documento.value !== "" && titulo_documento.value !== "" && descripcion.value !== "" && requisito.value !=="") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar_requisito");
		http.append("requisito", requisito.value);
		http.append("comentario", comentario.value);
		http.append("nomenclatura", nomenclatura.value);
		http.append("documento", codigo_documento.value);
		http.append("descripcion", descripcion.value);
		http.append("documento_soporte", soporte.value);

		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_requisitos.php"); 
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request.responseText);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				//console.log(resultado);
				if (resultado.status !== true) {
					contenedor.innerHTML = info;
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				//	console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
					descripcion.value = "";
					nomenclatura.value = "";
					titulo_documento.value = "";
					codigo_documento.value = "";
					soporte.value = "";
					comentario.value = "";
					requisito.value = "";
					//tabla
					var tabla = resultado.data;
					// console.log(tabla);
					contenedor.innerHTML = tabla;
					// $('#tabla').DataTable({
					// 	pageLength: 50,
					// 	responsive: true
					// });
				});
			}
		};
	} else {
		contenedor.innerHTML = info;
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
	if (nomenclatura.value === "") {
		nomenclatura.classList.add("is-invalid");
	} else {
		nomenclatura.classList.remove("is-invalid");
	}
	if (titulo_documento.value === "") {
		titulo_documento.classList.add("is-invalid");
	} else {
		titulo_documento.classList.remove("is-invalid");
	}
	
	if (requisito.value === "") {
		requisito.classList.add("is-invalid");
	} else {
		requisito.classList.remove("is-invalid");
	}
}

function Modificar() {
	contenedor = document.getElementById("result");
	info = contenedor.innerHTML;
	loadingCogs(contenedor);
	codigo = document.getElementById('codigo');
	// console.log(codigo.value);
	nomenclatura = document.getElementById('nomenclatura');
	descripcion = document.getElementById('descripcion');
	soporte = document.getElementById('soporte');
	codigo_documento = document.getElementById('codigo-documento');
	titulo_documento = document.getElementById("titulo-documento");
	comentario = document.getElementById('comentario');
	requisito = document.getElementById('requisito');
	if (codigo.value !== "" && nomenclatura.value !== "" && codigo_documento.value !== "" && titulo_documento !== "" && descripcion.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-modificar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar_requisito");
		http.append("codigo",codigo.value);
		http.append("requisito", requisito.value);
		http.append("comentario", comentario.value);
		http.append("nomenclatura", nomenclatura.value);
		http.append("documento", codigo_documento.value);
		http.append("descripcion", descripcion.value);
		http.append("documento_soporte", soporte.value);

		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_requisitos.php");
		request.send(http);
		request.onreadystatechange = function () {
			// console.log(request.responseText);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Modificar'); });
					return;
				}
				//console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					deloadingBtn(boton, '<i class="fa fa-save"></i> Modificar');
					$('#requisito').val(null).trigger('change');;
					comentario.value = "";
					descripcion.value = "";
					nomenclatura.value = "";
					titulo_documento.value = "";
					codigo_documento.value = "";
					soporte.value = "";
					//tabla
					var tabla = resultado.data;
					// console.log(tabla);
					contenedor.innerHTML = tabla;
					// $('#tabla').DataTable({
					// 	pageLength: 50,
					// 	responsive: true
					// });
					document.getElementById("btn-grabar").className = "btn btn-primary btn-sm";
					document.getElementById("btn-modificar").className = "btn btn-primary btn-sm hidden";
				});
			}
		};
	} else {
		contenedor.innerHTML = info;
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
	if (nomenclatura.value === "") {
		nomenclatura.classList.add("is-invalid");
	} else {
		nomenclatura.classList.remove("is-invalid");
	}
	if (descripcion.value === "") {
		descripcion.classList.add("is-invalid");
	} else {
		descripcion.classList.remove("is-invalid");
	}
	if (codigo_documento.value === "") {
		codigo_documento.classList.add("is-invalid");
	} else {
		codigo_documento.classList.remove("is-invalid");
	}
	if (requisito.value === "") {
		requisito.classList.add("is-invalid");
	} else {
		requisito.classList.remove("is-invalid");
	}
}



function cambioSituacion(codigo, situacion) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	codigo_documento = document.getElementById('codigo-documento');
	titulo_documento = document.getElementById("titulo-documento");
	http.append("request", "situacion");
	http.append("codigo", codigo);
	http.append("situacion", situacion);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_requisitos.php");
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
				nomenclatura.value = "";
				titulo_documento.value = "";
				codigo_documento.value = "";
				soporte.value = "";
				//tabla
				// console.log(resultado.data);
				var tabla = resultado.data;
				console.log(tabla);

				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
			});
		}
	};
}

function ver_descripcion_requisito(codigo){		
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/requisitos/descripcionComentario.php",{codigo:codigo}, function(data){
	// Ponemos la respuesta de nuestro script en el DIV recargado
	$("#Pcontainer").html(data);
	});
	abrirModal();
}
function procesosAsignados(codigo){		
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/requisitos/procesos.php",{codigo:codigo}, function(data){
	// Ponemos la respuesta de nuestro script en el DIV recargado
	$("#Pcontainer").html(data);
	});
	abrirModal();
}
function seleccionarProgramacion(hashkey) {
	window.location.href = "FRMejecutar.php?hashkey=" + hashkey;
}
