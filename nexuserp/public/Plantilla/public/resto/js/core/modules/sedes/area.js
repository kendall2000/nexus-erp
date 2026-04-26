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
	
	function comboSector(){
		contenedor = document.getElementById("divsector");
		loadingCogs(contenedor);
		/////////// POST /////////
		var sede = document.getElementById("sede").value;
		var http = new FormData();
		http.append("request","sector");
		http.append("sede",sede);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_area.php");
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
				var combo = resultado.combo;
				console.log( combo );
				contenedor.innerHTML = combo;
				$(".select2").select2();
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
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_area.php");
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
						{extend: 'excel', title: 'Tabla de Areas'},
						{extend: 'pdf', title: 'Tabla de Areas'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Areas'
						}
					]
				});
			}
		};     
	}
	
	
	function seleccionarArea(codigo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","get");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_area.php");
		request.send(http);
		request.onreadystatechange = function(){
			//console.log( request );
			if(request.readyState != 4) return;
			if(request.status === 200){
				console.log( request.responseText );
				resultado = JSON.parse(request.responseText);
				if(resultado.status !== true){
					swal("Error", resultado.message , "error");
					return;
				}
				var data = resultado.data;
				//console.log( data );
				//set
				document.getElementById("codigo").value = data.codigo;
				document.getElementById("nombre").value = data.nombre;
				document.getElementById("nivel").value = data.nivel;
				document.getElementById("sede").value = data.sede;
				//combo
				var combo = resultado.combo;
				document.getElementById("divsector").innerHTML = combo;
				document.getElementById("sector").value = data.sector;
				//tabla
				var tabla = resultado.tabla;
				contenedor.innerHTML = tabla;
				$('#tabla').DataTable({
					pageLength: 50,
					responsive: true
				});
				$(".select2").select2();
				//botones
				document.getElementById("nombre").focus(); 
				document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
				document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
				//--
			}
		};     
	}
						
	function Grabar(){
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		nombre = document.getElementById('nombre');
		nivel = document.getElementById("nivel");
		//--
		selectsede = document.getElementById("select2-sede-container");
		selectsector = document.getElementById("select2-sector-container");
		
		if(nombre.value !== "" && sede.value !== "" && sector.value !== "" && nivel.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabar");
			http.append("sede", sede.value);
			http.append("sector", sector.value);
			http.append("nivel", nivel.value);
			http.append("nombre", nombre.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_area.php");
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
			if(sede.value === ""){
				selectsede.className = "select-danger select2-selection__rendered";
			}else{
				selectsede.className = "select2-selection__rendered";
			}
			if(sector.value === ""){
				selectsector.className = "select-danger select2-selection__rendered";
			}else{
				selectsector.className = "select2-selection__rendered";
			}
			if(nivel.value === ""){
				nivel.classList.add("is-invalid");
			}else{
				nivel.classList.remove("is-invalid");
			}
			if(nombre.value === ""){
				nombre.classList.add("is-invalid");
			}else{
				nombre.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function Modificar(){
		codigo = document.getElementById('codigo');
		sede = document.getElementById("sede");
		sector = document.getElementById("sector");
		nombre = document.getElementById('nombre');
		nivel = document.getElementById("nivel");
		//-
		selectsede = document.getElementById("select2-sede-container");
		selectsector = document.getElementById("select2-sector-container");
		
		if(nombre.value !== "" && sede.value !== "" && sector.value !== "" && nivel.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-modificar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","modificar");
			http.append("codigo", codigo.value);
			http.append("sede", sede.value);
			http.append("sector", sector.value);
			http.append("nivel", nivel.value);
			http.append("nombre", nombre.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_area.php");
			request.send(http);
			request.onreadystatechange = function(){
			   console.log( request );
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
			if(sede.value === ""){
				selectsede.className = "select-danger select2-selection__rendered";
			}else{
				selectsede.className = "select2-selection__rendered";
			}
			if(sector.value === ""){
				selectsector.className = "select-danger select2-selection__rendered";
			}else{
				selectsector.className = "select2-selection__rendered";
			}
			if(nivel.value === ""){
				nivel.classList.add("is-invalid");
			}else{
				nivel.classList.remove("is-invalid");
			}
			if(nombre.value === ""){
				nombre.classList.add("is-invalid");
			}else{
				nombre.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function deshabilitaArea(codigo){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea quitar a esta \u00E1rea del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
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
		request.open("POST", "ajax_fns_area.php");
		request.send(http);
		request.onreadystatechange = function(){
			console.log( request );
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
	
////////////////////// QRs /////////////////////////////////////
	function printTableQR(codigo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tablaQR");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_area.php");
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
						{extend: 'excel', title: 'Tabla de Areas'},
						{extend: 'pdf', title: 'Tabla de Areas'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Areas'
						}
					]
				});
			}
		};     
	}
	
	
	function verQR(area){
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/sedes/QR.php",{codigo:area}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
   }