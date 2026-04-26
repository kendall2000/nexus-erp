//funciones javascript y validaciones
$(document).ready(function () {
    $(".select2").select2();
});

function atras() {
    if (document.getElementById('codigo').value != '') window.location.reload();
    else window.history.back();
}

// function Submit() {
//     myform = document.forms.f1;
//     myform.submit();
// }
/////////////////////// KM ///////////////////////////////
////////recargo de pagina de frmmis_indicadores//////////
function table_kpi() {
    contenedor = document.getElementById("result");
    proceso = document.getElementById("proceso");
    sistema = document.getElementById("sistema");
    loadingCogs(contenedor);//funcion que me pita el egranaje
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "table_kpi");
	http.append("proceso", proceso.value);
	http.append("sistema", sistema.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_indicador.php");
    request.send(http);
    request.onreadystatechange = function() {
        // console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                console.log( resultado );
                contenedor.innerHTML = '...';
                console.log(resultado.message);
              }
              //tabla
              contenedor.innerHTML = resultado.tabla;
              $('.dataTables-example').DataTable({
                  pageLength: 50,
                  responsive: true,
                  dom: '<"html5buttons"B>lTfgitp',
                  buttons: [

                  ]
              });
          }
      };
  }
////////////////////////////////////////

function Limpiar() {
    swal({
        text: "\u00BFDesea Limpiar la p\u00E1gina?, si a\u00FAn no a grabado perdera los datos escritos...",
        icon: "info",
        buttons: {
            cancel: "Cancelar",
            ok: {
                text: "Aceptar",
                value: true,
            },
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

function printTable(codigo,usuario) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla");
    http.append("codigo", codigo);
    http.append("usuario", usuario);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_indicador.php");
    request.send(http);
    request.onreadystatechange = function () {
        // console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            // console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
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
                buttons: [{
                    extend: 'copy'
                },
                {
                    extend: 'csv'
                },
                {
                    extend: 'excel',
                    title: 'Tabla de Indicadores'
                },
                {
                    extend: 'pdf',
                    title: 'Tabla de Indicadores'
                },
                {
                    extend: 'print',
                    customize: function (win) {
                        $(win.document.body).addClass('white-bg');
                        $(win.document.body).css('font-size', '10px');
                        $(win.document.body).find('table')
                            .addClass('compact')
                            .css('font-size', 'inherit');
                    },
                    title: 'Tabla de Indicadores'
                }
                ]
            });
        }
    };
}

function seleccionarIndicador(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "get");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_indicador.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            var data = resultado.data;
            //console.log( data );
            //set
            document.getElementById("codigo").value = data.codigo;
            document.getElementById("proceso").value = data.proceso;
            document.getElementById("sistema").value = data.sistema;
            document.getElementById("categoria").value = data.categoria;
            document.getElementById("clasificacion").value = data.clasificacion;
            document.getElementById("nombre").value = data.nombre;
            document.getElementById("descripcion").value = data.descripcion;
            document.getElementById("ideal").value = data.ideal;
            document.getElementById("max").value = data.max;
            document.getElementById("min").value = data.min;

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

