//funciones javascript y validaciones
$(document).ready(function () {
    $(".select2").select2({ width: '100%' });
});

function coment_ticket(ticket) {
    if (ticket !== "") {
        cerrar();
        //Realiza una peticion de contenido a la contenido.php
        $.post("../promts/ticket/coment_ticket.php", {}, function (data) {
            // Ponemos la respuesta de nuestro script en el DIV recargado
            $("#Pcontainer").html(data);
        });
        abrirModal();
        setTimeout(function () {
            $(".select2").select2({ width: '100%' });
        }, 500);

    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}


function status(ticket) {
    if (ticket !== "") {
        cerrar();
        //Realiza una peticion de contenido a la contenido.php
        $.post("../promts/ticket/status.php", {}, function (data) {
            // Ponemos la respuesta de nuestro script en el DIV recargado
            $("#Pcontainer").html(data);
        });
        abrirModal();
        setTimeout(function () {
            $(".select2").select2({ width: '100%' });
        }, 500);

    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function newFalla(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/ticket/newfalla.php", {codigo: codigo}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
    setTimeout(function () {
        $(".select2").select2({ width: '100%' });
    }, 500);
}

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

function upLoad() {
    myform = document.forms.f2;
    myform.action = "EXEcarga_foto.php";
    myform.submit();
}

function seleccionarTicket(codigo) {
    window.location.href = "FRMmodticket.php?codigo=" + codigo;
}


function tramitarTicket(codigo) {
    window.location.href = "FRMtramite.php?codigo=" + codigo;
}

async function Modificar() {
    ticket = document.getElementById("ticket");
    sede = document.getElementById("sede");
    sector = document.getElementById("sector");
    area = document.getElementById("area");
    incidente = document.getElementById("incidente");
    categoria = document.getElementById("categoria");
    descripcion = document.getElementById("descripcion");
    prioridad = document.getElementById("prioridad");
    //-- IMAGENES --
    archivo = document.getElementById("imagen");
    if (archivo.files.length < 1) {
        imagen = false;
    } else {
        imagen = true;
        //---
        valida = comprueba_extension(archivo.files[0].name, 3);
        if (valida !== 1) {
            swal("Ohoo!", "La extension de este archivo no es valida....", "error").then((value) => {
                console.log(value);
            });
            return;
        }
    }
    var arrpromises = new Array();
    if (ticket.value !== "" && sede.value !== "" && sector.value !== "" && area.value !== "" && incidente.value !== "" && prioridad.value !== "" && categoria.value !== "") {
        //botones
        abrir();
        btngrabar = document.getElementById("btn-grabar");
        btngrabar.className = 'btn btn-primary hidden';

        arrpromises[0] = await new Promise((resolve, reject) => {
            /////////// POST /////////
            let httpData = new FormData();
            httpData.append("request", "modificar");
            httpData.append("ticket", ticket.value);
            httpData.append("descripcion", descripcion.value);
            httpData.append("incidente", incidente.value);
            httpData.append("prioridad", prioridad.value);
            httpData.append("sede", sede.value);
            httpData.append("sector", sector.value);
            httpData.append("area", area.value);
            let requestData = new XMLHttpRequest();
            requestData.open("POST", "ajax_fns_ticket.php");
            requestData.onload = () => {
                if (requestData.status >= 200 && requestData.status < 300) {
                    //console.log(JSON.parse(requestData.response));
                    devuelve = JSON.parse(requestData.response);
                    if (devuelve.status === true) {
                        resolve(devuelve.message);
                    } else {
                        reject(devuelve.message);
                    }
                } else {
                    //console.log( JSON.parse(requestData.response) );
                    reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                }
            };
            requestData.onerror = () => reject(requestData.statusText);
            requestData.send(httpData);
        });

        if (imagen === true) { //si se carga imagen
            arrpromises[1] = await new Promise((resolve, reject) => {
                /////////// POST /////////
                let httpImagen = new FormData();
                httpImagen.append("nombre", archivo.files[0].name);
                httpImagen.append("ticket", ticket.value);
                httpImagen.append("posicion", document.getElementById("posicion").value);
                httpImagen.append("imagen", archivo.files[0]);

                let requestImagen = new XMLHttpRequest();
                requestImagen.open("POST", "ajax_cargar_imagen.php");
                requestImagen.onload = () => {
                    if (requestImagen.status >= 200 && requestImagen.status < 300) {
                        console.log(requestImagen.response);
                        devuelve = JSON.parse(requestImagen.response);
                        if (devuelve.status === true) {
                            resolve(devuelve.message);
                        } else {
                            reject(devuelve.message);
                        }
                    } else {
                        //console.log( JSON.parse(requestImagen.response) );
                        reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                    }
                };
                requestImagen.onerror = () => reject(requestImagen.statusText);
                requestImagen.send(httpImagen);
            });
        }

        await Promise.all(arrpromises).then(values => {
            //console.log(values);
            swal("Excelente!", "Ticket modificado satisfactoriamente...", "success").then((value) => {
                cerrar();
                window.location.href = "FRMsolicitados.php";
            });
        }, reason => {
            //console.log(reason);
            swal("Error", "Error en la trasaccion ...", "error").then((value) => {
                cerrar();
            });
        });

    } else {
        if (sede.value === "") {
            sede.parentNode.classList.add('has-error');
        } else {
            sede.parentNode.classList.remove('has-error');
        }
        if (sector.value === "") {
            secNombre.classList.add("is-invalid");
        } else {
            secNombre.classList.remove("is-invalid");
        }
        if (area.value === "") {
            area.parentNode.classList.add('has-error');
        } else {
            area.parentNode.classList.remove('has-error');
        }
        if (incidente.value === "") {
            incidente.parentNode.classList.add('has-error');
        } else {
            incidente.parentNode.classList.remove('has-error');
        }
        if (prioridad.value === "") {
            prioridad.parentNode.classList.add('has-error');
        } else {
            prioridad.parentNode.classList.remove('has-error');
        }
        if (categoria.value === "") {
            categoria.parentNode.classList.add('has-error');
        } else {
            categoria.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

async function Grabar() {
    sede = document.getElementById("sede");
    sector = document.getElementById("sector");
    area = document.getElementById("area");
    incidente = document.getElementById("incidente");
    categoria = document.getElementById("categoria");
    descripcion = document.getElementById("descripcion");
    prioridad = document.getElementById("prioridad");
    //-- IMAGENES --
    archivo = document.getElementById("imagen");
    if (archivo.files.length < 1) {
        imagen = false;
    } else {
        imagen = true;
        //---
        valida = comprueba_extension(archivo.files[0].name, 3);
        if (valida !== 1) {
            swal("Ohoo!", "La extension de este archivo no es valida....", "error").then((value) => {
                console.log(value);
            });
            return;
        }
    }
    var arrpromises = new Array();
    if (sede.value !== "" && sector.value !== "" && area.value !== "" && incidente.value !== "" && prioridad.value !== "" && categoria.value !== "") {
        //botones
        abrir();
        btngrabar = document.getElementById("btn-grabar");
        btngrabar.className = 'btn btn-primary hidden';

        arrpromises[0] = await new Promise((resolve, reject) => {
            /////////// POST /////////
            let httpData = new FormData();
            httpData.append("request", "grabar");
            httpData.append("descripcion", descripcion.value);
            httpData.append("incidente", incidente.value);
            httpData.append("prioridad", prioridad.value);
            httpData.append("sede", sede.value);
            httpData.append("sector", sector.value);
            httpData.append("area", area.value);
            let requestData = new XMLHttpRequest();
            requestData.open("POST", "ajax_fns_ticket.php");
            requestData.onload = () => {
                if (requestData.status >= 200 && requestData.status < 300) {
                    //console.log(JSON.parse(requestData.response));
                    devuelve = JSON.parse(requestData.response);
                    if (devuelve.status === true) {
                        resolve(devuelve.message);
                        // set
                        document.getElementById("ticket").value = devuelve.data['ticket'];
                        document.getElementById("sms").value = devuelve.data['sms'];
                        document.getElementById("posicion").value = devuelve.data['status'];
                        document.getElementById("pagina").value = devuelve.pagina;
                    } else {
                        reject(devuelve.message);
                    }
                } else {
                    //console.log( JSON.parse(requestData.response) );
                    reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                }
            };
            requestData.onerror = () => reject(requestData.statusText);
            requestData.send(httpData);
        });

        if (imagen === true) { //si se carga imagen
            arrpromises[1] = await new Promise((resolve, reject) => {
                /////////// POST /////////
                let httpImagen = new FormData();
                httpImagen.append("nombre", archivo.files[0].name);
                httpImagen.append("ticket", document.getElementById("ticket").value);
                httpImagen.append("posicion", document.getElementById("posicion").value);
                httpImagen.append("sms", document.getElementById("sms").value);
                httpImagen.append("imagen", archivo.files[0]);

                let requestImagen = new XMLHttpRequest();
                requestImagen.open("POST", "ajax_cargar_imagen.php");
                requestImagen.onload = () => {
                    console.log(requestImagen.status);
                    if (requestImagen.status >= 200 && requestImagen.status < 300) {
                        console.log(requestImagen.response);
                        devuelve = JSON.parse(requestImagen.response);
                        if (devuelve.status === true) {
                            resolve(devuelve.message);
                            document.getElementById("pagina").value = devuelve.pagina;
                        } else {
                            reject(devuelve.message);
                            console.log(devuelve.message);
                            document.getElementById("pagina").value = devuelve.pagina;
                        }
                    } else {
                        //console.log( JSON.parse(requestImagen.response) );
                        reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                    }
                };
                requestImagen.onerror = () => reject(requestImagen.statusText);
                requestImagen.send(httpImagen);
            });
        }

        await Promise.all(arrpromises).then(values => {
            //console.log(values);
            swal("Excelente!", "Ticket aperturado satisfactoriamente...", "success").then((value) => {
                cerrar();
                window.location.href = document.getElementById("pagina").value;
            });
        }, reason => {
            console.log(reason);
            //swal("Error", "Error en la trasaccion ...", "error").then((value) => {
            //cerrar();
            //});
        });

    } else {
        if (sede.value === "") {
            sede.parentNode.classList.add('has-error');
        } else {
            sede.parentNode.classList.remove('has-error');
        }
        if (sector.value === "") {
            secNombre.classList.add("is-invalid");
        } else {
            secNombre.classList.remove("is-invalid");
        }
        if (area.value === "") {
            area.parentNode.classList.add('has-error');
        } else {
            area.parentNode.classList.remove('has-error');
        }
        if (incidente.value === "") {
            incidente.parentNode.classList.add('has-error');
        } else {
            incidente.parentNode.classList.remove('has-error');
        }
        if (prioridad.value === "") {
            prioridad.parentNode.classList.add('has-error');
        } else {
            prioridad.parentNode.classList.remove('has-error');
        }
        if (categoria.value === "") {
            categoria.parentNode.classList.add('has-error');
        } else {
            categoria.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}



async function confirmStatus() {
    //--
    ticket = document.getElementById("ticket");
    status = document.getElementById("status");
    observacion = document.getElementById("observaciones");
    //--
    var arrpromises = new Array();
    if (status.value !== "") {
        arrpromises[0] = await new Promise((resolve, reject) => {
            /////////// POST /////////
            let httpData = new FormData();
            httpData.append("request", "cambiar_status");
            httpData.append("ticket", ticket.value);
            httpData.append("status", status.value);
            httpData.append("observacion", observacion.value);

            let requestData = new XMLHttpRequest();
            requestData.open("POST", "ajax_fns_ticket.php");
            requestData.onload = () => {
                if (requestData.status >= 200 && requestData.status < 300) {
                    //console.log(JSON.parse(requestData.response));
                    devuelve = JSON.parse(requestData.response);
                    if (devuelve.status === true) {
                        resolve(devuelve.message);
                    } else {
                        reject(devuelve.message);
                    }
                } else {
                    //console.log( JSON.parse(requestData.response) );
                    reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                }
            };
            requestData.onerror = () => reject(requestData.statusText);
            requestData.send(httpData);
        });

        await Promise.all(arrpromises).then(values => {
            //console.log(values);
            swal("Excelente!", "Status modificado satisfactoriamente...", "success").then((value) => {
                cerrar();
                window.location.reload();
            });
        }, reason => {
            //console.log(reason);
            swal("Error", "Error en la trasaccion ...", "error").then((value) => {
                cerrar();
            });
        });

    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }

}


async function confirmStatusFoto() {
    //--
    ticket = document.getElementById("ticket");
    status = document.getElementById("status");
    observacion = document.getElementById("observaciones");
    //--
    var arrpromises = new Array();
    if (status.value !== "") {
        arrpromises[0] = await new Promise((resolve, reject) => {
            /////////// POST /////////
            let httpData = new FormData();
            httpData.append("request", "cambiar_status");
            httpData.append("ticket", ticket.value);
            httpData.append("status", status.value);
            httpData.append("observacion", observacion.value);

            let requestData = new XMLHttpRequest();
            requestData.open("POST", "ajax_fns_ticket.php");
            requestData.onload = () => {
                if (requestData.status >= 200 && requestData.status < 300) {
                    //console.log(JSON.parse(requestData.response));
                    devuelve = JSON.parse(requestData.response);
                    if (devuelve.status === true) {
                        resolve(devuelve.status_nombre);
                    } else {
                        reject(devuelve.message);
                    }
                } else {
                    //console.log( JSON.parse(requestData.response) );
                    reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                }
            };
            requestData.onerror = () => reject(requestData.statusText);
            requestData.send(httpData);
        });
        //-- IMAGENES --
        archivo = document.getElementById("imagen");
        if (archivo.files.length > 0) {
            //a
            valida = comprueba_extension(archivo.files[0].name, 3);
            if (valida !== 1) {
                swal("Ohoo!", "La extension de este archivo no es valida....", "error").then((value) => {
                        window.location.reload();
                });
                return;
            }
            arrpromises[1] = await new Promise((resolve, reject) => {
                /////////// POST /////////
                let httpImagen = new FormData();
                httpImagen.append("nombre", archivo.files[0].name);
                httpImagen.append("ticket", ticket.value);
                httpImagen.append("posicion", status.value);
                httpImagen.append("imagen", archivo.files[0]);

                let requestImagen = new XMLHttpRequest();
                requestImagen.open("POST", "ajax_cargar_imagen.php");
                requestImagen.onload = () => {
                    if (requestImagen.status >= 200 && requestImagen.status < 300) {
                        //console.log(requestImagen.responseText);
                        devuelve = JSON.parse(requestImagen.response);
                        if (devuelve.status === true) {
                            resolve(devuelve.imagen);
                        } else {
                            reject(devuelve.message);
                        }
                    } else {
                        //console.log( JSON.parse(requestImagen.response) );
                        reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                    }
                };
                requestImagen.onerror = () => reject(requestImagen.statusText);
                requestImagen.send(httpImagen);
            });

        }

        await Promise.all(arrpromises).then(values => {
           //console.log(values);
            // swal({
            //     text: "\u00BFDesea Limpiar la p\u00E1gina?, si a\u00FAn no a grabado perdera los datos escritos...",
            //     icon: "info",
            //     buttons: {
            //         cancel: "Cancelar",
            //         ok: {text: "Aceptar", value: true, },
            //     }
            // }).then((value) => {
            //     switch (value) {
            //         case true:
            //             break;
            //         default:
            //             return;
            //     }
            // });

            swal("Excelente!", "Status modificado satisfactoriamente...", "success").then((value) => {
                //status
                // state = values[0];
                // status_nombre = '';
                // switch(state){
                //     case '7':
                //         status_nombre = 'Reportado';
                //     break;
                //     case '8':
                //         status_nombre = 'Recibido';
                //     break;
                //     case '9':
                //         status_nombre = 'En proceso';
                //     break;
                //     case '50':
                //         status_nombre = 'Pausado';
                //     break;
                //     case '100':
                //         status_nombre = 'Solucionado';
                //     break;
                //     case '0':
                //         status_nombre = 'Cancelado';
                //     break;
                // }



                // container_cards = document.querySelector(".card-deck");
                // div_contenedor = document.createElement("div");
                // div_contenedor.className = "col-md-4";
                // container_cards.appendChild(div_contenedor);

                // card = document.createElement("div");
                // card.className = "card";
                // div_contenedor.appendChild(card);
                // card.innerHTML = values[1];

                // card_body = document.createElement("div");
                // card_body.className = "card-body";
                // card.appendChild(card_body);
                // card_body.innerHTML = status_nombre;
                cerrar();
                //break confirmStatusFoto();
                window.location.reload();
            });
        }, reason => {
            //console.log(reason);
            swal("Error", "Error en la trasaccion ...", "error").then((value) => {
                cerrar();
            });
        });

    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

/////////////////////////////// Comprueba Extensiones //////////////////
// Para las Imagenes el Tipo = 1, Pdf = 2

function cambiaStatus() {
    var boton = document.getElementById("btn-grabar");
    loadingBtn(boton);
    //-- Lleva imagen?
    swal({
        title: "\u00BFAgregar Archivo?",
        icon: "info",
        text: "\u00BFDesea agregar un archivo con este cambio de status?.",
        buttons: {
            cancel: "No por esta vez",
            ok: {text: "Si, agregar archivo", value: true, },
        }
    }).then((value) => {
        switch (value) {
            case true:
                FotoJs();
                break;
            default:
                confirmStatus();
                break;
        }
    });
}

function agregar() {
    ticket = document.getElementById("ticket");
    usuario = document.getElementById("usuario");
    if (usuario.value !== "") {
        /////////// POST /////////
        var http = new FormData();
        http.append("request", "agregar_usuario");
        http.append("ticket", ticket.value);
        http.append("usuario", usuario.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ticket.php");
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
                        cerrar();
                    });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.reload();
                });
            }
        };
    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}


function GrabarFalla() {
    activo = document.getElementById('activo');
    falla = document.getElementById('falla');
    fecha = document.getElementById('fecha');
    hora = document.getElementById('hora');
    situacion = document.getElementById('situacion');
    ticket = document.getElementById('ticket');
    comentario = document.getElementById('comentarioFalla');
    console.log(comentario.value);
    if (activo.value !== "" && falla.value !== "" && fecha.value !== "" && hora.value !== "" && situacion.value !== "" && ticket.value !== "" && comentario.value !== "") {
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "grabar_falla");
        http.append("activo", activo.value);
        http.append("falla", falla.value);
        http.append("fecha", fecha.value);
        http.append("hora", hora.value);
        http.append("situacion", situacion.value);
        http.append("codigo_ticket", ticket.value);
        http.append("comentario", comentario.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ticket.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log(request.responseText);
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
                    window.open("CPPPMPROGRA/FRMprogramar.php?hashkey=" + resultado.hashkey);
                    cerrar();
                });
            }
        };
    } else {
        if (falla.value === "") {
            falla.classList.add("is-invalid");
        } else {
            falla.classList.remove("is-invalid");
        }
        if (fecha.value === "") {
            fecha.classList.add("is-invalid");
        } else {
            fecha.classList.remove("is-invalid");
        }
        if (hora.value === "") {
            hora.classList.add("is-invalid");
        } else {
            hora.classList.remove("is-invalid");
        }
        if (situacion.value === "") {
            situacion.parentNode.classList.add('has-error');
        } else {
            situacion.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function trasladar() {
    ticket = document.getElementById("ticket");
    usuario = document.getElementById("usuario");
    if (usuario.value !== "") {
        /////////// POST /////////
        var http = new FormData();
        http.append("request", "trasladar_usuario");
        http.append("ticket", ticket.value);
        http.append("usuario", usuario.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ticket.php");
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
                        cerrar();
                    });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.reload();
                });
            }
        };
    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function salirUsuario(ticket, usuario) {
    if (usuario !== "" && ticket != "") {
        /////////// POST /////////
        var http = new FormData();
        http.append("request", "salir_usuario");
        http.append("ticket", ticket);
        http.append("usuario", usuario);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ticket.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log(request);
            if (request.readyState != 4)
                return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    swal("Error", resultado.message, "error").then((value) => {
                        cerrar();
                    });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.reload();
                });
            }
        };
    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function verInformacion(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/ticket/informacion.php", {codigo: codigo}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}

function imagenStatus(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/ticket/imagenes.php", {codigo: codigo}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}


function Confirm_Cerrar_Ticket(codigo) {
    swal({
        title: "Cerrar Ticket",
        icon: "warning",
        text: "\u00BFDesea cerrar este ticket del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, },
        }
    }).then((value) => {
        switch (value) {
            case true:
                /////////// POST /////////
                var http = new FormData();
                http.append("request", "cerrar_ticket");
                http.append("ticket", codigo);
                var request = new XMLHttpRequest();
                request.open("POST", "ajax_fns_ticket.php");
                request.send(http);
                request.onreadystatechange = function () {
                    //console.log(request);
                    if (request.readyState != 4)
                        return;
                    if (request.status === 200) {
                        resultado = JSON.parse(request.responseText);
                        if (resultado.status !== true) {
                            swal("Error", resultado.message, "error").then((value) => {
                                document.getElementById("mod").className = "btn btn-primary"
                            });
                            return;
                        }
                        //console.log( resultado );
                        swal("Excelente!", resultado.message, "success").then((value) => {
                            window.location.reload();
                        });
                    }
                };
                break;
            default:
                return;
        }
    });
}

///////////////////////////// UTILITARIAS ///////////////////////////////////

function FotoJs(comentario = '') {
    if(comentario === ''){
        inpfile = document.getElementById("imagen");
        inpfile.click();
    }else{
         inpfile = document.getElementById("imagen_comentario");
        inpfile.click();
    }

}


function SetPrioridad(incidente) {
    if (incidente != "") {
        /////////// POST /////////
        var http = new FormData();
        http.append("request", "get_prioridad");
        http.append("incidente", incidente);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ticket.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log( request );
            if (request.readyState != 4)
                return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    //swal("Informaci\u00F3n", resultado.messaage, "info");
                    return;
                }
                var data = resultado.data;
                //console.log( data );
                //set
                document.getElementById("categoria").value = data.categoria;
                document.getElementById("prioridad").value = data.prioridad;
                // console.log( data.prioridad)
                //--
                $(".select2").select2({ width: '100%' });

            }
        };
    } else {
        //set
        document.getElementById("prioridad").value = '';
    }
}

