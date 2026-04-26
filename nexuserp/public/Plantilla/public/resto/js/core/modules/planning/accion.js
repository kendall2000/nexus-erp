
$(document).ready(function () {
    $(".select2").select2({ width: '100%' });
});

function Submit() {
    myform = document.forms.f1;
    myform.submit();
}

function Grabar() {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    //-
    desde = document.getElementById('desde');
    hasta = document.getElementById('hasta');
    descripcion = document.getElementById('descripcion');
    nombre = document.getElementById('nombre');
    tipo = document.getElementById('tipo');
    presupuesto = document.getElementById('presupuesto');
    objetivo = document.getElementById('objetivo');
    inicio = document.getElementById('inicio');
    fin = document.getElementById('fin');

    // Dias
    if (descripcion.value != "" && nombre.value != "" && tipo.value != "" && objetivo.value != "" && presupuesto.value != "") {
        // Nuevo esquema
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "grabar");
        http.append("descripcion", descripcion.value);
        http.append("nombre", nombre.value);
        if ((tipo.value == 'M' || tipo.value == 'W')) {
            http.append("inicio", inicio.value);
            http.append("fin", fin.value);
        }
        http.append("tipo", tipo.value);
        http.append("presupuesto", presupuesto.value);
        http.append("desde", desde.value);
        http.append("hasta", hasta.value);
        http.append("objetivo", objetivo.value);

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_planning.php");
        request.send(http);
        request.onreadystatechange = function () {
            // console.log(request);
            if (request.readyState != 4)
                return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    // console.log( resultado.sql );
                    swal("Advertencia!", resultado.message, "warning").then((value) => {
                        deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
                    });
                    return;
                }
                swal("Excelente!", resultado.message, "success").then((value) => {
                    deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
                    //contenedor.innerHTML = resultado.data;
                    nombre.value = "";
                    descripcion.value = "";
                    presupuesto.value = "";
                    document.getElementById("tipo").value = "U";
                    cambiaTipo();
                    printTable();
                });
            }
        };
    } else {
        if (descripcion.value === "") {
            descripcion.classList.add("is-invalid");
        } else {
            descripcion.classList.remove("is-invalid");
        }
        if (nombre.value === "") {
            nombre.classList.add("is-invalid");
        } else {
            nombre.classList.remove("is-invalid");
        }
        if (presupuesto.value === "") {
            presupuesto.classList.add("is-invalid");
        } else {
            presupuesto.classList.remove("is-invalid");
        }
        if (tipo.value === "") {
            tipo.parentNode.classList.add('has-error');
        } else {
            tipo.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
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
    request.open("POST", "ajax_fns_planning.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
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
            document.getElementById("desde").value = data.fini;
            document.getElementById("hasta").value = data.ffin;
            document.getElementById("descripcion").value = data.descripcion;
            document.getElementById("nombre").value = data.nombre;
            document.getElementById("tipo").value = data.tipo;
            document.getElementById("presupuesto").value = data.presupuesto;
            cambiaTipo();

            //tabla
            var tabla = resultado.tabla;
            contenedor.innerHTML = tabla;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true
            });
            // $(".select2").select2();
            //botones
            document.getElementById("nombre").focus();
            document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
            document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
            //--
        }
    };
}

