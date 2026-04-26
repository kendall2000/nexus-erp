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
	
	function loadingGif(pregunta){
		document.getElementById("foto"+pregunta).innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
	}
	
	function loadingButton(elemento){
		elemento.setAttribute("disabled","disabled");
		elemento.innerHTML = '<img src="../../CONFIG/img/img-loader.gif" width="15px" alt="cargando...">';
	}