function setArea(area) {
    if (area != "") {
        /////////// POST /////////
        var http = new FormData();
        http.append("request", "get_area");
        http.append("area", area);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ticket.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log( request );
            if (request.readyState != 4)
                return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    //swal("Informaci\u00F3n", resultado.messaage, "info");
                    return;
                }
                var data = resultado.data;
                //console.log( data );
                //set
                document.getElementById("sede").value = data.sede;
                document.getElementById("sector").value = data.sector;
                document.getElementById("secNombre").value = data.secNombre;
                //--
                $(".select2").select2({ width: '100%' });

            }
        };
    } else {
        //set
        document.getElementById("sede").value = '';
        document.getElementById("sector").value = '';
        document.getElementById("secNombre").value = '';
    }
}



    function print_table_tickets_solicitados(){
        contenedor = document.getElementById("result")
        incidente = document.getElementById("incidente")
        status = document.getElementById("status")
        prioridad = document.getElementById("prioridad")
        categoria = document.getElementById("categoria")
        desde = document.getElementById("desde")
        hasta = document.getElementById("hasta")
        loadingCogs(contenedor);
        /////////// POST //////////////
        var http = new FormData();
        http.append("request","print_table_tickets_solicitados");
        http.append("incidente", incidente.value);
        http.append("status", status.value);
        http.append("prioridad", prioridad.value);
        http.append("categoria", categoria.value);
        http.append("desde", desde.value);
        http.append("hasta", hasta.value);

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ticket.php");
        request.send(http);
        request.onreadystatechange = function(){
            //console.log( request );
            if(request.readyState != 4) return;
            if(request.status === 200){
               // console.log( request.responseText );
                resultado = JSON.parse(request.responseText);
                if(resultado.status !== true){
                    //console.log( resultado );
                    contenedor.innerHTML = '...';
                  //  console.log( resultado.message );
                    return;
                }
                //tabla
                var table = resultado.data;
                contenedor.innerHTML = table;
                $('#tabla').DataTable({
                    pageLength: 100,
                    responsive: true,
                    dom: '<"html5buttons"B>lTfgitp',
                    buttons: [
                        {extend: 'copy'},
                        {extend: 'csv'},
                        {extend: 'excel', title: 'Tabla de Tickets'},
                        {extend: 'pdf', title: 'Tabla de Tickets'},
                        {extend: 'print',
                            customize: function (win){
                                $(win.document.body).addClass('white-bg');
                                $(win.document.body).css('font-size', '10px');
                                $(win.document.body).find('table')
                                        .addClass('compact')
                                        .css('font-size', 'inherit');
                            }, title: 'Tabla de Tickets'
                        }
                    ]
                });
            }
        };
    }



