
function printTableCausas(pertenece) {
	contenedor = document.getElementById("causas");
	plan = document.getElementById("plan");
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tabla");
	http.append("plan", plan.value);
	http.append("pertenece", pertenece);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_analisis.php");
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

function SeleccionarCausas(codigo) {
	contenedor = document.getElementById("causas");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "get");
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_analisis.php");
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
			document.getElementById("pertenece").value = data.pertenece;
			causa = document.getElementById("causa");
			causa.value = data.causa;
			AutoGrowTextArea(causa);
			//tabla
			var tabla = resultado.tabla;
			contenedor.innerHTML = tabla;
			//botones
			document.getElementById("btn-agregar").className = "btn btn-primary btn-sm hidden";
			document.getElementById("btn-update").className = "btn btn-primary btn-sm";
		}
	};
}

function GrabarCausas() {
	contenedor = document.getElementById("causas");
	info = contenedor.innerHTML;
	loadingCogs(contenedor);
	//--
	plan = document.getElementById('plan');
	causa = document.getElementById('causa');
	pertenece = document.getElementById('pertenece');

	if (plan.value !== "" && causa.value !== "") {
		/////////// POST /////////
		var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar");
		http.append("plan", plan.value);
		http.append("causa", causa.value);
		http.append("pertenece", pertenece.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_analisis.php");
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
					causa.value = "";

					//tabla
					var tabla = resultado.data;
					contenedor.innerHTML = tabla;
				});
			}
		};
	} else {
		contenedor.innerHTML = info;
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
	if (causa.value === "") {
		causa.classList.add("is-invalid");
	} else {
		causa.classList.remove("is-invalid");
	}
}

function ModificarCausas() {
	contenedor = document.getElementById("causas");
	info = contenedor.innerHTML;
	loadingCogs(contenedor);
	//--
	codigo = document.getElementById('codigo');
	causa = document.getElementById('causa');
	plan = document.getElementById('plan');

	if (codigo.value !== "" && causa.value !== "") {
		/////////// POST /////////
		var http = new FormData();
		http.append("request", "modificar");
		http.append("codigo", codigo.value);
		http.append("causa", causa.value);
		http.append("plan", plan.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_analisis.php");
		request.send(http);
		request.onreadystatechange = function () {
			// console.log(request.responseText);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					swal("Error", resultado.message, "error").then((value) => { });
					return;
				}
				//console.log( resultado );
				swal("Excelente!", resultado.message, "success").then((value) => {
					causa.value = "";

					//tabla
					var tabla = resultado.data;
					contenedor.innerHTML = tabla;
					document.getElementById("btn-agregar").className = "btn btn-primary btn-sm";
					document.getElementById("btn-update").className = "btn btn-primary btn-sm hidden";
				});
			}
		};
	} else {
		contenedor.innerHTML = info;
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}
	if (causa.value === "") {
		causa.classList.add("is-invalid");
	} else {
		causa.classList.remove("is-invalid");
	}
}

function DeshabilitarCausas(codigo) {
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
				cambioSituacionCausas(codigo, 0);
				break;
			default:
				return;
		}
	});
}

function cambioSituacionCausas(codigo, situacion) {
	contenedor = document.getElementById("causas");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "situacion");
	http.append("codigo", codigo);
	http.append("situacion", situacion);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_analisis.php");
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
				//tabla
				// console.log(resultado.data);
				var tabla = resultado.data;
				contenedor.innerHTML = tabla;
			});
		}
	};
}

/////////////////////////// Extras ///////////////////////
function subcausa(codigo, nombre) {
	document.getElementById("padre").value = nombre;
	document.getElementById("rowPertence").classList.remove("hidden");
	document.getElementById("pertenece").value = codigo;
	printTableCausas(codigo);
}

function atrasAnalisis() {
	document.getElementById("rowPertence").classList.add("hidden");
	document.getElementById("pertenece").value = "";
	printTableCausas("");
	document.getElementById("btn-agregar").className = "btn btn-primary btn-sm";
	document.getElementById("btn-update").className = "btn btn-primary btn-sm hidden";
	document.getElementById("causa").value = "";
}