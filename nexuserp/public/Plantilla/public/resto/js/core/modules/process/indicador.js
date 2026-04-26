
function saveIndicador(codigo) {
	descripcion = document.getElementById("ind_desc");
	nombre = document.getElementById("indicador");
	unidad = document.getElementById("unidad");
	ideal = document.getElementById("ideal");
	max = document.getElementById("max");
	min = document.getElementById("min");
	if (codigo !== "") {
		var http = new FormData();
		http.append("request", "modificar");
		http.append("codigo", codigo);
		http.append("unidad", unidad.value);
		http.append("nombre", nombre.value);
		http.append("ideal", ideal.value);
		http.append("max", max.value);
		http.append("min", min.value);
		http.append("descripcion", descripcion.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_indicador.php");
		request.send(http);
		request.onreadystatechange = function () {
		//	console.log(request);
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
