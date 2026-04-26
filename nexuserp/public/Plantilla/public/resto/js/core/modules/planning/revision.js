function saveComentario(codigo) {
    comentario = document.getElementById("comentario" + codigo);
    var http = new FormData();
    http.append("request", "comentario");
    http.append("codigo", codigo);
    http.append("comentario", comentario.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_planning.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log(request.readyState);
        if (request.readyState != 4) return;
        if (request.status === 200) {
            // console.log(request.responseText);
            resultado = JSON.parse(request.responseText);
            //console.log(resultado);
            if (resultado.status !== true) {
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
        }
    };
}

function saveObservacion() {
    codigo = document.getElementById("codigo");
    observacion = document.getElementById("observaciones");
    var http = new FormData();
    http.append("request", "observacion");
    http.append("codigo", codigo.value);
    http.append("observacion", observacion.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_planning.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log(request.readyState);
        if (request.readyState != 4) return;
        if (request.status === 200) {
            // console.log(request.responseText);
            resultado = JSON.parse(request.responseText);
            //console.log(resultado);
            if (resultado.status !== true) {
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
        }
    };
}

function aprobarObjetivo() {
    codigo = document.getElementById("codigo").value;
    observaciones = document.getElementById("observaciones").value;
    swal({
        title: "\u00BFAPROBAR?",
        text: "\u00BFEsta seguro(a) de aprobar este objetivo?",
        icon: "info",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                cambioSituacion(codigo, 3, observaciones);
                break;
            default:
                return;
        }
    });
}

function verProgramacion(codigo, tipo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/planning/programacion.php", { codigo: codigo, tipo: tipo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}

function cambioSituacion(codigo, situacion, observacion) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion_revision");
    http.append("codigo", codigo);
    http.append("situacion", situacion);
    http.append("observacion", observacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_planning.php");
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
            swal("Excelente!", resultado.message, "success").then((value) => { console.log(value); window.location.href = "FRMaprobacion.php"; });
        }
    };
}

function rechazarObjetivo() {
    codigo = document.getElementById("codigo").value;
    observaciones = document.getElementById("observaciones").value;
    swal({
        title: "\u00BFSOLICITAR CORRECI\u00D3N?",
        text: "\u00BFEsta seguro(a) de rechazar estas acciones para solicitar una correcci\u00F3n?, el usuario asignado deber\u00E1 realizar las correcciones indicadas en esta revisi\u00F3n...",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                cambioSituacion(codigo, 1, observaciones);
                break;
            default:
                return;
        }
    });
}

function verDetalle(codigo, tipo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/planning/detalle.php", { codigo: codigo, tipo: tipo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}