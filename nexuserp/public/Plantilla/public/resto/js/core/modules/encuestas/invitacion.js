//funciones javascript y validaciones

	$(document).ready(function(){
		printTable('');
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
	
	function Submit(){
		myform = document.forms.f1;
		myform.submit();
	}
	
	function comboSector(cliente){
		xajax_Combo_Sector(cliente,'correo','divcorreo','comboArea(this.value);');
	}
	
	function comboArea(correo){
		xajax_Combo_Area(correo,'correo','divcorreo','');
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
		request.open("POST", "ajax_fns_invitacion.php");
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
						{extend: 'excel', title: 'Tabla de Invitaciones'},
						{extend: 'pdf', title: 'Tabla de Invitaciones'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Invitaciones'
						}
					]
				});
			}
		};     
	}
	
	
	function seleccionarInvitacion(codigo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","get");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_invitacion.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				//console.log( request.responseText );
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
				document.getElementById("cliente").value = data.cliente;
				document.getElementById("correo").value = data.correo;
				document.getElementById("url").value = data.url;
				document.getElementById("obs").value = data.obs;
				//tabla
				var tabla = resultado.tabla;
				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
				//botones
				document.getElementById("cliente").focus(); 
				document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
				document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
				//--
			}
		};     
	}
						
	function Grabar(){
		encuesta = document.getElementById("encuesta");
		cliente = document.getElementById("cliente");
		correo = document.getElementById("correo");
		url = document.getElementById("url");
		obs = document.getElementById('obs');
		
		if(encuesta.value !== "" && cliente.value !== "" && correo.value !== "" && url.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabar");
			http.append("encuesta", encuesta.value);
			http.append("cliente", cliente.value);
			http.append("correo", correo.value);
			http.append("url", url.value);
			http.append("obs", obs.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_invitacion.php");
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
			if(cliente.value === ""){
				cliente.classList.add("is-invalid");
			}else{
				cliente.classList.remove("is-invalid");
			}
			if(correo.value === ""){
				correo.classList.add("is-invalid");
			}else{
				correo.classList.remove("is-invalid");
			}
			if(url.value === ""){
				url.classList.add("is-invalid");
			}else{
				url.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function Modificar(){
		codigo = document.getElementById('codigo');
		encuesta = document.getElementById("encuesta");
		cliente = document.getElementById("cliente");
		correo = document.getElementById("correo");
		url = document.getElementById("url");
		obs = document.getElementById('obs');
		
		if(encuesta.value !== "" && cliente.value !== "" && correo.value !== "" && url.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-modificar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","modificar");
			http.append("codigo", codigo.value);
			http.append("encuesta", encuesta.value);
			http.append("cliente", cliente.value);
			http.append("correo", correo.value);
			http.append("url", url.value);
			http.append("obs", obs.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_invitacion.php");
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
			if(cliente.value === ""){
				cliente.classList.add("is-invalid");
			}else{
				cliente.classList.remove("is-invalid");
			}
			if(correo.value === ""){
				correo.classList.add("is-invalid");
			}else{
				correo.classList.remove("is-invalid");
			}
			if(url.value === ""){
				url.classList.add("is-invalid");
			}else{
				url.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function deshabilitarInvitacion(codigo){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea quitar a este cuestionario del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true },
			}
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(codigo,0);
					break;
				default:
				  return;
			}
		});
	}
	
	function cambioSituacion(codigo,situacion){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","situacion");
		http.append("codigo",codigo);
		http.append("situacion",situacion);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_invitacion.php");
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
	
	
	
	function hashkeyInvitacion(url){
		swal("Invitaci\u00F3n", "Comparta v\u00EDa correo electr\u00F3nico u otro medio la siguiente direcci\u00F3n (enlace): \n\n"+url, "info");
	}
