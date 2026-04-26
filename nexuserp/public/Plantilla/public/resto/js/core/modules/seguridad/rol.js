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
	
	function printTable(){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tablaroles");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_permiso.php");
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
					  swal("Error", resultado.message , "error");
					  return;
				}
				var data = resultado.tabla;
				//console.log( data );
				contenedor.innerHTML = data;
				$('#tabla').DataTable({
					pageLength: 100,
					responsive: true,
					dom: '<"html5buttons"B>lTfgitp',
					buttons: [
						{extend: 'copy'},
						{extend: 'csv'},
						{extend: 'excel', title: 'Tabla de Roles'},
						{extend: 'pdf', title: 'Tabla de Roles'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Roles'
						}
					]
				});
			}
		};     
	}
						
		
	function Grabar(){
		nombre = document.getElementById('nombre');
		descripcion = document.getElementById('descripcion');
		cantidad = document.getElementById('cantidad').value;
		var C = 0;
		if(cantidad > 0){
			if(nombre.value !== "" && descripcion.value !== ""){
				var arrpermiso = Array([]);
				var arrgrupo = Array([]);
				for (var i = 1; i <= cantidad; i++){
					chk = document.getElementById('chk'+i);
					if(chk.checked){
						permiso = document.getElementById('codigo'+i).value;
						grupo = document.getElementById('grupo'+i).value;
						arrpermiso[C] = permiso;
						arrgrupo[C] = grupo;
						C++;
					}
				}
				if(C > 0){
					/////////// POST /////////
					var boton = document.getElementById("btn-grabar");
					loadingBtn(boton);
					var http = new FormData();
					http.append("request","grabarol");
					http.append("nombre", nombre.value);
					http.append("descripcion", descripcion.value);
					http.append("permisos", arrpermiso);
					http.append("grupos", arrgrupo);
					http.append("cantidad", C);
					var request = new XMLHttpRequest();
					request.open("POST", "ajax_fns_permiso.php");
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
								window.location.href="FRMrol.php";
							});
						}
					};     
				}else{
					swal("Pero antes!", "Seleccione los permisos a asignar en este rol...", "info");
				}
			}else{
				if(nombre.value ===""){
					nombre.className = " form-danger";
				}else{
					nombre.className = " form-control";
				}
				if(descripcion.value ===""){
					descripcion.className = " form-danger";
				}else{
					descripcion.className = " form-control";
				}
				swal("Ups!", "Debe llenar los Campos Obligatorios", "warning");
			}
		}else{
			swal("Ups!", "No hay permisos por asignar...", "warning");
		}	
	}
	
	function Modificar(){
		codigo = document.getElementById('codigo');
		nombre = document.getElementById('nombre');
		descripcion = document.getElementById('descripcion');
		cantidad = document.getElementById('cantidad').value;
		var C = 0;
		if(cantidad > 0){
			if(codigo.value !== "" && nombre.value !== "" && descripcion.value !== ""){
				var arrpermiso = Array([]);
				var arrgrupo = Array([]);
				for (var i = 1; i <= cantidad; i++){
					chk = document.getElementById('chk'+i);
					if(chk.checked){
						permiso = document.getElementById('codigo'+i).value;
						grupo = document.getElementById('grupo'+i).value;
						arrpermiso[C] = permiso;
						arrgrupo[C] = grupo;
						C++;
					}
				}
				if(C > 0){
					/////////// POST /////////
					var boton = document.getElementById("btn-modificar");
					loadingBtn(boton);
					var http = new FormData();
					http.append("request","modificarol");
					http.append("codigo", codigo.value);
					http.append("nombre", nombre.value);
					http.append("descripcion", descripcion.value);
					http.append("permisos", arrpermiso);
					http.append("grupos", arrgrupo);
					http.append("cantidad", C);
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
							//console.log( resultado );
							swal("Excelente!", resultado.message, "success").then((value) => {
								window.location.href="FRMrol.php";
							});
						}
					};     
				}else{
					swal("Pero antes!", "Seleccione los permisos a asignar en este rol...", "info");
				}
			}else{
				if(nombre.value ===""){
					nombre.className = " form-danger";
				}else{
					nombre.className = " form-control";
				}
				if(descripcion.value ===""){
					descripcion.className = " form-danger";
				}else{
					descripcion.className = " form-control";
				}
				swal("Ups!", "Debe llenar los Campos Obligatorios", "warning");
				
			}
		}else{
			swal("Ups!", "No hay permisos por asignar...", "warning");
		}	
	}
	
	
	function eliminarRol(codigo){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea realmente Eliminar este Rol de Permisos?...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: {
				  text: "Aceptar",
				  value: true,
				},
			},
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(codigo,0)
					break;
				default:
					return;
			}
		});
	}
	
	function cambioSituacion(codigo,situacion){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","situacionrol");
		http.append("codigo",codigo);
		http.append("situacion",situacion);
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
					swal("Error", resultado.message , "error");
					return;
				}
				swal("Excelente!", "Cambio de situaci\u00F3n satisfactorio!!!", "success").then((value)=>{ window.location.reload(); });
			}
		};     
	}
	
////---- Checkbox de asignacion de permisos en el rol

	function checkTodoGrupo(grupo){
		chkg = document.getElementById("chkg"+grupo);
		glist = document.getElementById("gruplist"+grupo);
		var cadena = glist.value;
		var separador = cadena.split("-");
		var cuantos = separador[1];
		var inicia = (parseInt(separador[1])-parseInt(separador[0]))+1;
		//alert(inicia+"-"+cuantos);
		if(chkg.checked) {
			for(var i = inicia; i <= cuantos; i++){
				document.getElementById("chk"+i).checked = true;
			}
		}else{
			for(var i = inicia; i <= cuantos; i++){
				document.getElementById("chk"+i).checked = false;
			}
		}
	}
				