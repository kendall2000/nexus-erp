//funciones javascript y validaciones
	$(document).ready(function() {
		$(".select2").select2();
	});
				
	function Limpiar(){
		texto = "�Desea Limpiar la Pagina?, perdera los datos escritos...";
		acc = "location.reload();";
		ConfirmacionJs(texto,acc);
	}
			
			
	function aceptar(){
		mail = document.getElementById("email");
		var boton = document.getElementById("btn-enviar");
		if(mail.value !== ""){
			/////////// POST /////////
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","password");
			http.append("email", mail.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_ayuda.php");
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
						deloadingBtn(boton,'<i class="far fa-envelope"></i> Enviar');
						mail.value = "";
					});
				}
			};     
			
		}else{
			swal("Ohoo!", "La direcci\u00F3n de correo es un campo obligatorio...", "error");
		}
	}
	
	function enviar(){
		nom = document.getElementById("nom");
		mail = document.getElementById("email");
		subj = document.getElementById("subj");
		msj = document.getElementById("msj");
		var boton = document.getElementById("btn-enviar");
		if(mail.value !== "" && nom.value !== "" && subj.value !== "" && msj.value !== ""){
			/////////// POST /////////
			loadingBtn(boton);
			var http = new FormData();
			http.append("request","mailadmin");
			http.append("nombre", nom.value);
			http.append("email", mail.value);
			http.append("subject", subj.value);
			http.append("msj", msj.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_ayuda.php");
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
						deloadingBtn(boton,'<i class="far fa-paper-plane"></i> Enviar');
						nom.value = "";
						mail.value = "";
						subj.value = "";
						msj.value = "";
					});
				}
			};     
		}else{
			if(nom.value ===""){
				nom.classList.add("is-invalid");
			}else{
				nom.classList.remove("is-invalid");
			}
			if(mail.value ===""){
				mail.classList.add("is-invalid");
			}else{
				mail.classList.remove("is-invalid");
			}
			if(subj.value ===""){
				subj.classList.add("is-invalid");
			}else{
				subj.classList.remove("is-invalid");
			}
			if(msj.value ===""){
				msj.classList.add("is-invalid");
			}else{
				msj.classList.remove("is-invalid");
			}
			swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
		}
	}