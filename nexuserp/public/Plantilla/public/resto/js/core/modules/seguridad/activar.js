//funciones javascript y validaciones
	
//////////////////////////////////////////////////////

	function aceptar(){
		codigo = document.getElementById('codigo');
		nombre = document.getElementById('nombre');
		telefono = document.getElementById('telefono');
		mail = document.getElementById('mail');
		usu = document.getElementById('usu');
		pass1 = document.getElementById("pass1");
		pass2 = document.getElementById("pass2");
		if(mail.value !=="" && nombre.value !=="" && usu.value !=="" && pass1.value !== "" && pass2.value !== ""){
			if(pass1.value === pass2.value){
				/////////// POST /////////
				var boton = document.getElementById("btn-aceptar");
				loadingBtn(boton);
				var http = new FormData();
				http.append("request","activar");
				http.append("codigo", codigo.value);
				http.append("nombre", nombre.value);
				http.append("mail", mail.value);
				http.append("telefono", telefono.value);
				http.append("usuario", usu.value);
				http.append("pass", pass1.value);
				var request = new XMLHttpRequest();
				request.open("POST", "ajax_fns_valida.php");
				request.send(http);
				request.onreadystatechange = function(){
				   console.log( request );
				   if(request.readyState != 4) return;
				   if(request.status === 200){
					resultado = JSON.parse(request.responseText);
						if(resultado.status !== true){
							//console.log( resultado.sql );
							swal("Error", resultado.message , "error").then((value) => { deloadingBtn(boton,'<i class="fa fa-check"></i> Aceptar'); });
							return;
						}
						swal("Excelente!", resultado.message, "success").then((value) => {
							window.location.href="../login.php?usu="+usu.value+"&pass="+pass1.value; 
						});
					}
				};     
				
				
			}else{
				pass1.classList.add("is-invalid");
				pass2.classList.add("is-invalid");
				swal("Ohoo!", "la Contrase\u00F1a y la Confirmaci\u00F3n no son iguales...", "error");
			}
		}else{
			if(usu.value ===""){
				usu.classList.add("is-invalid");
			}else{
				usu.classList.remove("is-invalid");
			}
			if(pass1.value ===""){
				pass1.classList.add("is-invalid");
			}else{
				pass1.classList.remove("is-invalid");
			}
			if(pass2.value ===""){
				pass2.classList.add("is-invalid");
			}else{
				pass2.classList.remove("is-invalid");
			}
			if(mail.value ===""){
				mail.classList.add("is-invalid");
			}else{
				mail.classList.remove("is-invalid");
			}
			if(nombre.value ===""){
				nombre.classList.add("is-invalid");
			}else{
				nombre.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}
	