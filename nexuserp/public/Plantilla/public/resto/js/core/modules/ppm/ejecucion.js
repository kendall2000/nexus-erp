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

function seleccionarProgramacion(hashkey) {
    window.location.href = "FRMejecutar.php?hashkey=" + hashkey;
}

function verProgramacion(hashkey) {
    window.location.href = "FRMrevision.php?hashkey=" + hashkey;
}

function FirmaJs(codigo) {
    window.location.href = "FRMfirma.php?codigo=" + codigo;
}


function update(elemento, campo) {
    codigo = document.getElementById("codigo");
    var http = new FormData();
    http.append("request", "update");
    http.append("codigo", codigo.value);
    http.append("campo", campo);
    http.append("valor", elemento.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecucion.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log(request.readyState);
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            console.log(request.responseText);
            resultado = JSON.parse(request.responseText);
            //console.log(resultado);
            if (resultado.status !== true) {
                console.log(resultado.message);
                return;
            }
            console.log(resultado.message);
        }
    };
}

function responder(programacion, cuestionario, pregunta, respuesta) {
    /////////// POST ////////////
    var http = new FormData();
    http.append("request", "responder_pregunta");
    http.append("programacion", programacion);
    http.append("cuestionario", cuestionario);
    http.append("pregunta", pregunta);
    http.append("respuesta", respuesta);

    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecucion.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log(request);
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                swal("Error", resultado.message, "error").then((value) => {
                });
                return;
            }

        }
    };
}

function enEspera() {
    codigo = document.getElementById("codigo");
    observacion = document.getElementById("obs");
    if (codigo.value != "") {
        /////////// POST ////////////
        var http = new FormData();
        http.append("request", "situacion_programacion");
        http.append("codigo", codigo.value);
        http.append("situacion", 2);
        http.append("observacion", observacion.value);

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ejecucion.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log(request);
            if (request.readyState != 4)
                return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    swal("Error", resultado.message, "error").then((value) => {
                        window.location.reload();
                    });
                    return;
                }
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.href = "FRMejecucion.php";
                });
            }
        };
    }
}

function enProceso() {
    codigo = document.getElementById("codigo");
    observacion = document.getElementById("obs");
    if (codigo.value != "") {
        /////////// POST ////////////
        var http = new FormData();
        http.append("request", "situacion_programacion");
        http.append("codigo", codigo.value);
        http.append("situacion", 3);
        http.append("observacion", observacion.value);

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ejecucion.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log(request);
            if (request.readyState != 4)
                return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    swal("Error", resultado.message, "error").then((value) => {
                        window.location.reload();
                    });
                    return;
                }
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.href = "FRMejecucion.php";
                });
            }
        };
    }
}

function cerrarProgramacion() {
    codigo = document.getElementById("codigo");
    observacion = document.getElementById("observacion");
    if (codigo.value != "") {
        swal({
            text: "\u00BFDesea cerrar esta actividad de mantenimiento programada?, no prodr\u00E1 ser modificada despu\u00E9s...",
            icon: "warning",
            buttons: {
                cancel: "Cancelar",
                ok: {text: "Aceptar", value: true, },
            }
        }).then((value) => {
            switch (value) {
                case true:
                    codigo = document.getElementById("codigo");
                    observacion = document.getElementById("obs");
                    if (codigo.value != "") {
                        /////////// POST ////////////
                        var http = new FormData();
                        http.append("request", "situacion_programacion");
                        http.append("codigo", codigo.value);
                        http.append("situacion", 4);
                        http.append("observacion", observacion.value);

                        var request = new XMLHttpRequest();
                        request.open("POST", "ajax_fns_ejecucion.php");
                        request.send(http);
                        request.onreadystatechange = function () {
                            //console.log(request);
                            if (request.readyState != 4)
                                return;
                            if (request.status === 200) {
                                resultado = JSON.parse(request.responseText);
                                if (resultado.status !== true) {
                                    swal("Error", resultado.message, "error").then((value) => {
                                        window.location.reload();
                                    });
                                    return;
                                }
                                swal("Excelente!", resultado.message, "success").then((value) => {
                                    window.location.href = "FRMejecucion.php";
                                });
                            }
                        };
                    }
                    break;
                default:
                    return;
            }
        });
    }
}

//////////////////////////////////////////////////////////////////

function FotoJs(codigo, posicion) {
    inpfile = document.getElementById("foto");
    inpfile.click();
    document.getElementById("codigo").value = codigo;
    document.getElementById("posicion").value = posicion;
}


async function subirImagen() {
    activo = document.getElementById("codigo");
    foto = document.getElementById("foto");
    posicion = document.getElementById("posicion");
    return new Promise((resolve, reject) => {
        /////////// POST /////////
        var http = new FormData();

        http.append("activo", activo.value);
        http.append("posicion", posicion.value);
        http.append("imagen", foto.files[0]);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_cargar_imagen.php");
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
    activo = document.getElementById("codigo");
    posicion = document.getElementById("posicion");
    foto = document.getElementById("foto");
    if (activo.value !== "" && posicion.value !== "") {
        extension = comprueba_extension(foto.value, 1);
        if (extension === 1) {
            /////////// POST /////////
            var boton = document.getElementById("btn-foto" + posicion.value);
            
            var contenedor = document.getElementById("div-imagen" + posicion.value);
            loadingBtn(boton);
            loadingDiv(contenedor);
            let resultImg = await subirImagen();
            if (resultImg.status) {
                deloadingBtn(boton, '<i class="fa fa-camera"></i> Agregar Imagen');
                deloadingDiv(contenedor, resultImg.imagen);
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



function deleteFotoConfirm(codigo, posicion) {
    swal({
        title: "\u00BFDesea Eliminar la Foto?",
        text: "\u00BFEsta seguro(a) de eliminar esta foto?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                deleteFoto(codigo, posicion);
                break;
            default:
                return;
        }
    });
}

function deleteFoto(codigo, posicion) {

    loadingGif(posicion); //coloca un gif cargando en la imagen
    myform = document.forms.f1;
    var formData = new FormData(myform);
    formData.append("codigo", codigo);
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
            document.getElementById("foto" + posicion).innerHTML = imagenes;
        } else {
            //alert("Error: " + request.status + " " + request.responseText);
            swal("Error en la carga", "Error en la carga de la imagen", "error");
            return;
        }
    };
    cerrar();
}

function loadingGif(posicion) {
    document.getElementById("foto" + posicion).innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
}

function loadingButton(elemento) {
    elemento.setAttribute("disabled", "disabled");
    elemento.innerHTML = '<img src="../../CONFIG/img/img-loader.gif" width="15px" alt="cargando...">';
}

function ejecutarPresupuesto(programacion) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/ppm/presupuesto.php", {codigo: programacion}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}

function grabarPresupuesto() {
    codigo = document.getElementById('codigo1');
    presupuesto = document.getElementById("presupuesto2");
    observacion = document.getElementById('obs1');
    ///
    if (presupuesto.value !== "") {
        var boton = document.getElementById("btn-presupuesto");
        loadingBtn(boton);
        /////////// POST ////////////
        var http = new FormData();
        http.append("request", "presupuesto");
        http.append("codigo", codigo.value);
        http.append("presupuesto", presupuesto.value);
        http.append("observacion", observacion.value);

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ejecucion.php");
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
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.reload();
                });
            }
        };
    } else {
        if (presupuesto.value === "") {
            presupuesto.classList.add("is-invalid");
        } else {
            presupuesto.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

