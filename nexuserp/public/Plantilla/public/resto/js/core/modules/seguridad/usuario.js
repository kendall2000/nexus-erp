//funciones javascript y validaciones
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

function printTable(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tabla");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_usuarios.php");
	request.send(http);
	request.onreadystatechange = function () {
		// console.log( request.responseText );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// console.log( resultado );
				contenedor.innerHTML = '...';
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			var data = resultado.tabla;
			//console.log( data );
			contenedor.innerHTML = data;
			$('#tabla').DataTable({
				responsive: true,
				pageLength: 50
			});
		}
	};
}


function seleccionarUsuario(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "get");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_usuarios.php");
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
			document.getElementById("nombre").value = data.nombre;
			document.getElementById("rol").value = data.rol;
			document.getElementById("mail").value = data.mail;
			document.getElementById("telefono").value = data.telefono;
			document.getElementById("usu").value = data.usuario;
			var habil = parseInt(data.habilita);
			if (habil === 0) {
				document.getElementById('habilita').checked = true;
			} else {
				document.getElementById('habilita').checked = false;
			}
			document.getElementById('habilita').removeAttribute('disabled');
			var seguridad = parseInt(data.seguridad);
			if (seguridad === 0) {
				document.getElementById('seguridad').checked = false;
			} else {
				document.getElementById('seguridad').checked = true;
				document.getElementById('seguridad').removeAttribute('disabled');
			}
			//tabla
			var tabla = resultado.tabla;
			//console.log( data );
			contenedor.innerHTML = tabla;
			$('#tabla').DataTable({
				responsive: true,
				pageLength: 50
			});
			$(".select2").select2();
			//botones
			document.getElementById("nombre").focus();
			document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
			document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
			//--
		}
	};
}

