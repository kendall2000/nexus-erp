
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
    loadingGif(tipo); //coloca un gif cargando en la imagen
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


function FotoJs() {
        posicion = document.getElementById("posicion");
	posicion.value = '1';
        inpfile = document.getElementById("foto");
	inpfile.click();
}


async function subirImagen() {
	codigo = document.getElementById("codigo");
	foto = document.getElementById("foto");
        posicion = document.getElementById("posicion");
	return new Promise((resolve, reject) => {
		/////////// POST /////////
		var http = new FormData();
		http.append("codigo", codigo.value);
                http.append("posicion", posicion.value);
		http.append("foto", foto.files[0]);
		
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
	foto = document.getElementById("foto");
	codigo = document.getElementById("codigo");
	if (codigo.value !== "" && foto.value !== "") {
		extension = comprueba_extension(foto.value,1);
		if (extension === 1) {
			/////////// POST /////////
			var boton = document.getElementById("btn-foto");
			var contenedor = document.getElementById("div-imagen");
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




function DocumentoJs() {
        posicion = document.getElementById("posicion");
	posicion.value = '2';
        inpfile = document.getElementById("documento");
	inpfile.click();
}
async function subirDocumento() {
	codigo = document.getElementById("codigo");
	documento = document.getElementById("documento");
        posicion = document.getElementById("posicion");
	return new Promise((resolve, reject) => {
		/////////// POST /////////
		var http = new FormData();
		http.append("codigo", codigo.value);
                http.append("posicion", posicion.value);
		http.append("documento", documento.files[0]);
		var request = new XMLHttpRequest();
		request.open("POST", "ajax_cargar_archivo.php");
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
	if (codigo.value !== "" && documento.value !== "") {
		extension = comprueba_extension(documento.value,2);
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
