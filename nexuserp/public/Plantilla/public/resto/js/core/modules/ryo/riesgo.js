function identificar(codigo, proceso) {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/risk/identificar.php", { codigo: codigo, proceso: proceso }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	$('#myModal').on('hidden.bs.modal', function () {
		// Cuando se cierra la ventana refrescamos la tabla
		printTableIdent();
	});
	abrirModal();
    setTimeout(function () {
		$(".select2").select2({ width: '100%' });
	}, 250);
}


function update(elemento, campo) {
	codigo = document.getElementById("codigo");
	var http = new FormData();
	http.append("request", "update");
	http.append("codigo", codigo.value);
	http.append("campo", campo);
	http.append("valor", elemento.value);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_ryo.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request.readyState);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			//console.log(resultado);
			if (resultado.status !== true) {
				console.log(resultado.message);
				return;
			}
		}
	};
}


///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////

function printTableIdent() {
	contenedor = document.getElementById("result");
	proceso = document.getElementById("proceso");
	sistema = document.getElementById("sistema");
	usuario = document.getElementById("usuario");
	tipo = document.getElementById("tipo");
	// console.log(proceso.value);
	// console.log(sistema.value);
	// console.log(usuario.value);
	// console.log(tipo.value);
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "print_table_identificacion");
	http.append("proceso", proceso.value);
	http.append("sistema", sistema.value);
	http.append("usuario", usuario.value);
	http.append("tipo", tipo.value);

	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_ryo.php");
	request.send(http);
	request.onreadystatechange = function () {
		// console.log(request);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log( request.responseText );
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// console.log(resultado);
				contenedor.innerHTML = '...';
				//swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			//tabla
			var data = resultado.tabla;
			contenedor.innerHTML = data;
			$(document).ready(function () {
				$('.dataTables-example').DataTable({
					destroy: true,
					pageLength: 100,
					responsive: true,
					dom: '<"html5buttons"B>lTfgitp',
					buttons: []
				});
			});
		}
	};
}