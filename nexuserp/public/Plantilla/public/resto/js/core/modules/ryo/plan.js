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
				situacion(codigo,3);
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
				situacion(codigo,2);
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
	request.open("POST", "ajax_fns_ryo.php");
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
				window.location.href = "FRMaprobacion.php";
			});
		}
	};
}

function solicitarCorreccion(tipo) {
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
				correccion(tipo);
				break;
			default:
				return;
		}
	});
}

function correccion(tipo) {
	codigo = document.getElementById("codigo").value;
	var justificacion = document.createElement("textarea");
	justificacion.onkeyup = function () {
		textoLargo(this);
	};
	justificacion.onblur = function () {
		var http = new FormData();
		if (tipo == 1) {
			http.append("request", "update");
			http.append("campo", 8);
		}
		else {
			http.append("request", "update_oportunidad");
			http.append("campo", 4);
		}
		http.append("codigo", codigo);
		http.append("valor", this.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ryo.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request.readyState);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				//console.log(resultado);
				if (resultado.status !== true) {
					console.log(resultado.message);
					return;
				}
				// console.log(resultado.message);
			}
		};
	}
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
				if (tipo == 1) http.append("request", "situacion");
				else http.append("request", "situacion_oportunidad");
				http.append("situacion", 1);
				http.append("codigo", codigo);
				var request = new XMLHttpRequest();
				request.open("POST", "ajax_fns_ryo.php");
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