//funciones javascript y validaciones
	$(document).ready(function(){
		$(".select2").select2();
			
		$('#range .input-daterange').datepicker({
			keyboardNavigation: false,
			forceParse: false,
			autoclose: true,
			format: "dd/mm/yyyy"
		});
	});
	
	function Submit(){
		myform = document.forms.f1;
		myform.submit();
	}

	function mostrarComentarios(codigo){
		cerrar();
		//Realiza una peticion de contenido a la contenido.php
		$.post("../promts/encuestas/comentarios.php", { codigo: codigo }, function (data) {
			// Ponemos la respuesta de nuestro script en el DIV recargado
			$("#Pcontainer").html(data);
		});
		abrirModal();
		setTimeout(function () {
			$(".select2").select2();
		}, 250);
	}