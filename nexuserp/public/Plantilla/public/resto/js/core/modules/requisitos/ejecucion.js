function usuariosProceso(codigo) {
	cerrar();
	//Realiza duna peticion de contenido a la contenido.php
	$.post("../promts/process/usuarios.php", { codigo: codigo }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}

function EvaluarProgramacion(plan,proceso,crud){
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/requisitos/evaluacion.php", { plan: plan, proceso: proceso, crud:crud }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}
function actualizarEstado(){
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
		request.open("POST", "ajax_fns_evaluacion.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					// swal("Error", resultado.message, "error").then((value) => {
					// 	window.location.reload();
					// });
					return;
				}
				// swal("Excelente!", "Evaluacion apeturada Correctamente", "success").then((value) => {
				// 	//window.location.href = "FRMrequisito_evaluar.php";
				// });
			}
		};
	}
}
function actualizarObservacion() {
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
		request.open("POST", "ajax_fns_evaluacion.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					swal("Error", resultado.message, "error").then((value) => {
						//window.location.reload();
					});
					return;
				}
				// swal("Excelente!", resultado.message, "success").then((value) => {
				// 	window.location.href = "FRMrequisito_evaluar.php";
				// });
			}
		};
	}
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
		request.open("POST", "ajax_fns_evaluacion.php");
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
					window.location.href = "FRMrequisito_evaluar.php";
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
		request.open("POST", "ajax_fns_evaluacion.php");
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
					window.location.href = "FRMrequisito_evaluar.php";
				});
			}
		};
	}
}
function cerrarProgramacion() {
	codigo = document.getElementById("codigo");
	observacion = document.getElementById("obs");
	if (codigo.value != "") {
		swal({
			text: "\u00BFDesea Finalizar la evaluacion de este requisito?, no prodr\u00E1 ser modificada despu\u00E9s...",
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
						request.open("POST", "ajax_fns_evaluacion.php");
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
									window.location.href = "FRMrequisito_evaluar.php";
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

function modificarEvaluacion(codigo,plan,proceso){
	//--
	cumple = 0;
	cumpleSi = document.getElementById('flexRadioDefault1');
	cumpleNo = document.getElementById('flexRadioDefault2');
	observacion = document.getElementById('observacion');
	tipo = document.getElementById('tipo');
	aambientales = document.getElementById('aambientales');
	documento_soporte = document.getElementById("dsoporte");
	//console.log(documento_soporte.value);
	dubicacion = document.getElementById('dubicacion');
	aire = document.getElementById('aire');
	aireValidate = validateChecks(aire);
	suelo = document.getElementById('suelo');
	sueloValidate = validateChecks(suelo);
	agua = document.getElementById('agua');
	aguaValidate = validateChecks(agua);
	flora = document.getElementById('flora');
	floraValidate = validateChecks(flora);
	fauna = document.getElementById('fauna');
	faunaValidate = validateChecks(fauna);
	biota = document.getElementById('biota');
	biotaValidate = validateChecks(biota);
	paisaje = document.getElementById('paisaje');
	paisajeValidate = validateChecks(paisaje);
	console.log(cumple);
	if (cumple !== '' && observacion.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar_evaluacion");
		http.append("codigo", codigo);
		http.append("plan", plan);
		http.append("proceso", proceso);
		if(cumpleSi.checked == true){
			http.append("cumple", 1);
		}
		if(cumpleSi.checked == false){
			http.append("cumple", 0);
		}
		http.append("observacion", observacion.value);
		http.append("tipo", tipo.value);	
		http.append("aambiental", aambientales.value);	
		http.append("dsoporte", documento_soporte.value);	
		http.append("dubicacion", dubicacion.value);	
		http.append("aire", aireValidate);	
		http.append("suelo", sueloValidate);
		http.append("agua", aguaValidate);	
		http.append("flora", floraValidate);	
		http.append("fauna", faunaValidate);	
		http.append("biota", biotaValidate);	
		http.append("paisaje", paisajeValidate);	
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_evaluacion.php"); 
		request.send(http);
		request.onreadystatechange = function () {
			if (request.readyState != 4) return;
			if (request.status === 200) {
				console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		swal("Ohoo!", "Debe llenar los Campos Obligatorios..", "error");
	}
	if (observacion.value === "") {
		observacion.classList.add("is-invalid");
	} else {
		observacion.classList.remove("is-invalid");
	}
}

function validateChecks(combo){
	if(combo.checked == true){
		cumple = 1;
	}else if(combo.checked == false){
		cumple = 0;
	}
	return cumple;
}
function grabarEvaluacion(plan, proceso){
	//--
	cumpleSi = document.getElementById('flexRadioDefault1');
	cumpleNo = document.getElementById('flexRadioDefault2');
	observacion = document.getElementById('observacion');
	tipo = document.getElementById('tipo');
	aambientales = document.getElementById('aambientales');
	documento_soporte = document.getElementById("dsoporte");
	dubicacion = document.getElementById('dubicacion');
	aire = document.getElementById('aire');
	aireValidate = validateChecks(aire);
	suelo = document.getElementById('suelo');
	sueloValidate = validateChecks(suelo);
	agua = document.getElementById('agua');
	aguaValidate = validateChecks(agua);
	flora = document.getElementById('flora');
	floraValidate = validateChecks(flora);
	fauna = document.getElementById('fauna');
	faunaValidate = validateChecks(fauna);
	biota = document.getElementById('biota');
	biotaValidate = validateChecks(biota);
	paisaje = document.getElementById('paisaje');
	paisajeValidate = validateChecks(paisaje);
	if (cumple !== '' && observacion.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar_evaluacion");
		http.append("plan", plan);
		http.append("proceso", proceso);
		if(cumpleSi.checked == true){
			http.append("cumple", 1);
		}
		if(cumpleSi.checked == false){
			http.append("cumple", 0);
		}
		http.append("observacion", observacion.value);
		http.append("tipo", tipo.value);	
		http.append("aambiental", aambientales.value);	
		http.append("dsoporte", documento_soporte.value);	
		http.append("dubicacion", dubicacion.value);	
		http.append("aire", aireValidate);	
		http.append("suelo", sueloValidate);
		http.append("agua", aguaValidate);	
		http.append("flora", floraValidate);	
		http.append("fauna", faunaValidate);	
		http.append("biota", biotaValidate);	
		http.append("paisaje", paisajeValidate);	
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_evaluacion.php"); 
		request.send(http);
		request.onreadystatechange = function () {
			if (request.readyState != 4) return;
			if (request.status === 200) {
				console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		swal("Ohoo!", "Debe llenar los Campos Obligatorios..", "error");
	}
	if (observacion.value === "") {
		observacion.classList.add("is-invalid");
	} else {
		observacion.classList.remove("is-invalid");
	}
}