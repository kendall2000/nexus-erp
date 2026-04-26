document.addEventListener('DOMContentLoaded', domActions, false);
var dataAnualProg;

function domActions () {
	$('#rangeAnualProg .input-daterange').datepicker({
		keyboardNavigation: false,
		forceParse: false,
		autoclose: true,
		format: "dd/mm/yyyy"
	});
	$('#range .input-daterange').datepicker({
		keyboardNavigation: false,
		forceParse: false,
		autoclose: true,
		format: "dd/mm/yyyy"
	});	
	printProg();
	calendarioMenu();
}

function january(){
	alert("january");
}

function printProg(){
	var inputFini = document.getElementById("fini"); 
	var inputFfin = document.getElementById("ffin");
	var finiValue = inputFini.value;
	var ffinValue = inputFfin.value;
	console.log("finiValue, ffinValue",finiValue,ffinValue);
	var http = new FormData();
	http.append("request","anualProg");
	http.append("fini",finiValue);
	http.append("ffin",ffinValue);
	var request = new XMLHttpRequest();
	request.open("POST","ajax_fns_audit.php");
	request.send(http);
	request.onreadystatechange = function(){
		if(request.readyState != 4) return;
		if(request.status === 200){
			var result = JSON.parse(request.responseText);
			if(result.status !== true){
				anualProg.innerHTML = '...';
				console.log(result.message);
				return;
			}
		}
		var table = result.tabla;
		eleAnualProg = document.getElementById("anualProg");
		eleAnualProg.innerHTML=table;
		dataAnualProg = result.data;
	};
}

// function prog(audits){
// 	var comentario = prompt("MENSAJE DE LA DIRECCI\u00d3N,  PARA L\u00cdDERES DEL SISTEMA", "Ej: Bien hecho / Favor programar / Poner atencion a ...")
// 	console.log(audits);
// }

// function result(month){
// 	var comentario = prompt("REVISION POR LA DIRECCION: Resultados de Auditoria para el mes de " + month +". Comentario de Direccion para Lideres de Sistema", "Ej: Bien hecho / Elaborar Ishikawas / Levantar Acciones ...")
// 	console.log(comentario);
// }


function calendarioMenu(){
	sede = document.getElementById("sede");
	departamento = document.getElementById("departamento");
	categoria = document.getElementById('categoria');
	desde = document.getElementById("desde");
	hasta = document.getElementById("hasta");
	//--
	contenedor = document.getElementById("calendarContainer");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request","calendario_auditoria");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns.php");
	http.append("sede", sede.value);
	http.append("departamento", departamento.value);
	http.append("categoria", categoria.value);
	http.append("fini", desde.value);
	http.append("ffin", hasta.value);
	request.send(http);
	request.onreadystatechange = function(){
		//console.log( request );
		if(request.readyState != 4) return;
		if(request.status === 200){
			resultado = JSON.parse(request.responseText);
			if(resultado.status !== true){
				contenedor.innerHTML = '...';
				console.log( "Error: ", resultado.message, ';', request.responseText );
				console.log( request.responseText );
				return;
			}
			//data
			let data = resultado.data;
			//console.log( resultado.parametros );
			//console.log( data );
			contenedor.innerHTML = '';
			var fullCalendar = document.createElement("div");
			fullCalendar.setAttribute("id", "fullCalendar");
			contenedor.appendChild(fullCalendar);
			////////////////// CALENDARIO ///////////////////////
			$calendar = $('#fullCalendar');
			today = new Date();
			y = today.getFullYear();
			m = today.getMonth();
			d = today.getDate();
			
			$calendar.fullCalendar({
				viewRender: function(view, element) {
					// We make sure that we activate the perfect scrollbar when the view isn't on Month
					if (view.name != 'month') {
						$(element).find('.fc-scroller').perfectScrollbar();
					}
				},
				header: {
					left: 'title',
					center: 'month,agendaWeek,agendaDay',
					right: 'prev,next,today'
				},
				defaultDate: today,
				selectable: false,
				selectHelper: false,
				editable: false,
				eventLimit: true, // allow "more" link when too many events
				// color classes: [ event-blue | event-azure | event-green | event-orange | event-red ]
				events: data
			});
		}
	}; 
}


function listFallas(activo,falla){
	cerrar();
	//Realiza una peticion de contenido a la contenido.php
	$.post("promts/activos/historial_fallas.php",{activo:activo, falla:falla}, function(data){
	// Ponemos la respuesta de nuestro script en el DIV recargado
	$("#Pcontainer").html(data);
	});
	abrirModal();
}