function print_table_tickets_asignados(){
        contenedor = document.getElementById("result")
        incidente = document.getElementById("incidente")
        status = document.getElementById("status")
        prioridad = document.getElementById("prioridad")
        categoria = document.getElementById("categoria")
        desde = document.getElementById("desde")
        hasta = document.getElementById("hasta")
        loadingCogs(contenedor);
        /////////// POST //////////////
        var http = new FormData();
        http.append("request","print_table_tickets_asignados");
        http.append("incidente", incidente.value);
        http.append("status", status.value);
        http.append("prioridad", prioridad.value);
        http.append("categoria", categoria.value);
        http.append("desde", desde.value);
        http.append("hasta", hasta.value);

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ticket.php");
        request.send(http);
        request.onreadystatechange = function(){
            //console.log( request );
            if(request.readyState != 4) return;
            if(request.status === 200){
               // console.log( request.responseText );
                resultado = JSON.parse(request.responseText);
                if(resultado.status !== true){
                    //console.log( resultado );
                    contenedor.innerHTML = '...';
                  //  console.log( resultado.message );
                    return;
                }
                //tabla
                var table = resultado.data;
                contenedor.innerHTML = table;
                $('#tabla').DataTable({
                    pageLength: 100,
                    responsive: true,
                    dom: '<"html5buttons"B>lTfgitp',
                    buttons: [
                        {extend: 'copy'},
                        {extend: 'csv'},
                        {extend: 'excel', title: 'Tabla de Tickets'},
                        {extend: 'pdf', title: 'Tabla de Tickets'},
                        {extend: 'print',
                            customize: function (win){
                                $(win.document.body).addClass('white-bg');
                                $(win.document.body).css('font-size', '10px');
                                $(win.document.body).find('table')
                                        .addClass('compact')
                                        .css('font-size', 'inherit');
                            }, title: 'Tabla de Tickets'
                        }
                    ]
                });
            }
        };
    }

    ///////////////////////////// NUEVAS - FILTROS ///////////////////////////////////////////
    function comboIncidente(categoria){
        contenedor = document.getElementById("divincidente");
        loadingCogs(contenedor);
        ///////// POST /////////
        // var categoria = document.getElementById("categoria").value;
        // console.log(categoria)
        var http = new FormData();
        http.append("request","incidente_categoria");
        http.append("categoria",categoria);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ticket.php");
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
        document.getElementById("prioridad").value = '';
    }

    function comboArea(sede){
        contenedor = document.getElementById("divarea");
        loadingCogs(contenedor);
        ///////// POST /////////
        // var categoria = document.getElementById("sede").value;
        // console.log(sede)
        var http = new FormData();
        http.append("request","area_sede");
        http.append("sede",sede);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ticket.php");
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
        document.getElementById("sector").value = '';
        document.getElementById("secNombre").value = '';
    }

