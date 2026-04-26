//funciones javascript y validaciones
	$(document).ready(function() {
		printTable('');
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
	
	function getdepmun(){
		contenedor = document.getElementById("divmun");
		loadingCogs(contenedor);
		/////////// POST /////////
		var departamento = document.getElementById("departamento").value;
		var http = new FormData();
		http.append("request","mundep");
		http.append("departamento",departamento);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_sede.php");
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
		request.open("POST", "ajax_fns_sede.php");
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
						{extend: 'excel', title: 'Tabla de Sedes'},
						{extend: 'pdf', title: 'Tabla de Sedes'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Sedes'
						}
					]
				});
			}
		};     
	}
	
	
	function seleccionarSede(codigo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","get");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_sede.php");
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
				document.getElementById("nombre").value = data.nombre;
				document.getElementById("zona").value = data.zona;
				document.getElementById("direccion").value = data.direccion;
				document.getElementById("departamento").value = data.departamento;
				document.getElementById("latitud").value = data.latitud;
				document.getElementById("longitud").value = data.longitud;
				//combo
				var combo = resultado.combo;
				document.getElementById("divmun").innerHTML = combo;
				document.getElementById("municipio").value = data.municipio;
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
		nombre = document.getElementById('nombre');
		departamento = document.getElementById("departamento");
		municipio = document.getElementById("municipio");
		direccion = document.getElementById("direccion");
		zona = document.getElementById("zona");
		latitud = document.getElementById("latitud");
		longitud = document.getElementById("longitud");
		
		selectdepartamento = document.getElementById("select2-departamento-container");
		selectmunicipio = document.getElementById("select2-municipio-container");
		
		if(nombre.value !== "" && direccion.value !== "" && departamento.value !== "" && municipio.value !== "" && zona.value !== "" && latitud.value !== "" && longitud.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabar");
			http.append("nombre", nombre.value);
			http.append("departamento", departamento.value);
			http.append("municipio", municipio.value);
			http.append("direccion", direccion.value);
			http.append("zona", zona.value);
			http.append("lat", latitud.value);
			http.append("long", longitud.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_sede.php");
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
			if(nombre.value === ""){
				nombre.classList.add("is-invalid");
			}else{
				nombre.classList.remove("is-invalid");
			}
			if(departamento.value === ""){
				selectdepartamento.className = "select-danger select2-selection__rendered";
			}else{
				selectdepartamento.className = "select2-selection__rendered";
			}
			if(municipio.value === ""){
				selectmunicipio.className = "select-danger select2-selection__rendered";
			}else{
				selectmunicipio.className = "select2-selection__rendered";
			}
			if(direccion.value === ""){
				direccion.classList.add("is-invalid");
			}else{
				direccion.classList.remove("is-invalid");
			}
			if(zona.value === ""){
				zona.classList.add("is-invalid");
			}else{
				zona.classList.remove("is-invalid");
			}
			if(latitud.value === ""){
				latitud.classList.add("is-invalid");
			}else{
				latitud.classList.remove("is-invalid");
			}
			if(longitud.value === ""){
				longitud.classList.add("is-invalid");
			}else{
				longitud.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function Modificar(){
		codigo = document.getElementById('codigo');
		nombre = document.getElementById("nombre");
		departamento = document.getElementById("departamento");
		municipio = document.getElementById("municipio");
		direccion = document.getElementById("direccion");
		zona = document.getElementById("zona");
		latitud = document.getElementById("latitud");
		longitud = document.getElementById("longitud");
		//-
		selectdepartamento = document.getElementById("select2-departamento-container");
		selectmunicipio = document.getElementById("select2-municipio-container");
		
		if(nombre.value !== "" && direccion.value !== "" && departamento.value !== "" && municipio.value !== "" && zona.value !== "" && latitud.value !== "" && longitud.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-modificar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","modificar");
			http.append("codigo", codigo.value);
			http.append("nombre", nombre.value);
			http.append("departamento", departamento.value);
			http.append("municipio", municipio.value);
			http.append("direccion", direccion.value);
			http.append("zona", zona.value);
			http.append("lat", latitud.value);
			http.append("long", longitud.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_sede.php");
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
			if(nombre.value === ""){
				nombre.classList.add("is-invalid");
			}else{
				nombre.classList.remove("is-invalid");
			}
			if(departamento.value === ""){
				selectdepartamento.className = "select-danger select2-selection__rendered";
			}else{
				selectdepartamento.className = "select2-selection__rendered";
			}
			if(municipio.value === ""){
				selectmunicipio.className = "select-danger select2-selection__rendered";
			}else{
				selectmunicipio.className = "select2-selection__rendered";
			}
			if(direccion.value === ""){
				direccion.classList.add("is-invalid");
			}else{
				direccion.classList.remove("is-invalid");
			}
			if(zona.value === ""){
				zona.classList.add("is-invalid");
			}else{
				zona.classList.remove("is-invalid");
			}
			if(latitud.value === ""){
				latitud.classList.add("is-invalid");
			}else{
				latitud.classList.remove("is-invalid");
			}
			if(longitud.value === ""){
				longitud.classList.add("is-invalid");
			}else{
				longitud.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	
	function plotMap(){
		var n = Math.floor(Math.random() * 11);
		var k = Math.floor(Math.random() * 1000000);
		var popname = String.fromCharCode(n) + k;
		var myWindow = window.open('FRMmapa_plot.php', 'popup'+popname, 'width=900,height=600,dependent=0,scrollbars=1');
	}
	
	function viewMap(latitud,longitud){
		var n = Math.floor(Math.random() * 11);
		var k = Math.floor(Math.random() * 1000000);
		var popname = String.fromCharCode(n) + k;
		window.open('FRMmapa_view.php?latitud='+latitud+'&longitud='+longitud, 'popup'+popname, 'width=900,height=600,dependent=0,scrollbars=1');
	}
	
	function AceptarCoordenadas(){
		lati = document.getElementById("latitud").value;
		longi = document.getElementById("longitud").value;
		//alert(plaza+","+jer+","+desc);
		if(lati !== '' && longi !== ''){
			//FORMULARIO QUE RECIBE
			self.opener.document.getElementById("latitud").value = lati;
			self.opener.document.getElementById("longitud").value = longi;
			window.close();
		}else{
			swal("Alto!", "Aun no ha seleccionado coordenadas...", "warning");
		}
	}

	function deshabilitaSede(codigo){
		swal({
			title: "\u00BFEsta Seguro?",
			text: "\u00BFDesea quitar a esta sede del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
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
		request.open("POST", "ajax_fns_sede.php");
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
				console.log( resultado.sql );
				swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value)=>{ window.location.reload(); });
			}
		};     
	}