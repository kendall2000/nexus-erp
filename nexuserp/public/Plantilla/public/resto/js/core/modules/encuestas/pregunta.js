//funciones javascript y validaciones

	$(document).ready(function(){
		printTable('','');
		$(".select2").select2();
	});
	
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
	
	function Submit(){
		myform = document.forms.f1;
		myform.submit();
	}
	
	function printTable(codigo){
		encuesta = document.getElementById("encuesta");
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla");
		http.append("codigo",codigo);
		http.append("encuesta",encuesta.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_pregunta.php");
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
						{extend: 'excel', title: 'Tabla de Preguntas'},
						{extend: 'pdf', title: 'Tabla de Preguntas'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Preguntas'
						}
					]
				});
			}
		};     
	}
	
	
	function seleccionarPregunta(codigo,encuesta){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","get");
		http.append("codigo",codigo);
		http.append("encuesta",encuesta);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_pregunta.php");
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
				document.getElementById("encuesta").value = data.encuesta;
				document.getElementById("seccion").value = data.seccion;
				document.getElementById("pregunta").value = data.pregunta;
				document.getElementById("peso").value = data.peso;
				document.getElementById("tipo").value = data.tipo;
				//tabla
				var tabla = resultado.tabla;
				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
				$(".select2").select2();
				//botones
				document.getElementById("pregunta").focus(); 
				document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
				document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
				//--
			}
		};     
	}
	
	function GrabarPregunta(){
		encuesta = document.getElementById("encuesta");
		seccion = document.getElementById("seccion");
		pregunta = document.getElementById('pregunta');
		tipo = document.getElementById("tipo");
		peso = document.getElementById("peso");
		///
		selectseccion = document.getElementById("select2-seccion-container");
		if(encuesta.value !== "" && seccion.value !== "" && pregunta.value !== "" && tipo.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabar");
			http.append("encuesta", encuesta.value);
			http.append("seccion", seccion.value);
			http.append("pregunta", pregunta.value);
			http.append("tipo", tipo.value);
			http.append("peso", peso.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_pregunta.php");
			request.send(http);
			request.onreadystatechange = function(){
			   //console.log( request );
			   if(request.readyState != 4) return;
			   if(request.status === 200){
				resultado = JSON.parse(request.responseText);
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
			if(pregunta.value === ""){
				pregunta.classList.add("is-invalid");
			}else{
				pregunta.classList.remove("is-invalid");
			}
			if(seccion.value === ""){
				selectseccion.className = "select-danger select2-selection__rendered";
			}else{
				selectseccion.className = "select2-selection__rendered";
			}
			if(tipo.value === ""){
				tipo.classList.add("is-invalid");
			}else{
				tipo.classList.remove("is-invalid");
			}
			if(peso.value === ""){
				peso.classList.add("is-invalid");
			}else{
				peso.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function ModificarPregunta(){
		codigo = document.getElementById('codigo');
		encuesta = document.getElementById("encuesta");
		seccion = document.getElementById("seccion");
		pregunta = document.getElementById('pregunta');
		tipo = document.getElementById("tipo");
		peso = document.getElementById("peso");
		///
		selectseccion = document.getElementById("select2-seccion-container");
		
		if(encuesta.value !== "" && seccion.value !== "" && pregunta.value !== "" && tipo.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-modificar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","modificar");
			http.append("codigo", codigo.value);
			http.append("encuesta", encuesta.value);
			http.append("seccion", seccion.value);
			http.append("pregunta", pregunta.value);
			http.append("tipo", tipo.value);
			http.append("peso", peso.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_pregunta.php");
			request.send(http);
			request.onreadystatechange = function(){
			   //console.log( request );
			   if(request.readyState != 4) return;
			   if(request.status === 200){
				resultado = JSON.parse(request.responseText);
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
			if(pregunta.value === ""){
				pregunta.classList.add("is-invalid");
			}else{
				pregunta.classList.remove("is-invalid");
			}
			if(seccion.value === ""){
				selectseccion.className = "select-danger select2-selection__rendered";
			}else{
				selectseccion.className = "select2-selection__rendered";
			}
			if(tipo.value === ""){
				tipo.classList.add("is-invalid");
			}else{
				tipo.classList.remove("is-invalid");
			}
			if(peso.value === ""){
				peso.classList.add("is-invalid");
			}else{
				peso.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	
	function deshabilitarPregunta(codigo,encuesta){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea quitar a esta pregunta del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true },
			}
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(codigo,encuesta,0);
					break;
				default:
				  return;
			}
		});
	}
	
	function cambioSituacion(codigo,encuesta,situacion){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","situacion");
		http.append("codigo",codigo);
		http.append("encuesta",encuesta);
		http.append("situacion",situacion);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_pregunta.php");
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
