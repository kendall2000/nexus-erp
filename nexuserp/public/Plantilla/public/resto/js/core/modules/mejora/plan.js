
////////////////////// Plan ////////////////////////////
function aprobar(codigo) {
	swal({
		title: "Aprobar Plan",
		text: "\u00BFEsta seguro de aprobar este plan de acci\u00F3n?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				situacion(codigo, 3);
				break;
			default:
				return;
		}
	});
}

function solicitar(codigo) {
	swal({
		title: "Aprobar Plan",
		text: "\u00BFEsta seguro de solicitar la aprobaci\u00F3n este plan de acci\u00F3n?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				situacion(codigo, 2);
				break;
			default:
				return;
		}
	});
}

function situacion(codigo, situacion) {
	var http = new FormData();
	http.append("request", "situacion_plan");
	http.append("situacion", situacion);
	http.append("codigo", codigo);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_plan.php");
	request.send(http);
	request.onreadystatechange = function () {
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			swal("Excelente!", resultado.message, "success").then((value) => {
				window.location.href = "../menu_mejora.php";
			});
		}
	};
}

function solicitarCorreccion() {
	swal({
		title: "Solicitar Correcci\u00F3n",
		text: "\u00BFEsta seguro de solicitar la correcci\u00F3n de este plan de acci\u00F3n?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				correccion();
				break;
			default:
				return;
		}
	});
}

function correccion() {
	codigo = document.getElementById("codigo").value;
	var justificacion = document.createElement("textarea");
	justificacion.onkeyup = function () {
		textoLargo(this);
	};
	justificacion.classList.remove("is-invalid");
	swal({
		title: 'Ingrese una Justificaci\u00F3n',
		content: justificacion,
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				var http = new FormData();
				http.append("request", "corregir_plan");
				http.append("justificacion", justificacion.value);
				http.append("codigo", codigo);
				var request = new XMLHttpRequest();
				request.open("POST", "ajax_fns_plan.php");
				request.send(http);
				request.onreadystatechange = function () {
					if (request.readyState != 4) return;
					if (request.status === 200) {
						// console.Log(request.responseText);
						resultado = JSON.parse(request.responseText);
						if (resultado.status !== true) {
							//swal("Informaci\u00F3n", resultado.message, "info");
							return;
						}
						swal("Excelente!", resultado.message, "success").then((value) => {
							window.location.href = "FRMaprobacion.php";
						});
					}
				};
				break;
		}
	});
}