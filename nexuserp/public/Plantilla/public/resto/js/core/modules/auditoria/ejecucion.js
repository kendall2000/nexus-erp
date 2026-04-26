//funciones javascript y validaciones
$(document).ready(function () {
    $(".select2").select2();
});

function Limpiar() {
    swal({
        text: "\u00BFDesea Limpiar la p\u00E1gina?, si a\u00FAn no a grabado perdera los datos escritos...",
        icon: "info",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, },
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

function Submit() {
    myform = document.forms.f1;
    myform.submit();
}

function responderAplica(auditoria, pregunta, ejecucion, seccion, tipo) {
    //Obstiene los elementos aplica (aplica | no aplica), respuesta (valor) y Observaciones
    var aplica = document.getElementById('aplica' + pregunta);
    var peso = document.getElementById('peso' + pregunta);
    var respuesta = document.getElementById('respuesta' + pregunta);
    var observacion = document.getElementById('observacion' + pregunta);
    //valida si aplica o no aplica
    var aplicacion = aplica.className;
    aplicacion = aplicacion.replace("btn btn-warning active", 2); // revisa la clase del boton, la convierte a numero pero cambiada (inversa)
    aplicacion = aplicacion.replace("btn btn-warning", 1); // revisa la clase del boton, la convierte a numero pero cambiada (inversa)
    aplicacion = parseInt(aplicacion);
    var texto = (aplicacion === 1) ? "Aplica" : "No Aplica";
    aplica.innerHTML = texto;
    //si es una pregunta 1-10
    if (tipo === '1') {
        var combo = document.getElementById('combo' + pregunta);
        //si la pregunta aplica habilita campos
        if (aplicacion === 1) {
            combo.value = "0";
            respuesta.value = "0";
            observacion.value = "";
            combo.removeAttribute("disabled");
            observacion.removeAttribute("disabled");
        } else { //si la pregunta no aplica deshabilita campos
            combo.value = "0";
            respuesta.value = "0";
            observacion.value = "";
            combo.setAttribute("disabled", "disabled");
            observacion.setAttribute("disabled", "disabled");
        }
    } else { //si es una pregunta SI - NO o SAT - NO SAT
        var labelSI = document.getElementById('labelSI' + pregunta);
        var labelNO = document.getElementById('labelNO' + pregunta);
        //console.log("aplicacion ",aplicacion);
        //si la pregunta aplica habilita campos
        if (aplicacion === 1) {
            observacion.value = "";
            labelSI.removeAttribute("disabled");
            labelNO.removeAttribute("disabled");
            observacion.removeAttribute("disabled");
        } else { //si la pregunta no aplica deshabilita campos
            labelSI.className = "btn btn-white";
            labelNO.className = "btn btn-white";
            respuesta.value = "0";
            observacion.value = "";
            labelSI.setAttribute("disabled", "disabled");
            labelNO.setAttribute("disabled", "disabled");
            observacion.setAttribute("disabled", "disabled");
        }
    }
    //console.log(auditoria,pregunta,ejecucion,seccion,tipo,peso.value,aplicacion,respuesta.value);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "responderPonderacion");
    http.append("auditoria", auditoria);
    http.append("pregunta", pregunta);
    http.append("ejecucion", ejecucion);
    http.append("seccion", seccion);
    http.append("tipo", tipo);
    http.append("peso", peso.value);
    http.append("aplica", aplicacion);
    http.append("respuesta", respuesta.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecutar.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            //console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                swal("Error", resultado.message, "error").then((value) => {
                    console.log(value);
                });
                return;
            }
            console.log(resultado.sql);
            console.log(resultado.message);
            return;
        }
    };
}

