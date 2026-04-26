//funciones javascript y validacionesprogramacion
$(document).ready(function () {
	$(".select2").select2();
});

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
	window.location.href = "FRMmodprogramacion.php?hashkey=" + hashkey;
}

function verProgramacion(hashkey) {
	window.location.href = "FRMorden.php?hashkey=" + hashkey;
}

function Grabar() 
{
	activo = document.getElementById("activo");
	usuario = document.getElementById("usuario");
	presupuesto = document.getElementById("presupuesto");
	moneda = document.getElementById("moneda");
	categoria = document.getElementById("categoria");
	tipo = document.getElementById("tipo");
	cuestionario = document.getElementById('cuestionario');
	desde = document.getElementById("desde");
	hasta = document.getElementById('hasta');
	observacion = document.getElementById('observacion');
	hashkey = document.getElementById('hashkey');
	//diasssss
	var arrDias = Array();
	var hasSomething = false;
	if(tipo.value == 'M')
	{
		for (var dia = 1; dia <= 31; dia++) {
		//	console.log("dia" + dia);
			arrDias[dia] = (document.getElementById('dia' + dia).classList.contains('active')) ? 1 : 0;
			if (arrDias[dia]) hasSomething = true;
		}
	} else if(tipo.value == 'W'){
		for (var dia = 1; dia <= 7; dia++) {
			arrDias[dia] = (document.getElementById('diaW' + dia).classList.contains('active')) ? 1 : 0;
			if (arrDias[dia]) hasSomething = true;
		}
	} 
	console.log(arrDias);
	if (hasSomething && activo.value !== "" && usuario.value !== "" && categoria.value !== "" && tipo.value !== "" && presupuesto.value !== "" && moneda.value !== "" && cuestionario.value !== "" && desde.value !== "" && hasta.value !== "") {
		/////////// POST ////////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar_programacion");
		http.append("activo", activo.value);
		http.append("usuario", usuario.value);
		http.append("presupuesto", presupuesto.value);
		http.append("moneda", moneda.value);
		http.append("categoria", categoria.value);
		http.append("tipo", tipo.value);
		http.append("desde", desde.value);
		http.append("hasta", hasta.value);
		http.append("cuestionario", cuestionario.value); 	
		http.append("observacion", observacion.value);
		http.append("dias", arrDias);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_programacion.php");
		request.send(http);
		request.onreadystatechange = function () {
			console.log(request);
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
					if(hashkey.value != ""){
						window.location.href = "FRMprogramar.php?hashkey=" + hashkey.value;
					}else{
						window.location.href = "FRMprogramar.php";
					}
				});
			}
		};
	} else {
		if (activo.value === "") {
			activo.parentNode.classList.add('has-error');
		} else {
			activo.parentNode.classList.remove('has-error');
		}
		if (usuario.value === "") {
			usuario.parentNode.classList.add('has-error');
		} else {
			usuario.parentNode.classList.remove('has-error');
		}
		if (categoria.value === "") {
			categoria.parentNode.classList.add('has-error');
		} else {
			categoria.parentNode.classList.remove('has-error');
		}
		if (tipo.value === "") {
			tipo.parentNode.classList.add('has-error');
		} else {
			tipo.parentNode.classList.remove('has-error');
		}
		if (cuestionario.value === "") {
			cuestionario.parentNode.classList.add('has-error');
		} else {
			cuestionario.parentNode.classList.remove('has-error');
		}
		if (presupuesto.value === "") {
			presupuesto.classList.add("is-invalid");
		} else {
			presupuesto.classList.remove("is-invalid");
		}
		if (moneda.value === "") {
			moneda.parentNode.classList.add('has-error');
		} else {
			moneda.parentNode.classList.remove('has-error');
		}
		if (desde.value === "") {
			desde.classList.add("is-invalid");
		} else {
			desde.classList.remove("is-invalid");
		}
		if (hasta.value === "") {
			hasta.classList.add("is-invalid");
		} else {
			hasta.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function Modificar() {
	codigo = document.getElementById('codigo');
	presupuesto = document.getElementById("presupuesto");
	moneda = document.getElementById("moneda");
	categoria = document.getElementById("categoria");
	cuestionario = document.getElementById('cuestionario');
	fecha = document.getElementById("fecha");
	observacion = document.getElementById('observacion');


	if (activo.value !== "" && categoria.value !== "" && presupuesto.value !== "" && moneda.value !== "" && cuestionario.value !== "" && fecha.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar_programacion");
		http.append("codigo", codigo.value);
		http.append("presupuesto", presupuesto.value);
		http.append("moneda", moneda.value);
		http.append("categoria", categoria.value);
		http.append("cuestionario", cuestionario.value);
		http.append("fecha", fecha.value);
		http.append("observacion", observacion.value);
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
					window.location.href = "FRMprogramacion.php";
				});
			}
		};
	} else {
		if (categoria.value === "") {
			categoria.parentNode.classList.add('has-error');
		} else {
			categoria.parentNode.classList.remove('has-error');
		}
		if (cuestionario.value === "") {
			cuestionario.parentNode.classList.add('has-error');
		} else {
			cuestionario.parentNode.classList.remove('has-error');
		}
		if (presupuesto.value === "") {
			presupuesto.classList.add("is-invalid");
		} else {
			presupuesto.classList.remove("is-invalid");
		}
		if (moneda.value === "") {
			moneda.parentNode.classList.add('has-error');
		} else {
			moneda.parentNode.classList.remove('has-error');
		}
		if (fecha.value === "") {
			fecha.classList.add("is-invalid");
		} else {
			fecha.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function Confirm_Delete_Programacion(codigo) {
	swal({
		text: "\u00BFDesea cancelar esta programacion?, no prodr\u00E1 ser usada despu\u00E9s...",
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
				http.append("request", "situacion_programacion");
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


function reProgramar() {
	programacion = document.getElementById('programacion');
	fechaold = document.getElementById('fechaold');
	fechanew = document.getElementById("fechanew");
	justificacion = document.getElementById('justificacion');

	if (programacion.value !== "" && fechaold.value !== "" && fechanew.value !== "" && justificacion.value !== "") {
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		/////////// POST /////////
		var http = new FormData();
		http.append("request", "reprogramar");
		http.append("programacion", programacion.value);
		http.append("fechaold", fechaold.value);
		http.append("fechanew", fechanew.value);
		http.append("justificacion", justificacion.value);
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
					window.location.href = "FRMreprogramacion.php";
				});
			}
		};
	} else {
		if (fechaold.value === "") {
			fechaold.classList.add("is-invalid");
		} else {
			fechaold.classList.remove("is-invalid");
		}
		if (fechanew.value === "") {
			fechanew.classList.add("is-invalid");
		} else {
			fechanew.classList.remove("is-invalid");
		}
		if (justificacion.value === "") {
			justificacion.classList.add("is-invalid");
		} else {
			justificacion.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}


function reAsignar() {
	programacion = document.getElementById('programacion');
	usuarioold = document.getElementById('usuarioold');
	usuarionew = document.getElementById("usuarionew");
	justificacion = document.getElementById('justificacion');

	if (programacion.value !== "" && usuarioold.value !== "" && usuarionew.value !== "" && justificacion.value !== "") {
		abrir();
		/////////// POST /////////
		var http = new FormData();
		http.append("request", "reasignar");
		http.append("programacion", programacion.value);
		http.append("usuarioold", usuarioold.value);
		http.append("usuarionew", usuarionew.value);
		http.append("justificacion", justificacion.value);
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
					window.location.href = "FRMreasignacion.php";
				});
			}
		};
	} else {
		if (usuarioold.value === "") {
			usuarioold.classList.add("is-invalid");
		} else {
			usuarioold.classList.remove("is-invalid");
		}
		if (usuarionew.value === "") {
			usuarionew.classList.add("is-invalid");
		} else {
			usuarionew.classList.remove("is-invalid");
		}
		if (justificacion.value === "") {
			justificacion.classList.add("is-invalid");
		} else {
			justificacion.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}
function tipoProgramacion(tipo) {
	contenedorSemana = document.getElementById('containerSemana');
	contenedorMes = document.getElementById('containerMes');
	if (tipo === 'M') {
		contenedorSemana.style.display = 'none';
		contenedorMes.style.display = 'block';
	} else if(tipo === 'W') {
		contenedorSemana.style.display = 'block';
		contenedorMes.style.display = 'none';
	}else if(tipo == 'S'){
		contenedorSemana.style.display = 'none';
		contenedorMes.style.display = 'none';
	}
}


function getPeriodicidad(codigo){
	/////////// POST /////////
	periodicidad = document.getElementById("periodicidad");
	var http = new FormData();
	http.append("request", "get_periodicidad");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_programacion.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			//console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				console.log( resultado );
				// contenedor.innerHTML = '...';
				//console.log( resultado.message );
				return;
			}
			//tabla
			var data = resultado.data;
			var periodicidadd;
			switch (data.periodicidad) {
				case "D":
					periodicidadd = "Diario";
					break;
				case "W":
					periodicidadd = "Semanal";
					break;
				case "M":
					periodicidadd = "Mensual";
					break;
				case "Y":
					periodicidadd = "Anual";
					break;
				case "V":
					periodicidadd = "Variado";
					break;
			}
			periodicidad.value = periodicidadd;
		}
	};
}



function tablaReprogramacion(){
	contenedor = document.getElementById('result');
	contenedor.innerHTML = "";
	activo = document.getElementById('activo');
	usuario = document.getElementById('usuario');
	categoria = document.getElementById('categoria');
	area = document.getElementById('area');
	desde = document.getElementById('desde');
	hasta = document.getElementById('hasta');
	var http = new FormData();
	http.append("request", "tabla_reprogramacion");
	http.append("activo", activo.value);
	http.append("usuario", usuario.value);
	http.append("categoria", categoria.value);
	http.append("area", area.value);
	http.append("desde", desde.value);
	http.append("hasta", hasta.value);
	
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_programacion.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado );
				//contenedor.innerHTML = '...';
				//console.log( resultado.message );
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
						}, title: 'Reprogramacion de Actividades'
					}
				]
			});
		}
	};
}

