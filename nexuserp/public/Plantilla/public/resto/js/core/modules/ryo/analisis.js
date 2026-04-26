$(document).ready(function() {
   
    $(".select2").select2({ width: '100%' });
});
function Submit() {
	myform = document.forms.f1;
	myform.submit();
}

function updateCondicionOportunidad() {
	viabilidad = document.getElementById("viabilidad").value;
	rentabilidad = document.getElementById("rentabilidad").value;
	document.getElementById("condicion").value = get_condicion_oportunidad(viabilidad * rentabilidad);
}
function updateCondicion() {
	probabilidad = document.getElementById("probabilidad").value;
	impacto = document.getElementById("impacto").value;
	document.getElementById("condicion").value = get_condicion(probabilidad * impacto);
}
function get_condicion_oportunidad(condicion) {
    if(condicion <= 5) return "Trivial";
    if(condicion > 5 && condicion <= 10) return "Viable";
    if(condicion > 10 && condicion <= 15) return "Factible";
    if(condicion > 15) return "Prioritario";
}
function get_condicion(condicion)
{
	if (condicion < 5) return "Riesgo Bajo";
	if (condicion >= 5 && condicion < 10) return "Riesgo Medio";
	if (condicion >= 10 && condicion < 15) return "Riesgo Alto";
	if (condicion >= 15) return "Riesgo Critico";
}
function analizar(codigo) {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/risk/analizar.php", { codigo: codigo }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	$('#myModal').on('hidden.bs.modal', function () {
		// Cuando se cierra la ventana refrescamos la tabla
		printTableAnalisis();
	});
	abrirModal();
    setTimeout(function () {
		$(".select2").select2({ width: '100%' });
	}, 250);
}

function analizarOportunidad(codigo, proceso) {
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("../promts/risk/analizar_oportunidad.php", { codigo: codigo, proceso: proceso }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
    setTimeout(function () {
		$(".select2").select2({ width: '100%'});
	}, 250);
}

// --------------------------------------------------------------------------------
function printTableAnalisis(){
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
	// /////////// POST /////////
	var http = new FormData();
	http.append("request", "print_table_analisis");
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