function responderPonderacion(auditoria, pregunta, ejecucion, seccion, tipo, respuesta) {
    var aplica = document.getElementById('aplica' + pregunta);
    var peso = document.getElementById('peso' + pregunta).value;
    document.getElementById('respuesta' + pregunta).value = respuesta;
    //valida si aplica o no aplica
    var aplicacion = aplica.className;
    aplicacion = aplicacion.replace("btn btn-warning active", "1"); // revisa la clase del boton, la convierte a numero
    aplicacion = aplicacion.replace("btn btn-warning", "2"); // revisa la clase del boton, la convierte a numero
    //
    //console.log(auditoria,pregunta,ejecucion,seccion,tipo,peso,aplicacion,respuesta);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "responderPonderacion");
    http.append("auditoria", auditoria);
    http.append("pregunta", pregunta);
    http.append("ejecucion", ejecucion);
    http.append("seccion", seccion);
    http.append("tipo", tipo);
    http.append("peso", peso);
    http.append("aplica", aplicacion);
    http.append("respuesta", respuesta);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecutar.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado.sql );
                swal("Error", resultado.message, "error").then((value) => {
                    console.log(value);
                });
                return;
            }
            console.log(resultado.message);
            return;
        }
    };
}

function responderTexto(auditoria, pregunta, ejecucion, seccion, observacion) {
    //console.log(auditoria,pregunta,ejecucion,observacion);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "responderTexto");
    http.append("auditoria", auditoria);
    http.append("pregunta", pregunta);
    http.append("ejecucion", ejecucion);
    http.append("seccion", seccion);
    http.append("observacion", observacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecutar.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado.sql );
                swal("Error", resultado.message, "error").then((value) => {
                    console.log(value);
                });
                return;
            }
            console.log(resultado.sql);
            console.log(resultado.message);
            return;
        }
    };
}

function depObservaciones(ejecucion, departamento, observacion) {
    console.log(ejecucion, departamento, observacion);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "observacion");
    http.append("ejecucion", ejecucion);
    http.append("departamento", departamento);
    http.append("observacion", observacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecutar.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado.sql );
                swal("Error", resultado.message, "error").then((value) => {
                    console.log(value);
                });
                return;
            }
            console.log(resultado.message);
            return;
        }
    };
}

function cerrarEjecucion() {
    pendientes = parseFloat(document.getElementById("pendientes").value);
    ejecucion = document.getElementById("ejecucion");
    nota = document.getElementById("nota");
    correos = document.getElementById("correos");
    responsable = document.getElementById("responsable");
    obs = document.getElementById("obs");
    if (pendientes <= 0) {
        if (correos.value !== "" && responsable.value !== "") {
            /////////// POST /////////
            var boton = document.getElementById("btn-cerrar");
            loadingBtn(boton);
            var http = new FormData();
            http.append("request", "cerrar");
            http.append("ejecucion", ejecucion.value);
            http.append("nota", nota.value);
            http.append("correos", correos.value);
            http.append("responsable", responsable.value);
            http.append("obs", obs.value);
            var request = new XMLHttpRequest();
            request.open("POST", "ajax_fns_ejecutar.php");
            request.send(http);
            request.onreadystatechange = function () {
                //console.log( request );
                if (request.readyState != 4)
                    return;
                if (request.status === 200) {
                    resultado = JSON.parse(request.responseText);
                    if (resultado.status !== true) {
                        //console.log( resultado.sql );
                        swal("Error", resultado.message, "error").then((value) => {
                            console.log(value);
                            deloadingBtn(boton, '<i class="fa fa-folder"></i> Cerrar');
                        });
                        return;
                    }
                    //console.log( resultado );
                    swal("Excelente!", resultado.message, "success").then((value) => {
                        console.log(value);
                        window.location.href = "FRMejecutar.php";
                    });
                }
            };
        } else {
            if (correos.value === "") {
                correos.classList.add("is-invalid");
            } else {
                correos.classList.remove("is-invalid");
            }
            if (responsable.value === "") {
                responsable.classList.add("is-invalid");
            } else {
                responsable.classList.remove("is-invalid");
            }
            swal("Alto!", "Indique el nombre del encargado o responsable evaluado...", "error");
        }
    } else {
        swal("A\u00FAn no termina", "Tiene preguntas pendientes...", "warning");
    }
}


function FotoJs(pregunta) {
    inpfile = document.getElementById("imagen");
    inpfile.click();
    document.getElementById("pregunta").value = pregunta;
}


