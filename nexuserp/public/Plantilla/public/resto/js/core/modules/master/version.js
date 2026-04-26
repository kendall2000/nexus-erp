//funciones javascript y validaciones
	$(document).ready(function() {
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
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_version.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					  //console.log( resultado );
					  contenedor.innerHTML = '...';
					  swal("Error", resultado.message , "error");
					  return;
				}
				var data = resultado.tabla;
				//console.log( data );
				contenedor.innerHTML = data;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true,
					dom: '<"html5buttons"B>lTfgitp',
					buttons: [
						{extend: 'copy'},
						{extend: 'csv'},
						{extend: 'excel', title: 'Tabla de Versiones de Software'},
						{extend: 'pdf', title: 'Tabla de Versiones de Software'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Versiones de Software'
						}
					]
				});
			}
		};     
	}
	
	
	function seleccionarVersion(codigo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","get");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_version.php");
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
				document.getElementById("software").value = data.software;
				document.getElementById("plataforma").value = data.plataforma;
				document.getElementById("version").value = data.version;
				//tabla
				var tabla = resultado.tabla;
				//console.log( data );
				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
				//botones
				document.getElementById("software").focus(); 
				document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
				document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
				//--
			}
		};     
	}
						
	function Grabar(){
		software = document.getElementById('software');
		plataforma = document.getElementById('plataforma');
		version = document.getElementById('version');
		//--
		selectplataforma = document.getElementById("select2-plataforma-container");
		
		if(software.value !== "" && plataforma.value !== "" && version.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabar");
			http.append("software", software.value);
			http.append("plataforma", plataforma.value);
			http.append("version", version.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_version.php");
			request.send(http);
			request.onreadystatechange = function(){
			   //console.log( request );
			   if(request.readyState != 4) return;
			   if(request.status === 200){
				resultado = JSON.parse(request.responseText);
					if(resultado.status !== true){
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
			if(software.value === ""){
				software.classList.add("is-invalid");
			}else{
				software.classList.remove("is-invalid");
			}
			if(plataforma.value === ""){
				selectplataforma.className = "select-danger select2-selection__rendered";
			}else{
				selectplataforma.className = "select2-selection__rendered";
			}
			if(version.value === ""){
				version.classList.add("is-invalid");
			}else{
				version.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function Modificar(){
		codigo = document.getElementById('codigo');
		software = document.getElementById('software');
		plataforma = document.getElementById('plataforma');
		version = document.getElementById('version');
		
		if(software.value !== "" && plataforma.value !== "" && version.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-modificar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","modificar");
			http.append("codigo", codigo.value);
			http.append("software", software.value);
			http.append("plataforma", plataforma.value);
			http.append("version", version.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_version.php");
			request.send(http);
			request.onreadystatechange = function(){
			   //console.log( request );
			   if(request.readyState != 4) return;
			   if(request.status === 200){
				resultado = JSON.parse(request.responseText);
					if(resultado.status !== true){
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
			if(software.value === ""){
				software.classList.add("is-invalid");
			}else{
				software.classList.remove("is-invalid");
			}
			if(plataforma.value === ""){
				plataforma.classList.add("is-invalid");
			}else{
				plataforma.classList.remove("is-invalid");
			}
			if(version.value === ""){
				version.classList.add("is-invalid");
			}else{
				version.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function eliminarVersion(codigo){
		swal({
			title: "\u00BFEliminar?",
			text: "\u00BFEst\u00E1 seguro de eliminar a este grupo de permisos?",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
			}
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(codigo);
					break;
				default:
				  return;
			}
		});
	}
   
   function cambioSituacion(codigo){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","eliminar");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_version.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
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