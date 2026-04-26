//funciones javascript y validaciones
//


$(document).ready(function () {
    $(".select2").select2({ width: '100%' });
});

function atras() {
    if (document.getElementById('codigo').value != '') window.location.reload();
    else window.history.back();
}

function Submit() {
    myform = document.forms.f1;
    myform.submit();
}

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

function Grabar() {
    indicador = document.getElementById("indicador");
    inicio = document.getElementById('inicio');
    fin = document.getElementById('fin');
    hini = document.getElementById('hini');
    hfin = document.getElementById('hfin');
    observacion = document.getElementById('observacion');
    tipo = document.getElementById("tipo");
    desde = document.getElementById("desde");
    hasta = document.getElementById('hasta');
    // Devuelve 1 si esta activo de otra forma un 0
    // 
    var arrDias = Array();
    arrDias[0] = (document.getElementById('diaL').classList.contains('active')) ? 1 : 0;;
    arrDias[1] = (dia2 = document.getElementById('diaM').classList.contains('active')) ? 1 : 0;
    arrDias[2] = (dia3 = document.getElementById('diaW').classList.contains('active')) ? 1 : 0;
    arrDias[3] = (dia4 = document.getElementById('diaJ').classList.contains('active')) ? 1 : 0;
    arrDias[4] = (dia5 = document.getElementById('diaV').classList.contains('active')) ? 1 : 0;
    arrDias[5] = (dia6 = document.getElementById('diaS').classList.contains('active')) ? 1 : 0;
    arrDias[6] = (dia7 = document.getElementById('diaD').classList.contains('active')) ? 1 : 0;
    //console.log(arrDias);
    if (desde.value != "" && hasta.value != "" && indicador.value != "" && tipo.value != "" && observacion.value != "" ) {
        // Nuevo esquema
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "grabar_programacion");
        http.append("indicador", indicador.value);
        http.append("observacion", observacion.value);
        if ((tipo.value == 'M' || tipo.value == 'W')) {
            http.append("inicio", inicio.value);
            http.append("fin", fin.value);
        }
        http.append("hini", hini.value);
        http.append("hfin", hfin.value);
        http.append("tipo", tipo.value);
        http.append("desde", desde.value);
        http.append("hasta", hasta.value);
        http.append("dias", arrDias)
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_indicador.php");
        request.send(http);
        request.onreadystatechange = function () {
            // console.log(request);
            if (request.readyState != 4)
                return;
            if (request.status === 200) {
             //   console.log(request.responseText);
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                     console.log( resultado.sql );
                    swal("Advertencia!", resultado.message, "warning").then((value) => {
                        deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
                    });
                    return;
                }
                swal("Excelente!", resultado.message, "success").then((value) => {
                    deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
                    window.location.reload();
                });
            }
        };
    } else {
        if (hini.value === "") {
            hini.classList.add("is-invalid");
        } else {
            hini.classList.remove("is-invalid");
        }
        if (hfin.value === "") {
            hfin.classList.add("is-invalid");
        } else {
            hfin.classList.remove("is-invalid");
        }
        if (desde.value === "") {
            desde.classList.add("is-invalid");
        } else {
            desde.classList.remove("is-invalid");
        }
        if (hasta.value === "") {
            hasta.classList.add("is-invalid");
        } else {
            hasta.classList.remove("is-invalid");
        }
        if (tipo.value === "") {
            tipo.parentNode.classList.add('has-error');
        } else {
            tipo.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");

    }
}

function ModificarProgramacion() {
    codigo = document.getElementById("codigo");
    hini = document.getElementById('hini');
    hfin = document.getElementById('hfin');
    observacion = document.getElementById('observacion');
    fecha = document.getElementById("fecha");

    if (hini.value != "" && hfin.value != "" && codigo.value != "" && fecha.value != "") {

        // Nuevo esquema
        /////////// POST /////////
        var boton = document.getElementById("btn-modificar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "modificar_programacion");
        http.append("codigo", codigo.value);
        http.append("fecha", fecha.value);
        http.append("observacion", observacion.value);
        http.append("hini", hini.value);
        http.append("hfin", hfin.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_indicador.php");
        request.send(http);
        request.onreadystatechange = function () {
          //  console.log(request);
            if (request.readyState != 4) return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    //console.log( resultado.sql );
                    //swal("Informaci\u00F3n", resultado.message, "info");
                    return;
                }
                swal("Excelente!", "Programacion modificada satisfactoriamente!!!", "success").then((value) => {
                    window.history.back();
                });
            }
        };
    } else {
        if (hini.value === "") {
            hini.classList.add("form-danger");
            hini.classList.remove("form-control");
        } else {
            hini.classList.add("form-control");
            hini.classList.remove("form-danger");
        }
        if (hfin.value === "") {
            hfin.classList.add("form-danger");
            hfin.classList.remove("form-control");
        } else {
            hfin.classList.add("form-control");
            hfin.classList.remove("form-danger");
        }
        if (fecha.value === "") {
            fecha.classList.add("form-danger");
            fecha.classList.remove("form-control");
        } else {
            fecha.classList.add("form-control");
            fecha.classList.remove("form-danger");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function deshabilitarProgramacion(codigo) {
    swal({
        text: "\u00BFDesea quitar esta programacion del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
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
                cambioSituacionProgramacion(codigo, 0);
                break;
            default:
                return;
        }
    });
}

function cambioSituacionProgramacion(codigo, situacion) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion_programacion");
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

function seleccionarProgramacion(hashkey) {
    window.location.href = "FRMmodhorario.php?hashkey=" + hashkey;
}

function printTableProgramacion(codigo) {
    indicador = document.getElementById("indicador");
    tipo = document.getElementById("tipo");
    desde = document.getElementById("desde");
    hasta = document.getElementById('hasta');

    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla_programacion");
    http.append("codigo", codigo);
    http.append("indicador", indicador.value);
    http.append("tipo", tipo.value);
    http.append("desde", desde.value);
    http.append("hasta", hasta.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_indicador.php");
    request.send(http);
    request.onreadystatechange = function () {
        // console.log(request);
        if (request.readyState != 4) return;
        if (request.status === 200) {
        //console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado );
                contenedor.innerHTML = '...';
                console.log(resultado.message);
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
                    title: 'Tabla de Programacion'
                },
                {
                    extend: 'pdf',
                    title: 'Tabla de Programacion'
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
                    title: 'Tabla de Programacion'
                }
                ]
            });
        }
    };
}

