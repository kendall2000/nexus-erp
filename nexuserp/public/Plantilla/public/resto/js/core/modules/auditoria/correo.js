//funciones javascript y validaciones

$(document).ready(function () {
	printTable('');
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

function printTable(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tabla");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_correo.php");
	request.send(http);
	request.onreadystatechange = function () {
		// console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado );
				contenedor.innerHTML = '...';
				console.log(resultado.message);
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
					{ extend: 'excel', title: 'Tabla de Correos' },
					{ extend: 'pdf', title: 'Tabla de Correos' },
					{
						extend: 'print',
						customize: function (win) {
							$(win.document.body).addClass('white-bg');
							$(win.document.body).css('font-size', '10px');
							$(win.document.body).find('table')
								.addClass('compact')
								.css('font-size', 'inherit');
						}, title: 'Tabla de Correos'
					}
				]
			});
		}
	};
}


function seleccionarCorreo(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "get");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_correo.php");
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
			// console.log( data );
			//set
			document.getElementById("codigo").value = data.codigo;
			setSelect2("sede", data.sede);
			document.getElementById("auditoria").value = data.auditoria;
			document.getElementById("nombre").value = data.nombre;
			document.getElementById("correo").value = data.correo;
			//tabla
			var tabla = resultado.tabla;
			contenedor.innerHTML = tabla;
			$('#tabla').DataTable({
				pageLength: 50,
				responsive: true
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
	sede = document.getElementById('sede');
	auditoria = document.getElementById('auditoria');
	nombre = document.getElementById('nombre');
	correo = document.getElementById('correo');

	if (sede.value !== "" && auditoria.value !== "" && nombre.value !== "" && correo.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar");
		http.append("sede", sede.value);
		http.append("auditoria", auditoria.value);
		http.append("nombre", nombre.value);
		http.append("correo", correo.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_correo.php");
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
		if (sede.value === "") {
            sede.parentNode.classList.add('has-error');
		} else {
            sede.parentNode.classList.remove('has-error');
		}
		if (auditoria.value === "") {
            auditoria.parentNode.classList.add('has-error');
		} else {
            auditoria.parentNode.classList.remove('has-error');
		}
		if (nombre.value === "") {
			nombre.classList.add("is-invalid");
		} else {
			nombre.classList.remove("is-invalid");
		}
		if (correo.value === "") {
			correo.classList.add("is-invalid");
		} else {
			correo.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function Modificar() {
	codigo = document.getElementById('codigo');
	sede = document.getElementById('sede');
	auditoria = document.getElementById('auditoria');
	nombre = document.getElementById('nombre');
	correo = document.getElementById('correo');
	if (sede.value !== "" && auditoria.value !== "" && nombre.value !== "" && correo.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-modificar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar");
		http.append("codigo", codigo.value);
		http.append("sede", sede.value);
		http.append("auditoria", auditoria.value);
		http.append("nombre", nombre.value);
		http.append("correo", correo.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_correo.php");
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
		if (sede.value === "") {
            sede.parentNode.classList.add('has-error');
		} else {
            sede.parentNode.classList.remove('has-error');
		}
		if (auditoria.value === "") {
            auditoria.parentNode.classList.add('has-error');
		} else {
            auditoria.parentNode.classList.remove('has-error');
		}
		if (nombre.value === "") {
			nombre.classList.add("is-invalid");
		} else {
			nombre.classList.remove("is-invalid");
		}
		if (correo.value === "") {
			correo.classList.add("is-invalid");
		} else {
			correo.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function eliminarCorreo(codigo) {
	swal({
		text: "\u00BFDesea quitar a esta categor\u00EDa del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true },
		}
	}).then((value) => {
		switch (value) {
			case true:
				eliminar(codigo);
				break;
			default:
				return;
		}
	});
}


function eliminar(codigo) {
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "delete");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_correo.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado.sql );
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value) => { window.location.reload(); });
		}
	};
}