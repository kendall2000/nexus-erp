//funciones javascript y validaciones
	$(document).ready(function(){
		$(".select2").select2({width: '100%'});

		$('.input-group.date').datepicker({
			format: 'dd/mm/yyyy',
			keyboardNavigation: false,
			forceParse: false,
			calendarWeeks: true,
			autoclose: true
		});

		$('.timepicker').datetimepicker({
			//          format: 'H:mm',    // use this format if you want the 24hours timepicker
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
		// progressBar();
		// graficasCumplimiento();
		// graficasEjecucion();
		// tablaHorarios();
	});

	/// Llama a un re-calculo
	function Submit(){};
	function applyFiltersStatics(){
		progressBar();
		graficasCumplimiento();
		graficasEjecucion();
		tablaHorarios();
		changeButton(0);
		changeButtonTables(0);
	};

	function limpiarTables(){
		pieContainer = document.getElementById("pieContainer");
		stocked2Container = document.getElementById("stocked2Container");
		stocked1Container = document.getElementById("stocked1Container");
		gaugeContainer = document.getElementById("gaugeContainer");
		pieContainer.innerHTML = '';
		stocked2Container.innerHTML = '';
		stocked1Container.innerHTML = '';
		gaugeContainer.innerHTML = '';
	}

	function changeButton(status){
		up = document.getElementById('up');
		down = document.getElementById('down');
		if(status == 0){
			up.classList.remove('hidden');
			down.classList.add('hidden');
		}else if(status == 1){
			down.classList.remove('hidden');
			up.classList.add('hidden');
			limpiarTables();
		}
	}

	function changeButtonTables(status){
		up = document.getElementById('up-programacion');
		down = document.getElementById('down-programacion');
		if(status == 0){
			up.classList.remove('hidden');
			down.classList.add('hidden');
		}else if(status == 1){
			document.getElementById('tablaContainer').innerHTML = '...';
			down.classList.remove('hidden');
			up.classList.add('hidden');
		}
	}



	/////////////// -------- COMBOS -------------/////////////

	function comboSector(){
		/////////// POST /////////
		var sede = document.getElementById("sede").value;
		contenedorsector = document.getElementById("divsector");
		loadingCogs(contenedorsector);
		var http = new FormData();
		http.append("request","sector");
		http.append("sede",sede);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					  //console.log( resultado );
					  contenedorsector.innerHTML = '...';
					  swal("Error", resultado.message , "error");
					  return;
				}
				var combo = resultado.combo;
				// console.log( combo );
				contenedorsector.innerHTML = combo;
				$(".select2").select2({width: '100%'});
			}
		};
	}

	function comboArea(){
		/////////// POST //////////////////
		var sector = document.getElementById("sector").value;
		contenedorarea = document.getElementById("divarea");
		loadingCogs(contenedorarea);
		var http = new FormData();
		http.append("request","area");
		http.append("sector",sector);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					  //console.log( resultado );
					  contenedorarea.innerHTML = '...';
					  swal("Error", resultado.message , "error");
					  return;
				}
				var combo = resultado.combo;
				//console.log( combo );
				contenedorarea.innerHTML = combo;
				$(".select2").select2({width: '100%'});
			}
		};
	}


	/////////////// -------- ESTADISTICAS -------------/////////////
	function graficasCumplimiento(){
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		categoria = document.getElementById('categoria');
		fecha = document.getElementById("fecha");
		hora = document.getElementById("hora");
		// console.log(sede.value, sector.value, categoria.value, fecha.value, hora.value);
		//--
		pieContainer = document.getElementById("pieContainer");
		stocked2Container = document.getElementById("stocked2Container");
		loadingCogs(pieContainer);
		loadingCogs(stocked2Container);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","categorias_status_checklist");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_checklist.php");
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("categoria", categoria.value);
		http.append("fecha", fecha.value);
		http.append("hora", hora.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					pieContainer.innerHTML = '...';
					stocked2Container.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				let dataResultado = resultado.data;
				//console.log( resultado.parametros );
				//console.log( dataResultado );
				pieContainer.innerHTML = '';
				stocked2Container.innerHTML = '';
				var stocked1 = document.createElement("div");
				stocked1.setAttribute("id", "pie");
				pieContainer.appendChild(stocked1);
				var stocked2 = document.createElement("div");
				stocked2.setAttribute("id", "stocked2");
				stocked2Container.appendChild(stocked2);
				//console.log( dataResultado.categorias );
				////////////////// GRAFICA 1 ///////////////////////
				console.log(dataResultado.vencido);
				c3.generate({
					bindto: '#stocked2',
					data:{
						columns: [
							dataResultado.ejecutado,
							dataResultado.pendiente,
							dataResultado.vencido
						],
						colors:{
							Ejecutado: '#1D9619',
							Pendiente: '#fbc658',
							Vencido: '#A80000'
						},
						type: 'bar',
						groups: [
							['Ejecutado', 'Pendiente', 'Vencido']
						]
					},
					axis: {
						rotated: true,
						x: {
							type: 'category',
							categories: dataResultado.categorias
						}
					}
				});

				////////////////// GRAFICA 2 ///////////////////////
				c3.generate({
					bindto: '#pie',
					data:{
						columns: [
							dataResultado.ejecutado,
							dataResultado.pendiente,
							dataResultado.vencido
						],
						colors:{
							Ejecutado: '#1D9619',
							Pendiente: '#fbc658',
							Vencido: '#A80000'
						},
						type : 'pie'
					}
				});
			}
		};
	}

	function progressBar(){
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		categoria = document.getElementById('categoria');
		fecha = document.getElementById("fecha");
		/////////// POST /////////
		var http = new FormData();
		http.append("request","cumplimiento_checklist");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_checklist.php");
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("categoria", categoria.value);
		http.append("fecha", fecha.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					console.log( "Error: ", resultado.message, ';', request.responseText );
					// console.log( request.responseText );
					return;
				}
				//data
				let data = resultado.data;
				//console.log( resultado.parametros );
				//console.log( data );
				////////////////// PROGRESS BAR ///////////////////////
				document.getElementById("progressEjecutado").style.width = data.porcentEjecutado + "%";
				document.getElementById("progressPendiente").style.width = data.porcentPendiente + "%";
				document.getElementById("progressVencido").style.width = data.porcentVencido + "%";
				//--
				document.getElementById("spanEjecutado").innerHTML = data.porcentEjecutado + "%";
				document.getElementById("spanPendiente").innerHTML = data.porcentPendiente + "%";
				document.getElementById("spanVencido").innerHTML = data.porcentVencido + "%";
				//--
				document.getElementById("spanEjecutado").setAttribute("title", data.porcentEjecutado + "% Ejecutado hoy al momento");
				document.getElementById("spanPendiente").setAttribute("title", data.porcentPendiente + "% Pendiente hoy al momento");
				document.getElementById("spanVencido").setAttribute("title", data.porcentVencido + "% Vencido hoy al momento");
			}
		};
	}

	function graficasEjecucion(){
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		categoria = document.getElementById('categoria');
		fecha = document.getElementById("fecha");
		hora = document.getElementById("hora");
		//--
		stocked1Container = document.getElementById("stocked1Container");
		gaugeContainer = document.getElementById("gaugeContainer");
		loadingCogs(stocked1Container);
		loadingCogs(gaugeContainer);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","ejecucion_checklist");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_checklist.php");
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("categoria", categoria.value);
		http.append("fecha", fecha.value);
		http.append("hora", hora.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					stocked1Container.innerHTML = '...';
					gaugeContainer.innerHTML = '...';
				///	console.log( "Error: ", resultado.message, ';', request.responseText );
				//	console.log( request.responseText );
					return;
				}
				//data
				let dataResultado = resultado.data;
				//console.log( resultado.parametros );
				//console.log( dataResultado );
				stocked1Container.innerHTML = '';
				gaugeContainer.innerHTML = '';
				var stocked = document.createElement("div");
				stocked.setAttribute("id", "stocked");
				stocked1Container.appendChild(stocked);
				var gauge = document.createElement("div");
				gauge.setAttribute("id", "gauge");
				gaugeContainer.appendChild(gauge);
				////////////////// GRAFICA 1 ///////////////////////
				c3.generate({
					bindto: '#stocked',
					data:{
						columns: [
							dataResultado.respsi,
							dataResultado.respno
						],
						colors:{
							SI: '#2582AA',
							NO: '#BCBCBC'
						},
						type: 'bar',
						onclick: function (d, i) { console.log(dataResultado.codigos); categoriaID(d, dataResultado.codigos); },
						groups: [
							['SI', 'NO']
						]
					},
					axis: {
						rotated: true,
						x: {
							type: 'category',
							categories: dataResultado.categorias
						}
					}
				});

				////////////////// GRAFICA 2 ///////////////////////
				c3.generate({
					bindto: '#gauge',
					data:{
						columns: [
							dataResultado.porcentSi
						],
						type: 'gauge'
					},
					color:{
						pattern: ['#2582AA', '#BCBCBC']

					}
				});

			}
		};
	}
	////////////////////////////

	function tablaHorarios(){
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		categoria = document.getElementById('categoria');
		fecha = document.getElementById("fecha");
		hora = document.getElementById("hora");
		//--
		tablaContainer = document.getElementById("tablaContainer");
		loadingCogs(tablaContainer);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla_horarios");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_checklist.php");
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("categoria", categoria.value);
		http.append("fecha", fecha.value);
		http.append("hora", hora.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request.responseText );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					tablaContainer.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					// console.log( request.responseText );
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
			}
		};
	}


	function categoriaID(data, arrcodigos){
		var position = parseInt(data.x);
		var codigo = arrcodigos[position];
		var tipo = data.id;
		//alert(codigo);
		var sede = document.getElementById("sede").value;
		var sector = document.getElementById("sector").value;
		var area = document.getElementById("area").value;
		var hora = document.getElementById("hora").value;
		var fecha = document.getElementById("fecha").value;
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("promts/checklist/respuestas.php",{categoria:codigo, sede:sede, sector:sector, area:area, hora:hora, fecha:fecha, tipo:tipo }, function(data){
			// Ponemos la respuesta de nuestro script en el DIV recargado
			$("#Pcontainer").html(data);
		});
		abrirModal();
	}
