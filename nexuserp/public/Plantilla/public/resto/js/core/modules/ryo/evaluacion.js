function Finalizar() {
    swal({
        title: "\u00BFListo?",
        text: "\u00BFEsta seguro(a) de finalizar esta evaluacion?",
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
                puntuacion = document.getElementById('puntuacion');
                if (puntuacion.value != "" && codigo.value != "" && observacion.value != "") {
                    finalizacion(codigo)
                }
                else {
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

                break;
            default:
                return;
        }
    });
}

async function finalizacion(codigo) {
    var arrpromises = new Array();
    arrpromises[0] = await new Promise((resolve, reject) => {
        var usuario = document.getElementById("usuario");
        var http = new FormData();
        http.append("request", "update");
        http.append("codigo", codigo.value);
        http.append("campo", 6);
        http.append("valor", usuario.value);
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
        var today = new Date();
        var date = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate();
        var http = new FormData();
        http.append("request", "update");
        http.append("codigo", codigo.value);
        http.append("campo", 7);
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
    arrpromises[2] = await new Promise((resolve, reject) => {
        var http = new FormData();
        http.append("request", "situacion");
        http.append("codigo", codigo.value);
        http.append("situacion", 5);
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
            window.location.href = "FRMevaluacion.php";
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
