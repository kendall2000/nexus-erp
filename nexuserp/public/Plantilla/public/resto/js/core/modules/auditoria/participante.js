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
	
	function printTable(usuario){
		programacion = document.getElementById("programacion");
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla");
		http.append("usuario",usuario);
		http.append("programacion",programacion.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_participante.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				console.log( request.responseText );
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
						{extend: 'excel', title: 'Tabla de Participantes'},
						{extend: 'pdf', title: 'Tabla de Participantes'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Participantes'
						}
					]
				});
			}
		};     
	}
	
	
	function seleccionarParticipante(programacion,usuario){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","get");
		http.append("programacion",programacion);
		http.append("usuario",usuario);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_participante.php");
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
				document.getElementById("usuario").value = data.usuario;
				document.getElementById("programacion").value = data.programacion;
				document.getElementById("tratamiento").value = data.tratamiento;
				document.getElementById("rol").value = data.rol;
				document.getElementById("asignacion").value = data.asignacion;
				//tabla
				var tabla = resultado.tabla;
				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
				$(".select2").select2();
				//botones
				document.getElementById("tratamiento").focus(); 
				//--
			}
		};     
	}
	
	function Grabar(){
		programacion = document.getElementById("programacion");
		usuario = document.getElementById('usuario');
		tratamiento = document.getElementById("tratamiento");
		rol = document.getElementById("rol");
		asignacion = document.getElementById("asignacion");
		///
		selectusuario = document.getElementById("select2-usuario-container");
		if(programacion.value !== "" && usuario.value !== "" && rol.value !== "" && tratamiento.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabar");
			http.append("programacion", programacion.value);
			http.append("usuario", usuario.value);
			http.append("tratamiento", tratamiento.value);
			http.append("rol", rol.value);
			http.append("asignacion", asignacion.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_participante.php");
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
			if(usuario.value === ""){
				selectusuario.className = "select-danger select2-selection__rendered";
			}else{
				selectusuario.className = "select2-selection__rendered";
			}
			if(rol.value === ""){
				rol.classList.add("is-invalid");
			}else{
				rol.classList.remove("is-invalid");
			}
			if(tratamiento.value === ""){
				tratamiento.classList.add("is-invalid");
			}else{
				tratamiento.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	
	function deshabilitarParticipante(programacion,usuario){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea quitar a este participante del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true },
			}
		}).then((value) => {
			switch (value) {
				case true:
					deleteParticipante(programacion,usuario);
					break;
				default:
				  return;
			}
		});
	}
	
	function deleteParticipante(programacion,usuario){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","delete");
		http.append("usuario",usuario);
		http.append("programacion",programacion);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_participante.php");
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