//////////////////////////////////////////////////////////
/////////////////////COMENTARIOS//////////////////////////
//////////////////////////////////////////////////////////
function AgregarComentario() {
    var boton = document.getElementById("btn-grabar");
    loadingBtn(boton);
    //-- Lleva imagen?
    swal({
        title: "\u00BFAgregar Archivo?",
        icon: "info",
        text: "\u00BFDesea agregar un archivo adjunto a este comentario?.",
        buttons: {
            cancel: "No por esta vez",
            ok: {text: "Si, agregar archivo", value: true, },
        }
    }).then((value) => {
        switch (value) {
            case true:
                FotoJs(true);
                break;
            default:
                confirmComentario();
                break;
        }
    });
}

async function ConfirmComentarioFoto() {
    //--
    ticket = document.getElementById("ticket");
    comentario = document.getElementById("comentario_ticket");
    //--
    var arrpromises = new Array();
    if (status.value !== "") {
        arrpromises[0] = await new Promise((resolve, reject) => {
            /////////// POST /////////
            let httpData = new FormData();
            httpData.append("request", "agregar_comentario");
            httpData.append("ticket", ticket.value);
            httpData.append("comentario", comentario.value);

            let requestData = new XMLHttpRequest();
            requestData.open("POST", "ajax_fns_ticket.php");
            requestData.onload = () => {
                if (requestData.status >= 200 && requestData.status < 300) {
                    //console.log(JSON.parse(requestData.response));
                    devuelve = JSON.parse(requestData.response);
                    if (devuelve.status === true) {
                        resolve(devuelve.code_comment);
                    } else {
                        reject(devuelve.message);
                    }
                } else {
                    //console.log( JSON.parse(requestData.response) );
                    reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                }
            };
            requestData.onerror = () => reject(requestData.statusText);
            requestData.send(httpData);
        });
        //-- IMAGENES --
        archivo = document.getElementById("imagen_comentario");
        coment_ticket = devuelve.code_comment;
        if (archivo.files.length > 0) {
            //a
            valida = comprueba_extension(archivo.files[0].name, 3);
            if (valida !== 1) {
                swal("Ohoo!", "La extension de este archivo no es valida....", "error").then((value) => {
                        window.location.reload();
                });
                return;
            }
            arrpromises[1] = await new Promise((resolve, reject) => {
                /////////// POST /////////
                let httpImagen = new FormData();
                httpImagen.append("nombre", archivo.files[0].name);
                httpImagen.append("imagen_comentario", archivo.files[0]);
                httpImagen.append("ticket", ticket.value);
                httpImagen.append("comentario", coment_ticket);

                let requestImagen = new XMLHttpRequest();
                requestImagen.open("POST", "ajax_cargar_imagen.php");
                requestImagen.onload = () => {
                    if (requestImagen.status >= 200 && requestImagen.status < 300) {
                        console.log(JSON.parse(requestImagen.response));

                        devuelve = JSON.parse(requestImagen.response);
                        if (devuelve.status === true) {
                            resolve(devuelve.imagen);
                        } else {
                            reject(devuelve.message);
                        }
                    } else {
                        //console.log( JSON.parse(requestImagen.response) );
                        reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                    }
                };
                requestImagen.onerror = () => reject(requestImagen.statusText);
                requestImagen.send(httpImagen);
            });

        }
        console.log(arrpromises[1]);
        await Promise.all(arrpromises).then(values => {
            swal("Excelente!", "Comentario Agregado Satisfactoriamente...", "success").then((value) => {
                cerrar();
                window.location.reload();
            });
        }, reason => {
            swal("Error", "Error en la trasaccion ...", "error").then((value) => {
                cerrar();
            });
        });

    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}


async function confirmComentario() {
    //--
    ticket = document.getElementById("ticket");
    comentario = document.getElementById("comentario_ticket");
    //--
    var arrpromises = new Array();
    if (status.value !== "") {
        arrpromises[0] = await new Promise((resolve, reject) => {
           /////////// POST /////////
            let httpData = new FormData();
            httpData.append("request", "agregar_comentario");
            httpData.append("ticket", ticket.value);
            httpData.append("comentario", comentario.value);

            let requestData = new XMLHttpRequest();
            requestData.open("POST", "ajax_fns_ticket.php");
            requestData.onload = () => {
                if (requestData.status >= 200 && requestData.status < 300) {
                    //console.log(JSON.parse(requestData.response));
                    devuelve = JSON.parse(requestData.response);
                    if (devuelve.status === true) {
                        resolve(devuelve.message);
                    } else {
                        reject(devuelve.message);
                    }
                } else {
                    //console.log( JSON.parse(requestData.response) );
                    reject('No se pudo conectar al servidor para realizar la transacci\u00F3n...');
                }
            };
            requestData.onerror = () => reject(requestData.statusText);
            requestData.send(httpData);
        });

        await Promise.all(arrpromises).then(values => {
            //console.log(values);
            swal("Excelente!", "Comentario agregado satisfactoriamente...", "success").then((value) => {
                cerrar();
                window.location.reload();
            });
        }, reason => {
            //console.log(reason);
            swal("Error", "Error en la trasaccion ...", "error").then((value) => {
                cerrar();
            });
        });

    } else {
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}
