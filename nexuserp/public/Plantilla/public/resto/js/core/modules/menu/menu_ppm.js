//funciones javascript y validaciones

	$(document).ready(function(){
		$(".select2").select2({width: '100%'});

		$('#range .input-daterange').datepicker({
			keyboardNavigation: false,
			forceParse: false,
			autoclose: true,
			format: "dd/mm/yyyy"
		});

		//conteoStatus();
		//categoriasPrioridades();
		//usuariosTrabajo();
		//activosOff();
		//tablaFallas();
		//tablaProgramacion();
		//tablaPresupuestos();
	});

	 function Submit(){
	 	conteoStatus();
	 	categoriasPrioridades();
	 	usuariosTrabajo();
	 	activosOff();
	 	tablaFallas();
	 	tablaProgramacion();
	 	tablaPresupuestos();
	}

	function ocultar(pos){
		switch(pos){
			case 1:
				document.getElementById("resultCategorias").innerHTML = '';
				document.getElementById("btn-down-categoria").style.display = 'block';
			break;
			case 2:
				document.getElementById("resultTrabajo").innerHTML = '';
				document.getElementById("btn-down-trabajo").style.display = 'block';
			break;
			case 3:
				document.getElementById("resultActivosOff").innerHTML = '';
				document.getElementById("btn-down-off").style.display = 'block';
			break;
			case 4:
				document.getElementById("resultFallas").innerHTML = '';
				document.getElementById("btn-down-fallas").style.display = 'block';
			break;
			case 5:
				document.getElementById("resultProgramacion").innerHTML = '';
				document.getElementById("btn-down-programacion").style.display = 'block';
			break;
			case 6:
				document.getElementById("resultPresupuesto").innerHTML = '';
				document.getElementById("btn-down-presupuesto").style.display = 'block';
			break;
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
				console.log( combo );
				contenedorsector.innerHTML = combo;
				$(".select2").select2({width: '100%'});
			}
		};
		comboActivo();
	}

	function comboArea(){
		/////////// POST /////////
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
				console.log( combo );
				contenedorarea.innerHTML = combo;
				$(".select2").select2({width: '100%'});
			}
		};
	}

	function comboActivo(){
		/////////// POST /////////
		var sede = document.getElementById("sede").value;
		var area = document.getElementById("area").value;
		contenedoractivo = document.getElementById("divactivo");
		loadingCogs(contenedoractivo);
		var http = new FormData();
		http.append("request","activo");
		http.append("sede",sede);
		http.append("area",area);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ppm.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					  //console.log( resultado );
					  contenedoractivo.innerHTML = '...';
					  swal("Error", resultado.message , "error");
					  return;
				}
				var combo = resultado.combo;
				console.log( combo );
				contenedoractivo.innerHTML = combo;
				$(".select2").select2({width: '100%'});
			}
		};
	}


	/////////////// -------- ESTADISTICAS -------------/////////////
	function conteoStatus(){
		activo = document.getElementById('activo');
		usuario = document.getElementById('usuario');
		categoria = document.getElementById('categoria');
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		desde = document.getElementById('desde');
		hasta = document.getElementById("hasta");
		/////////// POST /////////
		var http = new FormData();
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ppm.php");
		http.append("request","conteo_status");
		http.append("activo", activo.value);
		http.append("usuario", usuario.value);
		http.append("categoria", categoria.value);
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("desde", desde.value);
		http.append("hasta", hasta.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				let data = resultado;
				console.log( resultado );
				//console.log( data );
				////////////////// CONTADORES ///////////////////////
				document.getElementById("contadorFinal").innerHTML = parseInt(data.final1) + parseInt(data.final2);
				document.getElementById("contadorEspera").innerHTML = parseInt(data.espera1) + parseInt(data.espera2);
				document.getElementById("contadorProceso").innerHTML = parseInt(data.proceso1) + parseInt(data.proceso2);
				document.getElementById("contadorPendiente").innerHTML = parseInt(data.pendiente1) + parseInt(data.pendiente2);
				////////////////// PROGRESS BAR ///////////////////////
				document.getElementById("progressFinal").style.width = data.porcentFinalizado + "%";
				document.getElementById("progressEspera").style.width = data.porcentEspera + "%";
				document.getElementById("progressProceso").style.width = data.porcentProceso + "%";
				document.getElementById("progressPendiente").style.width = data.porcentPendiente + "%";
				//--
				document.getElementById("spanFinal").innerHTML = data.porcentFinalizado + "%";
				document.getElementById("spanEspera").innerHTML = data.porcentEspera + "%";
				document.getElementById("spanProceso").innerHTML = data.porcentProceso + "%";
				document.getElementById("spanPendiente").innerHTML = data.porcentPendiente + "%";
				//--
				document.getElementById("spanFinal").setAttribute("title", data.porcentFinalizado + "% Finalizados");
				document.getElementById("spanEspera").setAttribute("title", data.porcentEspera + "% en Espera");
				document.getElementById("spanProceso").setAttribute("title", data.porcentProceso + "% en Proceso");
				document.getElementById("spanPendiente").setAttribute("title", data.porcentPendiente + "% Pendientes de ejecutar");
			}
		};
	}



	function categoriasPrioridades(){
		activo = document.getElementById('activo');
		usuario = document.getElementById('usuario');
		categoria = document.getElementById('categoria');
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		desde = document.getElementById('desde');
		hasta = document.getElementById("hasta");
		//--
		resultCategorias = document.getElementById("resultCategorias");
		loadingCogs(resultCategorias);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","categorias_status");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ppm.php");
		http.append("activo", activo.value);
		http.append("usuario", usuario.value);
		http.append("categoria", categoria.value);
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("desde", desde.value);
		http.append("hasta", hasta.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					resultCategorias.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					return;
				}
				//data
				resultCategorias.innerHTML = resultado.tabla;
				document.getElementById("btn-down-categoria").style.display = 'none';
			}
		};
	}


	function usuariosTrabajo(){
		activo = document.getElementById('activo');
		usuario = document.getElementById('usuario');
		categoria = document.getElementById('categoria');
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		desde = document.getElementById('desde');
		hasta = document.getElementById("hasta");
		//--
		resultTrabajo = document.getElementById("resultTrabajo");
		loadingCogs(resultTrabajo);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","usuarios_trabajo");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ppm.php");
		http.append("activo", activo.value);
		http.append("usuario", usuario.value);
		http.append("categoria", categoria.value);
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("desde", desde.value);
		http.append("hasta", hasta.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					resultTrabajo.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				resultTrabajo.innerHTML = resultado.tabla;
				document.getElementById("btn-down-trabajo").style.display = 'none';
			}
		};
	}

	function activosOff(){
		activo = document.getElementById('activo');
		usuario = document.getElementById('usuario');
		categoria = document.getElementById('categoria');
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		desde = document.getElementById('desde');
		hasta = document.getElementById("hasta");
		//--
		resultActivosOff = document.getElementById("resultActivosOff");
		loadingCogs(resultActivosOff);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","activos_off");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ppm.php");
		http.append("activo", activo.value);
		http.append("usuario", usuario.value);
		http.append("categoria", categoria.value);
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("desde", desde.value);
		http.append("hasta", hasta.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					resultActivosOff.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					return;
				}
				//data
				resultActivosOff.innerHTML = resultado.tabla;
				document.getElementById("btn-down-off").style.display = 'none';
			}
		};
	}

	function tablaFallas(){
		activo = document.getElementById('activo');
		usuario = document.getElementById('usuario');
		categoria = document.getElementById('categoria');
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		desde = document.getElementById('desde');
		hasta = document.getElementById("hasta");
		//--
		resultFallas = document.getElementById("resultFallas");
		loadingCogs(resultFallas);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla_fallas");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ppm.php");
		http.append("activo", activo.value);
		http.append("usuario", usuario.value);
		http.append("categoria", categoria.value);
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("desde", desde.value);
		http.append("hasta", hasta.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					resultFallas.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					return;
				}
				//data
				resultFallas.innerHTML = resultado.tabla;
				document.getElementById("btn-down-fallas").style.display = 'none';

			}
		};
	}


	function tablaProgramacion(){
		activo = document.getElementById('activo');
		usuario = document.getElementById('usuario');
		categoria = document.getElementById('categoria');
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		desde = document.getElementById('desde');
		hasta = document.getElementById("hasta");
		//--
		resultProgramacion = document.getElementById("resultProgramacion");
		loadingCogs(resultProgramacion);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla_programacion");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ppm.php");
		http.append("activo", activo.value);
		http.append("usuario", usuario.value);
		http.append("categoria", categoria.value);
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("desde", desde.value);
		http.append("hasta", hasta.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					resultProgramacion.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				resultProgramacion.innerHTML = resultado.tabla;
				////////////////// TABLA ///////////////////////
				$('#dataTables-programacion').DataTable({
					pageLength: 10,
					responsive: true,
					dom: '<"html5buttons"B>lTfgitp',
					buttons: []
				});
				document.getElementById("btn-down-programacion").style.display = 'none';
			}
		};
	}


	function tablaPresupuestos(){
		activo = document.getElementById('activo');
		usuario = document.getElementById('usuario');
		categoria = document.getElementById('categoria');
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		area = document.getElementById("area");
		desde = document.getElementById('desde');
		hasta = document.getElementById("hasta");
		//--
		resultPresupuesto = document.getElementById("resultPresupuesto");
		loadingCogs(resultPresupuesto);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla_presupuestos");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_ppm.php");
		http.append("activo", activo.value);
		http.append("usuario", usuario.value);
		http.append("categoria", categoria.value);
		http.append("sede", sede.value);
		http.append("sector", sector.value);
		http.append("area", area.value);
		http.append("desde", desde.value);
		http.append("hasta", hasta.value);
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					resultPresupuesto.innerHTML = '...';
					console.log( "Error: ", resultado.message, ';', request.responseText );
					console.log( request.responseText );
					return;
				}
				//data
				resultPresupuesto.innerHTML = resultado.tabla;
				////////////////// TABLA ///////////////////////
				$('#dataTables-presupuesto').DataTable({
					pageLength: 10,
					responsive: true,
					dom: '<"html5buttons"B>lTfgitp',
					buttons: []
				});
				document.getElementById("btn-down-presupuesto").style.display = 'none';
			}
		};
	}
