
/////////////////////////////////////// Archivos //////////////////////////////
function openInput(id) {
    inpfile = document.getElementById(id);
    inpfile.click();
}

function loadingGif(posicion) {
    document.getElementById("archivo" + posicion).innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
}

async function upload(archivo, tipo) {
    codigo = document.getElementById("codigo");
    loadingGif(tipo); //coloca un gif cargando en la imagenddddddddddd
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
                httpArchivo.append("posicion", tipo); // en este caso la posicion es la misma que el tipo		
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
        await Promise.all(arrpromises[0]).then(values => {
            //console.log(values);
            swal("Excelente!", "Archivo subido satisfactoriamente...", "success").then((value) => {
                window.location.reload();
            });
        }, reason => {
            //console.log(reason);
            swal("Error", "Error en la trasaccion ...", "error").then((value) => {
                window.location.reload();
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

function Cancelar(codigo) {
    swal({
        title: "\u00BFListo?",
        text: "\u00BFEsta seguro(a) de cancelar esta ejecucion?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true, }
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

function Finalizar() {
    swal({
        title: "\u00BFListo?",
        text: "\u00BFEsta seguro(a) de finalizar esta ejecucion?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                observacion = document.getElementById('observacion');
                codigo = document.getElementById('codigo');
                evidencia = document.getElementById('evidencia').value;
                if (evidencia && codigo.value != "" && observacion.value != "") {
                    finalizacion(codigo)
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

async function finalizacion(codigo) {
    var arrpromises = new Array();
    arrpromises[0] = await new Promise((resolve, reject) => {
        var today = new Date();
        var date = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate();
        var http = new FormData();
        http.append("request", "update");
        http.append("codigo", codigo.value);
        http.append("campo", 2);
        http.append("valor", date);
        var requestArchivo = new XMLHttpRequest();
        requestArchivo.open("POST", "ajax_fns_ejecucion.php");
        requestArchivo.onload = () => {
            if (requestArchivo.status >= 200 && requestArchivo.status < 300) {
                //console.log(requestArchivo);
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
        requestArchivo.send(http);
    }).catch(e => {
        console.log(e);
    });
    arrpromises[1] = await new Promise((resolve, reject) => {
        var http = new FormData();
        http.append("request", "situacion");
        http.append("codigo", codigo.value);
        http.append("situacion", 3);
        var requestArchivo = new XMLHttpRequest();
        requestArchivo.open("POST", "ajax_fns_ejecucion.php");
        requestArchivo.onload = () => {
            if (requestArchivo.status >= 200 && requestArchivo.status < 300) {
                //console.log(requestArchivo);
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
        requestArchivo.send(http);
    }).catch(e => {
        console.log(e);
    });
    await Promise.all(arrpromises).then(values => {
        //console.log(values);
        swal("Excelente!", "Registro finalizado satisfactoriamente...", "success").then((value) => {
            window.location.href = "FRMejecucion.php";
        });
    }, reason => {
        //console.log(reason);
        swal("Error", "Error en la trasaccion ...", "error").then((value) => {
            cerrar();
        });
    }).catch(e => {
        console.log(e);
    });
}

async function update(elemento, campo) {
    codigo = document.getElementById("codigo").value;
    var http = new FormData();
    http.append("request", "update");
    http.append("codigo", codigo);
    http.append("campo", campo);
    http.append("valor", elemento.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecucion.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log(request.readyState);
        if (request.readyState != 4) return;
        if (request.status === 200) {
            // console.log(request.responseText);
            resultado = JSON.parse(request.responseText);
            //console.log(resultado);
            if (resultado.status !== true) {
                console.log(resultado.message);
                return;
            }
            // console.log(resultado.message);
        }
    };
}

async function cambioSituacion(codigo, situacion) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion");
    http.append("codigo", codigo);
    http.append("situacion", situacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecucion.php");
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
            swal("Excelente!", "Registro modificado satisfactoriamente!!!", "success").then((value) => {
                window.location.href = "FRMejecucion.php";
            });
        }
    };
}
