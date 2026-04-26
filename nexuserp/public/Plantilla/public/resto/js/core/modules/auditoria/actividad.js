//funciones javascript y validaciones

	$(document).ready(function(){
		printTable('');
		$('.timepicker').datetimepicker({
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
		$('#simple .input-group.date').datepicker({
			format: 'dd/mm/yyyy',
			keyboardNavigation: false,
			forceParse: false,
			calendarWeeks: true,
			autoclose: true
		});
		$(".select2").select2();
	});
	
	function Submit(){
		myform = document.forms.f1;
		myform.submit();
	}
	
	
	function Limpiar(){
		swal({
			text: "\u00BFDesea Limpiar la p\u00E1gina?, si a\u00FAn no a grabado perdera los datos escritos...",
			icon: "info",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
			}
		}).then((value) => {
			switch (value) {
				case true:
					window.location.reload();
					break;
				default:
				  return;
			}
		});
	}
	
	function printTable(codigo){
		programacion = document.getElementById("programacion");
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla");
		http.append("codigo",codigo);
		http.append("programacion",programacion.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_actividad.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//console.log( resultado );
					contenedor.innerHTML = '...';
					console.log( resultado.message );
					return;
				}
				//tabla
				var data = resultado.tabla;
				contenedor.innerHTML = data;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true,
					dom: '<"html5buttons"B>lTfgitp',
					buttons: [
						{extend: 'copy'},
						{extend: 'csv'},
						{extend: 'excel', title: 'Tabla de Actividades'},
						{extend: 'pdf', title: 'Tabla de Actividades'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Actividades'
						}
					]
				});
			}
		};     
	}
	
	
	function seleccionarActividad(codigo,programacion){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","get");
		http.append("codigo",codigo);
		http.append("programacion",programacion);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_actividad.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					swal("Error", resultado.message , "error");
					return;
				}
				var data = resultado.data;
				//console.log( data );
				//set
				document.getElementById("codigo").value = data.codigo;
				document.getElementById("programacion").value = data.programacion;
				document.getElementById("fecha").value = data.fecha;
				document.getElementById("hora").value = data.hora;
				document.getElementById("descripcion").value = data.descripcion;
				document.getElementById("obs").value = data.obs;
				//tabla
				var tabla = resultado.tabla;
				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
				$(".select2").select2();
				//botones
				document.getElementById("descripcion").focus(); 
				document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
				document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
				//--
			}
		};     
	}
	
	function GrabarActividad(){
		programacion = document.getElementById("programacion");
		fecha = document.getElementById("fecha");
		hora = document.getElementById('hora');
		descripcion = document.getElementById("descripcion");
		obs = document.getElementById("obs");
		///
		if(programacion.value !== "" && fecha.value !== "" && hora.value !== "" && descripcion.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabar");
			http.append("programacion", programacion.value);
			http.append("fecha", fecha.value);
			http.append("hora", hora.value);
			http.append("descripcion", descripcion.value);
			http.append("obs", obs.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_actividad.php");
			request.send(http);
			request.onreadystatechange = function(){
			   //console.log( request );
			   if(request.readyState != 4) return;
			   if(request.status === 200){
				resultado = JSON.parse(request.responseText);
					//console.log( resultado.sql );
					if(resultado.status !== true){
						console.log( resultado.sql );
						swal("Error", resultado.message , "error").then((value) => { deloadingBtn(boton,'<i class="fa fa-save"></i> Grabar'); });
						return;
					}
					//console.log( resultado );
					swal("Excelente!", resultado.message, "success").then((value) => {
						window.location.reload();
					});
				}
			};     
		}else{
			if(fecha.value === ""){
				fecha.classList.add("is-invalid");
			}else{
				fecha.classList.remove("is-invalid");
			}
			if(hora.value === ""){
				hora.classList.add("is-invalid");
			}else{
				hora.classList.remove("is-invalid");
			}
			if(descripcion.value === ""){
				descripcion.classList.add("is-invalid");
			}else{
				descripcion.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function ModificarActividad(){
		codigo = document.getElementById('codigo');
		programacion = document.getElementById("programacion");
		fecha = document.getElementById("fecha");
		hora = document.getElementById('hora');
		descripcion = document.getElementById("descripcion");
		obs = document.getElementById("obs");
		///
		selectfecha = document.getElementById("select2-fecha-container");
		
		if(programacion.value !== "" && fecha.value !== "" && hora.value !== "" && descripcion.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-modificar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","modificar");
			http.append("codigo", codigo.value);
			http.append("programacion", programacion.value);
			http.append("fecha", fecha.value);
			http.append("hora", hora.value);
			http.append("descripcion", descripcion.value);
			http.append("obs", obs.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_actividad.php");
			request.send(http);
			request.onreadystatechange = function(){
			   //console.log( request );
			   if(request.readyState != 4) return;
			   if(request.status === 200){
				resultado = JSON.parse(request.responseText);
					if(resultado.status !== true){
						//console.log( resultado.sql );
						swal("Error", resultado.message , "error").then((value) => { deloadingBtn(boton,'<i class="fa fa-save"></i> Grabar'); });
						return;
					}
					//console.log( resultado );
					swal("Excelente!", resultado.message, "success").then((value) => {
						window.location.reload();
					});
				}
			};     
		}else{
			if(fecha.value === ""){
				fecha.classList.add("is-invalid");
			}else{
				fecha.classList.remove("is-invalid");
			}
			if(hora.value === ""){
				hora.classList.add("is-invalid");
			}else{
				hora.classList.remove("is-invalid");
			}
			if(descripcion.value === ""){
				descripcion.classList.add("is-invalid");
			}else{
				descripcion.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	
	function deshabilitarActividad(codigo,programacion){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea quitar a esta actividad del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true },
			}
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(codigo,programacion,0);
					break;
				default:
				  return;
			}
		});
	}
	
	function cambioSituacion(codigo,programacion,situacion){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","situacion");
		http.append("codigo",codigo);
		http.append("programacion",programacion);
		http.append("situacion",situacion);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_actividad.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					//console.log( resultado.sql );
					swal("Error", resultado.message , "error");
					return;
				}
				swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value)=>{ window.location.reload(); });
			}
		};     
	}
