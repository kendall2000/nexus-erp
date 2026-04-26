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
						
							
////////////////////////////////////////////////////////////////////////////////////
////////////////////////////// perfil FRMpassword ///////////////////////////////
	
	function ModificarPass(){
		codigo = document.getElementById('codigo');
		usu = document.getElementById('usu');
		pass1 = document.getElementById("pass1");
		pass2 = document.getElementById("pass2");
						
		if(codigo.value !== "" && usu.value !== "" && pass1.value !== "" && pass2.value !== ""){
			if(pass1.value == pass2.value){
				/////////// POST /////////
				var boton = document.getElementById("btn-grabar");
				loadingBtn(boton);
				var http = new FormData();
				http.append("request","password");
				http.append("codigo", codigo.value);
				http.append("usuario", usu.value);
				http.append("pass", pass1.value);
				var request = new XMLHttpRequest();
				request.open("POST", "ajax_fns_perfil.php");
				request.send(http);
				request.onreadystatechange = function(){
				   console.log( request );
				   if(request.readyState != 4) return;
				   if(request.status === 200){
					resultado = JSON.parse(request.responseText);
						if(resultado.status !== true){
							swal("Error", resultado.message , "error");
							return;
						}
						//console.log( resultado );
						swal("Excelente!", resultado.message, "success").then((value) => {
							window.location.reload();
						});
					}
				};     
			}else{
				pass1.classList.add("is-invalid");
				pass2.classList.add("is-invalid");
				swal("Ohoo!", "la Contrase\u00F1a y la Confirmaci\u00F3n no son iguales...", "error");
			}
		}else{
			if(usu.value === ""){
				usu.classList.add("is-invalid");
			}else{
				usu.classList.remove("is-invalid");
			}
			if(pass1.value === ""){
				pass1.classList.add("is-invalid");
			}else{
				pass1.classList.remove("is-invalid");
			}
			if(pass2.value === ""){
				pass2.classList.add("is-invalid");
			}else{
				pass2.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function comprueba_vacios(n,x){
		texto = n.value;
		if(texto === ""){ 
			document.getElementById(x).className="text-warning fa fa-warning";
		}else{
			document.getElementById(x).className="text-success fa fa-check";
			var rojo = 0;
			var amar = 0;
			var verd = 0;
			var seguridad = seguridad_clave(texto);
			seguridad = parseInt(seguridad);
			
			if (seguridad <= 35) {
				rojo = parseInt(seguridad);
				document.getElementById("progress1").style.width = rojo + "%";
				document.getElementById("progress2").style.width = 0 + "%";
				document.getElementById("progress3").style.width = 0 + "%";
			}if (seguridad > 35 && seguridad <= 70) {
				rojo = 35;
				amar = parseInt(seguridad)-35;
				document.getElementById("progress1").style.width = rojo + "%";
				document.getElementById("progress2").style.width = amar + "%";
				document.getElementById("progress3").style.width = 0 + "%";
			}if (seguridad > 70) {
				rojo = 35;
				amar = 35;
				verd = parseInt(seguridad)-70;
				document.getElementById("progress1").style.width = rojo + "%";
				document.getElementById("progress2").style.width = amar + "%";
				document.getElementById("progress3").style.width = verd + "%";
			}
			
		}
	}

	function comprueba_iguales(n1,n2){
		texto1 = n1.value;
		texto2 = n2.value;
		if(texto1 == texto2){
			document.getElementById('pas2').className="text-success fa fa-check";
		}else{
			//alert(texto2);
			if(texto2 === ""){
				document.getElementById('pas2').className="text-warning fa fa-warning";
			}else{
				document.getElementById('pas2').className="text-danger fa fa-times";
			}
		}
	}
	
	function seguridad_clave(clave){
		var seguridad = 0;
		if(clave.length!=0){
			if (tiene_numeros(clave) && tiene_letras(clave)){
				  seguridad += 30;
			}
			if (tiene_minusculas(clave) && tiene_mayusculas(clave)){
				  seguridad += 30;
			}
			if (clave.length >= 4 && clave.length <= 5){
				  seguridad += 10;
			}else{
				if (clave.length >= 6 && clave.length <= 8){
					  seguridad += 30;
				}else{
					if (clave.length > 8){
						seguridad += 40;
					}
				}
			}
		}
		return seguridad;           
	}
	
	
///////// Perfil /////////////

	function ModificarPerfil(){
		codigo = document.getElementById('codigo');
		nombre = document.getElementById("nombre");
		mail = document.getElementById("mail");
		telefono = document.getElementById("telefono");
		
		if(codigo.value !== "" && nombre.value !== "" && mail.value !== "" && telefono.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","perfil");
			http.append("codigo", codigo.value);
			http.append("nombre", nombre.value);
			http.append("email", mail.value);
			http.append("telefono", telefono.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_perfil.php");
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
			if(mail.value === ""){
				mail.classList.add("is-invalid");
			}else{
				mail.classList.remove("is-invalid");
			}
			if(telefono.value === ""){
				telefono.classList.add("is-invalid");
			}else{
				telefono.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
////////////////////////////////////////////////////////////////////////////////////
////////////////////////////// AJUSTES ///////////////////////////////
	
	function ModificarAjustes(){
		codigo = document.getElementById('codigo');
		idioma = document.getElementById('idioma');
		notificaciones = document.getElementById("notificaciones");
		var notifica;
		if(notificaciones.checked){ //valida si esta seleccionado o no
			notifica = 1;
		}else{
			notifica = 0;
		}
		if(codigo.value !== "" && idioma.value !== ""){
			/////////// POST /////////
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","ajustes");
			http.append("codigo", codigo.value);
			http.append("idioma", idioma.value);
			http.append("notificaciones", notifica);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_perfil.php");
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
					//console.log( resultado );
					swal("Excelente!", resultado.message, "success").then((value) => {
						window.location.reload();
					});
				}
			};     
		}else{
			if(idioma.value === ""){
				idioma.classList.add("is-invalid");
			}else{
				idioma.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function validarEmail(valor) {
		var filtro = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;	
		if (filtro.test(valor)){
			return true;
		} else {
			return false;
		}
	}
	
	function checkValue(n){
		if(n == 1){ 
			document.getElementById("cambio2").checked = false;
		}else if(n == 2){ 
			document.getElementById("cambio1").checked = false;
		}
	}

	
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

	
	///////// Fotografia /////////////
	
	function FotoJs(){
		inpfile = document.getElementById("imagen");
		inpfile.click();
	}
	
	function Cargar(){
		codigo = document.getElementById("codigo");
		archivo = document.getElementById("imagen");
		if(archivo.value !== ""){
			exdoc = comprueba_extension(archivo.value,1);
			if(exdoc === 1){
				/////////// POST /////////
				var contenedor = document.getElementById("img-container");
				var boton = document.getElementById("btn-cargar");
				loadingDiv(contenedor);
				loadingBtn(boton);
				var http = new FormData();
				http.append("request","ajustes");
				http.append("codigo", codigo.value);
				http.append("imagen", archivo.files[0]);
				var request = new XMLHttpRequest();
				request.open("POST", "EXEcarga_foto.php");
				request.send(http);
				request.onreadystatechange = function(){
				   console.log( request );
				   if(request.readyState != 4) return;
				   if(request.status === 200){
					resultado = JSON.parse(request.responseText);
						if(resultado.status !== true){
							swal("Error", resultado.message , "error").then((value) => {
								console.log( value );
								deloadingDiv(contenedor,'<img class="img-thumbnail" src="../../CONFIG/Fotos/nofoto.jpg" alt="..." >');
								deloadingBtn(boton,'<i class="fas fa-camera"></i> Cambiar Fotograf&iacute;a...');
							});	
							return;
						}
						console.log( resultado );
						swal("Excelente!", resultado.message, "success").then((value) => {
							console.log( value );
							window.location.href = "FRMeditfoto.php";
						});
					}
				};     
				
			}else{
				swal("Alto!", "Este archivo no es extencion .jpg \u00F3 .png", "error");
			}		
		}else{
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	
	function validarEmail(valor) {
		var filtro = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;	
		if (filtro.test(valor)){
			return true;
		} else {
			return false;
		}
	}
	