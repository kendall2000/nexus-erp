///////////////////////// Ejecucion ////////////////////

function finalizarEjecucion() {
    swal({
        title: "\u00BFListo?",
        text: "\u00BFEsta seguro(a) de finalizar la ejecucion de esta accion?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                observacion = document.getElementById('observacion');
                codigo = document.getElementById('ejecucion');
                evidencia = document.getElementById('evidencia');
                if (evidencia.value != ""  && codigo.value != "" && observacion.value != "")
                    cambiaSituacion(codigo.value, 2);
                else {
                    if (observacion.value === "") {
                        observacion.classList.add("is-invalid");
                        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
                    } else {
                        observacion.classList.remove("is-invalid");
                    }
                    if (!evidencia)
                        swal("Ohoo!", "Debe subir al menos una evidencia...", "error");
                }
                break;
            default:
                return;
        }
    });
}

function Modificar() {
    observacion = document.getElementById('observacion');
    codigo = document.getElementById('ejecucion');

    if (codigo.value != "" && observacion.value != "") {
        // Nuevo esquema
        /////////// POST /////////
        var http = new FormData();
        http.append("request", "modificar");
        http.append("observacion", observacion.value);
        http.append("codigo", codigo.value);

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ejecucion.php");
        request.send(http);
        request.onreadystatechange = function () {
            // console.log(request);
            if (request.readyState != 4)
                return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    // console.log( resultado.sql );
                    return;
                }
            }
        };
    }
}


function cambiaSituacion(codigo, situacion) { // Codigo de la programacion
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion");
    http.append("codigo", codigo);
    http.append("situacion", situacion);

    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecucion.php");
    request.send(http);
    request.onreadystatechange = function () {
        // console.log(request);
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                // console.log( resultado.sql );
                // swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            swal("Excelente!", resultado.message, "success").then((value) => {
                window.location.href = "FRMejecucion.php";
            });
        }
    };
}


function situacionProgramacion(codigo, situacion) { // Codigo de la programacion
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion_programacion");
    http.append("codigo", codigo);
    http.append("situacion", situacion);

    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecucion.php");
    request.send(http);
    request.onreadystatechange = function () {
        // console.log(request);
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                // console.log( resultado.sql );
                // swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            swal("Excelente!", resultado.message, "success").then((value) => {
                window.location.href = "FRMejecucion.php";
            });
        }
    };
}
function Confirm_Cerrar_Accion(codigo) {
    swal({
        title: "\u00BFListo?",
        text: "\u00BFEsta seguro(a) de cancelar esta ejecucion?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                situacionProgramacion(codigo, 0);
                break;
            default:
                return;
        }
    });
}
///////////////////////// Evaluacion ////////////////////

function finalizarEvaluacion() {
    swal({
        title: "\u00BFListo?",
        text: "\u00BFEsta seguro(a) de finalizar la evaluacion de esta ejecucion?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                GrabarEvaluacion();
                break;
            default:
                return;
        }
    });
}