async function subirImagen() {
    auditoria = document.getElementById("auditoria").value;
    ejecucion = document.getElementById("ejecucion").value;
    pregunta = document.getElementById("pregunta").value;
    archivo = document.getElementById("imagen");
    return new Promise((resolve, reject) => {
        /////////// POST /////////
        var http = new FormData();
        http.append("auditoria", auditoria);
        http.append("ejecucion", ejecucion);
        http.append("pregunta", pregunta);
        http.append("imagen", archivo.files[0]);

        var request = new XMLHttpRequest();
        request.open("POST", "EXEcarga_foto.php");
        request.onload = () => {
            if (request.status >= 200 && request.status < 300) {
                //console.log(request.response);
                resolve(JSON.parse(request.response));
            } else {
                reject(request.statusText);
            }
        };
        request.onerror = () => reject(request.statusText);
        request.send(http);
    });
}

async function cargarFoto() {
    auditoria = document.getElementById("auditoria").value;
    ejecucion = document.getElementById("ejecucion").value;
    pregunta = document.getElementById("pregunta").value;
    archivo = document.getElementById("imagen");
    if (auditoria.value !== "" && ejecucion.value !== "" && pregunta.value != "" && archivo.value !== "") {
        extension = comprueba_extension(archivo.value, 1);
        if (extension === 1) {
            /////////// POST /////////
            var boton = document.getElementById("btn-foto");
            var contenedor = document.getElementById("div-imagen" + pregunta);
            //loadingBtn(boton);
            loadingDiv(contenedor);
            let resultImg = await subirImagen();
            if (resultImg.status) {
            
            var arrimagenes = resultImg.img;
            console.log(resultImg);
            var imagenes = '';
            arrimagenes.forEach(function (element) {
                //console.log(element.foto);
                imagenes += element.foto;
            });
                document.getElementById("div-imagen" + pregunta).innerHTML = imagenes;
            } else {
                swal("Error", resultImg.message, "error").then((value) => {
                    console.log(value);
                    window.location.reload();
                });
                return;
            }
        } else {
            swal("Alto!", "Este archivo no es extencion .jpg \u00F3 .png", "error");
        }
    } else {
        swal("Error", "Uno de los parametros est\u00E1 vacio, por favor refreseque e intente de nuevo...", "error");
    }
}

//function uploadImage() {
//    myform = document.forms.f1;
//    auditoria = document.getElementById("auditoria").value;
//    ejecucion = document.getElementById("ejecucion").value;
//    pregunta = document.getElementById("pregunta").value;
//    archivo = document.getElementById("imagen");
//    if (archivo.value) {
//        if (archivo.value !== "") {
//            exdoc = comprueba_extension(archivo.value, 1);
//            if (exdoc !== 1) {
//                swal("Alto!", "Este archivo no es extencion .jpg \u00F3 .png", "error");
//                return;
//            }
//        }
//    } else {
//        swal("Alto!", "El archivo viene vacio...", "error");
//        return;
//    }
//    var observacion;
//    loadingGif(pregunta); //coloca un gif cargando en la imagen
//
//    var formData = new FormData(myform);
//    formData.append("auditoria", auditoria);
//    formData.append("ejecucion", ejecucion);
//    formData.append("pregunta", pregunta);
//    formData.append("imagen", archivo.files[0]);
//    var request = new XMLHttpRequest();
//    request.open("POST", "EXEcarga_foto.php");
//    request.send(formData);
//    request.onreadystatechange = function () {
//        if (request.readyState != 4)
//            return;
//        //alert(request.status);
//        if (request.status === 200) {
//            //alert("Status: " + request.status + " | Respuesta: " + request.responseText);
//            //console.log(request.responseText);
//            resultado = JSON.parse(request.responseText);
//            //alert(resultado.status + ", " + resultado.message + ", " + resultado.img);
//            //console.log(resultado);
//            if (resultado.status !== 1) {
//                swal("Error en la carga", resultado.message, "error");
//                return;
//            }
//            var arrimagenes = resultado.img;
//            var imagenes = '';
//            arrimagenes.forEach(function (element) {
//                //console.log(element.foto);
//                imagenes += element.foto;
//            });
//            document.getElementById("foto" + pregunta).innerHTML = imagenes;
//        } else {
//            //alert("Error: " + request.status + " " + request.responseText);
//            swal("Error en la carga", "Error en la carga de la imagen", "error");
//            return;
//        }
//    };
//
//}

