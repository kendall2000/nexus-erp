
function saveControl(codigo) {
	descripcion = document.getElementById("con_desc");
	if (codigo !== "") {
		var http = new FormData();
		http.append("request", "modificar");
		http.append("codigo", codigo);
		http.append("descripcion", descripcion.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_control.php");
		request.send(http);
		request.onreadystatechange = function () {
			//console.log(request.readyState);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				// console.log(request.responseText);
				resultado = JSON.parse(request.responseText);
				//console.log(resultado);
				if (resultado.status !== true) {
					//swal("Informaci\u00F3n", resultado.message, "info");
					return;
				}
			}
		};
	}
}
