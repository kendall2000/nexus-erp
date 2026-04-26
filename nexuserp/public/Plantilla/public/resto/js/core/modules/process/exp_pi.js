function relacionarPartesInteresadas(codigo=""){
    window.location.assign("/ROOT/CPPROCESS/FRMexpectativas.php");
}

function printTable() {
	contenedor = document.getElementById("result");
	loadingCogs(contenedor);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "tabla_expectativa");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_expectativa.php");
	request.send(http);
	
	request.onreadystatechange = function () {
		if (request.readyState != 4){
			console.log("readyState no es 4, es: " + request.readyState);
			return;
		}
		if (request.status === 200) {
			//console.log( request.responseText );
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
			//	console.log(resultado);
				contenedor.innerHTML = '...';
				swal("Informaci\u00F3n", resultado.message, "info");
				return;
			}
			//tabla
			/*
			var data = resultado.tabla;
			contenedor.innerHTML = data;  //verificar el estandar */
			contenedor.innerHTML = resultado.tabla; //verificar si cumple estandares


			$('#tabla').DataTable({
				pageLength: 50,
				responsive: true,
				dom: '<"html5buttons"B>lTfgitp',
				buttons: [
					{ extend: 'copy' },
					{ extend: 'csv' },
					{ extend: 'excel', title: 'Tabla de Actividades' },
					{ extend: 'pdf', title: 'Tabla de Actividades' },
					{
						extend: 'print',
						customize: function (win) {
							$(win.document.body).addClass('white-bg');
							$(win.document.body).css('font-size', '10px');
							$(win.document.body).find('table')
								.addClass('compact')
								.css('font-size', 'inherit');
						}, title: 'Tabla de Actividades'
					}
				]
			});
		}
	};
}


function Grabar() {
	contenedor = document.getElementById("result");
	info = contenedor.innerHTML;
	loadingCogs(contenedor);

	let tipo = document.getElementById('tipo');                
	let nombre = document.getElementById('nombre');             
	let comentario = document.getElementById('comentario');   
	if (tipo.value != "" && nombre.value != "" && comentario.value != "") {
		/////////// POST /////////
                var boton = document.getElementById("btn-grabar");
		loadingBtn(boton);
		var http = new FormData();
		http.append("request", "grabar_expectativa");
		http.append("tipo", nombre.value);
		http.append("nombre", tipo.value);
		http.append("comentario", comentario.value);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_fns_expectativa.php");
		request.send(http);
		request.onreadystatechange = function () {
			// console.log(request.responseText);
			if (request.readyState != 4) return;
			if (request.status === 200) {
				console.log("request status es 200");
				resultado = JSON.parse(request.responseText);
				if (resultado.status !== true) {
					contenedor.innerHTML = info;
					swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
					return;
				}
				swal("Excelente!", resultado.message, "success").then((value) => {
					deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
					//setSelect2("tipo","");
                                        tipo.value = "";
					comentario.value = "";
					nombre.value = "";
					var tabla = resultado.data;
					contenedor.innerHTML = tabla;
					$('#tabla').DataTable({
						pageLength: 50,
						responsive: true
					});
				});
			}
		};
	} else {
		contenedor.innerHTML = info;
		swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
	}	
        if (tipo.value == "") {
                console.log("tipo vacio");
		tipo.classList.add('is-invalid');
	} else {
		tipo.classList.remove('is-invalid');
	}
	if (nombre.value == "") {
		nombre.classList.add("is-invalid");
	} else {
		nombre.classList.remove("is-invalid");
	}
	if (comentario.value == "") {
		comentario.classList.add("is-invalid");
	} else {
		comentario.classList.remove("is-invalid");
	}	
}