function Grabar() {
    proceso = document.getElementById('proceso');
    sistema = document.getElementById('sistema');
    categoria = document.getElementById('categoria');
    clasificacion = document.getElementById('clasificacion');
    nombre = document.getElementById('nombre');
    descripcion = document.getElementById('descripcion');
    ideal = document.getElementById('ideal');
    max = document.getElementById('max');
    min = document.getElementById('min');

    if (proceso.value !== "" && sistema.value !== "" && categoria.value !== "" && clasificacion.value !== "" &&
        nombre.value !== "" && descripcion.value !== "" && ideal.value !== "" &&
        max.value !== "" && min.value !== "") {
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "grabar");
        http.append("proceso", proceso.value);
        http.append("sistema", sistema.value);
        http.append("categoria", categoria.value);
        http.append("clasificacion", clasificacion.value);
        http.append("nombre", nombre.value);
        http.append("descripcion", descripcion.value);
        http.append("ideal", ideal.value);
        http.append("max", max.value);
        http.append("min", min.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_indicador.php");
        request.send(http);
        request.onreadystatechange = function () {
            console.log(request);
            if (request.readyState != 4) return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    swal("Error", resultado.message, "error").then((value) => {
                        deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
                    });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.reload();
                });
            }
        };
    } else {
        if (descripcion.value === "") {
            descripcion.classList.add("is-invalid");
        } else {
            descripcion.classList.remove("is-invalid");
        }
        if (proceso.value === "") {
            proceso.parentNode.classList.add('has-error');
        } else {
            proceso.parentNode.classList.remove('has-error');
        }
        if (sistema.value === "") {
            sistema.parentNode.classList.add('has-error');
        } else {
            sistema.parentNode.classList.remove('has-error');
        }
        if (categoria.value === "") {
            categoria.parentNode.classList.add('has-error');
        } else {
            categoria.parentNode.classList.remove('has-error');
        }
        if (clasificacion.value === "") {
            clasificacion.parentNode.classList.add('has-error');
        } else {
            clasificacion.parentNode.classList.remove('has-error');
        }
        if (nombre.value === "") {
            nombre.classList.add("is-invalid");
        } else {
            nombre.classList.remove("is-invalid");
        }
        if (ideal.value === "") {
            ideal.classList.add("is-invalid");
        } else {
            ideal.classList.remove("is-invalid");
        }
        if (max.value === "") {
            max.classList.add("is-invalid");
        } else {
            max.classList.remove("is-invalid");
        }
        if (min.value === "") {
            min.classList.add("is-invalid");
        } else {
            min.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function Modificar() {
    codigo = document.getElementById('codigo');
    proceso = document.getElementById('proceso');
    sistema = document.getElementById('sistema');
    categoria = document.getElementById('categoria');
    clasificacion = document.getElementById('clasificacion');
    nombre = document.getElementById('nombre');
    descripcion = document.getElementById('descripcion');
    ideal = document.getElementById('ideal');
    max = document.getElementById('max');
    min = document.getElementById('min');

    if (proceso.value !== "" && sistema.value !== "" && categoria.value !== "" && clasificacion.value !== "" &&
        nombre.value !== "" && descripcion.value !== "" && ideal.value !== "" &&
        max.value !== "" && min.value !== "" && codigo.value !== "") {
        /////////// POST /////////
        var boton = document.getElementById("btn-modificar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "modificar");
        http.append("codigo", codigo.value);
        http.append("proceso", proceso.value);
        http.append("sistema", sistema.value);
        http.append("categoria", categoria.value);
        http.append("clasificacion", clasificacion.value); // Clasificacion
        http.append("nombre", nombre.value);
        http.append("descripcion", descripcion.value);
        http.append("ideal", ideal.value);
        http.append("max", max.value);
        http.append("min", min.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_indicador.php");
        request.send(http);
        request.onreadystatechange = function () {
            console.log(request);
            if (request.readyState != 4) return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    swal("Error", resultado.message, "error").then((value) => {
                        deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
                    });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.reload();
                });
            }
        };
    } else {
        if (descripcion.value === "") {
            descripcion.classList.add("is-invalid");
        } else {
            descripcion.classList.remove("is-invalid");
        }
        if (proceso.value === "") {
            proceso.parentNode.classList.add('has-error');
        } else {
            proceso.parentNode.classList.remove('has-error');
        }
        if (sistema.value === "") {
            sistema.parentNode.classList.add('has-error');
        } else {
            sistema.parentNode.classList.remove('has-error');
        }
        if (categoria.value === "") {
            categoria.parentNode.classList.add('has-error');
        } else {
            categoria.parentNode.classList.remove('has-error');
        }
        if (clasificacion.value === "") {
            clasificacion.parentNode.classList.add('has-error');
        } else {
            clasificacion.parentNode.classList.remove('has-error');
        }
        if (nombre.value === "") {
            nombre.classList.add("is-invalid");
        } else {
            nombre.classList.remove("is-invalid");
        }
        if (ideal.value === "") {
            ideal.classList.add("is-invalid");
        } else {
            ideal.classList.remove("is-invalid");
        }
        if (max.value === "") {
            max.classList.add("is-invalid");
        } else {
            max.classList.remove("is-invalid");
        }
        if (min.value === "") {
            min.classList.add("is-invalid");
        } else {
            min.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function detalle(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/indicadores/detalle.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}

function deshabilitarIndicador(codigo) {
    swal({
        text: "\u00BFDesea quitar a este indicador del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {
                text: "Aceptar",
                value: true
            },
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
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion");
    http.append("codigo", codigo);
    http.append("situacion", situacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_indicador.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado.sql );
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value) => {
                window.location.reload();
            });
        }
    };
}

////////////////////////// HORARIOS ////////////////////////////////////////////

function tipoProgramacion(tipo) {
    contenedorSemana = document.getElementById('containerSemana');
    contenedorMes = document.getElementById('containerMes');

    if (tipo === 'M') {
        contenedorSemana.style.display = 'none';
        contenedorMes.style.display = 'block';
    } else {
        contenedorSemana.style.display = 'block';
        contenedorMes.style.display = 'none';
    }

    document.getElementById('diaL').className = 'btn btn-white';
    document.getElementById('diaM').className = 'btn btn-white';
    document.getElementById('diaW').className = 'btn btn-white';
    document.getElementById('diaJ').className = 'btn btn-white';
    document.getElementById('diaV').className = 'btn btn-white';
    document.getElementById('diaS').className = 'btn btn-white';
    document.getElementById('diaD').className = 'btn btn-white';

    for (var i = 1; i <= 31; i++) {
        document.getElementById('dia' + i).className = 'btn btn-white';
    }

}
