$(document).ready(function() {
   
    $(".select2").select2();
});

function Submit() {
	myform = document.forms.f1;
	myform.submit();
}

function solicitarAprobacion(codigo,tipo) {
	swal({
		title: "Solicitar Aprobacion",
		text: "\u00BFEsta seguro de solicitar la aprobacion de este plan de acci\u00F3n?",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
		}
	}).then((value) => {
		switch (value) {
			case true:
				solicitar(codigo,tipo);
				break;
			default:
				return;
		}
	});
}

function solicitar(codigo,tipo) {
	var http = new FormData();
	if(tipo==1)http.append("request", "situacion");
	else http.append("request", "situacion_oportunidad");
	http.append("situacion", 2);
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
				window.location.reload();
			});
		}
	};
}