function Seleccionar(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "get");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_expectativa.php");
    request.send(http);
    request.onreadystatechange = function () {
        console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            var data = resultado.data;
            document.getElementById("codigo").value = data.codigo;
            document.getElementById('nombre').value = data.nombre;
            document.getElementById('comentario').value = data.comentario;
            document.getElementById('tipo').value = Number(data.tipo); 
            document.getElementById('situacion').value = Number(data.situacion)+1;
                                            //tabla
            var tabla = resultado.tabla;
            contenedor.innerHTML = tabla;
            $('#tabla').DataTable({
                    pageLength: 50,
                    responsive: true
            });
            //botones

        }
    };
    document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
    document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
}

function modificar() {
    contenedor = document.getElementById("result");
    info = contenedor.innerHTML;
    loadingCogs(contenedor);
    //--
    codigo = document.getElementById('codigo');
    tipo = document.getElementById('tipo');
    sistema = document.getElementById('nombre');
    descripcion = document.getElementById('descripcion');
    situacion = document.getElementById('situacion');

    if (codigo.value !== "" && tipo.value !== "" && nombre.value !== "" && comentario.value !== "" &&situacion.value !== "") {
            /////////// POST /////////
        var boton = document.getElementById("btn-modificar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "modificar_expectativa");
        http.append("codigo", codigo.value);
          http.append("tipo", tipo.value);
        http.append("nombre", nombre.value);
        http.append("comentario", comentario.value);
        http.append("situacion", situacion.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_expectativa.php");

        request.send(http);
        request.onreadystatechange = function () {
                //console.log(request.responseText);
               if (request.readyState != 4) return;
               if (request.status === 200) {
                    resultado = JSON.parse(request.responseText);
                    if (resultado.status !== true) {
                        //	console.log("errrror");//////
                        swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
                        return;
                    }
                    //console.log( resultado );
                    swal("Excelente!", resultado.message, "success").then((value) => {
                        deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
/*
                        codigo.value = "";
                        nombre.value = "";
                        tipo.value = "";
                        comentario.value = "";
                        situacion.value = "";
                        */
                        window.location.reload();
                        var tabla = resultado.data;
                        contenedor.innerHTML = tabla;
                        $('#tabla').DataTable({
                                pageLength: 50,
                                responsive: true
                        });
                    });
               }
        };
    } else {
            contenedor.innerHTML = info;
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
    /*if (codigo.value === "") {
            codigo.parentNode.classList.add('has-error');
    } else {
            codigo.parentNode.classList.remove('has-error');
    }
if (tipo.value === "") {
            tipo.parentNode.classList.add('has-error');
    } else {
            tipo.parentNode.classList.remove('has-error');
    }
    if (nombre.value === "") {
            nombre.parentNode.classList.add("is-invalid");
    } else {
            nombre.parentNode.classList.remove("is-invalid");
    }
    if (comentario.value === "") {
            descripcion.parentNode.classList.add("is-invalid");
    } else {
            comentario.parentNode.classList.remove("is-invalid");
    }
    if (situacion.value === "") {
            situacion.parentNode.classList.add('has-error');
    } else {
            situacion.parentNode.classList.remove('has-error');
    }*/
    
}

function Limpiar() {
	swal({
		text: "\u00BFDesea Limpiar la p\u00E1gina?, si a\u00FAn no a grabado perdera los datos escritos...",
		icon: "info",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true, },
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

function Deshabilitar(codigo) {
	swal({
		text: "\u00BFDesea quitar este registro del listado?, no prodr\u00E1 ser usado despu\u00E9s...",
		icon: "warning",
		buttons: {
			cancel: "Cancelar",
			ok: { text: "Aceptar", value: true },
		}
	}).then((value) => {
		switch (value) {
			case true:
				cambioSituacion(codigo, 0);
				break;
			default:
				return;
		}
	});
}

function cambioSituacion(codigo, situacion) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion");
    http.append("codigo", codigo);
    http.append("situacion", situacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_expectativa.php");
    request.send(http);
    request.onreadystatechange = function () {
        // console.log(request.responseText);
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado.sql );
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            
        //    ok: { text: "Aceptar", value: true },
            
            swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then(() => {
                window.location.reload();
                $('#tabla').DataTable({
                    pageLength: 50,
                    responsive: true
                });
            });
        }
    };
}

