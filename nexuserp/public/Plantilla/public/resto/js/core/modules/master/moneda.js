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
	
	function printTableCambio(codigo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla_cambio");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_moneda.php");
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
				$('#tablaCambio').DataTable({
					pageLength: 50,
					responsive: true,
					dom: '<"html5buttons"B>lTfgitp',
					buttons: [
						{extend: 'copy'},
						{extend: 'csv'},
						{extend: 'excel', title: 'Tabla de Monedas'},
						{extend: 'pdf', title: 'Tabla de Monedas'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Monedas'
						}
					]
				});
				$(".select2").select2();
			}
		};     
	}
	
	
	function printTableMonedas(){
		contenedor = document.getElementById("resultPromt");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla_monedas");
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_moneda.php");
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
				//tabla
				var data = resultado.tabla;
				contenedor.innerHTML = data;
				$('#tablaMonedas').DataTable({
					pageLength: 50,
					responsive: true,
					dom: '<"html5buttons"B>lTfgitp',
					buttons: [
						{extend: 'copy'},
						{extend: 'csv'},
						{extend: 'excel', title: 'Tabla de Monedas'},
						{extend: 'pdf', title: 'Tabla de Monedas'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Monedas'
						}
					]
				});
			}
		};     
	}
					
	function agregaMoneda(){
		var codigo = 0;
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/moneda/masmonedas.php",{pag:codigo}, function(data){
		// Ponemos la respuesta de nuestro script en el DIV recargado
		$("#Pcontainer").html(data);
		});
		abrirModal();
	}
	
	function seleccionarMoneda(codigo){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","get");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_moneda.php");
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
				document.getElementById("codigo1").value = data.codigo;
				document.getElementById("descripcion1").value = data.descripcion;
				document.getElementById("simbolo1").value = data.simbolo;
				document.getElementById("pais1").value = data.pais;
				document.getElementById("cambio1").value = data.cambio;
				document.getElementById("compra1").value = data.compra;
				document.getElementById("venta1").value = data.venta;
				//tabla
				//botones
				document.getElementById("descripcion1").focus(); 
				document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
				document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
				//--
			}
		};     
	}
	
	
	function Grabar(){
		descripcion = document.getElementById("descripcion1");
		simbolo = document.getElementById("simbolo1");
		pais = document.getElementById("pais1");
		cambio = document.getElementById("cambio1");
		compra = document.getElementById("compra1");
		venta = document.getElementById("venta1");
		if(descripcion.value !== "" && simbolo.value !== "" && pais.value !== "" && cambio.value !== "" && compra.value !== "" && venta.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","grabar");
			http.append("descripcion", descripcion.value);
			http.append("simbolo", simbolo.value);
			http.append("pais", pais.value);
			http.append("cambio", cambio.value);
			http.append("compra", compra.value);
			http.append("venta", venta.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_moneda.php");
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
			if(descripcion.value === ""){
				descripcion.className = " form-danger";
			}else{
				descripcion.className = " form-control";
			}
			if(simbolo.value === ""){
				simbolo.className = " form-danger";
			}else{
				simbolo.className = " form-control";
			}
			if(pais.value === ""){
				pais.className = " form-danger";
			}else{
				pais.className = " form-control";
			}
			if(cambio.value === ""){
				cambio.className = " form-danger";
			}else{
				cambio.className = " form-control";
			}
			if(compra.value === ""){
				compra.className = " form-danger";
			}else{
				compra.className = " form-control";
			}
			if(venta.value === ""){
				venta.className = " form-danger";
			}else{
				venta.className = " form-control";
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	
	function Modificar(){
		codigo = document.getElementById("codigo1");
		descripcion = document.getElementById("descripcion1");
		simbolo = document.getElementById("simbolo1");
		pais = document.getElementById("pais1");
		cambio = document.getElementById("cambio1");
		compra = document.getElementById("compra1");
		venta = document.getElementById("venta1");
		if(codigo.value !== "" && descripcion.value !== "" && simbolo.value !== "" && pais.value !== "" && cambio.value !== "" && compra.value !== "" && venta.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-modificar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","modificar");
			http.append("codigo", codigo.value);
			http.append("descripcion", descripcion.value);
			http.append("simbolo", simbolo.value);
			http.append("pais", pais.value);
			http.append("cambio", cambio.value);
			http.append("compra", compra.value);
			http.append("venta", venta.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_moneda.php");
			request.send(http);
			request.onreadystatechange = function(){
			   //console.log( request );
			   if(request.readyState != 4) return;
			   if(request.status === 200){
				resultado = JSON.parse(request.responseText);
					//console.log( resultado.sql );
					if(resultado.status !== true){
						swal("Error", resultado.message , "error").then((value) => { deloadingBtn(boton,'<i class="fa fa-save"></i> Grabar'); });
						return;
					}
					swal("Excelente!", resultado.message, "success").then((value) => {
						window.location.reload();
					});
				}
			};     
		}else{
			if(descripcion.value === ""){
				descripcion.className = " form-danger";
			}else{
				descripcion.className = " form-control";
			}
			if(simbolo.value === ""){
				simbolo.className = " form-danger";
			}else{
				simbolo.className = " form-control";
			}
			if(pais.value === ""){
				pais.className = " form-danger";
			}else{
				pais.className = " form-control";
			}
			if(cambio.value === ""){
				cambio.className = " form-danger";
			}else{
				cambio.className = " form-control";
			}
			if(compra.value === ""){
				compra.className = " form-danger";
			}else{
				compra.className = " form-control";
			}
			if(venta.value === ""){
				venta.className = " form-danger";
			}else{
				venta.className = " form-control";
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
				
	
	function quitarMoneda(moneda){
		swal({
			title: "\u00BFQuitar esta Moneda?",
			text: "\u00BFEsta seguro de quitar esta moneda del listado?",
			icon: "warning",
			buttons: {
				cancel: "Cancelar",
				ok: { text: "Aceptar", value: true,},
			}
		}).then((value) => {
			switch (value) {
				case true:
					xajax_Cambia_Situacion_Moneda(moneda);
					break;
				default:
				  return;
			}
		});
	}
	

	
	function GrabarTasaCambio(){
		moneda = document.getElementById("moneda");
		cambio = document.getElementById("cambio");
		compra = document.getElementById("compra");
		venta = document.getElementById("venta");
		//--
		selectmoneda = document.getElementById("select2-moneda-container");
		
		if(moneda.value !== "" && cambio.value !== "" && compra.value !== "" && venta.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-tasa");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","tasa_cambio");
			http.append("moneda", moneda.value);
			http.append("cambio", cambio.value);
			http.append("compra", compra.value);
			http.append("venta", venta.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_moneda.php");
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
			if(moneda.value === ""){
				selectmoneda.className = "select-danger select2-selection__rendered";
			}else{
				selectmoneda.className = "select2-selection__rendered";
			}
			if(cambio.value === ""){
				cambio.className = " form-danger";
			}else{
				cambio.className = " form-control";
			}
			if(compra.value === ""){
				compra.className = " form-danger";
			}else{
				compra.className = " form-control";
			}
			if(venta.value === ""){
				venta.className = " form-danger";
			}else{
				venta.className = " form-control";
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}