function GrabarEvaluacion() { // Codigo de la programacion
    observacion = document.getElementById('observacion');
    puntuacion = document.getElementById('puntuacion');
    codigo = document.getElementById('codigo');

    if (codigo.value != "" && observacion.value != "" && puntuacion.value != "") {
        // Nuevo esquema
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "grabar_evaluacion");
        http.append("observacion", observacion.value);
        http.append("puntuacion", puntuacion.value);
        http.append("ejecucion", codigo.value);

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ejecucion.php");
        request.send(http);
        request.onreadystatechange = function () {
            // console.log(request);
            if (request.readyState != 4)
                return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    // console.log( resultado.sql );
                    //swal("Informaci\u00F3n", resultado.message, "info");
                    return;
                }
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.href = "FRMevaluacion.php";
                });
            }
        };
    } else {
        if (observacion.value === "") {
            observacion.classList.add("is-invalid");
        } else {
            observacion.classList.remove("is-invalid");
        }
        if (puntuacion.value === "") {
            puntuacion.classList.add("is-invalid");
        } else {
            puntuacion.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}
////////////////////////////////////////////////////////////////////////////////////
///////////////////////// INSERCION DE FOTOS VIA AJAX //////////////////////////////
////////////////////////////////////////////////////////////////////////////////////

function FotoJs(ejecucion, posicion) {
    inpfile = document.getElementById("imagen");
    inpfile.click();
    document.getElementById("codigo").value = ejecucion;
    document.getElementById("posicion").value = posicion;
}

async function subirImagen() {
    codigo = document.getElementById("codigo");
    foto = document.getElementById("imagen");
    posicion = document.getElementById("posicion");
    return new Promise((resolve, reject) => {
        /////////// POST /////////
        var http = new FormData();
        //http.append("codigo", codigo.value);
        http.append("posicion", posicion.value);
        http.append("ejecucion", codigo.value);
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
    foto = document.getElementById("imagen");
    codigo = document.getElementById("codigo");
    posicion = document.getElementById("posicion");
    evidencia = document.getElementById('evidencia');
    if (codigo.value !== "" && foto.value !== "") {
        extension = comprueba_extension(foto.value, 1);
        if (extension === 1) {
            /////////// POST /////////
            var boton = document.getElementById("btn-foto" + posicion.value);
            var contenedor = document.getElementById("foto" + posicion.value);
            loadingBtn(boton);
            loadingDiv(contenedor);
            let resultImg = await subirImagen();
            if (resultImg.status) {
                deloadingBtn(boton, '<i class="fa fa-camera"></i> Agregar Imagen');
                deloadingDiv(contenedor, resultImg.imagen);
                evidencia.value = true;
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

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////// INSERCION DE DOCUMENTOS VIA AJAX ///////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




function DocumentoJs(ejecucion) {
    inpfile = document.getElementById("documento");
    inpfile.click();
    document.getElementById("codigo").value = ejecucion;
}
async function subirDocumento() {
    codigo = document.getElementById("codigo");
    documento = document.getElementById("documento");
    posicion = document.getElementById("posicion");
    return new Promise((resolve, reject) => {
        /////////// POST /////////
        var http = new FormData();

        http.append("ejecucion", codigo.value);
        http.append("posicion", 1);
        http.append("documento", documento.files[0]);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_cargar_documento.php");
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

async function cargarDocumento() {
    documento = document.getElementById("documento");
    codigo = document.getElementById("codigo");
    evidencia = document.getElementById('evidencia');

    if (codigo.value !== "" && documento.value !== "") {
        extension = comprueba_extension(documento.value, 2);
        if (extension === 1) {
            /////////// POST /////////
            var boton = document.getElementById("btn-documento");
            var contenedor = document.getElementById("div-documento");
            loadingBtn(boton);
            loadingDiv(contenedor);
            let resultImg = await subirDocumento();
            //console.log(resultImg);
            if (resultImg.status) {
                deloadingBtn(boton, '<i class="fa fa-file-text"></i> Agregar Documento');
                deloadingDiv(contenedor, resultImg.imagen);
                evidencia.value = true;
            } else {
                swal("Error", resultImg.message, "error").then((value) => {
                    console.log(value);
                    window.location.reload();
                });
                return;
            }
        } else {
            swal("Alto!", "Este archivo no es extencion .PDF...", "error");
        }
    } else {
        swal("Error", "Uno de los parametros est\u00E1 vacio, por favor refreseque e intente de nuevo...", "error");
    }
}



function Submit() {
    myform = document.forms.f1;
    myform.submit();
}


function loadingGif(posicion) {
    document.getElementById("foto" + posicion).innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
}

function deleteFotoConfirm(codigo, posicion) {
    swal({
        title: "\u00BFDesea Eliminar la Foto?",
        text: "\u00BFEsta seguro(a) de eliminar esta imagen del archivo de este activo?",
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

/////////////////////////////////////////////////////////////////////////////////////////////////////////////
//async function uploadDocumento() {
//    ejecucion = document.getElementById("codigo");
//    document.getElementById("documento1").innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
//
//    //--
//    var arrpromises = new Array();
//    if (ejecucion.value !== "") {
//        //-- Documento --
//        archivo = document.getElementById("documento");
//        if (archivo.files.length > 0) {
//            valida = comprueba_extension(archivo.files[0].name,2);
//            if (valida !== 1) {
//                swal("Ohoo!", "La extension de este documento no es valida....", "error").then((value) => {
//                    window.location.reload();
//                });
//                return;
//            }
//            arrpromises[0] = await new Promise((resolve, reject) => {
//                /////////// POST /////////
//                let httpDocumento = new FormData();
//                httpDocumento.append("nombre", archivo.files[0].name);
//                httpDocumento.append("ejecucion", ejecucion.value);
//                httpDocumento.append("posicion", 1);
//                httpDocumento.append("documento", archivo.files[0]);
//
//                let requestDocumento = new XMLHttpRequest();
//                requestDocumento.open("POST", "ajax_cargar_documento.php");
//                requestDocumento.onload = () => {
//                    if (requestDocumento.status >= 200 && requestDocumento.status < 300) {
//                        //console.log(requestDocumento);
//                        devuelve = JSON.parse(requestDocumento.response);
//                        if (devuelve.status === true) {
//                            resolve(devuelve.message);
//                        } else {
//                            reject(devuelve.message);
//                        }
//                    } else {
//                        //console.log( JSON.parse(requestDocumento.response) );
//                        reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
//                    }
//                };
//                requestDocumento.onerror = () => reject(requestDocumento.statusText);
//                requestDocumento.send(httpDocumento);
//            }).catch(e => {
//                console.log(e);
//            });
//
//        }
//
//        await Promise.all(arrpromises).then(values => {
//            //console.log(values);
//            swal("Excelente!", "documento subido satisfactoriamente...", "success").then((value) => {
//                window.location.reload();
//            });
//        }, reason => {
//            //console.log(reason);
//            swal("Error", "Error en la trasaccion ...", "error").then((value) => {
//                cerrar();
//            });
//        }).catch(e => {
//            console.log(e);
//        });
//
//    } else {
//        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error").then((value) => {
//            window.location.reload();
//        });
//    }
//}

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


function deleteDocumentoConfirm(codigo) {
    swal({
        title: "\u00BFDesea Eliminar el Documento?",
        text: "\u00BFEsta seguro(a) de eliminar este documento del archivo de esta programacion?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                deleteDocumento(codigo);
                break;
            default:
                return;
        }
    });
}


function deleteDocumento(codigo) {
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

////////////////////////////// Refrescar Filtros /////////////////////////////////

function filtra_usuarios_rep(proceso){
    contenedor = document.getElementById("divusuario");
    loadingCogs(contenedor);
    ///////// POST /////////
    // var categoria = document.getElementById("sede").value;
    // console.log(sede)
    var http = new FormData();
    http.append("request","usuario_proceso");
    http.append("proceso",proceso);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_planning.php");
    request.send(http);
    request.onreadystatechange = function(){
        // console.log( request );
        if(request.readyState != 4) return;
        if(request.status === 200){
            // console.log(request.responseText);
            resultado = JSON.parse(request.responseText);
            if(resultado.status !== true){
                //console.log( resultado );
                contenedor.innerHTML = '...';
                swal("Error", resultado.message , "error");
                return;
            }
            var combo = resultado.combo;
            // console.log( combo );
            contenedor.innerHTML = combo;
            $(".select2").select2({ width: '100%' });
        }
    };
    // document.getElementById("sector").value = '';
    // document.getElementById("secNombre").value = '';
}