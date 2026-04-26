function prompt_PI(codigo) {
	//alert(codigo);
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/encuestas/partesInteresadas.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
	}


	function prompt_NEX(codigo) {
	//alert(codigo);
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/encuestas/Necesidades_expectativas.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
	}

	function encuestar(grupo){
		  swal({
                title: "Encuestas",
                text: "Desea Enviar Encuestas a cada una de las Partes Interesadas Asignadas? ",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((value) => {
                if (value) {
                	//alert(grupo);
                	sendEmail(grupo);
                    //window.open("https://bpm.desarrollogt.net/ROOT/CPPROCESS/FRMaprobaciones.php");
                    cerrar();	
                } else {
                    cerrar();
                }
            });
	}


	function sendEmail(grupo){
		/////////// POST /////////
		var http = new FormData();
		http.append("request","mail_partes_interesadas");
		http.append("grupo",grupo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_validacion.php");
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
				swal("Excelente!", resultado.message , "success").then((value)=>{ window.location.reload(); });
			}
		};     
	}	

		function printTablePartesInteresadas(codigo){
		contenedor = document.getElementById("result");
		loadingCogs(contenedor);
		/////////// POST /////////
		var http = new FormData();
		http.append("request","tabla_partes_interesadas");
		http.append("codigo",codigo);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_validacion.php");
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
						{extend: 'excel', title: 'Tabla de Cuestionarios'},
						{extend: 'pdf', title: 'Tabla de Cuestionarios'},
						{extend: 'print',
							customize: function (win){
								$(win.document.body).addClass('white-bg');
								$(win.document.body).css('font-size', '10px');
								$(win.document.body).find('table')
										.addClass('compact')
										.css('font-size', 'inherit');
							}, title: 'Tabla de Cuestionarios'
						}
					]
				});
			}
		};     
	}