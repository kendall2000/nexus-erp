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
		auditoria = document.getElementById("auditoria");
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla");
		http.append("codigo",codigo);
		http.append("auditoria",auditoria.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_seccion.php");
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
						{extend: 'excel', title: 'Tabla de Secciones'},
						{extend: 'pdf', title: 'Tabla de Secciones'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Secciones'
						}
					]
				});
			}
		};     
	}
	
	
	function seleccionarSeccion(codigo,auditoria){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","get");
		http.append("codigo",codigo);
		http.append("auditoria",auditoria);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_seccion.php");
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
				document.getElementById("auditoria").value = data.auditoria;
				document.getElementById("numero").value = data.numero;
				document.getElementById("titulo").value = data.titulo;
				document.getElementById("proposito").value = data.proposito;
				//tabla
				var tabla = resultado.tabla;
				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
				$(".select2").select2();
				//botones
				document.getElementById("numero").focus(); 
				document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
				document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
				//--
			}
		};     
	}
	
	function GrabarSeccion(){
		auditoria = document.getElementById("auditoria");
		numero = document.getElementById('numero');
		titulo = document.getElementById('titulo');
		proposito = document.getElementById('proposito');
		
		if(auditoria.value !== "" && numero.value !== "" && titulo.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabar");
			http.append("auditoria", auditoria.value);
			http.append("numero", numero.value);
			http.append("titulo", titulo.value);
			http.append("proposito", proposito.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_seccion.php");
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
			if(numero.value === ""){
				numero.classList.add("is-invalid");
			}else{
				numero.classList.remove("is-invalid");
			}
			if(titulo.value === ""){
				titulo.classList.add("is-invalid");
			}else{
				titulo.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function ModificarSeccion(){
		codigo = document.getElementById('codigo');
		auditoria = document.getElementById("auditoria");
		numero = document.getElementById('numero');
		titulo = document.getElementById('titulo');
		proposito = document.getElementById('proposito');
		
		if(auditoria.value !== "" && numero.value !== "" && titulo.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-modificar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","modificar");
			http.append("codigo", codigo.value);
			http.append("auditoria", auditoria.value);
			http.append("numero", numero.value);
			http.append("titulo", titulo.value);
			http.append("proposito", proposito.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_seccion.php");
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
			if(numero.value === ""){
				numero.classList.add("is-invalid");
			}else{
				numero.classList.remove("is-invalid");
			}
			if(titulo.value === ""){
				titulo.classList.add("is-invalid");
			}else{
				titulo.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	
	function deshabilitarSeccion(codigo,auditoria){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea quitar a esta seccion del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true },
			}
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(codigo,auditoria,0);
					break;
				default:
				  return;
			}
		});
	}
	
	function cambioSituacion(codigo,auditoria,situacion){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","situacion");
		http.append("codigo",codigo);
		http.append("auditoria",auditoria);
		http.append("situacion",situacion);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_seccion.php");
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