function Modificar() {
    codigo = document.getElementById('codigo');
    hoy = document.getElementById('hoy');
    desde = document.getElementById('desde');
    hasta = document.getElementById('hasta');
    descripcion = document.getElementById('descripcion');
    nombre = document.getElementById('nombre');
    tipo = document.getElementById('tipo');
    presupuesto = document.getElementById('presupuesto');
    objetivo = document.getElementById('objetivo');
    inicio = document.getElementById('inicio');
    fin = document.getElementById('fin');
    if (codigo.value !== "" && descripcion.value != "" && nombre.value != "" && tipo.value != "" && objetivo.value != "" && presupuesto.value != "") {
        /////////// POST /////////
        var boton = document.getElementById("btn-modificar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "modificar");
        http.append("codigo", codigo.value);
        http.append("descripcion", descripcion.value);
        http.append("nombre", nombre.value);
        if ((tipo.value == 'M' || tipo.value == 'W')) {
            http.append("inicio", inicio.value);
            http.append("fin", fin.value);
        }
        http.append("tipo", tipo.value);
        http.append("presupuesto", presupuesto.value);
        http.append("desde", desde.value);
        http.append("hasta", hasta.value);
        http.append("objetivo", objetivo.value);

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_planning.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log(request);
            if (request.readyState != 4)
                return;
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
                    deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
                    document.getElementById("btn-grabar").className = "btn btn-primary btn-sm";
                    document.getElementById("btn-modificar").className = "btn btn-primary btn-sm hidden";
                    contenedor.innerHTML = resultado.data;
                    nombre.value = "";
                    descripcion.value = "";
                    presupuesto.value = "";
                    document.getElementById("tipo").value = "U";
                    cambiaTipo();
                });
            }
        };
    } else {
        if (descripcion.value === "") {
            descripcion.classList.add("is-invalid");
        } else {
            descripcion.classList.remove("is-invalid");
        }
        if (nombre.value === "") {
            nombre.classList.add("is-invalid");
        } else {
            nombre.classList.remove("is-invalid");
        }
        if (presupuesto.value === "") {
            presupuesto.classList.add("is-invalid");
        } else {
            presupuesto.classList.remove("is-invalid");
        }
        if (tipo.value === "") {
            tipo.parentNode.classList.add('has-error');
        } else {
            tipo.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function quitarElemento(codigo) {
    swal({
        title: "Eliminar Accion",
        text: "\u00BFEsta seguro de quitar esta Accion?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, },
        }
    }).then((value) => {
        switch (value) {
            case true:
                deleteElemento(codigo);
                break;
            default:
                return;
        }
    });
}

function deleteElemento(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);

    var http = new FormData();
    http.append("request", "delete");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_planning.php");
    request.send(http);
    request.onreadystatechange = function () {
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            //    console.log(request.responseText);
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            swal("Excelente!", resultado.message, "success");
            printTable();
        }
    };
}


function solicitarAprobacion(codigo, objetivo) {
    swal({
        title: "Solicitar Aprobacion",
        text: "\u00BFEsta seguro de solicitar la aprobacion de este objetivo?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, },
        }
    }).then((value) => {
        switch (value) {
            case true:
                aprobar(codigo, objetivo);
                break;
            default:
                return;
        }
    });
}

function aprobar(codigo, objetivo) {
    var http = new FormData();
    http.append("request", "aprobacion");
    http.append("codigo", codigo);
    http.append("objetivo", objetivo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_planning.php");
    request.send(http);
    request.onreadystatechange = function () {
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            // console.Log(request.responseText);
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            swal("Excelente!", resultado.message, "success").then((value) => {
                 window.location.href = "FRMacciones_objetivo.php";
            });
        }
    };
}
///////////////////// Adicionales //////////////////

function verDetalle(codigo, tipo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/planning/detalle.php", {codigo: codigo, tipo: tipo}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}

function cambiaTipo() {
    rangos = document.getElementById("rangos");
    tipo = document.getElementById("tipo");
    switch (tipo.value) {
        case "W":
            rangos.classList.remove("hidden");
            inicio.innerHTML = "";
            fin.innerHTML = "";
            // --
            var option = document.createElement("option");
            var option2 = document.createElement("option");
            option.value = 1;
            option.innerHTML = "Lunes";
            option2.value = 1;
            option2.innerHTML = "Lunes";
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
            break;
        case "M":
            rangos.classList.remove("hidden");
            inicio.innerHTML = "";
            fin.innerHTML = "";
            for (i = 1; i <= 31; i++) {
                var option = document.createElement("option");
                var option2 = document.createElement("option");
                option.value = i;
                option.innerHTML = "D\u00EDa " + i;
                option2.value = i;
                option2.innerHTML = "D\u00EDa " + i;
                $('#inicio').append(option).trigger('change');
                $('#fin').append(option2).trigger('change');
            }
            break;
        default:
            rangos.classList.add("hidden");
            inicio.innerHTML = "";
            fin.innerHTML = "";
            break;
    }
    tipo.focus();
    // $(".select2").select2();
}


function printTable() {
    contenedor = document.getElementById("result");
    usuario = document.getElementById('usuario');
    codigo = document.getElementById("codigo");
    loadingCogs(contenedor);
    /////////// POST //////////////
    var http = new FormData();
    http.append("request", "tabla_acciones");
    http.append("usuario", usuario.value);    
    http.append("codigo", codigo.value);

    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_planning.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
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
                pageLength: 100,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [
                    {extend: 'copy'},
                    {extend: 'csv'},
                    {extend: 'excel', title: 'Tabla de Acciones'},
                    {extend: 'pdf', title: 'Tabla de Acciones'},
                    {extend: 'print',
                        customize: function (win) {
                            $(win.document.body).addClass('white-bg');
                            $(win.document.body).css('font-size', '10px');
                            $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                        }, title: 'Tabla de Documentos'
                    }
                ]
            });
        }
    };
}






