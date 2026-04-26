
//funciones javascript y validaciones
$(document).ready(function () {
	$(".select2").select2();

	$('.input-group.date').datepicker({
		format: 'dd/mm/yyyy',
		keyboardNavigation: false,
		forceParse: false,
		calendarWeeks: true,
		autoclose: true
	});

	$('.timepicker').datetimepicker({
		//          format: 'H:mm',    ///// use this format if you want the 24hours timepicker
		format: 'H:mm', //use this format if you want the 12hours timpiecker with AM/PM toggle
		icons: {
			time: "fa fa-clock-o",
			date: "fa fa-calendar",
			up: "fa fa-chevron-up",
			down: "fa fa-chevron-down",
			previous: 'fa fa-chevron-left',
			next: 'fa fa-chevron-right',
			today: 'fa fa-screenshot',
			clear: 'fa fa-trash',
			close: 'fa fa-remove'
		}
	});
	graficasCumplimiento();
	graficasGenerales();
});

function Submit() {
	graficasCumplimiento();
	graficasGenerales();
};

function graficasCumplimiento() {
	cumplimientoProceso();
	cumplimientoSistema();
	cumplimientoTipo();
	cumplimientoGeneral();
}

function graficasGenerales() {
	objetivosSistema();
	accionesSistema();
	objetivosStatus();
	accionesStatus();
}
//////////////////////////////// Graficas de Cumplimiento //////////////////////////////////
function cumplimientoGeneral() {
	//--
	pieContainer = document.getElementById("pieContainer");
	loadingCogs(pieContainer);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "cumplimiento_general");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				pieContainer.innerHTML = '...';
				pieContainer.innerHTML = '...';
				// console.log("Error: ", resultado.message, ';', request.responseText);
				// console.log(request.responseText);
				return;
			}
			//data
			pieContainer.innerHTML = '';
			var stockedPie = document.createElement("div");
			stockedPie.setAttribute("id", "pie");
			pieContainer.appendChild(stockedPie);

			////////////////// GRAFICA PIE ///////////////////////
			c3.generate({
				bindto: '#pie',
				data: {
					columns: [
						['Cumplido', resultado.cumplimiento],
						['Pendiente', (100 - resultado.cumplimiento)],
					],
					colors: {
						Cumplido: '#1D9619',
						Pendiente: '#fbc658'
					},
					type: 'pie'
				}
			});
		}
	};
}
function cumplimientoProceso() {
	//--
	// pieContainer = document.getElementById("pieContainer");
	stocked0Container = document.getElementById("stocked0Container");
	// loadingCogs(pieContainer);
	loadingCogs(stocked0Container);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "cumplimiento_proceso");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// pieContainer.innerHTML = '...';
				stocked0Container.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			stocked0Container.innerHTML = resultado.data;
		}
	};
}
function cumplimientoSistema() {
	//--
	stocked2Container = document.getElementById("stocked2Container");
	loadingCogs(stocked2Container);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "cumplimiento_sistema");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// pieContainer.innerHTML = '...';
				stocked2Container.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			stocked2Container.innerHTML = resultado.data;
		}
	};
}
function cumplimientoTipo() {
	//--
	stocked1Container = document.getElementById("stocked1Container");
	loadingCogs(stocked1Container);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "cumplimiento_tipo");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// pieContainer.innerHTML = '...';
				stocked1Container.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			stocked1Container.innerHTML = resultado.data;
		}
	};
}

function tablaObjetivos() {
	proceso = document.getElementById("proceso");
	sistema = document.getElementById("sistema");
	//--
	tablaContainer = document.getElementById("tablaContainer");
	loadingCogs(tablaContainer);
	/////////// POST ////////////
	var http = new FormData();
	http.append("request", "tabla_usuario");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	http.append("proceso", proceso.value);
	http.append("sistema", sistema.value);
	request.send(http);
	request.onreadystatechange = function () {
		// console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			//console.log( request.responseText );
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				tablaContainer.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			//data
			tablaContainer.innerHTML = resultado.data;
			//console.log( resultado.parametros );
			//console.log( dataResultado );
			////////////////// TABLA ///////////////////////
			$('.dataTables-example').DataTable({
				pageLength: 25,
				responsive: true,
				dom: '<"html5buttons"B>lTfgitp',
				buttons: []
			});
		}
	};
}
//////////////////////////////// Graficas Generales //////////////////////////////////

function objetivosSistema() {
	//--
	generalContainer1 = document.getElementById("generalContainer1");
	loadingCogs(generalContainer1);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "objetivos_sistema");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// pieContainer.innerHTML = '...';
				generalContainer1.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			generalContainer1.innerHTML = resultado.data;
		}
	};
}

function accionesSistema() {
	//--
	generalContainer3 = document.getElementById("generalContainer3");
	loadingCogs(generalContainer3);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "acciones_sistema");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// pieContainer.innerHTML = '...';
				generalContainer3.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			generalContainer3.innerHTML = resultado.data;
		}
	};
}

function objetivosStatus(){
	//--
	generalContainer0 = document.getElementById("generalContainer0");
	loadingCogs(generalContainer0);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "objetivos_status");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// pieContainer.innerHTML = '...';
				generalContainer0.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			generalContainer0.innerHTML = resultado.data;
		}
	};
}

function accionesStatus(){
	//--
	generalContainer2 = document.getElementById("generalContainer2");
	loadingCogs(generalContainer2);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "acciones_status");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// pieContainer.innerHTML = '...';
				generalContainer2.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			generalContainer2.innerHTML = resultado.data;
		}
	};
}

function verEvaluaciones(codigo) {
	cerrar();
	//Realiza una petiSSScion de contenido a la contenido.php
	$.post("../promts/planning/evaluacion.php", { codigo: codigo }, function (data) {
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
	});
	abrirModal();
}