function Grabar() {
	nombre = document.getElementById("nombre");
	rol = document.getElementById('rol');
	mail = document.getElementById("mail");
	telefono = document.getElementById("telefono");
	usu = document.getElementById("usu");
	pass = document.getElementById("pass");
	//alert(cambio);
	selectrol = document.getElementById("select2-rol-container");
	var ValMail = false;

	if (nombre.value !== "" && rol.value !== "" && usu.value !== "" && pass.value !== "" && mail.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar");
		http.append("nombre", nombre.value);
		http.append("mail", mail.value);
		http.append("telefono", telefono.value);
		http.append("rol", rol.value);
		http.append("usuario", usu.value);
		http.append("pass", pass.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_usuarios.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log( request );
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
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
		if (nombre.value === "") {
			nombre.classList.add("is-invalid");
		} else {
			nombre.classList.remove("is-invalid");
		}
		if (rol.value === "") {
			selectrol.className = "select-danger select2-selection__rendered";
		} else {
			selectrol.className = "select2-selection__rendered";
		}
		if (usu.value === "") {
			usu.classList.add("is-invalid");
		} else {
			usu.classList.remove("is-invalid");
		}
		if (pass.value === "") {
			pass.classList.add("is-invalid");
		} else {
			pass.classList.remove("is-invalid");
		}
		if (mail.value === "") {
			mail.classList.add("is-invalid");
		} else {
			mail.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function Modificar() {
	codigo = document.getElementById('codigo');
	nombre = document.getElementById('nombre');
	rol = document.getElementById('rol');
	mail = document.getElementById('mail');
	telefono = document.getElementById('telefono');
	usu = document.getElementById("usu");
	pass = document.getElementById("pass");
	habilita = (document.getElementById("habilita").checked) ? 0 : 1;
	seguridad = (document.getElementById("seguridad").checked) ? 1 : 0;
	//--
	selectrol = document.getElementById("select2-rol-container");

	if (nombre.value !== "" && rol.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-modificar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar");
		http.append("codigo", codigo.value);
		http.append("nombre", nombre.value);
		http.append("mail", mail.value);
		http.append("telefono", telefono.value);
		http.append("rol", rol.value);
		http.append("usuario", usu.value);
		http.append("pass", pass.value);
		http.append("habilita", habilita);
		http.append("seguridad", seguridad);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_usuarios.php");
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
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		if (nombre.value === "") {
			nombre.classList.add("is-invalid");
		} else {
			nombre.classList.remove("is-invalid");
		}
		if (rol.value === "") {
			selectrol.className = "select-danger select2-selection__rendered";
		} else {
			selectrol.className = "select2-selection__rendered";
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function verInformacion(codigo) {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/usuario/info_usuario.php", { codigo: codigo }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}

function deshabilitarUsuario(usuario) {
	swal({
		text: "\u00BFEst\u00E1 seguro de deshabilitar a este usuario?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				cambioSituacion(usuario, 0);
				break;
			default:
				return;
		}
	});
}

function habilitarUsuario(usuario) {
	swal({
		text: "\u00BFEst\u00E1 seguro de habilitar a este usuario?",
		icon: "info",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				cambioSituacion(usuario, 1);
				break;
			default:
				return;
		}
	});
}

function cambioSituacion(codigo, situacion) {
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "situacion");
	http.append("codigo", codigo);
	http.append("situacion", situacion);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_usuarios.php");
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
			swal("Excelente!", "Cambio de situaci\u00F3n satisfactorio!!!", "success").then((value) => { window.location.reload(); });
		}
	};
}

function asignarPermisos() {
	usuario = document.getElementById('usuario');
	rol = document.getElementById('rol');
	cant = document.getElementById('cant').value;
	var C = 0;
	if (cant > 0) {
		if (usuario.value !== "" && rol.value !== "") {
			var arrperm = Array([]);
			var arrgrup = Array([]);
			for (var i = 1; i <= cant; i++) {
				chk = document.getElementById('chk' + i);
				if (chk.checked) {
					perm = document.getElementById('cod' + i).value;
					grup = document.getElementById('gru' + i).value;
					arrperm[C] = perm;
					arrgrup[C] = grup;
					C++;
				}
			}
			if (C > 0) {
				/////////// POST /////////
				var boton = document.getElementById("btn-asignar");
				loadingBtn(boton);
				var http = new FormData();
				http.append("request", "asignar");
				http.append("usuario", usuario.value);
				http.append("rol", rol.value);
				http.append("permisos", arrperm);
				http.append("grupos", arrgrup);
				http.append("cantidad", C);
				var request = new XMLHttpRequest();
				request.open("POST", "ajax_fns_usuarios.php");
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
				swal("Pero antes!", "Seleccione los permisos a asignar en este rol...", "info");
			}
		} else {
			if (usuario.value === "") {
				usuario.className = " form-danger";
			} else {
				usuario.className = " form-control";
			}
			if (rol.value === "") {
				rol.className = " form-danger";
			} else {
				rol.className = " form-control";
			}
			swal("Ups!", "Debe llenar los Campos Obligatorios", "warning");
		}
	} else {
		swal("Ups!", "No hay permisos por asignar...", "warning");
	}
}


function asignarSede(arrsedes) {
	usuario = document.getElementById('usuario');

	if (usuario.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-asignar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "asignar_sede");
		http.append("usuario", usuario.value);
		http.append("sedes", arrsedes);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_usuarios.php");
		request.send(http);
		request.onreadystatechange = function () {
			console.log(request);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					//console.log( resultado.sql );
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				//console.log( resultado.sql );
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function asignarCategorias(arrcategorias) {
	usuario = document.getElementById('usuario');

	if (usuario.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-asignar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "asignar_categoria");
		http.append("usuario", usuario.value);
		http.append("categorias", arrcategorias);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_usuarios.php");
		request.send(http);
		request.onreadystatechange = function () {
			console.log(request);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					//console.log( resultado.sql );
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				//console.log( resultado.sql );
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}


function asignarCategoriasIndicadores(arrcategorias) {
	usuario = document.getElementById('usuario');

	if (usuario.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-asignar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "asignar_categoria_indicador");
		http.append("usuario", usuario.value);
		http.append("categorias", arrcategorias);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_usuarios.php");
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
				//console.log( resultado.sql );
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.reload();
				});
			}
		};
	} else {
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function asignarDepartamento(arrdepartamentos) {
	usuario = document.getElementById('usuario');

	if (usuario.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-asignar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "asignar_departamento");
		http.append("usuario", usuario.value);
		http.append("departamentos", arrdepartamentos);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_usuarios.php");
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

function checkTodoGrupo(grupo) {
	chkg = document.getElementById("chkg" + grupo);
	glist = document.getElementById("gruplist" + grupo);
	var cadena = glist.value;
	var separador = cadena.split("-");
	var cuantos = separador[1];
	var inicia = (parseInt(separador[1]) - parseInt(separador[0])) + 1;
	//alert(inicia+"-"+cuantos);
	if (chkg.checked) {
		for (var i = inicia; i <= cuantos; i++) {
			document.getElementById("chk" + i).checked = true;
		}
	} else {
		for (var i = inicia; i <= cuantos; i++) {
			document.getElementById("chk" + i).checked = false;
		}
	}
}


////////////////////////////////////////////// ASIGNACION DE PERMISOS ////////////////////////////////

function printTableAsignacion() {
	contenedor = document.getElementById("encabezado");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tablaasignacion");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_usuarios.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado );
				contenedor.innerHTML = '...';
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			var data = resultado.tabla;
			//console.log( data );
			contenedor.innerHTML = data;
			$('.select2').select2({
				width: '100%'
			});
			$('#tabla').DataTable({
				responsive: true,
				pageLength: 50
			});
		}
	};
}


function cuadroRoles(codigo) {
	contenedor = document.getElementById("encabezado");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "cuadroroles");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_usuarios.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado );
				contenedor.innerHTML = '...';
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			var data = resultado.cuadro;
			//console.log( data );
			contenedor.innerHTML = data;
			$('.select2').select2({
				width: '100%'
			});
		}
	};
}


function cuadroPermisosRol(codigo) {
	contenedor = document.getElementById("cuerpo");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "permisosroles");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_usuarios.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado );
				contenedor.innerHTML = '...';
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			var data = resultado.tabla;
			//console.log( data );
			contenedor.innerHTML = data;
		}
	};
}