function tipoProgramacion() {
    contenedorSemana = document.getElementById('containerSemana');
    contenedorMes = document.getElementById('containerMes');
    tipo = document.getElementById('tipo').value;

    if (tipo === 'M') {
        contenedorSemana.style.display = 'none';
        contenedorMes.style.display = 'block';
    } else if (tipo === 'W') {
        contenedorSemana.style.display = 'block';
        contenedorMes.style.display = 'none';
    }
    else {
        contenedorSemana.style.display = 'none';
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


function cambiaTipo() {
    rangos = document.getElementById("rangos");
    tipo = document.getElementById("tipo");
    containerSemana = document.getElementById("semanal");
    switch (tipo.value) {
        case "W":
           // rangos.classList.remove("hidden");
            inicio.innerHTML = "";
            fin.innerHTML = "";
            // --
            var option = document.createElement("option");
            var option2 = document.createElement("option");
            option.value = 1;
            option.innerHTML = "Lunes";
            option2.value = 5;
            option2.innerHTML = "Viernes";
            $('#inicio').append(option).trigger('change');
            $('#fin').append(option2).trigger('change');
            // --
            var option = document.createElement("option");
            var option2 = document.createElement("option");
            option.value = 2;
            option.innerHTML = "Martes";
            option2.value = 2;
            option2.innerHTML = "Martes";
            $('#inicio').append(option).trigger('change');
            $('#fin').append(option2).trigger('change');
            // --
            var option = document.createElement("option");
            var option2 = document.createElement("option");
            option.value = 3;
            option.innerHTML = "Miercoles";
            option2.value = 3;
            option2.innerHTML = "Miercoles";
            $('#inicio').append(option).trigger('change');
            $('#fin').append(option2).trigger('change');
            // --
            var option = document.createElement("option");
            var option2 = document.createElement("option");
            option.value = 4;
            option.innerHTML = "Jueves";
            option2.value = 4;
            option2.innerHTML = "Jueves";
            $('#inicio').append(option).trigger('change');
            $('#fin').append(option2).trigger('change');
            // --
            var option = document.createElement("option");
            var option2 = document.createElement("option");
            option.value = 5;
            option.innerHTML = "Viernes";
            option2.value = 5;
            option2.innerHTML = "Viernes";
            $('#inicio').append(option).trigger('change');
            $('#fin').append(option2).trigger('change');
            // --
            var option = document.createElement("option");
            var option2 = document.createElement("option");
            option.value = 6;
            option.innerHTML = "Sabado";
            option2.value = 6;
            option2.innerHTML = "Sabado";
            $('#inicio').append(option).trigger('change');
            $('#fin').append(option2).trigger('change');
            // --
            var option = document.createElement("option");
            var option2 = document.createElement("option");
            option.value = 7;
            option.innerHTML = "Domingo";
            option2.value = 7;
            option2.innerHTML = "Domingo";
            $('#inicio').append(option).trigger('change');
            $('#fin').append(option2).trigger('change');
            containerSemana.classList.add("hidden");
            break;
        case 'D':
            containerSemana.classList.remove("hidden");
            rangos.classList.add("hidden");
        break;
        case "M":
            containerSemana.classList.add("hidden");
            hasta  = document.getElementById("hasta");
           // rangos.classList.remove("hidden");
            inicio.innerHTML = "";
            fin.innerHTML = "";
            
            var date = new Date();
            var ultimoDia = new Date(date.getFullYear(), date.getMonth() + 1, 0);
            const anioActual = date.getFullYear();

            hasta.value = '31/12/' +anioActual;
            
            for (i = 1; i <= 31; i++) {
                var option = document.createElement("option");
                var option2 = document.createElement("option");
                option.value = i;
                option.innerHTML = "D\u00EDa " + i;
                option2.value = ultimoDia.getDate();
                option2.innerHTML = "D\u00EDa " + ultimoDia.getDate();
                $('#inicio').append(option).trigger('change');
                $('#fin').append(option2).trigger('change');
            }
            break;
        default:
            containerSemana.classList.add("hidden");
            rangos.classList.add("hidden");
            inicio.innerHTML = "";
            fin.innerHTML = "";
            break;
    }
    tipo.focus();
    // $(".select2").select2();
}