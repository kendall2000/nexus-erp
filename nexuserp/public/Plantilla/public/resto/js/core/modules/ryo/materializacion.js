
/////////////////////////////////////// Archivos //////////////////////////////
function openInput(posicion) {
    inpfile = document.getElementById("imagen");
    inpfile.click();
    document.getElementById("posicion").value = posicion
}

function loadingGif(posicion) {
    document.getElementById("archivo" + posicion).innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
}

async function upload(archivo, tipo) {
    codigo = document.getElementById("codigo");
    posicion = document.getElementById("posicion").value;
    loadingGif(posicion); //coloca un gif cargando en la imagen
    //--
    var arrpromises = new Array();
    if (codigo.value !== "") {
        if (archivo.files.length > 0) {
            valida = comprueba_extension(archivo.files[0].name, tipo);
            if (valida !== 1) {
                swal("Ohoo!", "La extension de este archivo no es valida....", "error").then((value) => {
                    console.log(value);
                });
                return;
            }
            arrpromises[0] = await new Promise((resolve, reject) => {
                /////////// POST /////////
                let httpArchivo = new FormData();
                httpArchivo.append("nombre", archivo.files[0].name);
                httpArchivo.append("codigo", codigo.value);
                httpArchivo.append("archivo", archivo.files[0]);
                httpArchivo.append("posicion", posicion); // en este caso la posicion es la misma que el tipo		
                let requestArchivo = new XMLHttpRequest();
                requestArchivo.open("POST", "ajax_cargar_archivo.php");
                requestArchivo.onload = () => {
                    if (requestArchivo.status >= 200 && requestArchivo.status < 300) {
                        console.log(requestArchivo);
                        devuelve = JSON.parse(requestArchivo.response);
                        if (devuelve.status === true) {
                            resolve(devuelve.message);
                        } else {
                            reject(devuelve.message);
                        }
                    } else {
                        //console.log( JSON.parse(requestArchivo.response) );
                        reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                    }
                };
                requestArchivo.onerror = () => reject(requestArchivo.statusText);
                requestArchivo.send(httpArchivo);
            }).catch(e => {
                console.log(e);
            });
        }
        await Promise.all(arrpromises).then(values => {
            //console.log(values);
            swal("Excelente!", "Archivo subido satisfactoriamente...", "success").then((value) => {
                window.location.reload();
            });
        }, reason => {
            //console.log(reason);
            swal("Error", "Error en la trasaccion ...", "error").then((value) => {
                cerrar();
            });
        }).catch(e => {
            console.log(e);
        });

    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error").then((value) => {
            window.location.reload();
        });
    }
}

function Finalizar() {
    swal({
        title: "\u00BFListo?",
        text: "\u00BFEsta seguro(a) de materializar el riesgo con estos datos?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                codigo = document.getElementById('codigo');
                evidencia = document.getElementById('evidencia').value;
                if (evidencia && codigo.value != "") {
                    cambioSituacion(codigo.value, 2)
                }
                else {
                    if (observacion.value === "") {
                        observacion.classList.add("is-invalid");
                        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
                    } else {
                        observacion.classList.remove("is-invalid");
                    }
                    if (!evidencia) swal("Ohoo!", "Debe subir al menos una evidencia...", "error");
                }

                break;
            default:
                return;
        }
    });
}

function asignarUsuario() {
    riesgo = document.getElementById('codigo');
    arrUsuario = $('[name="duallistbox1[]"]').val();

    if (riesgo.value !== "") {
        /////////// POST /////////
        var http = new FormData();
        http.append("request", "asignar_riesgo");
        http.append("riesgo", riesgo.value);
        http.append("usuarios", arrUsuario);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ryo.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log( request );
            if (request.readyState != 4) return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    //console.log( resultado.sql );
                    swal("Error", resultado.message, "error").then((value) => { deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar'); });
                    return;
                }
                // console.log(resultado);
            }
        };
    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

async function cambioSituacion(codigo, situacion) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion");
    http.append("codigo", codigo);
    http.append("situacion", situacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ryo.php");
    request.send(http);
    request.onreadystatechange = function () {
        // console.log(request.responseText);
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                // console.log( resultado.sql );
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            swal("Excelente!", "Registro modificado satisfactoriamente!!!", "success").then((value) => {
                window.location.href = "FRMmaterializacion.php";
            });
        }
    };
}