function deleteFotoConfirm(codigo, auditoria, pregunta, ejecucion) {
    swal({
        title: "\u00BFDesea Eliminar la Foto?",
        text: "\u00BFEsta seguro(a) de eliminar esta imagen del archivo de auditor\u00EDa?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                deleteFoto(codigo, auditoria, pregunta, ejecucion);
                break;
            default:
                return;
        }
    });
}

function deleteFoto(codigo, auditoria, pregunta, ejecucion) {

    loadingGif(pregunta); //coloca un gif cargando en la imagen
    myform = document.forms.f1;
    var formData = new FormData(myform);
    formData.append("codigo", codigo);
    formData.append("auditoria", auditoria);
    formData.append("ejecucion", ejecucion);
    formData.append("pregunta", pregunta);
    var request = new XMLHttpRequest();
    request.open("POST", "EXEdelete_foto.php");
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState != 4)
            return;
        //alert(request.status);
        if (request.status === 200) {
            //alert("Status: " + request.status + " | Respuesta: " + request.responseText);
            //console.log(request.responseText);
            resultado = JSON.parse(request.responseText);
            //alert(resultado.status + ", " + resultado.message + ", " + resultado.img);
            //console.log(resultado);
            if (resultado.status !== 1) {
                swal("Error en la transacci\u00F3n", resultado.message, "error");
                return;
            }
            var arrimagenes = resultado.img;
            var imagenes = '';
            arrimagenes.forEach(function (element) {
                //console.log(element.foto);
                imagenes += element.foto;
            });
            document.getElementById("div-imagen" + pregunta).innerHTML = imagenes;
        } else {
            //alert("Error: " + request.status + " " + request.responseText);
            swal("Error en la carga", "Error en la carga de la imagen", "error");
            return;
        }
    };
    cerrar();
}

function validaFoto(pregunta, file) {
    if (file.value !== "") {
        exdoc = comprueba_extension(file.value, 1);
        if (exdoc !== 1) {
            swal("Alto!", "Este archivo no es extencion .jpg \u00F3 .png", "error");
        } else {
            uploadImage();
        }
    }
}

function loadingGif(pregunta) {
    document.getElementById("div-imagen" + pregunta).innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
}

function loadingButton(elemento) {
    elemento.setAttribute("disabled", "disabled");
    elemento.innerHTML = '<img src="../../CONFIG/img/img-loader.gif" width="15px" alt="cargando...">';
}

function menuFoto(codigo, auditoria, pregunta, ejecucion) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/auditoria/menu_foto.php", {codigo: codigo, auditoria: auditoria, pregunta: pregunta, ejecucion: ejecucion}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#lblparrafo").html(data);
    });
    abrir();
}

function verFoto(codigo, auditoria, pregunta, ejecucion) {
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/auditoria/imagen.php", {codigo: codigo, auditoria: auditoria, pregunta: pregunta, ejecucion: ejecucion}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#lblparrafo").html(data);
    });
}



function solicitarAprobacion(ejecucion) {
    swal({
        title: "\u00BFSolicitar Aprobaci\u00F3n?",
        text: "\u00BFEsta seguro(a) de solicitar aprobaci\u00F3n de este formulario de auditor\u00EDa?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                cambioSituacion(ejecucion, 3, 'Solicitando se revise y apruebe...');
                break;
            default:
                return;
        }
    });
}

function cambioSituacion(ejecucion, situacion, obs) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion");
    http.append("ejecucion", ejecucion);
    http.append("situacion", situacion);
    http.append("obs", obs);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecutar.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado.sql );
                swal("Error", resultado.message, "error");
                return;
            }
            if (situacion == 4 || situacion == 5) {
                swal("Excelente!", "Cambio de situaci\u00F3n realizado satisfactoriamente...", "success").then((value) => {
                    console.log(value);
                    window.location.href = "FRMaprobaciones.php";
                });
            } else {
                swal("Excelente!", "Cambio de situaci\u00F3n realizado satisfactoriamente...", "success").then((value) => {
                    console.log(value);
                    window.location.reload();
                });
            }
        }
    };
}
	