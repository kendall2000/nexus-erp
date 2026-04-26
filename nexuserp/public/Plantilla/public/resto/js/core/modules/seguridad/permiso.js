//funciones javascript y validaciones
	$(document).ready(function() {
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
	
	function printTable(codigo,grupo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tablapermisos");
		http.append("codigo",codigo);
		http.append("grupo",grupo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_permiso.php");
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
						{extend: 'excel', title: 'Tabla de Permisos'},
						{extend: 'pdf', title: 'Tabla de Permisos'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Permisos'
						}
					]
				});
			}
		};     
	}
	
	
	function seleccionarPermiso(codigo,grupo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","getpermiso");
		http.append("codigo",codigo);
		http.append("grupo",grupo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_permiso.php");
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
				document.getElementById("cod").value = data.codigo;
				document.getElementById("grupo").value = data.grupo;
				document.getElementById("desc").value = data.nombre;
				document.getElementById("clv").value = data.clave;
				//tabla
				var tabla = resultado.tabla;
				//console.log( data );
				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
				$(".select2").select2();
				//botones
				document.getElementById("desc").focus(); 
				document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
				document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
				//--
			}
		};     
	}
				
	function Grabar(){
		grupo = document.getElementById('grupo');
		desc = document.getElementById('desc');
		clv = document.getElementById('clv');
		//--
		selectgrupo = document.getElementById("select2-grupo-container");
		
		if(grupo.value !== "" && desc.value !== "" && clv.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabarpermiso");
			http.append("grupo", grupo.value);
			http.append("nombre", desc.value);
			http.append("clave", clv.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_permiso.php");
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
			if(grupo.value ===""){
				selectgrupo.className = "select-danger select2-selection__rendered";
			}else{
				selectgrupo.className = " form-control";
			}
			if(desc.value ===""){
				desc.className = " form-danger";
			}else{
				desc.className = " form-control";
			}
			if(clv.value ===""){
				clv.className = " form-danger";
			}else{
				clv.className = " form-control";
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function Modificar(){
		cod = document.getElementById('cod');
		grupo = document.getElementById('grupo');
		desc = document.getElementById('desc');
		clv = document.getElementById('clv');
		//--
		selectgrupo = document.getElementById("select2-grupo-container");
		
		if(cod.value !=="" && grupo.value !== "" && desc.value !== "" && clv.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-modificar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","modificarpermiso");
			http.append("codigo", cod.value);
			http.append("grupo", grupo.value);
			http.append("nombre", desc.value);
			http.append("clave", clv.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_permiso.php");
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
					swal("Excelente!", resultado.message, "success").then((value) => {
						window.location.reload();
					});
				}
			};     
		}else{
			if(grupo.value ===""){
				selectgrupo.className = "select-danger select2-selection__rendered";
			}else{
				selectgrupo.className = " form-control";
			}
			if(desc.value ===""){
				desc.className = " form-danger";
			}else{
				desc.className = " form-control";
			}
			if(clv.value ===""){
				clv.className = " form-danger";
			}else{
				clv.className = " form-control";
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	