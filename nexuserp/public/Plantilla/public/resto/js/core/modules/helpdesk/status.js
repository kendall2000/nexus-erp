//funciones javascript y validaciones

$(document).ready(function () {
	printTable('');
	///------------
	$("input[name='posicion']").TouchSpin({
		min: 0,
		max: 100,
		step: 1,
		buttondown_class: 'btn-spiner',
		buttonup_class: 'btn-spiner'
	});
	$(".select2").select2();
	$('#color').colorpicker();
	var divStyle = $('.back-change')[0].style;
	$('#color').colorpicker({
		color: divStyle.backgroundColor
	}).on('changeColor', function (ev) {
		divStyle.backgroundColor = ev.color.toHex();
	});
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

function printTable(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tabla");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_status_hd.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			//console.log( request.responseText );
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//console.log( resultado );
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
					{ extend: 'excel', title: 'Tabla de Statuss' },
					{ extend: 'pdf', title: 'Tabla de Statuss' },
					{
						extend: 'print',
						customize: function (win) {
							$(win.document.body).addClass('white-bg');
							$(win.document.body).css('font-size', '10px');
							$(win.document.body).find('table')
								.addClass('compact')
								.css('font-size', 'inherit');
						}, title: 'Tabla de Statuss'
					}
				]
			});
		}
	};
}


function seleccionarStatus(codigo) {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "get");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_status_hd.php");
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
			document.getElementById("posicion").value = data.posicion;
			document.getElementById("nombre").value = data.nombre;
			document.getElementById("color").value = data.color;
			document.getElementById("btn-color").style.backgroundColor = data.color;
			//tabla
			var tabla = resultado.tabla;
			contenedor.innerHTML = tabla;
			$('#tabla').DataTable({
				pageLength: 50,
				responsive: true
			});
			//botones
			document.getElementById("nombre").focus();
			document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
			document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
			//--
		}
	};
}


function Grabar() {
	posicion = document.getElementById('posicion');
	nombre = document.getElementById('nombre');
	color = document.getElementById('color');

	if (posicion.value !== "" && nombre.value !== "" && color.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar");
		http.append("posicion", posicion.value);
		http.append("nombre", nombre.value);
		http.append("color", color.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_status_hd.php");
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
		if (posicion.value === "") {
			posicion.classList.add("is-invalid");
		} else {
			posicion.classList.remove("is-invalid");
		}
		if (nombre.value === "") {
			nombre.classList.add("is-invalid");
		} else {
			nombre.classList.remove("is-invalid");
		}
		if (color.value === "") {
			color.classList.add("is-invalid");
		} else {
			color.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function Modificar() {
	codigo = document.getElementById('codigo');
	posicion = document.getElementById('posicion');
	nombre = document.getElementById('nombre');
	color = document.getElementById('color');

	if (posicion.value !== "" && nombre.value !== "" && color.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-modificar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "modificar");
		http.append("codigo", codigo.value);
		http.append("posicion", posicion.value);
		http.append("nombre", nombre.value);
		http.append("color", color.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_status_hd.php");
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
		if (posicion.value === "") {
			posicion.classList.add("is-invalid");
		} else {
			posicion.classList.remove("is-invalid");
		}
		if (nombre.value === "") {
			nombre.classList.add("is-invalid");
		} else {
			nombre.classList.remove("is-invalid");
		}
		if (color.value === "") {
			color.classList.add("is-invalid");
		} else {
			color.classList.remove("is-invalid");
		}
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
}

function deshabilitarStatus(codigo) {
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
				cambioSituacion(codigo, 0);
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
	request.open("POST", "ajax_fns_status_hd.php");
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