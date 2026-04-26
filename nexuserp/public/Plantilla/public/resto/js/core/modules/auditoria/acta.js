//funciones javascript y validaciones
	$(document).ready(function() {
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
	
	function grabarActa(){
		ejecucion = document.getElementById("ejecucion");
		auditoria = document.getElementById("auditoria");
		programacion = document.getElementById("programacion");
		fini = document.getElementById("fini");
		hini = document.getElementById("hini");
		ffin = document.getElementById("ffin");
		hfin = document.getElementById("hfin");
		observaciones = document.getElementById("observaciones");
		if(fini.value !== "" && hini.value !== "" && ffin.value !== "" && hfin.value !== ""){
			var boton = document.getElementById("btn-grabar");
			loadingBtn(boton);
			/////////// POST /////////
			var http = new FormData();
			http.append("request","grabar");
			http.append("ejecucion", ejecucion.value);
			http.append("auditoria", auditoria.value);
			http.append("programacion", programacion.value);
			http.append("fini", fini.value);
			http.append("hini", hini.value);
			http.append("ffin", ffin.value);
			http.append("hfin", hfin.value);
			http.append("observaciones", observaciones.value);
			var request = new XMLHttpRequest();
			request.open("POST", "ajax_fns_acta.php");
			request.send(http);
			request.onreadystatechange = function(){
				//console.log( request );
				if(request.readyState != 4) return;
				if(request.status === 200){
					console.log( request.responseText );
					resultado = JSON.parse(request.responseText);
					if(resultado.status !== true){
						swal("Error", resultado.message , "error").then((value) => {
							console.log( value );
							deloadingBtn(boton,'<i class="fas fa-save"></i> Grabar');
						});
						return;
					}
					//console.log( resultado );
					var hashkey = resultado.hashkey;
					swal("Excelente!", resultado.message, "success").then((value) => {
						console.log( value );
						window.open("CPREPORTES/REPacta.php?hashkey="+hashkey, "_blank");
						window.location.reload();
					});
				}
			};     
			
		}else{
			if(fini.value === ""){
				fini.classList.add("is-invalid");
			}else{
				fini.classList.remove("is-invalid");
			}
			if(hini.value === ""){
				hini.classList.add("is-invalid");
			}else{
				hini.classList.remove("is-invalid");
			}
			if(ffin.value === ""){
				ffin.classList.add("is-invalid");
			}else{
				ffin.classList.remove("is-invalid");
			}
			if(hfin.value === ""){
				hfin.classList.add("is-invalid");
			}else{
				hfin.classList.remove("is-invalid");
			}
			swal("Alto!", "Debe llenar los campos obligatorios...", "error");
		}
	}