function comentarioAgendadas(codigo) {
	// swal({
	// 	title: "MENSAJE DE LA DIRECCI\u00d3N,  PARA L\u00cdDERES DEL SISTEMA",
	// 	text: "Auditorias agendadas para el mes de " + codigo,
	// 	icon: "info",
	// 	content: {
	// 		element: "input",
	// 		attributes: {
	// 			placeholder: "Ej: Bien hecho / Favor programar / Poner atencion a ... ",
	// 			type: "text",
	// 			id: "mensaje"
	// 		},
	// 	},
	// 	buttons: {
	// 		cancel: "Cancelar",
	// 		ok: { text: "Guardar", value: true },
	// 	}
	// }).then((value) => {
	// 	switch (value) {
	// 		case true:
	// 			guardarComentario(codigo, mensaje, 0);
	// 			break;
	// 		default:
	// 			return;
	// 	}
	// });
}

function comentarioResultados(month) {
	// swal({
	// 	title: "REVISION POR LA DIRECCION:",
	// 	text: "Resultados de Auditoria para el mes de " + month +". Comentario de Direccion para Lideres de Sistema",
	// 	icon: "success",
	// 	content: {
	// 		element: "input",
	// 		attributes: {
	// 			placeholder: "Ej: Bien hecho / Favor programar / Poner atencion a ... ",
	// 			type: "text",
	// 			id: "mensaje"
	// 		},
	// 	},
	// 	buttons: {
	// 		cancel: "Cancelar",
	// 		ok: { text: "Guardar", value: true },
	// 	}
	// }).then((value) => {
	// 	switch (value) {
	// 		case true:
	// 			guardarComentario(month, mensaje, 1);
	// 			break;
	// 		default:
	// 			return;
	// 	}
	// });
}

function guardarComentario(value, mensaje, tipo) {
	if (mensaje.value !== "") {
		console.log(mensaje.value)
		if (tipo == 0){
			console.log('Comentario de auditorias agendadas')
		} else if (tipo == 1){
			console.log('Comentario de resultados de auditorias')
		}
		/////////// POST /////////
		// let http = new FormData();
		// http.append("request", "request_api");
		// http.append("codigo", codigo);
		// http.append("observaciones", observaciones.value);
		// let request = new XMLHttpRequest();
		// request.open("POST", "ajax_fns_xyz.php");
		// request.send(http);
		// request.onreadystatechange = function () {
		// 	//console.log( request );
		// 	if (request.readyState != 4) return;
		// 	if (request.status === 200) {
		// 		//console.log(request.responseText);
		// 		resultado = JSON.parse(request.responseText);
		// 		if (resultado.status !== true) {
		// 			swal("Error", resultado.message, "error").then((value) => {
		// 				console.log(resultado.message);
		// 			});
		// 			return;
		// 		}
		// 		swal("Excelente!", resultado.message, "success").then((value) => {
		// 			window.location.reload();
		// 		});
		// 	}
		// };
	} else {
		swal("Ohoo!", "Debe ingresar un mensaje...", "warning");
	}
}

/*
//funciones javascript y validaciones
	$(document).ready(function(){
		$(".select2").select2();
			
		$('#anualRange .input-daterange').datepicker({
			keyboardNavigation: false,
			forceParse: false,
			autoclose: true,
			format: "dd/mm/yyyy"
		});

		$('#range .input-daterange').datepicker({
			keyboardNavigation: false,
			forceParse: false,
			autoclose: true,
			format: "dd/mm/yyyy"
		});
		
		calendarioMenu();
	});

	function calendarioMenu(){
		sede = document.getElementById("sede");
		departamento = document.getElementById("departamento");
		categoria = document.getElementById('categoria');
		desde = document.getElementById("desde");
		hasta = document.getElementById("hasta");
		//--
		contenedor = document.getElementById("calendarContainer");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","calendario_auditoria");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns.php");
		http.append("sede", sede.value);
		http.append("departamento", departamento.value);
		http.append("categoria", categoria.value);
		http.append("fini", desde.value);
		http.append("ffin", hasta.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					contenedor.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				let data = resultado.data;
				//console.log( resultado.parametros );
				//console.log( data );
				contenedor.innerHTML = '';
				var fullCalendar = document.createElement("div");
				fullCalendar.setAttribute("id", "fullCalendar");
				contenedor.appendChild(fullCalendar);
				////////////////// CALENDARIO ///////////////////////
				$calendar = $('#fullCalendar');
				today = new Date();
				y = today.getFullYear();
				m = today.getMonth();
				d = today.getDate();
				
				$calendar.fullCalendar({
					viewRender: function(view, element) {
						// We make sure that we activate the perfect scrollbar when the view isn't on Month
						if (view.name != 'month') {
							$(element).find('.fc-scroller').perfectScrollbar();
						}
					},
					header: {
						left: 'title',
						center: 'month,agendaWeek,agendaDay',
						right: 'prev,next,today'
					},
					defaultDate: today,
					selectable: false,
					selectHelper: false,
					editable: false,
					eventLimit: true, // allow "more" link when too many events
					// color classes: [ event-blue | event-azure | event-green | event-orange | event-red ]
					events: data
				});
			}
		}; 
	}
	

	function listFallas(activo,falla){
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("promts/activos/historial_fallas.php",{activo:activo, falla:falla}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
	}

	*/