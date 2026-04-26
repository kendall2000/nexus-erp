//funciones javascript y validaciones

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
	
	function printBiblioteca(categoria){
		categoria = document.getElementById('categoria');
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST //////////////
		var http = new FormData();
		http.append("request","biblioteca");
		http.append("categoria", categoria.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_biblioteca.php");
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
					pageLength: 100,
					responsive: true,
					dom: '<"html5buttons"B>lTfgitp',
					buttons: [
						{extend: 'copy'},
						{extend: 'csv'},
						{extend: 'excel', title: 'Tabla de Documentos'},
						{extend: 'pdf', title: 'Tabla de Documentos'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Documentos'
						}
					]
				});
			}
		};     
	}
	
	function printTable(codigo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla");
		http.append("codigo",codigo);
		http.append("tipo","gestor");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_biblioteca.php");
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
						{extend: 'excel', title: 'Tabla de Documentos'},
						{extend: 'pdf', title: 'Tabla de Documentos'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Documentos'
						}
					]
				});
			}
		};     
	}
	//////////
	
	
	function printTableVersion(){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla");
		http.append("codigo","");
		http.append("tipo","versiones");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_biblioteca.php");
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
						{extend: 'excel', title: 'Tabla de Documentos'},
						{extend: 'pdf', title: 'Tabla de Documentos'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Documentos'
						}
					]
				});
			}
		};     
	}
	
	
	function printTableAprobacion(){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla");
		http.append("codigo","");
		http.append("tipo","aprobaciones");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_biblioteca.php");
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
						{extend: 'excel', title: 'Tabla de Documentos'},
						{extend: 'pdf', title: 'Tabla de Documentos'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Documentos'
						}
					]
				});
			}
		};     
	}
	
	
	function seleccionarBiblioteca(codigo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","get");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_biblioteca.php");
		request.send(http);
		request.onreadystatechange = function(){
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
				document.getElementById("categoria").value = data.categoria;
				document.getElementById("codint").value = data.codint;
				document.getElementById("usuario").value = data.usuario;
				document.getElementById("fecvence").value = data.fecvence;
				document.getElementById("titulo").value = data.titulo;
				document.getElementById("descripcion").value = data.descripcion;
				//tabla
				var tabla = resultado.tabla;
				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
				$(".select2").select2();
				//botones
				document.getElementById("titulo").focus(); 
				document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
				document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
				//--
			}
		};     
	}
						
	function Grabar(){
		categoria = document.getElementById("categoria");
		codint = document.getElementById('codint');
		usuario = document.getElementById('usuario');
		fecvence = document.getElementById('fecvence');
		titulo = document.getElementById('titulo');
		descripcion = document.getElementById("descripcion");
		//---
		selectcategoria = document.getElementById("select2-categoria-container");
		
		if(categoria.value !== "" && codint.value !== "" && usuario.value !== "" && titulo.value !== "" && fecvence.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabar");
			http.append("categoria", categoria.value);
			http.append("codint", codint.value);
			http.append("usuario", usuario.value);
			http.append("fecvence", fecvence.value);
			http.append("titulo", titulo.value);
			http.append("descripcion", descripcion.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_biblioteca.php");
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
			if(categoria.value === ""){
				selectcategoria.className = "select-danger select2-selection__rendered";
			}else{
				selectcategoria.classList.remove("is-invalid");
			}
			if(codint.value === ""){
				codint.classList.add("is-invalid");
			}else{
				codint.classList.remove("is-invalid");
			}
			if(usuario.value === ""){
				usuario.classList.add("is-invalid");
			}else{
				usuario.classList.remove("is-invalid");
			}
			if(fecvence.value === ""){
				fecvence.classList.add("is-invalid");
			}else{
				fecvence.classList.remove("is-invalid");
			}
			if(titulo.value === ""){
				titulo.classList.add("is-invalid");
			}else{
				titulo.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	
	function Modificar(){
		codigo = document.getElementById("codigo");
		categoria = document.getElementById("categoria");
		codint = document.getElementById('codint');
		usuario = document.getElementById('usuario');
		fecvence = document.getElementById('fecvence');
		titulo = document.getElementById('titulo');
		descripcion = document.getElementById("descripcion");
		//---
		selectcategoria = document.getElementById("select2-categoria-container");
		
		if(codigo.value !== "" && categoria.value !== "" && codint.value !== "" && usuario.value !== "" && titulo.value !== "" && fecvence.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-modificar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","modificar");
			http.append("codigo", codigo.value);
			http.append("categoria", categoria.value);
			http.append("codint", codint.value);
			http.append("usuario", usuario.value);
			http.append("fecvence", fecvence.value);
			http.append("titulo", titulo.value);
			http.append("descripcion", descripcion.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_biblioteca.php");
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
			if(categoria.value === ""){
				selectcategoria.className = "select-danger select2-selection__rendered";
			}else{
				selectcategoria.classList.remove("is-invalid");
			}
			if(codint.value === ""){
				codint.classList.add("is-invalid");
			}else{
				codint.classList.remove("is-invalid");
			}
			if(usuario.value === ""){
				usuario.classList.add("is-invalid");
			}else{
				usuario.classList.remove("is-invalid");
			}
			if(fecvence.value === ""){
				fecvence.classList.add("is-invalid");
			}else{
				fecvence.classList.remove("is-invalid");
			}
			if(titulo.value === ""){
				titulo.classList.add("is-invalid");
			}else{
				titulo.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	
	function obosletoBiblioteca(codigo){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea marcar como obsoleto a este documento de la Biblioteca?",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true },
			}
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(codigo,10);
					break;
				default:
				  return;
			}
		});
	}
	
	function deshabilitarBiblioteca(codigo){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea quitar este documento de la Biblioteca?, no prodr\u00E1 ser usada despu\u00E9s...",
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
	
	function solicitarAprobacion(codigo){
		swal({
			title: "\u00BFDesea Solicitar Aprobaci\u00F3n?",
			text: "\u00BFDesea solicitar la revisi\u00F3n y aprobaci\u00F3n de este documento en la biblioteca?",
			icon: "info",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true },
			}
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(codigo,2);
					break;
				default:
				  return;
			}
		});
	}
	
	
	function aprobarBiblioteca(codigo){
		swal({
			title: "\u00BFDesea Aprobar?",
			text: "\u00BFDesea marcar esta revisi\u00F3n como v\u00E1lida y aprobar este documento en la biblioteca?",
			icon: "info",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true },
			}
		}).then((value) => {
			switch (value) {
				case true:
					cambioSituacion(codigo,3);
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
		request.open("POST", "ajax_fns_biblioteca.php");
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
				switch (situacion) {
					case 10:
						swal("Ok!", "Registro marcado como obsoleto...", "success").then((value)=>{ console.log(value); window.location.reload(); });
						break;
					case 3:
						swal("Excelente", "Documento de Bilblioteca Aprobado!!!", "success").then((value)=>{ console.log(value); window.location.reload(); });
						break;
					case 2:
						swal("Excelente", "Solcitud registrada satisfactoriamente...", "success").then((value)=>{ console.log(value); window.location.reload(); });
						break;
					case 0:
						swal("OK", "Registro eliminado....", "success").then((value)=>{ console.log(value); window.location.reload(); });
					default:
						return;
				}
				
			}
		};     
	}
	
	///////// VERSIONAMIENTO /////////////
	
	function newdocumento(documento){
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/biblioteca/newdocumento.php",{documento:documento}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
    }
	
	
	function verHistorial(documento){
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/biblioteca/historial.php",{documento:documento}, function(data){
			// Ponemos la respuesta de nuestro script en el DIV recargado
			$("#Pcontainer").html(data);
		});
		abrirModal();
    }
	
	
	function eliminarDocumento(codigo){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea eliminar el archivo (documento) de la Biblioteca?, no prodr\u00E1 ser recuperada despu\u00E9s...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
			}
		}).then((value) => {
			switch (value) {
				case true:
					borrarArchivo(codigo);
					break;
				default:
				  return;
			}
		});
	}
	
	function borrarArchivo(codigo){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","delete_documento");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_biblioteca.php");
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
				swal("Excelente!", "Registro eliminado satisfactoriamente!!!", "success").then((value)=>{ console.log( value ); window.location.reload(); });
			}
		};     
	}
	
	function DocumentoJs(codigo){
		codigo = document.getElementById("codigo");
		verzion = document.getElementById("version");
		fecvence = document.getElementById("fecvence");
		descripcion = document.getElementById("descripcion");
		if(codigo.value !== "" && verzion.value !== "" && fecvence.value !== "" && descripcion.value !== ""){
			inpfile = document.getElementById("documento");
			inpfile.click();
		}else{
			if(verzion.value === ""){
				verzion.classList.add("is-invalid");
			}else{
				verzion.classList.remove("is-invalid");
			}
			if(fecvence.value === ""){
				fecvence.classList.add("is-invalid");
			}else{
				fecvence.classList.remove("is-invalid");
			}
			if(descripcion.value === ""){
				descripcion.classList.add("is-invalid");
			}else{
				descripcion.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	async function Cargar(){
		codigo = document.getElementById("codigo");
		verzion = document.getElementById("version");
		fecvence = document.getElementById("fecvence");
		descripcion = document.getElementById("descripcion");
		archivo = document.getElementById("documento");
		if(archivo.value !== "" && verzion.value !== "" && fecvence.value !== "" && descripcion.value !== ""){
			exdoc = comprueba_extension(archivo.value,2);
			if(exdoc === 1){
				/////////// POST /////////
				var boton = document.getElementById("btn-documento");
				loadingBtn(boton);
				let resultVersion = await grabarVersion();
				if(resultVersion.status){
					let resultDoc = await subirDocumento();
					if(resultDoc.status){
						swal("Excelente!", resultDoc.message, "success").then((value) => {
							console.log( value );
							window.location.reload();
						});
					}else{
						swal("Error", resultDoc.message , "error").then((value) => {
							console.log( value );
							deloadingBtn(boton,'<i class="fas fa-file-upload"></i> Seleccionar Documento y Cargar');
						});	
						return;
					}
				}else{
					swal("Error", resultVersion.message , "error").then((value) => {
						console.log( value );
						deloadingBtn(boton,'<i class="fas fa-file-upload"></i> Seleccionar Documento y Cargar');
					});	
					return;
				}
			}else{
				swal("Alto!", "Este archivo no es extencion .pdf", "error");
			}		
		}else{
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	
	async function grabarVersion(){
		codigo = document.getElementById("codigo");
		verzion = document.getElementById("version"); //con z para omitir la palabra reservada
		fecvence = document.getElementById("fecvence");
		descripcion = document.getElementById("descripcion");
		return new Promise( (resolve, reject) => {
			/////////// POST /////////
			var http = new FormData();
			http.append("request", "version");
			http.append("codigo", codigo.value);
			http.append("version", verzion.value);
			http.append("fecvence", fecvence.value);
			http.append("descripcion", descripcion.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_biblioteca.php");
			request.onload = () => {
				if (request.status >= 200 && request.status < 300) {
				    resolve(JSON.parse(request.response));
				} else {
				    reject(request.statusText);
				}
			};
			request.onerror = () => reject(request.statusText);
			request.send(http);
		});
	}
	
	
	async function subirDocumento(){
		codigo = document.getElementById("codigo");
		archivo = document.getElementById("documento");
		return new Promise( (resolve, reject) => {
			/////////// POST /////////
			var http = new FormData();
			http.append("codigo", codigo.value);
			http.append("documento", archivo.files[0]);
			//console.log(archivo.files[0])
			var request = new XMLHttpRequest();
			request.open("POST", "EXEsetdocumento.php");
			request.onload = () => {
				if (request.status >= 200 && request.status < 300) {
					//console.log(request.response);
				    resolve(JSON.parse(request.response));
				} else {
				    reject(request.statusText);
				}
			};
			request.onerror = () => reject(request.statusText);
			request.send(http);
		});
	}
	
	
	
	function tablaDocumento(){
		categoria = document.getElementById("categoria");
		contenedor = document.getElementById("result");
		/////////// POST /////////
		if(categoria.value != ''){
		loadingCogs(contenedor);
		var http = new FormData();
		http.append("request","biblioteca");
		http.append("categoria",categoria.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_biblioteca.php");
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
							{extend: 'excel', title: 'Tabla de Documentos'},
							{extend: 'pdf', title: 'Tabla de Documentos'},
							{extend: 'print',
								customize: function (win){
									$(win.document.body).addClass('white-bg');
									$(win.document.body).css('font-size', '10px');
									$(win.document.body).find('table')
											.addClass('compact')
											.css('font-size', 'inherit');
								}, title: 'Tabla de Documentos'
							}
						]
					});
				}
			};  
		}   
	}