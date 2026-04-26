//funciones javascript y validaciones

	$(document).ready(function(){
		$(".select2").select2({width: '100%'});

		$('#range .input-daterange').datepicker({
			keyboardNavigation: false,
			forceParse: false,
			autoclose: true,
			format: "dd/mm/yyyy"
		});

		conteoTickets();
		//estadisticasStatus();
		//estadisticasPrioridad();
		//estadisticasSemanal();
		//tablaTickets();
		//categoriaCategorias();
	});

	function ocultar(pos){
		switch(pos){
			case 1:
				document.getElementById("categoriasContainer").innerHTML = '';
				document.getElementById("btn-down-categorias").style.display = 'block';
			break;
			case 2:
			 	document.getElementById("tablaContainer").innerHTML = '';
			 	document.getElementById("btn-down-tickets").style.display = 'block';
			break;
			// case 3:
			// 	document.getElementById("resultActivosOff").innerHTML = '';
			// 	document.getElementById("btn-down-off").style.display = 'block';
			// break;
			// case 4:
			// 	document.getElementById("resultFallas").innerHTML = '';
			// 	document.getElementById("btn-down-fallas").style.display = 'block';
			// break;
			// case 5:
			// 	document.getElementById("resultProgramacion").innerHTML = '';
			// 	document.getElementById("btn-down-programacion").style.display = 'block';
			// break;
		}

	}

	function Submit(){
		conteoTickets();
		estadisticasStatus();
		estadisticasPrioridad();
		estadisticasSemanal();
		tablaTickets();
		categoriaCategorias();
	}

	function conteoTickets(){
		sede = document.getElementById("sede");
		fini = document.getElementById('desde');
		ffin = document.getElementById("hasta");
		//--
		conteoContenedor = document.getElementById("conteoContainer");
		loadingCogs(conteoContenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","conteo_tickets");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_helpdesk.php");
		http.append("sede", sede.value);
		http.append("fini", fini.value);
		http.append("ffin", ffin.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					conteoContenedor.innerHTML = "...";
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				let data = resultado.data;
				//console.log( resultado.parametros );
				//console.log( data );
				//////////////////  ///////////////////////
				conteoContenedor.innerHTML = data;
			}
		};
	}

	function estadisticasStatus(){
		sede = document.getElementById("sede");
		fini = document.getElementById('desde');
		ffin = document.getElementById("hasta");
		//--
		pieContenedor = document.getElementById("pieContainer");
		loadingCogs(pieContenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","estadistica_status");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_helpdesk.php");
		http.append("sede", sede.value);
		http.append("fini", fini.value);
		http.append("ffin", ffin.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					pieContenedor.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				let dataResultado = resultado.data;
				let colorResultado = resultado.colores;
				//console.log( resultado.parametros );
				//console.log( dataResultado );
				//console.log( colorResultado );
				pieContenedor.innerHTML = '';
				var pie = document.createElement("div");
				pie.setAttribute("id", "pie");
				pieContenedor.appendChild(pie);
				//console.log( dataResultado.categorias );
				////////////////// GRAFICA ///////////////////////
				document.getElementById("btn-down-status").style.display = 'none';
				c3.generate({
					bindto: '#pie',
					data:{
						columns: dataResultado,
						colors: colorResultado,
						type : 'pie'
					}
				});
			}
		};
	}

	function estadisticasPrioridad(){
		sede = document.getElementById("sede");
		fini = document.getElementById('desde');
		ffin = document.getElementById("hasta");
		//--
		stockedContenedor = document.getElementById("stockedContainer");
		loadingCogs(stockedContenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","estadistica_prioridad");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_helpdesk.php");
		http.append("sede", sede.value);
		http.append("fini", fini.value);
		http.append("ffin", ffin.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					stockedContenedor.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				let dataResultado = resultado.data;
				let colorResultado = resultado.colores;
				//console.log( resultado.parametros );
				//console.log( dataResultado );
				//console.log( colorResultado );
				stockedContenedor.innerHTML = '';
				var stocked = document.createElement("div");
				stocked.setAttribute("id", "stocked");
				stockedContenedor.appendChild(stocked);
				//console.log( dataResultado.categorias );
				////////////////// GRAFICA ///////////////////////
				document.getElementById("btn-down-criticidad").style.display = 'none';
				c3.generate({
					bindto: '#stocked',
					data:{
						columns: dataResultado,
						colors: colorResultado,
						type: 'bar'
					},
					axis: {
						x: {
							type: 'category',
							categories: ['Criticidad']
						}
					}
            });
			}
		};
	}

	function estadisticasSemanal(){
		sede = document.getElementById("sede");
		fini = document.getElementById('desde');
		ffin = document.getElementById("hasta");
		//--
		gaugeContenedor = document.getElementById("gaugeContainer");
		loadingCogs(gaugeContenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","estadistica_semanal");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_helpdesk.php");
		http.append("sede", sede.value);
		http.append("fini", fini.value);
		http.append("ffin", ffin.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					gaugeContenedor.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				let porcentaje = resultado.porcentaje;
				//console.log( resultado.parametros );
				//console.log( porcentaje );
				gaugeContenedor.innerHTML = '';
				var stocked = document.createElement("div");
				stocked.setAttribute("id", "gauge");
				gaugeContenedor.appendChild(stocked);
				//console.log( dataResultado.categorias );
				////////////////// GRAFICA ///////////////////////
				document.getElementById("btn-down-semanal").style.display = 'none';
				c3.generate({
                bindto: '#gauge',
                data:{
                    columns: [
                        porcentaje
                    ],
                    type: 'gauge'
                },
                color:{
                    pattern: ['#90C98F', '#BABABA']

                }
            });
			}
		};
	}


	function tablaTickets(){
		sede = document.getElementById("sede");
		fini = document.getElementById('desde');
		ffin = document.getElementById("hasta");
		//--
		tablaContainer = document.getElementById("tablaContainer");
		loadingCogs(tablaContainer);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla_tickets");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_helpdesk.php");
		http.append("sede", sede.value);
		http.append("fini", fini.value);
		http.append("ffin", ffin.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					tablaContainer.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				let dataResultado = resultado.tabla;
				tablaContainer.innerHTML = resultado.tabla;
				//console.log( resultado.parametros );
				//console.log( dataResultado );
				////////////////// TABLA ///////////////////////
				$('.dataTables-example').DataTable({
					pageLength: 100,
					responsive: true,
					dom: '<"html5buttons"B>lTfgitp',
					buttons: []
				});
				document.getElementById("btn-down-tickets").style.display = 'none';
			}
		};
	}

	function categoriaCategorias(){
		sede = document.getElementById("sede");
		fini = document.getElementById('desde');
		ffin = document.getElementById("hasta");
		//--
		categoriasContenedor = document.getElementById("categoriasContainer");
		loadingCogs(categoriasContenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","categorias_prioridades");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_helpdesk.php");
		http.append("sede", sede.value);
		http.append("fini", fini.value);
		http.append("ffin", ffin.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					categoriasContenedor.innerHTML = "...";
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				let data = resultado.data;
				//console.log( resultado.parametros );
				//console.log( data );
				//////////////////  ///////////////////////
				categoriasContenedor.innerHTML = data;
				document.getElementById("btn-down-categorias").style.display = 'none';
			}
		};
	}

	function verConteo(codigo,fini,ffin,sede){
		//Realiza una peticion de contenido a la contenido.php
		$.post("promts/ticket/conteos_categoria.php",{codigo:codigo,fini:fini,ffin:ffin,sede:sede}, function(data){
			// Ponemos la respuesta de nuestro script en el DIV recargado
			$("#Pcontainer").html(data);
		});
		abrirModal();
	}

	function verConteoStatus(codigo,fini,ffin,sede){
		//Realiza una peticion de contenido a la contenido.php
		$.post("promts/ticket/conteos_status.php",{codigo:codigo,fini:fini,ffin:ffin,sede:sede}, function(data){
			// Ponemos la respuesta de nuestro script en el DIV recargado
			$("#Pcontainer").html(data);
		});
		abrirModal();
	}

	function verConteoPrioridad(codigo,fini,ffin,sede){
		//Realiza una peticion de contenido a la contenido.php
		$.post("promts/ticket/conteos_prioridad.php",{codigo:codigo,fini:fini,ffin:ffin,sede:sede}, function(data){
			// Ponemos la respuesta de nuestro script en el DIV recargado
			$("#Pcontainer").html(data);
		});
	}

	function verInformacion(codigo){
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("promts/ticket/informacion_menu.php",{codigo:codigo}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
   }

	function verInformacion2(codigo){
		//Realiza una peticion de contenido a la contenido.php
		$.post("promts/ticket/informacion_menu.php",{codigo:codigo}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
   }
